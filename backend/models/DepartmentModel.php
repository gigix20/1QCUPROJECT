<?php
// backend/models/DepartmentModel.php

class DepartmentModel {
  private $conn;
  public function __construct($conn) {
    $this->conn = $conn;
  }

  // GET ALL DEPARTMENTS
  public function getAllDepartments() {
    $sql  = "SELECT department_id, department_name
             FROM   tbl_departments
             ORDER BY department_name";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

}
?>