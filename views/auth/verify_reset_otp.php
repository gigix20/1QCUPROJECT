<?php session_start(); ?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
</head>
<body>

<h2>Verify OTP</h2>

<form method="POST" action="/1QCUPROJECT/backend/routes/verify_reset_otp_route.php">
    <label>Enter the OTP sent to your email:</label>
    <input type="text" name="otp" maxlength="6" required>
    <button type="submit">Verify OTP</button>
</form>

<?php
if (isset($_SESSION['otp_msg'])) {
    echo "<p>" . $_SESSION['otp_msg'] . "</p>";
    unset($_SESSION['otp_msg']);
}
?>

</body>
</html>