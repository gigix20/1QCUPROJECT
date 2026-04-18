<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../config/database.php';

class UserController
{
    private $user;
    private $pdo;

    public function __construct()
    {
        $this->user = new User();
        $this->pdo  = $this->user->getPdo();
    }

    public function getAll()
    {
        $stmt = $this->pdo->prepare("
            SELECT USER_ID, FULL_NAME, EMAIL, DEPARTMENT, EMPLOYEE_ID,
                   ROLE, IS_VERIFIED,
                   TO_CHAR(CREATED_AT, 'YYYY-MM-DD') AS CREATED_AT,
                   TO_CHAR(UPDATED_AT, 'YYYY-MM-DD HH24:MI') AS LAST_LOGIN
            FROM users
            ORDER BY CREATED_AT DESC
        ");
        $stmt->execute();
        ResponseHelper::sendSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getDepartments()
    {
        $stmt = $this->pdo->prepare("
            SELECT DEPARTMENT_ID, DEPARTMENT_NAME
            FROM tbl_departments
            WHERE STATUS = 'Active'
            ORDER BY DEPARTMENT_NAME ASC
        ");
        $stmt->execute();
        ResponseHelper::sendSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create($data)
    {
        $required = ['full_name', 'email', 'employee_id', 'department', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                ResponseHelper::sendError(400, "Missing required field: $field");
                return;
            }
        }

        $conflicts = $this->user->findConflicts($data['email'], $data['employee_id']);
        if (!empty($conflicts)) {
            foreach ($conflicts as $c) {
                if (strtolower($c['EMAIL']) === strtolower($data['email'])) {
                    ResponseHelper::sendError(409, 'Email already exists.');
                    return;
                }
                if (strtolower($c['EMPLOYEE_ID']) === strtolower($data['employee_id'])) {
                    ResponseHelper::sendError(409, 'Employee ID already exists.');
                    return;
                }
            }
        }

        $role = (isset($data['role']) && $data['role'] === 'Admin') ? 'Admin' : 'Staff';

        $this->user->create([
            'full_name'     => trim($data['full_name']),
            'email'         => trim($data['email']),
            'department'    => trim($data['department']),
            'employee_id'   => trim($data['employee_id']),
            'password'      => password_hash($data['password'], PASSWORD_BCRYPT),
            'otp_code'      => null,
            'otp_expires_at'=> null,
            'role'          => $role,
        ]);

        $stmt = $this->pdo->prepare("
            UPDATE users SET IS_VERIFIED = 1 WHERE EMAIL = ?
        ");
        $stmt->execute([trim($data['email'])]);

        ResponseHelper::sendSuccess(['message' => 'User created successfully.']);
    }

    public function update($data)
    {
        if (empty($data['user_id'])) {
            ResponseHelper::sendError(400, 'Missing user_id.');
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE USER_ID = ?");
        $stmt->execute([$data['user_id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            ResponseHelper::sendError(404, 'User not found.');
            return;
        }

        $emailCheck = $this->pdo->prepare("
            SELECT USER_ID FROM users WHERE EMAIL = ? AND USER_ID != ?
        ");
        $emailCheck->execute([$data['email'] ?? $existing['EMAIL'], $data['user_id']]);
        if ($emailCheck->fetch()) {
            ResponseHelper::sendError(409, 'Email already in use by another user.');
            return;
        }

        $empCheck = $this->pdo->prepare("
            SELECT USER_ID FROM users WHERE EMPLOYEE_ID = ? AND USER_ID != ?
        ");
        $empCheck->execute([$data['employee_id'] ?? $existing['EMPLOYEE_ID'], $data['user_id']]);
        if ($empCheck->fetch()) {
            ResponseHelper::sendError(409, 'Employee ID already in use by another user.');
            return;
        }

        $role     = (isset($data['role']) && $data['role'] === 'Admin') ? 'Admin' : 'Staff';
        $password = !empty($data['password'])
            ? password_hash($data['password'], PASSWORD_BCRYPT)
            : $existing['PASSWORD'];

        $stmt = $this->pdo->prepare("
            UPDATE users SET
                FULL_NAME   = ?,
                EMAIL       = ?,
                DEPARTMENT  = ?,
                EMPLOYEE_ID = ?,
                ROLE        = ?,
                PASSWORD    = ?
            WHERE USER_ID = ?
        ");
        $stmt->execute([
            trim($data['full_name']   ?? $existing['FULL_NAME']),
            trim($data['email']       ?? $existing['EMAIL']),
            trim($data['department']  ?? $existing['DEPARTMENT']),
            trim($data['employee_id'] ?? $existing['EMPLOYEE_ID']),
            $role,
            $password,
            $data['user_id'],
        ]);

        ResponseHelper::sendSuccess(['message' => 'User updated successfully.']);
    }

    public function delete($data)
    {
        if (empty($data['user_id'])) {
            ResponseHelper::sendError(400, 'Missing user_id.');
            return;
        }

        $stmt = $this->pdo->prepare("DELETE FROM users WHERE USER_ID = ?");
        $stmt->execute([$data['user_id']]);

        if ($stmt->rowCount() === 0) {
            ResponseHelper::sendError(404, 'User not found.');
            return;
        }

        ResponseHelper::sendSuccess(['message' => 'User deleted successfully.']);
    }
}