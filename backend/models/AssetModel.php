<?php
// backend/models/AssetModel.php

require_once __DIR__ . '/../helpers/audit_helper.php';

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


    // GET ALL ASSETS
    public function getAllAssets() {
        $sql = "SELECT a.asset_id, a.qr_code, a.description, a.serial_number,
                       c.category_name,
                       d.department_id, d.department_name,
                       cu.custodian_id,
                       cu.first_name  AS custodian_first,
                       cu.middle_name AS custodian_middle,
                       cu.last_name   AS custodian_last,
                       cu.suffix      AS custodian_suffix,
                       t.item_type_name, t.item_type_code, t.item_type_id,
                       a.location, a.status, a.is_certified,
                       a.is_deleted, a.deleted_at, a.deleted_by,
                       a.created_at,  a.updated_at
                FROM   tbl_assets a
                LEFT JOIN tbl_categories  c  ON a.category_id   = c.category_id
                LEFT JOIN tbl_departments d  ON a.department_id = d.department_id
                LEFT JOIN tbl_custodians  cu ON a.custodian_id  = cu.custodian_id
                LEFT JOIN tbl_item_types  t  ON a.item_type_id  = t.item_type_id
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
                       a.custodian_id,
                       cu.first_name  AS custodian_first,
                       cu.middle_name AS custodian_middle,
                       cu.last_name   AS custodian_last,
                       cu.suffix      AS custodian_suffix,
                       a.item_type_id, t.item_type_name, t.item_type_code,
                       a.location, a.status, a.is_certified, a.is_deleted,
                       a.created_at,  a.updated_at
                FROM   tbl_assets a
                LEFT JOIN tbl_categories  c  ON a.category_id   = c.category_id
                LEFT JOIN tbl_departments d  ON a.department_id = d.department_id
                LEFT JOIN tbl_custodians  cu ON a.custodian_id  = cu.custodian_id
                LEFT JOIN tbl_item_types  t  ON a.item_type_id  = t.item_type_id
                WHERE  a.asset_id = :asset_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':asset_id', $asset_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    // GET ASSETS BY IDS (for export)
    public function getAssetsByIds($ids) {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_map(function($i) {
            return ':id' . $i;
        }, array_keys($ids)));

        $sql = "SELECT a.asset_id, a.qr_code, a.description, a.serial_number,
                       c.category_name,
                       d.department_id, d.department_name,
                       cu.custodian_id,
                       cu.first_name  AS custodian_first,
                       cu.middle_name AS custodian_middle,
                       cu.last_name   AS custodian_last,
                       cu.suffix      AS custodian_suffix,
                       t.item_type_name, t.item_type_code, t.item_type_id,
                       a.location, a.status, a.is_certified,
                       a.is_deleted,  a.created_at, a.updated_at
                FROM   tbl_assets a
                LEFT JOIN tbl_categories  c  ON a.category_id   = c.category_id
                LEFT JOIN tbl_departments d  ON a.department_id = d.department_id
                LEFT JOIN tbl_custodians  cu ON a.custodian_id  = cu.custodian_id
                LEFT JOIN tbl_item_types  t  ON a.item_type_id  = t.item_type_id
                WHERE  a.asset_id IN ($placeholders)
                ORDER  BY a.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        foreach ($ids as $i => $id) {
            $stmt->bindValue(':id' . $i, trim($id));
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // ADD ASSET
    public function addAsset($data) {
        $sql = "INSERT INTO tbl_assets (
                    asset_id, qr_code, description, serial_number,
                    category_id, department_id, custodian_id, item_type_id,
                    location, status, is_certified,
                    created_at, updated_at
                ) VALUES (
                    :asset_id, :qr_code, :description, :serial_number,
                    :category_id, :department_id, :custodian_id, :item_type_id,
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
        $stmt->bindParam(':custodian_id',  $data['custodian_id']);
        $stmt->bindParam(':item_type_id',  $data['item_type_id']);
        $stmt->bindParam(':location',      $data['location']);
        $stmt->bindParam(':status',        $data['status']);
        $stmt->bindParam(':is_certified',  $data['is_certified']);
        $result = $stmt->execute();
        if ($result) {
            $this->conn->exec("COMMIT");
            logAudit($this->conn, 'ASSET_ADD', 'Assets',
                'Added asset: ' . $data['description'], $data['asset_id']);
        }
        return $result;
    }


    // UPDATE ASSET
    public function updateAsset($data) {
        $sql = "UPDATE tbl_assets
                SET    description   = :description,
                       serial_number = :serial_number,
                       category_id   = :category_id,
                       department_id = :department_id,
                       custodian_id  = :custodian_id,
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
        $stmt->bindParam(':custodian_id',  $data['custodian_id']);
        $stmt->bindParam(':item_type_id',  $data['item_type_id']);
        $stmt->bindParam(':location',      $data['location']);
        $stmt->bindParam(':status',        $data['status']);
        $stmt->bindParam(':is_certified',  $data['is_certified']);
        $result = $stmt->execute();
        if ($result) {
            $this->conn->exec("COMMIT");
            logAudit($this->conn, 'ASSET_EDIT', 'Assets',
                'Updated asset: ' . $data['description'], $data['asset_id']);
        }
        return $result;
    }


    // ── STAFF: REQUEST DELETION ──────────────────────────────────────────────────
    public function requestDeletion($asset_id, $deleted_by, $reason) {
        $sql1 = "UPDATE tbl_assets
                 SET    is_deleted = 1,
                        deleted_at = CURRENT_TIMESTAMP,
                        deleted_by = :deleted_by
                 WHERE  asset_id   = :asset_id";
        $stmt1 = $this->conn->prepare($sql1);
        $stmt1->bindParam(':asset_id',   $asset_id);
        $stmt1->bindParam(':deleted_by', $deleted_by);
        if (!$stmt1->execute()) return false;

        $sql2 = "INSERT INTO tbl_deletion_requests
                   (request_id, asset_id, requested_by, reason, status, created_at)
                 VALUES
                   (del_req_seq.NEXTVAL, :asset_id, :requested_by, :reason, 'Pending', CURRENT_TIMESTAMP)";
        $stmt2 = $this->conn->prepare($sql2);
        $stmt2->bindParam(':asset_id',     $asset_id);
        $stmt2->bindParam(':requested_by', $deleted_by);
        $stmt2->bindParam(':reason',       $reason);
        $result = $stmt2->execute();
        if ($result) {
            $this->conn->exec("COMMIT");
            logAudit($this->conn, 'ASSET_DELETE', 'Assets',
                'Deletion requested. Reason: ' . $reason, $asset_id);
        }
        return $result;
    }


    // ── ADMIN: GET ALL PENDING DELETION REQUESTS ─────────────────────────────────
    public function getDeletionRequests() {
        $sql = "SELECT r.request_id, r.asset_id, r.requested_by, r.reason,
                       r.status, r.created_at,
                       a.description, a.department_id,
                       d.department_name,
                       a.item_type_id, t.item_type_name,
                       a.location, a.status AS asset_status
                FROM   tbl_deletion_requests r
                JOIN   tbl_assets      a ON r.asset_id      = a.asset_id
                LEFT JOIN tbl_departments d ON a.department_id = d.department_id
                LEFT JOIN tbl_item_types  t ON a.item_type_id  = t.item_type_id
                WHERE  r.status = 'Pending'
                ORDER BY r.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // ── ADMIN: APPROVE ────────────────────────────────────────────────────────────
    public function approveDeletion($asset_id, $reviewed_by) {
        $sql1 = "UPDATE tbl_deletion_requests
                 SET    status      = 'Approved',
                        reviewed_by = :reviewed_by,
                        reviewed_at = CURRENT_TIMESTAMP
                 WHERE  asset_id    = :asset_id
                   AND  status      = 'Pending'";
        $stmt1 = $this->conn->prepare($sql1);
        $stmt1->bindParam(':asset_id',    $asset_id);
        $stmt1->bindParam(':reviewed_by', $reviewed_by);
        $stmt1->execute();

        $sql2  = "DELETE FROM tbl_assets WHERE asset_id = :asset_id";
        $stmt2 = $this->conn->prepare($sql2);
        $stmt2->bindParam(':asset_id', $asset_id);
        $result = $stmt2->execute();
        if ($result) {
            $this->conn->exec("COMMIT");
            logAudit($this->conn, 'ASSET_DELETE', 'Assets',
                'Deletion approved and asset permanently removed', $asset_id);
        }
        return $result;
    }


    // ── ADMIN: REJECT ─────────────────────────────────────────────────────────────
    public function rejectDeletion($asset_id, $reviewed_by) {
        $sql1 = "UPDATE tbl_assets
                 SET    is_deleted  = 0,
                        deleted_at  = NULL,
                        deleted_by  = NULL,
                        updated_at  = CURRENT_TIMESTAMP
                 WHERE  asset_id    = :asset_id";
        $stmt1 = $this->conn->prepare($sql1);
        $stmt1->bindParam(':asset_id', $asset_id);
        if (!$stmt1->execute()) return false;

        $sql2 = "UPDATE tbl_deletion_requests
                 SET    status      = 'Rejected',
                        reviewed_by = :reviewed_by,
                        reviewed_at = CURRENT_TIMESTAMP
                 WHERE  asset_id    = :asset_id
                   AND  status      = 'Pending'";
        $stmt2 = $this->conn->prepare($sql2);
        $stmt2->bindParam(':asset_id',    $asset_id);
        $stmt2->bindParam(':reviewed_by', $reviewed_by);
        $result = $stmt2->execute();
        if ($result) {
            $this->conn->exec("COMMIT");
            logAudit($this->conn, 'ASSET_EDIT', 'Assets',
                'Deletion request rejected, asset restored', $asset_id);
        }
        return $result;
    }


    // ── ADMIN: DIRECT DELETE ─────────────────────────────────────────────────────
    public function permanentDelete($asset_id) {
        $sql  = "DELETE FROM tbl_assets WHERE asset_id = :asset_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':asset_id', $asset_id);
        $result = $stmt->execute();
        if ($result) {
            $this->conn->exec("COMMIT");
            logAudit($this->conn, 'ASSET_DELETE', 'Assets',
                'Asset permanently deleted (admin direct)', $asset_id);
        }
        return $result;
    }


    // SEARCH ASSETS
    public function searchAssets($keyword) {
        $keyword = '%' . strtoupper($keyword) . '%';
        $sql = "SELECT a.asset_id, a.qr_code, a.description, a.serial_number,
                       c.category_name, d.department_name,
                       cu.custodian_id,
                       cu.first_name  AS custodian_first,
                       cu.last_name   AS custodian_last,
                       t.item_type_name, t.item_type_code,
                       a.location, a.status, a.is_certified
                FROM   tbl_assets a
                LEFT JOIN tbl_categories  c  ON a.category_id   = c.category_id
                LEFT JOIN tbl_departments d  ON a.department_id = d.department_id
                LEFT JOIN tbl_custodians  cu ON a.custodian_id  = cu.custodian_id
                LEFT JOIN tbl_item_types  t  ON a.item_type_id  = t.item_type_id
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
                       cu.custodian_id,
                       cu.first_name  AS custodian_first,
                       cu.last_name   AS custodian_last,
                       t.item_type_name, t.item_type_code,
                       a.location, a.status, a.is_certified
                FROM   tbl_assets a
                LEFT JOIN tbl_categories  c  ON a.category_id   = c.category_id
                LEFT JOIN tbl_departments d  ON a.department_id = d.department_id
                LEFT JOIN tbl_custodians  cu ON a.custodian_id  = cu.custodian_id
                LEFT JOIN tbl_item_types  t  ON a.item_type_id  = t.item_type_id
                WHERE  a.status = :status
                ORDER BY a.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}