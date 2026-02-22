<?php
session_start();

if (!isset($_SESSION['verify_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['verify_email'];
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
</head>
<body>

  <!-- Left side -->
  <div class="left-panel">
    <div class="logo">
      <img src="../../assets/img/mainlogo.png" alt="QCU Logo">
    </div>
    <h1>Email Verification</h1>
    <p>Please enter the verification code sent to your email address to activate your account.</p>
  </div>

  <!-- Right side -->
  <div class="right-panel">
    <h2>Verify Your Account</h2>

    <p class="verify-email-text">
      We sent a 6-digit code to:
      <strong><?php echo htmlspecialchars($email ?? 'your email'); ?></strong>
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
      Back to <a href="login.php">Login</a>
    </div>

  </div>

  <script src="../../scripts/auth/verify_email.js"></script>

</body>
</html>