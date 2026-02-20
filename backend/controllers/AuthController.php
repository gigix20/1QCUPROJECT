<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../services/MailService.php';

$pdo = $conn;

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /1QCUPROJECT/views/signup.php");
    exit;
}

$action = $_POST['form_action'] ?? '';

// REGISTER
if ($action === 'register') {

    $full_name   = trim($_POST['full_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $department  = trim($_POST['department'] ?? '');
    $employee_id = trim($_POST['employee_id'] ?? '');
    $password    = $_POST['password'] ?? '';
    $password_confirmation = $_POST['password_confirmation'] ?? '';

    // Empty fields
    if (empty($full_name) || empty($email) || empty($department) || empty($employee_id) || empty($password) || empty($password_confirmation)) {

        if ($isAjax) {
            echo json_encode(['success' => false, 'error' => 'empty']);
            exit;
        }

        header("Location: /1QCUPROJECT/views/signup.php?error=empty");
        exit;
    }

    // Password mismatch
    if ($password !== $password_confirmation) {

        if ($isAjax) {
            echo json_encode(['success' => false, 'error' => 'password_mismatch']);
            exit;
        }

        header("Location: /1QCUPROJECT/views/signup.php?error=password_mismatch");
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $otp = random_int(100000, 999999);
    $otpHashed = password_hash($otp, PASSWORD_DEFAULT);
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $check = $pdo->prepare("
        SELECT EMAIL, EMPLOYEE_ID, IS_VERIFIED 
        FROM USERS 
        WHERE EMAIL = ? OR EMPLOYEE_ID = ?
    ");
    $check->execute([$email, $employee_id]);
    $existingUser = $check->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {

        // If user exists but NOT verified → allow update
        if ($existingUser['IS_VERIFIED'] == 0) {
    
            $update = $pdo->prepare("
                UPDATE USERS 
                SET FULL_NAME = ?, 
                    DEPARTMENT = ?, 
                    EMPLOYEE_ID = ?, 
                    PASSWORD = ?, 
                    OTP_CODE = ?, 
                    OTP_EXPIRES_AT = ?
                WHERE EMAIL = ?
            ");
    
            $update->execute([
                $full_name,
                $department,
                $employee_id,
                $hashed_password,
                $otpHashed,
                $expires,
                $email
            ]);
    
            $_SESSION['verify_email'] = $email;
            sendOtpEmail($email, $otp);
    
            if ($isAjax) {
                echo json_encode([
                    'success' => true,
                    'redirect' => '/1QCUPROJECT/views/verify_email.php'
                ]);
                exit;
            }
    
            header("Location: /1QCUPROJECT/views/verify_email.php");
            exit;
        }
    
        // User exists and already verified → generic message for email or employee ID
        if ($isAjax) {
            echo json_encode(['success' => false, 'error' => 'exists']);
            exit;
        }
    
        header("Location: /1QCUPROJECT/views/signup.php?error=exists");
        exit;
    }

    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users
        (FULL_NAME, EMAIL, DEPARTMENT, EMPLOYEE_ID, PASSWORD, OTP_CODE, OTP_EXPIRES_AT, IS_VERIFIED)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0)
    ");
    $stmt->execute([$full_name, $email, $department, $employee_id, $hashed_password, $otpHashed, $expires]);

    $_SESSION['verify_email'] = $email;
    sendOtpEmail($email, $otp);

    if ($isAjax) {
        echo json_encode([
            'success' => true,
            'redirect' => '/1QCUPROJECT/views/verify_email.php'
        ]);
        exit;
    }

    header("Location: /1QCUPROJECT/views/verify_email.php");
    exit;
}

// VERIFY OTP
if ($action === 'verify_otp') {
    if ($isAjax) header('Content-Type: application/json');

    try {
        if (!isset($_SESSION['verify_email'])) {
            throw new Exception('no_session');
        }

        $email = $_SESSION['verify_email'];
        $otpInput = trim($_POST['otp']);

        $stmt = $pdo->prepare("SELECT OTP_CODE, OTP_EXPIRES_AT FROM users WHERE EMAIL = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($otpInput, $user['OTP_CODE']) || strtotime($user['OTP_EXPIRES_AT']) <= time()) {
            throw new Exception('invalid_otp');
        }

        // Mark verified
        $update = $pdo->prepare("
            UPDATE users
            SET IS_VERIFIED = 1, OTP_CODE = NULL, OTP_EXPIRES_AT = NULL
            WHERE EMAIL = ?
        ");
        $update->execute([$email]);

        unset($_SESSION['verify_email']);

        echo json_encode([
            'success' => true,
            'message' => 'Your account has been verified! Redirecting you to login...',
            'redirect' => '/1QCUPROJECT/views/login.php?verified=1'
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// RESEND OTP
if ($action === 'resend_otp') {
    if ($isAjax) header('Content-Type: application/json');

    try {
        if (!isset($_SESSION['verify_email'])) {
            throw new Exception('no_session');
        }

        $email = $_SESSION['verify_email'];
        $otp = random_int(100000, 999999);
        $otpHashed = password_hash($otp, PASSWORD_DEFAULT);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $stmt = $pdo->prepare("UPDATE users SET OTP_CODE = ?, OTP_EXPIRES_AT = ? WHERE EMAIL = ?");
        $stmt->execute([$otpHashed, $expires, $email]);

        sendOtpEmail($email, $otp);

        echo json_encode([
            'success' => true,
            'message' => 'OTP resent successfully'
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}
