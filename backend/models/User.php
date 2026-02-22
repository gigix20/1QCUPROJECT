<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $pdo;

    public function __construct() {
        global $conn;
        $this->pdo = $conn;
    }

    public function findByEmailOrEmployeeId($email, $employeeId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE EMAIL = ? OR EMPLOYEE_ID = ?");
        $stmt->execute([$email, $employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findConflicts($email, $employeeId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users 
            WHERE EMAIL = ? OR EMPLOYEE_ID = ?
        ");
        $stmt->execute([$email, $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // return all matches
    }

    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users
            (FULL_NAME, EMAIL, DEPARTMENT, EMPLOYEE_ID, PASSWORD, OTP_CODE, OTP_EXPIRES_AT, IS_VERIFIED)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['department'],
            $data['employee_id'],
            $data['password'],
            $data['otp_code'],
            $data['otp_expires_at']
        ]);
    }

    public function update($email, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE users SET
                FULL_NAME = ?,
                DEPARTMENT = ?,
                EMPLOYEE_ID = ?,
                PASSWORD = ?,
                OTP_CODE = ?,
                OTP_EXPIRES_AT = ?
            WHERE EMAIL = ?
        ");
        $stmt->execute([
            $data['full_name'],
            $data['department'],
            $data['employee_id'],
            $data['password'],
            $data['otp_code'],
            $data['otp_expires_at'],
            $email
        ]);
    }

    public function markVerified($email) {
        $stmt = $this->pdo->prepare("
            UPDATE users SET IS_VERIFIED = 1, OTP_CODE = NULL, OTP_EXPIRES_AT = NULL
            WHERE EMAIL = ?
        ");
        $stmt->execute([$email]);
    }

    public function updateOtp($email, $otpHashed, $expires) {
        $stmt = $this->pdo->prepare("
            UPDATE users SET OTP_CODE = ?, OTP_EXPIRES_AT = ? WHERE EMAIL = ?
        ");
        $stmt->execute([$otpHashed, $expires, $email]);
    }

    public function getOtpInfo($email) {
        $stmt = $this->pdo->prepare("SELECT OTP_CODE, OTP_EXPIRES_AT FROM users WHERE EMAIL = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE EMAIL = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Optional: expose PDO for remember-me token insert
    public function getPdo() {
        return $this->pdo;
    }
}