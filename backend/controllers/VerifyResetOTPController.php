<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class VerifyResetOTPController {

    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function handleRequest() {
        session_start();

        if (!isset($_SESSION['forgot_email'])) {
            die("Unauthorized access. Please start the reset process again.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $otpInput = trim($_POST['otp']);
            $email = $_SESSION['forgot_email'];

            $this->verifyOtp($email, $otpInput);
        }
    }

    private function verifyOtp($email, $otpInput) {
        $otpInfo = $this->userModel->getOtpInfo($email);

        if (!$otpInfo || empty($otpInfo['OTP_CODE'])) {
            $_SESSION['otp_msg'] = "No OTP found. Please request a new one.";
            header("Location: /1QCUPROJECT/views/auth/forgot_password.php");
            exit();
        }

        $otpHash = $otpInfo['OTP_CODE'];
        $expiresAt = strtotime($otpInfo['OTP_EXPIRES_AT']);
        $now = time();

        if ($now > $expiresAt) {
            $_SESSION['otp_msg'] = "OTP expired. Please request a new one.";
            header("Location: /1QCUPROJECT/views/auth/forgot_password.php");
            exit();
        }

        if (!password_verify($otpInput, $otpHash)) {
            $_SESSION['otp_msg'] = "Invalid OTP. Try again.";
            header("Location: /1QCUPROJECT/views/auth/verify_reset_otp.php");
            exit();
        }

        // OTP verified: clear it, mark session for password reset
        $this->userModel->updateOtp($email, null, null);
        $_SESSION['reset_email'] = $email;
        unset($_SESSION['forgot_email']);

        // Redirect to reset password page
        header("Location: /1QCUPROJECT/views/auth/reset_password.php");
        exit();
    }
}