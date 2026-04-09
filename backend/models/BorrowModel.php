<?php
// backend/models/BorrowModel.php

class BorrowModel {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }

  // GET ALL BORROWS
  public function getAllBorrows() {
    $sql = "SELECT b.borrow_id, b.asset_id, b.department_id,
                   b.first_name, b.middle_name, b.last_name, b.suffix,
                   b.borrow_date, b.due_date, b.return_date,
                   b.purpose, b.status, b.remarks,
                   b.created_at, b.updated_at,
                   a.description  AS asset_description,
                   a.item_type_id, t.item_type_name,
                   d.department_name,
                   c.first_name  AS liable_first,
                   c.middle_name AS liable_middle,
                   c.last_name   AS liable_last,
                   c.suffix      AS liable_suffix
            FROM   tbl_borrows b
            JOIN   tbl_assets      a ON b.asset_id      = a.asset_id
            JOIN   tbl_departments d ON b.department_id = d.department_id
            JOIN   tbl_item_types  t ON a.item_type_id  = t.item_type_id
            LEFT JOIN tbl_custodians c ON a.custodian_id = c.custodian_id
            ORDER  BY b.created_at DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // GET BORROW BY ID
  public function getBorrowById($borrow_id) {
    $sql = "SELECT b.borrow_id, b.asset_id, b.department_id,
                   b.first_name, b.middle_name, b.last_name, b.suffix,
                   b.borrow_date, b.due_date, b.return_date,
                   b.purpose, b.status, b.remarks,
                   b.created_at, b.updated_at,
                   a.description  AS asset_description,
                   a.item_type_id, t.item_type_name,
                   d.department_name,
                   c.first_name  AS liable_first,
                   c.middle_name AS liable_middle,
                   c.last_name   AS liable_last,
                   c.suffix      AS liable_suffix
            FROM   tbl_borrows b
            JOIN   tbl_assets      a ON b.asset_id      = a.asset_id
            JOIN   tbl_departments d ON b.department_id = d.department_id
            JOIN   tbl_item_types  t ON a.item_type_id  = t.item_type_id
            LEFT JOIN tbl_custodians c ON a.custodian_id = c.custodian_id
            WHERE  b.borrow_id = :borrow_id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':borrow_id', $borrow_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // ADD BORROW
  public function addBorrow($data) {
    $sql = "INSERT INTO tbl_borrows (
              asset_id, department_id,
              first_name, middle_name, last_name, suffix,
              borrow_date, due_date, purpose, status
            ) VALUES (
              :asset_id, :department_id,
              :first_name, :middle_name, :last_name, :suffix,
              :borrow_date, :due_date,
              :purpose, 'Pending'
            )";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':asset_id',      $data['asset_id']);
    $stmt->bindParam(':department_id', $data['department_id']);
    $stmt->bindParam(':first_name',    $data['first_name']);
    $stmt->bindParam(':middle_name',   $data['middle_name']);
    $stmt->bindParam(':last_name',     $data['last_name']);
    $stmt->bindParam(':suffix',        $data['suffix']);
    $stmt->bindParam(':borrow_date',   $data['borrow_date']);
    $stmt->bindParam(':due_date',      $data['due_date']);
    $stmt->bindParam(':purpose',       $data['purpose']);
    $stmt->execute();
    $this->conn->exec("COMMIT");
  }

  // GET BORROWS BY IDS (for filtered export)
  public function getBorrowsByIds($ids) {
    if (empty($ids)) return [];

    $placeholders = implode(',', array_map(function($i) {
      return ':id' . $i;
    }, array_keys($ids)));

    $sql = "SELECT b.borrow_id, b.asset_id, b.department_id,
                   b.first_name, b.middle_name, b.last_name, b.suffix,
                   b.borrow_date, b.due_date, b.return_date,
                   b.purpose, b.status, b.remarks,
                   b.created_at, b.updated_at,
                   a.description  AS asset_description,
                   a.item_type_id, t.item_type_name,
                   d.department_name,
                   c.first_name  AS liable_first,
                   c.middle_name AS liable_middle,
                   c.last_name   AS liable_last,
                   c.suffix      AS liable_suffix
            FROM   tbl_borrows b
            JOIN   tbl_assets      a ON b.asset_id      = a.asset_id
            JOIN   tbl_departments d ON b.department_id = d.department_id
            JOIN   tbl_item_types  t ON a.item_type_id  = t.item_type_id
            LEFT JOIN tbl_custodians c ON a.custodian_id = c.custodian_id
            WHERE  b.borrow_id IN ($placeholders)
            ORDER  BY b.created_at DESC";

    $stmt = $this->conn->prepare($sql);
    foreach ($ids as $i => $id) {
      $stmt->bindValue(':id' . $i, trim($id));
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // APPROVE BORROW
  public function approveBorrow($borrow_id) {
    $sql = "UPDATE tbl_borrows
            SET    status     = 'Borrowed',
                   updated_at = CURRENT_TIMESTAMP
            WHERE  borrow_id  = :borrow_id
            AND    status     = 'Pending'";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':borrow_id', $borrow_id);
    $stmt->execute();
    $this->conn->exec("COMMIT");

    $borrow = $this->getBorrowById($borrow_id);
    if ($borrow) {
      $this->updateAssetStatus($borrow['ASSET_ID'], 'In Use');
    }
  }

  // CANCEL BORROW
  public function cancelBorrow($borrow_id) {
    $borrow = $this->getBorrowById($borrow_id);

    $sql = "UPDATE tbl_borrows
            SET    status     = 'Cancelled',
                   updated_at = CURRENT_TIMESTAMP
            WHERE  borrow_id  = :borrow_id
            AND    status     = 'Pending'";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':borrow_id', $borrow_id);
    $stmt->execute();
    $this->conn->exec("COMMIT");
  }

  // RETURN BORROW
  public function returnBorrow($data) {
    $sql = "UPDATE tbl_borrows
            SET    status      = 'Returned',
                   return_date = :return_date,
                   remarks     = :remarks,
                   updated_at  = CURRENT_TIMESTAMP
            WHERE  borrow_id   = :borrow_id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':borrow_id',   $data['borrow_id']);
    $stmt->bindParam(':return_date', $data['return_date']);
    $stmt->bindParam(':remarks',     $data['remarks']);
    $stmt->execute();
    $this->conn->exec("COMMIT");

    $borrow = $this->getBorrowById($data['borrow_id']);
    if ($borrow) {
      $this->updateAssetStatus($borrow['ASSET_ID'], 'Available');
    }
  }

  // MARK OVERDUE
  public function markOverdue() {
    $sql = "UPDATE tbl_borrows
            SET    status     = 'Overdue',
                   updated_at = CURRENT_TIMESTAMP
            WHERE  status     = 'Borrowed'
            AND    TO_DATE(SUBSTR(due_date, 1, 10), 'YYYY-MM-DD') < TRUNC(SYSDATE)";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    $this->conn->exec("COMMIT");
  }

  // GET OVERDUE ACTIVE — still borrowed/overdue past due date
  public function getOverdueActive() {
    $sql = "SELECT b.borrow_id, b.asset_id, b.department_id,
                   b.first_name, b.middle_name, b.last_name, b.suffix,
                   b.borrow_date, b.due_date, b.purpose, b.status,
                   a.description AS asset_description,
                   d.department_name
            FROM   tbl_borrows b
            JOIN   tbl_assets      a ON b.asset_id      = a.asset_id
            JOIN   tbl_departments d ON b.department_id = d.department_id
            WHERE  b.status IN ('Borrowed', 'Overdue')
              AND  TO_DATE(SUBSTR(b.due_date, 1, 10), 'YYYY-MM-DD') < TRUNC(SYSDATE)
            ORDER  BY b.due_date ASC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // GET OVERDUE RETURNS — returned but after due date
  public function getOverdueReturns() {
    $sql = "SELECT b.borrow_id, b.asset_id, b.department_id,
                   b.first_name, b.middle_name, b.last_name, b.suffix,
                   b.borrow_date, b.due_date, b.return_date, b.purpose,
                   a.description AS asset_description,
                   d.department_name
            FROM   tbl_borrows b
            JOIN   tbl_assets      a ON b.asset_id      = a.asset_id
            JOIN   tbl_departments d ON b.department_id = d.department_id
            WHERE  b.status = 'Returned'
              AND  TO_DATE(SUBSTR(b.return_date, 1, 10), 'YYYY-MM-DD') > TO_DATE(SUBSTR(b.due_date, 1, 10), 'YYYY-MM-DD')
            ORDER  BY b.due_date ASC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // GET ASSET INFO FOR MODAL
  public function getAssetInfo($asset_id) {
    $sql = "SELECT a.asset_id, a.description, a.status,
                   t.item_type_name,
                   c.first_name, c.middle_name, c.last_name, c.suffix
            FROM   tbl_assets     a
            JOIN   tbl_item_types t ON a.item_type_id  = t.item_type_id
            LEFT JOIN tbl_custodians c ON a.custodian_id = c.custodian_id
            WHERE  a.asset_id  = :asset_id
            AND    a.is_deleted = 0";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':asset_id', $asset_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // UPDATE ASSET STATUS
  private function updateAssetStatus($asset_id, $status) {
    $sql = "UPDATE tbl_assets
            SET    status     = :status,
                   updated_at = CURRENT_TIMESTAMP
            WHERE  asset_id   = :asset_id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':status',   $status);
    $stmt->bindParam(':asset_id', $asset_id);
    $stmt->execute();
    $this->conn->exec("COMMIT");
  }

}
?>