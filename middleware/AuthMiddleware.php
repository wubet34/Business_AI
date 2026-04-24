<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

class AuthMiddleware {
    public static function handle(): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            jsonResponse(401, ['error' => 'Unauthorized. Please login.']);
        }
        return [
            'id'   => (int)$_SESSION['user_id'],
            'role' => $_SESSION['user_role'] ?? 'staff',
        ];
    }

    public static function requireAdmin(): array {
        $user = self::handle();
        if ($user['role'] !== 'admin') {
            jsonResponse(403, ['error' => 'Forbidden. Admin access required.']);
        }
        return $user;
    }
}
