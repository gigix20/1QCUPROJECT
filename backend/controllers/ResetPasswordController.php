<?php
require_once __DIR__ . '/../config/database.php';

class ResetPasswordController {

    public function handleRequest() {
        session_start();

        // 1️⃣ Ensure user came from OTP verification
        if (!isset($_SESSION['reset_email'])) {
            die("Unauthorized access. Please start the password reset process again.");
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $newPassword = trim($_POST['new_password']);
            $confirmPassword = trim($_POST['confirm_password']);

            // 2️⃣ Check passwords match
            if ($newPassword !== $confirmPassword) {
                $_SESSION['reset_msg'] = "Passwords do not match.";
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit();
            }

            $this->resetPassword($_SESSION['reset_email'], $newPassword);
        }
    }

    private function resetPassword($email, $newPassword) {
        global $conn;

        // 3️⃣ Hash new password
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // 4️⃣ Update users table
        $updateStmt = $conn->prepare("UPDATE users SET password = :password WHERE email = :email");
        $updateStmt->execute([
            'password' => $passwordHash,
            'email' => $email
        ]);

        // 5️⃣ Clear all used OTPs for this email
        $markUsedStmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = :email");
        $markUsedStmt->execute(['email' => $email]);

        // 6️⃣ Clear session
        unset($_SESSION['reset_email']);
        $_SESSION['reset_msg'] = "Password reset successfully. You can now log in.";

        header("Location: /1QCUPROJECT/views/auth/login.php");
        exit();
    }
}