<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
</head>
<body>

<h2>Forgot Password</h2>

<form method="POST" action="/1QCUPROJECT/backend/routes/forgot_password_route.php">
    <label>Email:</label>
    <input type="email" name="email" required>
    <button type="submit">Send OTP</button>
</form>

<?php
if (isset($_SESSION['forgot_msg'])) {
    echo "<p>" . $_SESSION['forgot_msg'] . "</p>";
    unset($_SESSION['forgot_msg']);
}

// Show debug OTP for testing
if (isset($_SESSION['debug_otp'])) {
    echo "<p>DEBUG OTP: " . $_SESSION['debug_otp'] . "</p>";
    unset($_SESSION['debug_otp']);
}
?>

</body>
</html>