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
  <link rel="stylesheet" href="../styles/global.css">
  <link rel="stylesheet" href="../styles/signup.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <!-- Left side -->
  <div class="left-panel">
    <div class="logo">
      <img src="../assets/img/mainlogo.png" alt="QCU Logo">
    </div>
    <h1>Email Verification</h1>
    <p>Please enter the verification code sent to your email.</p>
  </div>

  <!-- Right side -->
  <div class="right-panel">
    <h2>Verify Your Account</h2>

    <!-- Show email dynamically later using PHP -->
    <p class="verify-email-text">
      We sent a verification code to:<br>
      <strong><?php echo htmlspecialchars($email); ?></strong>
    </p>

    <form action="/1QCUPROJECT/backend/controllers/AuthController.php" method="POST" class="signup-form" autocomplete="off">
      
      <input type="hidden" name="action" value="verify_otp">

      <div style="margin-bottom:20px;">
        <label for="otp">Enter OTP Code</label>
        <input type="text" id="otp" name="otp" placeholder="Enter 6-digit code" maxlength="6" required>
      </div>

      <button type="submit" class="btn-primary">Verify Account</button>
    </form>

    <div style="margin-top:15px;">
      <form action="/1QCUPROJECT/backend/controllers/AuthController.php" method="POST">
        <input type="hidden" name="action" value="resend_otp">
        <button type="submit" class="btn-secondary">Resend OTP</button>
      </form>
    </div>

    <div class="login-link" style="margin-top:20px;">
      Back to <a href="login.php">Login</a>
    </div>

  </div>

</body>
</html>
