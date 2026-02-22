<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/MailService.php';

class AuthService {
    private $user;
    private $mailService;

    public function __construct() {
        $this->user = new User();
        $this->mailService = new MailService();
    }

    public function register($data) {
        // 1️⃣ Check for password confirmation
        if ($data['password'] !== $data['password_confirmation']) {
            return ['success' => false, 'error' => 'password_mismatch'];
        }
    
        // 2️⃣ Prepare hashed password & OTP
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $otp = random_int(100000, 999999);
        $otpHashed = password_hash($otp, PASSWORD_DEFAULT);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
        // 3️⃣ Fetch any users with matching email or employee ID
        $conflicts = $this->user->findConflicts($data['email'], $data['employee_id']);
    
        $sameUnverifiedUser = null;
        $employeeIdConflict = false;
    
        foreach ($conflicts as $user) {
            // Unverified email matches → candidate for update
            if ($user['EMAIL'] === $data['email'] && $user['IS_VERIFIED'] == 0) {
                $sameUnverifiedUser = $user;
            }
    
            // Employee ID already exists for a different verified user → block
            if ($user['EMPLOYEE_ID'] === $data['employee_id'] && $user['EMAIL'] !== $data['email'] && $user['IS_VERIFIED'] == 1) {
                $employeeIdConflict = true;
            }
    
            // Email already exists on a verified account → block
            if ($user['EMAIL'] === $data['email'] && $user['IS_VERIFIED'] == 1) {
                return ['success' => false, 'error' => 'exists'];
            }
        }
    
        if ($employeeIdConflict) {
            return ['success' => false, 'error' => 'exists'];
        }
    
        // 4️⃣ Update unverified account if exists
        if ($sameUnverifiedUser) {
            $this->user->update($data['email'], [
                'full_name'      => $data['full_name'],
                'department'     => $data['department'],
                'employee_id'    => $data['employee_id'],
                'password'       => $hashedPassword,
                'otp_code'       => $otpHashed,
                'otp_expires_at' => $expires
            ]);
    
            $this->mailService->sendOtpEmail($data['email'], $otp);
            $_SESSION['verify_email'] = $data['email'];
    
            return ['success' => true, 'redirect' => '/1QCUPROJECT/views/auth/verify_email.php'];
        }
    
        // 5️⃣ No conflicts → create new user
        $this->user->create([
            'full_name'      => $data['full_name'],
            'email'          => $data['email'],
            'department'     => $data['department'],
            'employee_id'    => $data['employee_id'],
            'password'       => $hashedPassword,
            'otp_code'       => $otpHashed,
            'otp_expires_at' => $expires
        ]);
    
        $this->mailService->sendOtpEmail($data['email'], $otp);
        $_SESSION['verify_email'] = $data['email'];
    
        return ['success' => true, 'redirect' => '/1QCUPROJECT/views/auth/verify_email.php'];
    }

    public function verifyOtp($otpInput) {
        if (!isset($_SESSION['verify_email'])) {
            return ['success' => false, 'error' => 'no_session'];
        }

        $email = $_SESSION['verify_email'];
        $otpInfo = $this->user->getOtpInfo($email);

        if (!$otpInfo || !password_verify($otpInput, $otpInfo['OTP_CODE']) || strtotime($otpInfo['OTP_EXPIRES_AT']) <= time()) {
            return ['success' => false, 'error' => 'invalid_otp'];
        }

        $this->user->markVerified($email);
        unset($_SESSION['verify_email']);

        return [
            'success' => true,
            'message' => 'Your account has been verified! Redirecting you to login...',
            'redirect' => '/1QCUPROJECT/views/auth/login.php?verified=1'
        ];
    }

    public function resendOtp() {
        if (!isset($_SESSION['verify_email'])) {
            return ['success' => false, 'error' => 'no_session'];
        }

        $email = $_SESSION['verify_email'];
        $otp = random_int(100000, 999999);
        $otpHashed = password_hash($otp, PASSWORD_DEFAULT);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $this->user->updateOtp($email, $otpHashed, $expires);
        $this->mailService->sendOtpEmail($email, $otp);

        return ['success' => true, 'message' => 'OTP resent successfully'];
    }
}