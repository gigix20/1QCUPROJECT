<?php
require_once __DIR__ . '/../models/User.php';

class TokenService {
    private $pdo;

    public function __construct() {
        global $conn;
        $this->pdo = $conn;
    }

    public function createRememberToken($userId) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $stmt = $this->pdo->prepare("
            INSERT INTO REMEMBER_TOKENS (USER_ID, TOKEN_HASH, EXPIRES_AT)
            VALUES (?, ?, SYSTIMESTAMP + INTERVAL '7' DAY)
        ");
        $stmt->execute([$userId, $tokenHash]);

        setcookie('remember_me', $token, time() + 604800, '/', '', true, true);

        return $token; // raw token only if needed
    }

    public function validateToken($token) {
        $tokenHash = hash('sha256', $token);
        $stmt = $this->pdo->prepare("
            SELECT * FROM REMEMBER_TOKENS WHERE TOKEN_HASH = ? AND EXPIRES_AT > SYSTIMESTAMP
        ");
        $stmt->execute([$tokenHash]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function revokeTokens($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM REMEMBER_TOKENS WHERE USER_ID = ?");
        $stmt->execute([$userId]);
    }

    public function revokeToken($token) {
        $tokenHash = hash('sha256', $token);
    
        $stmt = $this->pdo->prepare("DELETE FROM REMEMBER_TOKENS WHERE TOKEN_HASH = ?");
        $stmt->execute([$tokenHash]);
    
        // Delete cookie
        setcookie('remember_me', '', time() - 3600, '/', '', true, true);
    }
}