<?php
// classes/EnrollmentRepository.php
// Repository pattern: all enrollment-related data access lives here.
// Multi-step writes run inside ONE database transaction (InnoDB).
require_once __DIR__ . '/NoSlotsException.php';

class EnrollmentRepository {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    // Record a NEW student AND enroll them into a class (one transaction).
    // Returns the new student_id. Throws NoSlotsException if the class is full.
    public function recordStudent($full_name, $email, $phone, $class_id) {
        try {
            $this->db->beginTransaction();                          // 1
            $this->lockClassOrFail($class_id);                      // 2 (row lock + slot check)

            $stmt = $this->db->prepare(                             // 3
                "INSERT INTO students (full_name, email, phone)
                 VALUES (:full_name, :email, :phone)");
            $stmt->execute([
                ':full_name' => $full_name,
                ':email'     => $email === '' ? null : $email,
                ':phone'     => $phone === '' ? null : $phone,
            ]);
            $student_id = (int) $this->db->lastInsertId();          // 4

            $this->insertEnrollment($student_id, $class_id);        // 5
            $this->decrementSlot($class_id);                        // 6

            $this->db->commit();                                    // 7
            return $student_id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();                              // nothing is saved
            }
            throw $e;
        }
    }

    // Enroll an EXISTING student into a class (one transaction).
    public function enroll($student_id, $class_id) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT student_id FROM students WHERE student_id = :id");
            $stmt->execute([':id' => $student_id]);
            if (!$stmt->fetch()) {
                throw new RuntimeException('Student not found.');
            }

            $this->lockClassOrFail($class_id);

            // Prevent duplicate (active) enrollment of the same student in the same class.
            $stmt = $this->db->prepare(
                "SELECT enrollment_id FROM enrollments
                 WHERE student_id = :s AND class_id = :c AND status = 'active'");
            $stmt->execute([':s' => $student_id, ':c' => $class_id]);
            if ($stmt->fetch()) {
                throw new RuntimeException('This student is already enrolled in that class.');
            }

            $this->insertEnrollment($student_id, $class_id);
            $this->decrementSlot($class_id);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Cancel an enrollment and give the slot back (one transaction).
    public function cancel($enrollment_id) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT enrollment_id, class_id, status FROM enrollments
                 WHERE enrollment_id = :id FOR UPDATE");
            $stmt->execute([':id' => $enrollment_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                throw new RuntimeException('Enrollment not found.');
            }
            if ($row['status'] !== 'active') {
                throw new RuntimeException('Enrollment is already cancelled.');
            }

            $stmt = $this->db->prepare(
                "UPDATE enrollments SET status = 'cancelled' WHERE enrollment_id = :id");
            $stmt->execute([':id' => $enrollment_id]);

            $stmt = $this->db->prepare(
                "UPDATE classes SET slots = slots + 1 WHERE class_id = :cid");
            $stmt->execute([':cid' => $row['class_id']]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // ---- Queries (JOINs, search, filter, sort, pagination) -----------------

    // $f: q, status, course_id, sort (date|student|course|class|status), dir (asc|desc)
    // $perPage = 0 returns every row.
    public function allWithDetails(array $f = [], $page = 1, $perPage = 0) {
        list($where, $params) = $this->buildFilter($f);
        $sorts = [
            'date' => 'e.enrollment_date', 'student' => 's.full_name',
            'course' => 'co.course_name', 'class' => 'cl.class_code', 'status' => 'e.status',
        ];
        $col = $sorts[$f['sort'] ?? 'date'] ?? 'e.enrollment_date';
        $dir = strtolower($f['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        $sql = "SELECT e.enrollment_id, e.enrollment_date, e.status,
                       s.student_id, s.full_name, s.email,
                       cl.class_id, cl.class_code, cl.schedule, cl.instructor, cl.slots,
                       co.course_id, co.course_code, co.course_name
                FROM enrollments e
                INNER JOIN students s  ON e.student_id = s.student_id
                INNER JOIN classes  cl ON e.class_id   = cl.class_id
                INNER JOIN courses  co ON cl.course_id = co.course_id
                $where
                ORDER BY $col $dir, e.enrollment_id DESC";
        if ($perPage > 0) {
            $perPage = (int) $perPage;
            $offset  = (max(1, (int) $page) - 1) * $perPage;
            $sql .= " LIMIT $perPage OFFSET $offset";   // ints only (cast above)
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countWithDetails(array $f = []) {
        list($where, $params) = $this->buildFilter($f);
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM enrollments e
             INNER JOIN students s  ON e.student_id = s.student_id
             INNER JOIN classes  cl ON e.class_id   = cl.class_id
             INNER JOIN courses  co ON cl.course_id = co.course_id
             $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // ---- Summary reports (LEFT JOIN + GROUP BY) ----------------------------

    public function reportByCourse() {
        $stmt = $this->db->prepare(
            "SELECT co.course_code, co.course_name,
                    COUNT(DISTINCT cl.class_id) AS classes,
                    COUNT(CASE WHEN e.status = 'active'    THEN 1 END) AS active_enrollments,
                    COUNT(CASE WHEN e.status = 'cancelled' THEN 1 END) AS cancelled_enrollments
             FROM courses co
             LEFT JOIN classes     cl ON cl.course_id = co.course_id
             LEFT JOIN enrollments e  ON e.class_id   = cl.class_id
             GROUP BY co.course_id, co.course_code, co.course_name
             ORDER BY co.course_name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function reportByClass() {
        $stmt = $this->db->prepare(
            "SELECT cl.class_code, co.course_name, cl.schedule, cl.instructor,
                    cl.slots AS slots_left,
                    COUNT(CASE WHEN e.status = 'active' THEN 1 END) AS active_enrollments
             FROM classes cl
             INNER JOIN courses co ON cl.course_id = co.course_id
             LEFT JOIN enrollments e ON e.class_id = cl.class_id
             GROUP BY cl.class_id, cl.class_code, co.course_name, cl.schedule, cl.instructor, cl.slots
             ORDER BY co.course_name, cl.class_code");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Students with no active enrollment (LEFT JOIN ... IS NULL).
    public function studentsWithoutActiveEnrollment() {
        $stmt = $this->db->prepare(
            "SELECT s.student_id, s.full_name, s.email
             FROM students s
             LEFT JOIN enrollments e ON e.student_id = s.student_id AND e.status = 'active'
             WHERE e.enrollment_id IS NULL
             ORDER BY s.full_name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ---- Private helpers ---------------------------------------------------

    // Locks the class row for this transaction and checks remaining slots.
    private function lockClassOrFail($class_id) {
        $stmt = $this->db->prepare("SELECT slots FROM classes WHERE class_id = :id FOR UPDATE");
        $stmt->execute([':id' => $class_id]);
        $slots = $stmt->fetchColumn();
        if ($slots === false) {
            throw new RuntimeException('Class not found.');
        }
        if ((int) $slots <= 0) {
            throw new NoSlotsException('No slots available.');
        }
    }

    private function insertEnrollment($student_id, $class_id) {
        $stmt = $this->db->prepare(
            "INSERT INTO enrollments (student_id, class_id) VALUES (:s, :c)");
        $stmt->execute([':s' => $student_id, ':c' => $class_id]);
    }

    private function decrementSlot($class_id) {
        $stmt = $this->db->prepare(
            "UPDATE classes SET slots = slots - 1 WHERE class_id = :id AND slots > 0");
        $stmt->execute([':id' => $class_id]);
        if ($stmt->rowCount() !== 1) {
            throw new NoSlotsException('No slots available.');
        }
    }

    private function buildFilter(array $f) {
        $w = []; $p = [];
        if (!empty($f['q'])) {
            $w[] = "(s.full_name LIKE :q1 OR s.email LIKE :q2
                     OR cl.class_code LIKE :q3 OR co.course_name LIKE :q4)";
            foreach (['q1', 'q2', 'q3', 'q4'] as $k) { $p[":$k"] = '%' . $f['q'] . '%'; }
        }
        if (!empty($f['status']))    { $w[] = "e.status = :status";       $p[':status'] = $f['status']; }
        if (!empty($f['course_id'])) { $w[] = "co.course_id = :course_id"; $p[':course_id'] = (int) $f['course_id']; }
        return [$w ? ' WHERE ' . implode(' AND ', $w) : '', $p];
    }
}
