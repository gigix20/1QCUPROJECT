<?php 
session_start();

if (!isset($_SESSION['forgot_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['forgot_email'] ?? null;

function maskEmail($email) {
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];

    return substr($name, 0, 2) 
        . str_repeat('*', max(strlen($name) - 2, 0)) 
        . '@' 
        . $domain;
}

$maskedEmail = maskEmail($email);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Verify OTP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../styles/auth/verify_reset_otp.css">
    <link rel="stylesheet" href="../../styles/global.css">
</head>
<body>

    <div class="left-panel">
        <div class="icon-shield">
            <svg viewBox="0 0 90 90" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M45 10 L72 22 L72 46 C72 62 59 73 45 80 C31 73 18 62 18 46 L18 22 Z" stroke="#00ffff" stroke-width="2.5" fill="none" stroke-linejoin="round"/>
                <polyline points="33,47 41,55 57,39" stroke="#00ffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h1>OTP Verification</h1>
        <p>Check your email inbox for the 6-digit code we sent you and enter it to continue.</p>
    </div>

    <div class="right-panel">
        <h2>Verify OTP</h2>

        <p class="verify-email-text">
            We sent a 6-digit code to:
            <strong><?php echo htmlspecialchars($maskedEmail ?? 'your email'); ?></strong>
        </p>

        <form class="verify-form" method="POST" action="/1QCUPROJECT/backend/routes/verify_reset_otp_route.php">

            <div class="otp-wrapper">
                <label for="otp">One-Time Password</label>
                <input type="text" id="otp" name="otp" maxlength="6" placeholder="••••••" required>
            </div>

            <button type="submit" class="btn-primary">Verify OTP</button>

            <div class="btn-divider">or</div>

            <button type="submit" name="form_action" value="resend_otp" class="btn-secondary">
                <i class="fa-solid fa-rotate-right" style="margin-right:6px;"></i>Resend OTP
            </button>

            <p class="resend-hint" id="resendHint"></p>

            <?php
            if (isset($_SESSION['otp_msg'])) {
                echo '<p class="msg-box">' . htmlspecialchars($_SESSION['otp_msg']) . '</p>';
                unset($_SESSION['otp_msg']);
            }
            ?>
        </form>

        <div class="login-link">
            <a href="/1QCUPROJECT/views/auth/login.php">← Back to Login</a>
        </div>
    </div>

<script src="../../scripts/auth/verify_reset_otp.js"></script>

</body>
</html>