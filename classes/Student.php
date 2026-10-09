<?php
// classes/Student.php
class Student {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM students WHERE student_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function all() {
        $stmt = $this->db->prepare("SELECT * FROM students ORDER BY full_name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
