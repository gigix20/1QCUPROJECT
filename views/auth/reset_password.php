<?php
session_start();

// Ensure email is set from verified OTP
if (!isset($_SESSION['reset_email'])) {
    die("Unauthorized access. Please start the password reset process again.");
}

$email = $_SESSION['reset_email'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>

<h2>Reset Password</h2>

<form method="POST" action="../../backend/routes/reset_password_route.php">
    <label>New Password:</label>
    <input type="password" name="new_password" required>
    <br><br>
    <label>Confirm Password:</label>
    <input type="password" name="confirm_password" required>
    <br><br>
    <button type="submit">Reset Password</button>
</form>

<?php
if (isset($_SESSION['reset_msg'])) {
    echo "<p>" . $_SESSION['reset_msg'] . "</p>";
    unset($_SESSION['reset_msg']);
}
?>

</body>
</html>