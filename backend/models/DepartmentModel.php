<?php
class DepartmentModel {
  private $conn;
  public function __construct($conn) {
    $this->conn = $conn;
  }

  // GET ALL DEPARTMENTS
  public function getAllDepartments() {
    $sql = "SELECT department_id,
                   department_name,
                   first_name,
                   middle_name,
                   last_name,
                   suffix
            FROM   tbl_departments
            ORDER  BY department_name";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // GET DEPARTMENT BY ID
  public function getDepartmentById($department_id) {
    $sql = "SELECT department_id,
                   department_name,
                   first_name,
                   middle_name,
                   last_name,
                   suffix
            FROM   tbl_departments
            WHERE  department_id = :id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':id', $department_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // UPDATE LIABLE PERSON
  public function updateLiablePerson($data) {
    $sql = "UPDATE tbl_departments
            SET    first_name  = :first_name,
                   middle_name = :middle_name,
                   last_name   = :last_name,
                   suffix      = :suffix
            WHERE  department_id = :department_id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':department_id', $data['department_id']);
    $stmt->bindParam(':first_name',    $data['first_name']);
    $stmt->bindParam(':middle_name',   $data['middle_name']);
    $stmt->bindParam(':last_name',     $data['last_name']);
    $stmt->bindParam(':suffix',        $data['suffix']);
    return $stmt->execute();
  }
}
?>