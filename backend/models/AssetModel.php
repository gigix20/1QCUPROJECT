<?php
// backend/models/AssetModel.php

class AssetModel {
  private $conn;
  public function __construct($conn) {
    $this->conn = $conn;
  }

  
  // GET NEXT GLOBAL SEQUENCE
  public function getNextSequence() {
    $sql  = "SELECT asset_seq.NEXTVAL AS nextval FROM dual";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int) $row['NEXTVAL'];
  }

  
  // GENERATE ASSET ID
  public function generateAssetId($dept_name, $item_type_code, $seq) {
    // Get first word of department name e.g. "IT Department" -> "IT"
    $dept_code = strtoupper(explode(' ', trim($dept_name))[0]);
    $item_num  = str_pad($seq, 4, '0', STR_PAD_LEFT);
    return 'AST-' . $dept_code . '-' . $item_type_code . '-' . $item_num;
  }

  
  // GET ALL ASSETS
public function getAllAssets() {
  $sql = "SELECT a.asset_id, a.qr_code, a.description, a.serial_number,
                 c.category_name, d.department_name, d.department_id,
                 t.item_type_name, t.item_type_code, t.item_type_id,
                 a.location, a.status, a.is_certified,
                 a.is_deleted, a.deleted_at, a.deleted_by,
                 a.created_at, a.updated_at
          FROM   tbl_assets a
          LEFT JOIN tbl_categories  c ON a.category_id   = c.category_id
          LEFT JOIN tbl_departments d ON a.department_id  = d.department_id
          LEFT JOIN tbl_item_types  t ON a.item_type_id   = t.item_type_id
          ORDER BY a.created_at DESC";

  $stmt = $this->conn->prepare($sql);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

  
  // GET ASSET BY ID
  public function getAssetById($asset_id) {
    $sql = "SELECT a.asset_id, a.qr_code, a.description, a.serial_number,
                   a.category_id,   c.category_name,
                   a.department_id, d.department_name,
                   a.item_type_id,  t.item_type_name, t.item_type_code,
                   a.location, a.status, a.is_certified,
                   a.created_at, a.updated_at
            FROM   tbl_assets a
            LEFT JOIN tbl_categories  c ON a.category_id   = c.category_id
            LEFT JOIN tbl_departments d ON a.department_id  = d.department_id
            LEFT JOIN tbl_item_types  t ON a.item_type_id   = t.item_type_id
            WHERE  a.asset_id = :asset_id";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':asset_id', $asset_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  
  // ADD ASSET (with quantity)
  public function addAsset($data) {
    $sql = "INSERT INTO tbl_assets (
              asset_id, qr_code, description, serial_number,
              category_id, department_id, item_type_id,
              location, status, is_certified,
              created_at, updated_at
            ) VALUES (
              :asset_id, :qr_code, :description, :serial_number,
              :category_id, :department_id, :item_type_id,
              :location, :status, :is_certified,
              CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':asset_id',      $data['asset_id']);
    $stmt->bindParam(':qr_code',       $data['qr_code']);
    $stmt->bindParam(':description',   $data['description']);
    $stmt->bindParam(':serial_number', $data['serial_number']);
    $stmt->bindParam(':category_id',   $data['category_id']);
    $stmt->bindParam(':department_id', $data['department_id']);
    $stmt->bindParam(':item_type_id',  $data['item_type_id']);
    $stmt->bindParam(':location',      $data['location']);
    $stmt->bindParam(':status',        $data['status']);
    $stmt->bindParam(':is_certified',  $data['is_certified']);
    return $stmt->execute();
  }

  
  // UPDATE ASSET  
  public function updateAsset($data) {
    $sql = "UPDATE tbl_assets
            SET    description   = :description,
                   serial_number = :serial_number,
                   category_id   = :category_id,
                   department_id = :department_id,
                   item_type_id  = :item_type_id,
                   location      = :location,
                   status        = :status,
                   is_certified  = :is_certified,
                   updated_at    = CURRENT_TIMESTAMP
            WHERE  asset_id      = :asset_id";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':asset_id',      $data['asset_id']);
    $stmt->bindParam(':description',   $data['description']);
    $stmt->bindParam(':serial_number', $data['serial_number']);
    $stmt->bindParam(':category_id',   $data['category_id']);
    $stmt->bindParam(':department_id', $data['department_id']);
    $stmt->bindParam(':item_type_id',  $data['item_type_id']);
    $stmt->bindParam(':location',      $data['location']);
    $stmt->bindParam(':status',        $data['status']);
    $stmt->bindParam(':is_certified',  $data['is_certified']);
    return $stmt->execute();
  }

  
  // DELETE ASSET
 public function requestDeletion($asset_id, $deleted_by) {
  $sql = "UPDATE tbl_assets
          SET    is_deleted = 1,
                 deleted_at = CURRENT_TIMESTAMP,
                 deleted_by = :deleted_by
          WHERE  asset_id   = :asset_id";

  $stmt = $this->conn->prepare($sql);
  $stmt->bindParam(':asset_id',   $asset_id);
  $stmt->bindParam(':deleted_by', $deleted_by);
  return $stmt->execute();
}
 
  // SEARCH ASSETS 
  public function searchAssets($keyword) {
    $keyword = '%' . strtoupper($keyword) . '%';

    $sql = "SELECT a.asset_id, a.qr_code, a.description, a.serial_number,
                   c.category_name, d.department_name,
                   t.item_type_name, t.item_type_code,
                   a.location, a.status, a.is_certified
            FROM   tbl_assets a
            LEFT JOIN tbl_categories  c ON a.category_id   = c.category_id
            LEFT JOIN tbl_departments d ON a.department_id  = d.department_id
            LEFT JOIN tbl_item_types  t ON a.item_type_id   = t.item_type_id
            WHERE  UPPER(a.asset_id)        LIKE :keyword
               OR  UPPER(a.description)     LIKE :keyword
               OR  UPPER(a.serial_number)   LIKE :keyword
               OR  UPPER(d.department_name) LIKE :keyword
               OR  UPPER(t.item_type_name)  LIKE :keyword
            ORDER BY a.created_at DESC";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':keyword', $keyword);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // FILTER BY STATUS
  public function filterByStatus($status) {
    $sql = "SELECT a.asset_id, a.qr_code, a.description, a.serial_number,
                   c.category_name, d.department_name,
                   t.item_type_name, t.item_type_code,
                   a.location, a.status, a.is_certified
            FROM   tbl_assets a
            LEFT JOIN tbl_categories  c ON a.category_id   = c.category_id
            LEFT JOIN tbl_departments d ON a.department_id  = d.department_id
            LEFT JOIN tbl_item_types  t ON a.item_type_id   = t.item_type_id
            WHERE  a.status = :status
            ORDER BY a.created_at DESC";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':status', $status);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

}
?>