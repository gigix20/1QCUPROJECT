<?php
// controllers/AssetController.php
require_once __DIR__ . '/../models/AssetModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../services/AssetIdService.php';

class AssetController {
    private $model;
    private $conn;

    public function __construct($conn) {
        $this->conn  = $conn;
        $this->model = new AssetModel($conn);
    }


    // HANDLE ALL REQUESTS
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        switch ($action) {
            case 'getAll':              $this->getAll();              break;
            case 'getById':             $this->getById();             break;
            case 'add':                 $this->add();                 break;
            case 'update':              $this->update();              break;
            case 'delete':              $this->delete();              break;
            case 'adminDelete':         $this->adminDelete();         break;
            case 'getDeletionRequests': $this->getDeletionRequests(); break;
            case 'approveDeletion':     $this->approveDeletion();     break;
            case 'rejectDeletion':      $this->rejectDeletion();      break;
            case 'search':              $this->search();              break;
            case 'filterStatus':        $this->filterStatus();        break;
            default:                    ResponseHelper::sendError(400, 'Invalid action.');
        }
    }


    // GET ALL ASSETS
    private function getAll() {
        $assets = $this->model->getAllAssets();
        ResponseHelper::sendSuccess($assets);
    }


    // GET ASSET BY ID
    private function getById() {
        $asset_id = trim($_GET['asset_id'] ?? '');
        if (empty($asset_id)) {
            ResponseHelper::sendError(400, 'Asset ID is required.');
            return;
        }
        $asset = $this->model->getAssetById($asset_id);
        if (!$asset) {
            ResponseHelper::sendError(404, 'Asset not found.');
            return;
        }
        ResponseHelper::sendSuccess($asset);
    }


    // ADD ASSET
    private function add() {
        $description   = trim($_POST['description']   ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        $category_id   = trim($_POST['category_id']   ?? '');
        $department_id = trim($_POST['department_id'] ?? '');
        $custodian_id  = trim($_POST['custodian_id']  ?? '');
        $item_type_id  = trim($_POST['item_type_id']  ?? '');
        $location      = trim($_POST['location']      ?? '');
        $status        = trim($_POST['status']        ?? 'Available');
        $is_certified  = (int) ($_POST['is_certified'] ?? 0);
        $quantity      = max(1, (int) ($_POST['quantity'] ?? 1));

        if (empty($description))   { ResponseHelper::sendError(400, 'Description is required.');  return; }
        if (empty($department_id)) { ResponseHelper::sendError(400, 'Department is required.');   return; }
        if (empty($custodian_id))  { ResponseHelper::sendError(400, 'Custodian is required.');    return; }
        if (empty($item_type_id))  { ResponseHelper::sendError(400, 'Item type is required.');    return; }

        $deptStmt = $this->conn->prepare(
            "SELECT department_name FROM tbl_departments WHERE department_id = :id"
        );
        $deptStmt->bindParam(':id', $department_id);
        $deptStmt->execute();
        $deptRow = $deptStmt->fetch(PDO::FETCH_ASSOC);
        if (!$deptRow) { ResponseHelper::sendError(404, 'Department not found.'); return; }

        $typeStmt = $this->conn->prepare(
            "SELECT item_type_code FROM tbl_item_types WHERE item_type_id = :id"
        );
        $typeStmt->bindParam(':id', $item_type_id);
        $typeStmt->execute();
        $typeRow = $typeStmt->fetch(PDO::FETCH_ASSOC);
        if (!$typeRow) { ResponseHelper::sendError(404, 'Item type not found.'); return; }

        // Verify custodian belongs to selected department
        $custStmt = $this->conn->prepare(
            "SELECT custodian_id FROM tbl_custodians
             WHERE  custodian_id  = :cid
               AND  department_id = :did"
        );
        $custStmt->bindParam(':cid', $custodian_id);
        $custStmt->bindParam(':did', $department_id);
        $custStmt->execute();
        if (!$custStmt->fetch()) {
            ResponseHelper::sendError(400, 'Selected custodian does not belong to this department.');
            return;
        }

        $dept_name      = $deptRow['DEPARTMENT_NAME'];
        $item_type_code = $typeRow['ITEM_TYPE_CODE'];
        $generated      = [];

        for ($i = 0; $i < $quantity; $i++) {
            $seq      = $this->model->getNextSequence();
            $asset_id = AssetIdService::generate($dept_name, $item_type_code, $seq);
            $qr_code  = AssetIdService::generateQR($asset_id);

            $data = [
                'asset_id'      => $asset_id,
                'qr_code'       => $qr_code,
                'description'   => $description,
                'serial_number' => $serial_number ?: null,
                'category_id'   => $category_id   ?: null,
                'department_id' => $department_id,
                'custodian_id'  => $custodian_id,
                'item_type_id'  => $item_type_id,
                'location'      => $location      ?: null,
                'status'        => $status,
                'is_certified'  => $is_certified,
            ];

            if (!$this->model->addAsset($data)) {
                ResponseHelper::sendError(500, 'Failed to insert asset ' . $asset_id . '.');
                return;
            }
            $generated[] = $asset_id;
        }

        ResponseHelper::sendSuccess(
            ['generated' => $generated],
            count($generated) . ' asset(s) added successfully.'
        );
    }


    // UPDATE ASSET
    private function update() {
        $asset_id      = trim($_POST['asset_id']      ?? '');
        $description   = trim($_POST['description']   ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        $category_id   = trim($_POST['category_id']   ?? '');
        $department_id = trim($_POST['department_id'] ?? '');
        $custodian_id  = trim($_POST['custodian_id']  ?? '');
        $item_type_id  = trim($_POST['item_type_id']  ?? '');
        $location      = trim($_POST['location']      ?? '');
        $status        = trim($_POST['status']        ?? 'Available');
        $is_certified  = (int) ($_POST['is_certified'] ?? 0);

        if (empty($asset_id))     { ResponseHelper::sendError(400, 'Asset ID is required.');    return; }
        if (empty($description))  { ResponseHelper::sendError(400, 'Description is required.'); return; }
        if (empty($department_id)){ ResponseHelper::sendError(400, 'Department is required.');  return; }
        if (empty($custodian_id)) { ResponseHelper::sendError(400, 'Custodian is required.');   return; }
        if (empty($item_type_id)) { ResponseHelper::sendError(400, 'Item type is required.');   return; }

        if (!$this->model->getAssetById($asset_id)) {
            ResponseHelper::sendError(404, 'Asset not found.');
            return;
        }

        // Verify custodian belongs to selected department
        $custStmt = $this->conn->prepare(
            "SELECT custodian_id FROM tbl_custodians
             WHERE  custodian_id  = :cid
               AND  department_id = :did"
        );
        $custStmt->bindParam(':cid', $custodian_id);
        $custStmt->bindParam(':did', $department_id);
        $custStmt->execute();
        if (!$custStmt->fetch()) {
            ResponseHelper::sendError(400, 'Selected custodian does not belong to this department.');
            return;
        }

        $data = [
            'asset_id'      => $asset_id,
            'description'   => $description,
            'serial_number' => $serial_number ?: null,
            'category_id'   => $category_id   ?: null,
            'department_id' => $department_id,
            'custodian_id'  => $custodian_id,
            'item_type_id'  => $item_type_id,
            'location'      => $location      ?: null,
            'status'        => $status,
            'is_certified'  => $is_certified,
        ];

        if ($this->model->updateAsset($data)) {
            ResponseHelper::sendSuccess(null, 'Asset updated successfully.');
        } else {
            ResponseHelper::sendError(500, 'Failed to update asset.');
        }
    }


    // ── STAFF: REQUEST DELETION ──────────────────────────────────────────────────
    private function delete() {
        $asset_id   = trim($_POST['asset_id']   ?? '');
        $deleted_by = trim($_POST['deleted_by'] ?? 'staff');
        $reason     = trim($_POST['reason']     ?? '');

        if (empty($asset_id)) { ResponseHelper::sendError(400, 'Asset ID is required.');            return; }
        if (empty($reason))   { ResponseHelper::sendError(400, 'Reason for deletion is required.'); return; }

        $asset = $this->model->getAssetById($asset_id);
        if (!$asset) { ResponseHelper::sendError(404, 'Asset not found.'); return; }
        if ($asset['IS_DELETED'] == 1) {
            ResponseHelper::sendError(409, 'Asset already has a pending deletion request.');
            return;
        }

        if ($this->model->requestDeletion($asset_id, $deleted_by, $reason)) {
            ResponseHelper::sendSuccess(null, 'Deletion request submitted.');
        } else {
            ResponseHelper::sendError(500, 'Failed to submit deletion request.');
        }
    }


    // ── ADMIN: DIRECT HARD DELETE ─────────────────────────────────────────────────
    private function adminDelete() {
        $asset_id = trim($_POST['asset_id'] ?? '');
        if (empty($asset_id)) { ResponseHelper::sendError(400, 'Asset ID is required.'); return; }
        if (!$this->model->getAssetById($asset_id)) { ResponseHelper::sendError(404, 'Asset not found.'); return; }

        if ($this->model->permanentDelete($asset_id)) {
            ResponseHelper::sendSuccess(null, 'Asset permanently deleted.');
        } else {
            ResponseHelper::sendError(500, 'Failed to delete asset.');
        }
    }


    // ── ADMIN: GET PENDING DELETION REQUESTS ──────────────────────────────────────
    private function getDeletionRequests() {
        $requests = $this->model->getDeletionRequests();
        ResponseHelper::sendSuccess($requests);
    }


    // ── ADMIN: APPROVE ────────────────────────────────────────────────────────────
    private function approveDeletion() {
        $asset_id    = trim($_POST['asset_id']    ?? '');
        $reviewed_by = trim($_POST['reviewed_by'] ?? 'admin');
        if (empty($asset_id)) { ResponseHelper::sendError(400, 'Asset ID is required.'); return; }
        if (!$this->model->getAssetById($asset_id)) { ResponseHelper::sendError(404, 'Asset not found.'); return; }

        if ($this->model->approveDeletion($asset_id, $reviewed_by)) {
            ResponseHelper::sendSuccess(null, 'Asset deletion approved and removed from system.');
        } else {
            ResponseHelper::sendError(500, 'Failed to approve deletion.');
        }
    }


    // ── ADMIN: REJECT ─────────────────────────────────────────────────────────────
    private function rejectDeletion() {
        $asset_id    = trim($_POST['asset_id']    ?? '');
        $reviewed_by = trim($_POST['reviewed_by'] ?? 'admin');
        if (empty($asset_id)) { ResponseHelper::sendError(400, 'Asset ID is required.'); return; }

        if ($this->model->rejectDeletion($asset_id, $reviewed_by)) {
            ResponseHelper::sendSuccess(null, 'Deletion request rejected. Asset restored.');
        } else {
            ResponseHelper::sendError(500, 'Failed to reject deletion request.');
        }
    }


    // SEARCH ASSETS
    private function search() {
        $keyword = trim($_GET['keyword'] ?? '');
        if (empty($keyword)) { $this->getAll(); return; }
        $assets = $this->model->searchAssets($keyword);
        ResponseHelper::sendSuccess($assets);
    }


    // FILTER BY STATUS
    private function filterStatus() {
        $status = trim($_GET['status'] ?? '');
        if (empty($status) || $status === 'ALL') { $this->getAll(); return; }
        $assets = $this->model->filterByStatus($status);
        ResponseHelper::sendSuccess($assets);
    }
}