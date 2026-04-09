<?php
// backend/models/CustodianModel.php

class CustodianModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }


    // GET ALL CUSTODIANS (with department name)
    public function getAllCustodians()
    {
        $sql = "SELECT c.custodian_id,
                       c.department_id,
                       d.department_name,
                       c.first_name,
                       c.middle_name,
                       c.last_name,
                       c.suffix,
                       c.employee_id,
                       c.email,
                       c.phone,
                       c.status,
                       c.created_at,
                       COUNT(a.asset_id) AS asset_count
                FROM   tbl_custodians c
                LEFT JOIN tbl_departments d ON c.department_id = d.department_id
                LEFT JOIN tbl_assets      a ON a.custodian_id  = c.custodian_id
                                           AND a.is_deleted    = 0
                GROUP BY c.custodian_id,
                         c.department_id,
                         d.department_name,
                         c.first_name,
                         c.middle_name,
                         c.last_name,
                         c.suffix,
                         c.employee_id,
                         c.email,
                         c.phone,
                         c.status,
                         c.created_at
                ORDER BY d.department_name, c.last_name, c.first_name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // GET CUSTODIANS BY DEPARTMENT
    public function getCustodiansByDept($department_id)
    {
        $sql = "SELECT c.custodian_id,
                       c.department_id,
                       c.first_name,
                       c.middle_name,
                       c.last_name,
                       c.suffix,
                       c.employee_id,
                       c.email,
                       c.phone,
                       c.status
                FROM   tbl_custodians c
                WHERE  c.department_id = :department_id
                ORDER BY c.last_name, c.first_name";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':department_id', $department_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // GET CUSTODIAN BY ID
    public function getCustodianById($custodian_id)
    {
        $sql = "SELECT c.custodian_id,
                       c.department_id,
                       d.department_name,
                       c.first_name,
                       c.middle_name,
                       c.last_name,
                       c.suffix,
                       c.employee_id,
                       c.email,
                       c.phone,
                       c.status,
                       c.created_at
                FROM   tbl_custodians c
                LEFT JOIN tbl_departments d ON c.department_id = d.department_id
                WHERE  c.custodian_id = :custodian_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':custodian_id', $custodian_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    // ADD CUSTODIAN
    public function addCustodian($data)
    {
        $sql = "INSERT INTO tbl_custodians (
                    custodian_id,
                    department_id,
                    first_name,
                    middle_name,
                    last_name,
                    suffix,
                    employee_id,
                    email,
                    phone,
                    status,
                    created_at
                ) VALUES (
                    custodian_seq.NEXTVAL,
                    :department_id,
                    :first_name,
                    :middle_name,
                    :last_name,
                    :suffix,
                    :employee_id,
                    :email,
                    :phone,
                    :status,
                    CURRENT_TIMESTAMP
                )";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':department_id', $data['department_id']);
        $stmt->bindParam(':first_name',    $data['first_name']);
        $stmt->bindParam(':middle_name',   $data['middle_name']);
        $stmt->bindParam(':last_name',     $data['last_name']);
        $stmt->bindParam(':suffix',        $data['suffix']);
        $stmt->bindParam(':employee_id',   $data['employee_id']);
        $stmt->bindParam(':email',         $data['email']);
        $stmt->bindParam(':phone',         $data['phone']);
        $stmt->bindParam(':status',        $data['status']);
        return $stmt->execute();
    }


    // UPDATE CUSTODIAN
    public function updateCustodian($data)
    {
        $sql = "UPDATE tbl_custodians
                SET    department_id = :department_id,
                       first_name    = :first_name,
                       middle_name   = :middle_name,
                       last_name     = :last_name,
                       suffix        = :suffix,
                       employee_id   = :employee_id,
                       email         = :email,
                       phone         = :phone,
                       status        = :status
                WHERE  custodian_id  = :custodian_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':custodian_id',  $data['custodian_id']);
        $stmt->bindParam(':department_id', $data['department_id']);
        $stmt->bindParam(':first_name',    $data['first_name']);
        $stmt->bindParam(':middle_name',   $data['middle_name']);
        $stmt->bindParam(':last_name',     $data['last_name']);
        $stmt->bindParam(':suffix',        $data['suffix']);
        $stmt->bindParam(':employee_id',   $data['employee_id']);
        $stmt->bindParam(':email',         $data['email']);
        $stmt->bindParam(':phone',         $data['phone']);
        $stmt->bindParam(':status',        $data['status']);
        return $stmt->execute();
    }


    // DELETE CUSTODIAN
    // FK on tbl_assets.custodian_id is ON DELETE SET NULL, so assets are unaffected.
    public function deleteCustodian($custodian_id)
    {
        $sql  = "DELETE FROM tbl_custodians WHERE custodian_id = :custodian_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':custodian_id', $custodian_id);
        return $stmt->execute();
    }


    // COUNT ASSETS ASSIGNED TO CUSTODIAN (informational — delete is still allowed)
    public function getAssetCount($custodian_id)
    {
        $sql  = "SELECT COUNT(*) AS cnt
                 FROM   tbl_assets
                 WHERE  custodian_id = :custodian_id
                   AND  is_deleted   = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':custodian_id', $custodian_id);
        $stmt->execute();
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['CNT'] ?? 0);
    }


    // CHECK FOR DUPLICATE EMPLOYEE ID (excluding current custodian on edit)
    public function employeeIdExists($employee_id, $exclude_id = null)
    {
        if (empty($employee_id)) return false;
        $sql  = "SELECT COUNT(*) AS cnt FROM tbl_custodians
                 WHERE UPPER(employee_id) = UPPER(:employee_id)";
        if ($exclude_id !== null) $sql .= " AND custodian_id != :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':employee_id', $employee_id);
        if ($exclude_id !== null) $stmt->bindParam(':id', $exclude_id);
        $stmt->execute();
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['CNT'] ?? 0) > 0;
    }
}