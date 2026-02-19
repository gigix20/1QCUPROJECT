<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $otp   = $_POST['otp'];

    $stmt = $pdo->prepare("SELECT otp_code, otp_expires_at FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success'=>false,'message'=>'Invalid email']);
        exit;
    }

    if ($user['otp_code'] !== $otp) {
        echo json_encode(['success'=>false,'message'=>'Incorrect OTP']);
        exit;
    }

    if (strtotime($user['otp_expires_at']) < time()) {
        echo json_encode(['success'=>false,'message'=>'OTP expired']);
        exit;
    }

    // if OTP is correct → mark user as verified (is_verified: 0 = 1)
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE email = ?");
    $stmt->execute([$email]);

    echo json_encode(['success'=>true,'message'=>'Signup complete']);
}
