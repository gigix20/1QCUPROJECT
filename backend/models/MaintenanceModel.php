<?php
// backend/models/MaintenanceModel.php
class MaintenanceModel {
  private $conn;

  public function __construct($conn) {
    $this->conn = $conn;
  }
 
  // GET ALL MAINTENANCE
  public function getAllMaintenance() {
    $sql = "SELECT m.maintenance_id, m.asset_id, m.type_id,
                   m.issue_description,
                   m.tech_first_name, m.tech_middle_name,
                   m.tech_last_name, m.tech_suffix,
                   m.scheduled_date, m.completed_date,
                   m.notes, m.status,
                   m.created_at, m.updated_at,
                   a.description  AS asset_description,
                   a.item_type_id, t.item_type_name,
                   a.department_id, d.department_name,
                   mt.type_name   AS maintenance_type
            FROM   tbl_maintenance      m
            JOIN   tbl_assets           a  ON m.asset_id  = a.asset_id
            JOIN   tbl_item_types       t  ON a.item_type_id = t.item_type_id
            JOIN   tbl_departments      d  ON a.department_id = d.department_id
            JOIN   tbl_maintenance_types mt ON m.type_id   = mt.type_id
            ORDER  BY m.created_at DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // GET MAINTENANCE BY ID
  public function getMaintenanceById($maintenance_id) {
    $sql = "SELECT m.maintenance_id, m.asset_id, m.type_id,
                   m.issue_description,
                   m.tech_first_name, m.tech_middle_name,
                   m.tech_last_name, m.tech_suffix,
                   m.scheduled_date, m.completed_date,
                   m.notes, m.status,
                   m.created_at, m.updated_at,
                   a.description  AS asset_description,
                   a.item_type_id, t.item_type_name,
                   a.department_id, d.department_name,
                   mt.type_name   AS maintenance_type
            FROM   tbl_maintenance      m
            JOIN   tbl_assets           a  ON m.asset_id  = a.asset_id
            JOIN   tbl_item_types       t  ON a.item_type_id = t.item_type_id
            JOIN   tbl_departments      d  ON a.department_id = d.department_id
            JOIN   tbl_maintenance_types mt ON m.type_id   = mt.type_id
            WHERE  m.maintenance_id = :maintenance_id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':maintenance_id', $maintenance_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // ADD MAINTENANCE
  public function addMaintenance($data) {
    $sql = "INSERT INTO tbl_maintenance (
              asset_id, type_id, issue_description,
              tech_first_name, tech_middle_name, tech_last_name, tech_suffix,
              scheduled_date, notes, status
            ) VALUES (
              :asset_id, :type_id, :issue_description,
              :tech_first_name, :tech_middle_name, :tech_last_name, :tech_suffix,
              :scheduled_date, :notes, 'Pending'
            )";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':asset_id',          $data['asset_id']);
    $stmt->bindParam(':type_id',           $data['type_id']);
    $stmt->bindParam(':issue_description', $data['issue_description']);
    $stmt->bindParam(':tech_first_name',   $data['tech_first_name']);
    $stmt->bindParam(':tech_middle_name',  $data['tech_middle_name']);
    $stmt->bindParam(':tech_last_name',    $data['tech_last_name']);
    $stmt->bindParam(':tech_suffix',       $data['tech_suffix']);
    $stmt->bindParam(':scheduled_date',    $data['scheduled_date']);
    $stmt->bindParam(':notes',             $data['notes']);
    $stmt->execute();
    $this->conn->exec("COMMIT");
    $this->updateAssetStatus($data['asset_id'], 'Maintenance');
  }
 
  // UPDATE MAINTENANCE STATUS 
  public function updateStatus($data) {
    $sql = "UPDATE tbl_maintenance
            SET    status         = :status,
                   completed_date = :completed_date,
                   updated_at     = CURRENT_TIMESTAMP
            WHERE  maintenance_id = :maintenance_id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':maintenance_id', $data['maintenance_id']);
    $stmt->bindParam(':status',         $data['status']);
    $stmt->bindParam(':completed_date', $data['completed_date']);
    $stmt->execute();
    $this->conn->exec("COMMIT");

    // If completed or cancelled — set asset back to Available
    if ($data['status'] === 'Completed' || $data['status'] === 'Cancelled') {
      $maint = $this->getMaintenanceById($data['maintenance_id']);
      if ($maint) {
        $this->updateAssetStatus($maint['ASSET_ID'], 'Available');
      }
    }
  }
 
  // GET ASSET INFO FOR MODAL 
  public function getAssetInfo($asset_id) {
    $sql = "SELECT a.asset_id, a.description, a.status,
                   t.item_type_name,
                   d.department_name
            FROM   tbl_assets      a
            JOIN   tbl_departments d ON a.department_id = d.department_id
            JOIN   tbl_item_types  t ON a.item_type_id  = t.item_type_id
            WHERE  a.asset_id  = :asset_id
            AND    a.is_deleted = 0";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':asset_id', $asset_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
 
  // GET ALL MAINTENANCE TYPES 
  public function getMaintenanceTypes() {
    $sql  = "SELECT type_id, type_name FROM tbl_maintenance_types ORDER BY type_name";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
 
  // GET MAINTENANCE BY IDS (for export)
  public function getMaintenanceByIds($ids) {
    if (empty($ids)) return [];

    $placeholders = implode(',', array_map(function($i) {
      return ':id' . $i;
    }, array_keys($ids)));

    $sql = "SELECT m.maintenance_id, m.asset_id, m.type_id,
                   m.issue_description,
                   m.tech_first_name, m.tech_middle_name,
                   m.tech_last_name, m.tech_suffix,
                   m.scheduled_date, m.completed_date,
                   m.notes, m.status,
                   m.created_at, m.updated_at,
                   a.description  AS asset_description,
                   a.item_type_id, t.item_type_name,
                   a.department_id, d.department_name,
                   mt.type_name   AS maintenance_type
            FROM   tbl_maintenance      m
            JOIN   tbl_assets           a  ON m.asset_id     = a.asset_id
            JOIN   tbl_item_types       t  ON a.item_type_id = t.item_type_id
            JOIN   tbl_departments      d  ON a.department_id = d.department_id
            JOIN   tbl_maintenance_types mt ON m.type_id     = mt.type_id
            WHERE  m.maintenance_id IN ($placeholders)
            ORDER  BY m.created_at DESC";

    $stmt = $this->conn->prepare($sql);
    foreach ($ids as $i => $id) {
      $stmt->bindValue(':id' . $i, trim($id));
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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