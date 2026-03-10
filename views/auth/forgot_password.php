<?php 
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../styles/auth/forgot_password.css">
    <link rel="stylesheet" href="../../styles/global.css">
</head>
<body>

    <!-- LEFT PANEL -->
    <div class="left-panel">
        <div class="icon-lock">
            <svg viewBox="0 0 90 90" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="20" y="42" width="50" height="36" rx="8" fill="none" stroke="#00ffff" stroke-width="2.5"/>
                <path d="M30 42V30a15 15 0 0 1 30 0v12" stroke="#00ffff" stroke-width="2.5" stroke-linecap="round"/>
                <circle cx="45" cy="60" r="5" fill="#00ffff" opacity="0.9"/>
                <line x1="45" y1="65" x2="45" y2="72" stroke="#00ffff" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>
        <h1>Account Recovery</h1>
        <p>Enter your registered email and we'll send you a one-time password to reset your account.</p>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
        <h2>Forgot Password</h2>

        <form class="forgot-form" method="POST" action="/1QCUPROJECT/backend/routes/forgot_password_route.php" autocomplete="off">
            <p class="form-hint">Please verify your account first.</p>

            <div class="input-wrapper">
                <label for="email">Email Address</label>
                <input type="text" style="display:none">
                <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="off" required>
            </div>

            <button type="submit" class="btn-primary">Verify</button>

            <?php
            if (isset($_SESSION['forgot_msg'])) {
                echo '<p class="msg-box">' . htmlspecialchars($_SESSION['forgot_msg']) . '</p>';
                unset($_SESSION['forgot_msg']);
            }
            if (isset($_SESSION['debug_otp'])) {
                echo '<p class="debug-box">DEBUG OTP: ' . htmlspecialchars($_SESSION['debug_otp']) . '</p>';
                unset($_SESSION['debug_otp']);
            }
            ?>

        </form>

        <div class="back-link">
            <a href="/1QCUPROJECT/views/auth/login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>