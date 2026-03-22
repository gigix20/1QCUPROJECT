<?php
// backend/models/CategoryModel.php

class CategoryModel {
  private $conn;
  public function __construct($conn) {
    $this->conn = $conn;
  }

  // GET ALL CATEGORIES
  public function getAllCategories() {
    $sql  = "SELECT category_id, category_name
             FROM   tbl_categories
             ORDER BY category_name";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

}
?>