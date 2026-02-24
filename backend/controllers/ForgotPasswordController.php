<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/MailService.php';
require_once __DIR__ . '/../models/User.php';

class ForgotPasswordController {

    private $userModel;
    private $mailService;

    public function __construct() {
        $this->userModel = new User();
        $this->mailService = new MailService();
    }

    public function handleRequest() {
        session_start();

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $email = trim($_POST['email']);
            if (!empty($email)) {
                $this->processReset($email);
            } else {
                $_SESSION['forgot_msg'] = "Please enter your email.";
                header("Location: /1QCUPROJECT/views/auth/forgot_password.php");
                exit();
            }
        }
    }

    private function processReset($email) {
        // 1️⃣ Check if email exists
        $user = $this->userModel->findByEmail($email);

        // 2️⃣ Always respond with generic message to prevent email enumeration
        $_SESSION['forgot_msg'] = "If this email exists, an OTP has been sent to your email.";

        if (!$user) {
            header("Location: /1QCUPROJECT/views/auth/forgot_password.php");
            exit();
        }

        // 3️⃣ Generate OTP & hash
        $otp = mt_rand(100000, 999999); // 6-digit OTP
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes')); // OTP valid 10 minutes

        // 4️⃣ Save OTP in DB
        $this->userModel->updateOtp($email, $otpHash, $expiresAt);

        // 5️⃣ Send OTP via email
        $sent = $this->mailService->sendOtpEmail($email, $otp);
        if (!$sent) {
            error_log("Failed to send OTP to $email");
        }

        // 6️⃣ Save email in session for OTP verification step
        $_SESSION['forgot_email'] = $email;

        header("Location: /1QCUPROJECT/views/auth/verify_reset_otp.php");
        exit();
    }
}