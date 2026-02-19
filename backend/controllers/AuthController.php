<?php
session_start();
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../services/MailService.php';

$pdo = $conn;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /1QCUPROJECT/views/signup.php");
    exit;
}

$action = $_POST['action'] ?? '';

// REGISTER
if ($action === 'register') {

    $full_name   = trim($_POST['full_name']);
    $email       = trim($_POST['email']);
    $department  = trim($_POST['department']);
    $employee_id = trim($_POST['employee_id']);
    $password    = $_POST['password'] ?? '';
    $password_confirmation = $_POST['password_confirmation'] ?? '';

    if (empty($full_name) || empty($email) || empty($department) || empty($employee_id) || empty($password) || empty($password_confirmation)) {
        header("Location: /1QCUPROJECT/views/signup.php?error=empty");
        exit;
    }

    if ($password !== $password_confirmation) {
        header("Location: /1QCUPROJECT/views/signup.php?error=password_mismatch");
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Generate OTP
    $otp = random_int(100000, 999999);
    $otpHashed = password_hash($otp, PASSWORD_DEFAULT);
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Check if user exists
    $check = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $check->execute([$email]);
    $existingUser = $check->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        if ($existingUser['IS_VERIFIED'] == 0) {
            $update = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, department = ?, employee_id = ?, password = ?, otp_code = ?, otp_expires_at = ?
                WHERE email = ?
            ");
            $update->execute([$full_name, $department, $employee_id, $hashed_password, $otpHashed, $expires, $email]);

            $_SESSION['verify_email'] = $email;
            sendOtpEmail($email, $otp);
            header("Location: /1QCUPROJECT/views/verify_email.php");
            exit;
        } else {
            header("Location: /1QCUPROJECT/views/signup.php?error=exists");
            exit;
        }
    }

    // Insert new user (USER_ID auto-incremented via trigger)
    $stmt = $pdo->prepare("
        INSERT INTO users
        (FULL_NAME, EMAIL, DEPARTMENT, EMPLOYEE_ID, PASSWORD, OTP_CODE, OTP_EXPIRES_AT, IS_VERIFIED)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0)
    ");
    $stmt->execute([$full_name, $email, $department, $employee_id, $hashed_password, $otpHashed, $expires]);

    $_SESSION['verify_email'] = $email;
    sendOtpEmail($email, $otp);

    header("Location: /1QCUPROJECT/views/verify_email.php");
    exit;
}

// VERIFY OTP
if ($action === 'verify_otp') {

    if (!isset($_SESSION['verify_email'])) {
        header("Location: /1QCUPROJECT/views/login.php");
        exit;
    }

    $email = $_SESSION['verify_email'];
    $otpInput = trim($_POST['otp']);

    $stmt = $pdo->prepare("SELECT OTP_CODE, OTP_EXPIRES_AT FROM users WHERE EMAIL = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user &&
        password_verify($otpInput, $user['OTP_CODE']) &&
        strtotime($user['OTP_EXPIRES_AT']) > time()) {

        $update = $pdo->prepare("
            UPDATE users
            SET IS_VERIFIED = 1, OTP_CODE = NULL, OTP_EXPIRES_AT = NULL
            WHERE EMAIL = ?
        ");
        $update->execute([$email]);

        unset($_SESSION['verify_email']);
        header("Location: /1QCUPROJECT/views/login.php?verified=1");
        exit;
    }

    header("Location: /1QCUPROJECT/views/verify_email.php?error=invalid");
    exit;
}

// RESEND OTP
if ($action === 'resend_otp') {

    if (!isset($_SESSION['verify_email'])) {
        header("Location: /1QCUPROJECT/views/login.php");
        exit;
    }

    $email = $_SESSION['verify_email'];

    $otp = random_int(100000, 999999);
    $otpHashed = password_hash($otp, PASSWORD_DEFAULT);
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $stmt = $pdo->prepare("
        UPDATE users
        SET OTP_CODE = ?, OTP_EXPIRES_AT = ?
        WHERE EMAIL = ?
    ");
    $stmt->execute([$otpHashed, $expires, $email]);

    sendOtpEmail($email, $otp);

    header("Location: /1QCUPROJECT/views/verify_email.php?resent=1");
    exit;
}
