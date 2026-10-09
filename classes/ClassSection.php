<?php
// classes/ClassSection.php
// "slots" holds the REMAINING available slots of the class.
class ClassSection {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    public function allWithCourse() {
        $stmt = $this->db->prepare(
            "SELECT c.*, co.course_code, co.course_name
             FROM classes c
             INNER JOIN courses co ON c.course_id = co.course_id
             ORDER BY co.course_name, c.class_code");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM classes WHERE class_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($course_id, $code, $schedule, $instructor, $slots) {
        $stmt = $this->db->prepare(
            "INSERT INTO classes (course_id, class_code, schedule, instructor, slots)
             VALUES (:course_id, :code, :schedule, :instructor, :slots)");
        $stmt->execute([
            ':course_id' => $course_id, ':code' => $code, ':schedule' => $schedule,
            ':instructor' => $instructor, ':slots' => $slots
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $course_id, $code, $schedule, $instructor, $slots) {
        $stmt = $this->db->prepare(
            "UPDATE classes SET course_id = :course_id, class_code = :code,
                    schedule = :schedule, instructor = :instructor, slots = :slots
             WHERE class_id = :id");
        return $stmt->execute([
            ':course_id' => $course_id, ':code' => $code, ':schedule' => $schedule,
            ':instructor' => $instructor, ':slots' => $slots, ':id' => $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM classes WHERE class_id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getSlots($class_id) {
        $stmt = $this->db->prepare("SELECT slots FROM classes WHERE class_id = :id");
        $stmt->execute([':id' => $class_id]);
        $slots = $stmt->fetchColumn();
        return $slots === false ? null : (int) $slots;
    }
}
