<?php
// backend/models/ItemTypeModel.php

class ItemTypeModel {

  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  // GET ALL ITEM TYPES
  public function getAllItemTypes() {
    $sql  = "SELECT item_type_id, item_type_name, item_type_code
             FROM   tbl_item_types
             ORDER BY item_type_code";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

}
?>