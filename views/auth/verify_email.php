<?php
session_start();

if (!isset($_SESSION['verify_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['verify_email'];

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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Email - QCU Asset Management</title>
  <link rel="stylesheet" href="../../styles/global.css">
  <link rel="stylesheet" href="../../styles/auth/verify_email.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>

  <!-- Left side -->
  <div class="left-panel">
        <div class="icon-shield">
            <svg viewBox="0 0 90 90" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M45 10 L72 22 L72 46 C72 62 59 73 45 80 C31 73 18 62 18 46 L18 22 Z" stroke="#00ffff" stroke-width="2.5" fill="none" stroke-linejoin="round"/>
                <polyline points="33,47 41,55 57,39" stroke="#00ffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
    <h1>Email Verification</h1>
    <p>Please enter the verification code sent to your email address to activate your account.</p>
  </div>

  <!-- Right side -->
  <div class="right-panel">
    <h2>Verify Your Account</h2>

    <p class="verify-email-text">
      We sent a 6-digit code to:
      <strong><?php echo htmlspecialchars($maskedEmail ?? 'your email'); ?></strong>
    </p>

    <form name="verify_otp" action="/1QCUPROJECT/backend/controllers/AuthController.php" method="POST" class="verify-form" autocomplete="off">

      <input type="hidden" name="form_action" value="verify_otp">

      <div class="otp-wrapper">
        <label for="otp">OTP Code</label>
        <input type="text" id="otp" name="otp" placeholder="······" maxlength="6" required inputmode="numeric" pattern="\d{6}">
      </div>

      <button type="submit" class="btn-primary">
        Verify Account
      </button>

      <div class="btn-divider">or</div>

      <button type="submit" name="form_action" value="resend_otp" class="btn-secondary">
        <i class="fa-solid fa-rotate-right" style="margin-right:6px;"></i>Resend OTP
      </button>

      <p class="resend-hint" id="resend-hint"></p>

    </form>

    <div class="login-link">
        <a href="/1QCUPROJECT/views/auth/login.php">← Back to Login</a>
    </div>

  </div>

  <script src="../../scripts/auth/verify_email.js"></script>

</body>
</html>