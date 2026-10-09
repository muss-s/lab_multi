<?php
// classes/Course.php
class Course {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    // Optional $q filters by code or name (parameterized LIKE search).
    public function all($q = '') {
        if ($q !== '') {
            $stmt = $this->db->prepare(
                "SELECT * FROM courses
                 WHERE course_name LIKE :q1 OR course_code LIKE :q2
                 ORDER BY course_name");
            $stmt->execute([':q1' => "%$q%", ':q2' => "%$q%"]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM courses ORDER BY course_name");
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM courses WHERE course_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($code, $name, $description) {
        $stmt = $this->db->prepare(
            "INSERT INTO courses (course_code, course_name, description)
             VALUES (:code, :name, :description)");
        $stmt->execute([':code' => $code, ':name' => $name, ':description' => $description]);
        return $this->db->lastInsertId();
    }

    public function update($id, $code, $name, $description) {
        $stmt = $this->db->prepare(
            "UPDATE courses SET course_code = :code, course_name = :name,
                    description = :description
             WHERE course_id = :id");
        return $stmt->execute([
            ':code' => $code, ':name' => $name,
            ':description' => $description, ':id' => $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM courses WHERE course_id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
