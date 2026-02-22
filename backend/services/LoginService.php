<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/TokenService.php';

class LoginService {
    private $user;

    public function __construct() {
        $this->user = new User();
    }

    public function login($email, $password, $remember = false) {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'empty'];
        }

        $user = $this->user->findByEmail($email);
        if (!$user || !password_verify($password, $user['PASSWORD'])) {
            return ['success' => false, 'error' => 'invalid'];
        }

        if ($user['IS_VERIFIED'] == 0) {
            $_SESSION['verify_email'] = $email;
            return ['success' => false, 'error' => 'unverified', 'redirect' => '/1QCUPROJECT/views/auth/verify_email.php'];
        }

        // Successful login
        $_SESSION['user_id'] = $user['USER_ID'];
        $_SESSION['full_name'] = $user['FULL_NAME'];

        // Remember me
        if ($remember) {
            $tokenService = new TokenService();
            $tokenService->createRememberToken($user['USER_ID']);
        }

        return ['success' => true, 'redirect' => '/1QCUPROJECT/views/staff/landing_page.php'];
    }
}