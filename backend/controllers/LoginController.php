<?php
session_start();
require_once __DIR__ . '/../config/database.php'; 

$pdo = $conn;

// Detect AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'invalid_request']);
        exit;
    }
    header("Location: /1QCUPROJECT/views/login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember_me']);

// Check for empty fields
if (empty($email) || empty($password)) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'empty']);
        exit;
    }
    header("Location: /1QCUPROJECT/views/login.php?error=empty");
    exit;
}

// Fetch user by email (Oracle column names are uppercase)
$stmt = $pdo->prepare("SELECT * FROM USERS WHERE EMAIL = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// User not found or password incorrect
if (!$user || !password_verify($password, $user['PASSWORD'])) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'invalid']);
        exit;
    }
    header("Location: /1QCUPROJECT/views/login.php?error=invalid");
    exit;
}

// User exists but not verified
if ($user['IS_VERIFIED'] == 0) {
    $_SESSION['verify_email'] = $email;
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'unverified']);
        exit;
    }
    header("Location: /1QCUPROJECT/views/verify_email.php?error=unverified");
    exit;
}

// Successful login
$_SESSION['user_id']   = $user['USER_ID'];
$_SESSION['full_name'] = $user['FULL_NAME'];

// Remember me
if ($remember) {

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expires = time() + (60 * 60 * 24 * 7); // Token Expiration: 7 days

    // Save token in DB
    $stmt = $pdo->prepare("
        INSERT INTO REMEMBER_TOKENS (USER_ID, TOKEN_HASH, EXPIRES_AT)
        VALUES (?, ?, SYSTIMESTAMP + INTERVAL '7' DAY)
    ");
    $stmt->execute([$user['USER_ID'], $tokenHash]);

    // Set secure cookie
    setcookie(
        'remember_me',
        $token,
        $expires,
        '/',
        '',
        true,
        true
    );
}

// Respond
if ($isAjax) {
    echo json_encode(['success' => true, 'redirect' => '/1QCUPROJECT/views/landing_page.php']);
    exit;
}

header("Location: /1QCUPROJECT/views/landing_page.php?login=success");
exit;