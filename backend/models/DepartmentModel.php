<?php
// backend/models/DepartmentModel.php

class DepartmentModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }


    // GET ALL DEPARTMENTS (with asset count and custodian count)
    public function getAllDepartments()
    {
        $sql = "SELECT d.department_id,
                       d.department_name,
                       d.building,
                       d.department_head,
                       d.status,
                       d.created_at,
                       COUNT(DISTINCT a.asset_id)      AS asset_count,
                       COUNT(DISTINCT c.custodian_id)  AS custodian_count
                FROM   tbl_departments d
                LEFT JOIN tbl_assets     a ON a.department_id = d.department_id
                                          AND a.is_deleted    = 0
                LEFT JOIN tbl_custodians c ON c.department_id = d.department_id
                GROUP  BY d.department_id,
                          d.department_name,
                          d.building,
                          d.department_head,
                          d.status,
                          d.created_at
                ORDER  BY d.department_name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // GET DEPARTMENT BY ID
    public function getDepartmentById($department_id)
    {
        $sql = "SELECT department_id,
                       department_name,
                       building,
                       department_head,
                       status,
                       created_at
                FROM   tbl_departments
                WHERE  department_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $department_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    // ADD DEPARTMENT
    public function addDepartment($data)
    {
        $sql = "INSERT INTO tbl_departments (
                    department_id,
                    department_name,
                    building,
                    department_head,
                    status,
                    created_at
                ) VALUES (
                    dept_seq.NEXTVAL,
                    :department_name,
                    :building,
                    :department_head,
                    :status,
                    CURRENT_TIMESTAMP
                )";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':department_name', $data['department_name']);
        $stmt->bindParam(':building',        $data['building']);
        $stmt->bindParam(':department_head', $data['department_head']);
        $stmt->bindParam(':status',          $data['status']);
        return $stmt->execute();
    }


    // UPDATE DEPARTMENT
    public function updateDepartment($data)
    {
        $sql = "UPDATE tbl_departments
                SET    department_name = :department_name,
                       building        = :building,
                       department_head = :department_head,
                       status          = :status
                WHERE  department_id   = :department_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':department_id',   $data['department_id']);
        $stmt->bindParam(':department_name', $data['department_name']);
        $stmt->bindParam(':building',        $data['building']);
        $stmt->bindParam(':department_head', $data['department_head']);
        $stmt->bindParam(':status',          $data['status']);
        return $stmt->execute();
    }


    // DELETE DEPARTMENT — only if no active assets are assigned
    public function deleteDepartment($department_id)
    {
        $sql  = "DELETE FROM tbl_departments WHERE department_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $department_id);
        return $stmt->execute();
    }


    // CHECK IF DEPARTMENT HAS ACTIVE ASSETS
    public function hasAssets($department_id)
    {
        $sql  = "SELECT COUNT(*) AS cnt
                 FROM   tbl_assets
                 WHERE  department_id = :id
                   AND  is_deleted    = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $department_id);
        $stmt->execute();
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['CNT'] ?? 0) > 0;
    }


    // CHECK FOR DUPLICATE DEPARTMENT NAME (excluding current id on edit)
    public function nameExists($name, $exclude_id = null)
    {
        $sql  = "SELECT COUNT(*) AS cnt FROM tbl_departments
                 WHERE UPPER(department_name) = UPPER(:name)";
        if ($exclude_id !== null) $sql .= " AND department_id != :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        if ($exclude_id !== null) $stmt->bindParam(':id', $exclude_id);
        $stmt->execute();
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['CNT'] ?? 0) > 0;
    }
}
