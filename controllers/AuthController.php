<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/helpers.php';

class AuthController {
    private User $user;

    public function __construct() {
        $this->user = new User();
    }

    public function register(): void {
        $data = getRequestBody();
        $err  = requireFields($data, ['name', 'email', 'password']);
        if ($err) jsonResponse(422, ['error' => $err]);

        $name     = sanitize($data['name']);
        $email    = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        $password = $data['password'];
        $role     = in_array($data['role'] ?? '', ['admin', 'staff']) ? $data['role'] : 'staff';

        if (!$email) jsonResponse(422, ['error' => 'Invalid email address.']);
        if (strlen($password) < 6) jsonResponse(422, ['error' => 'Password must be at least 6 characters.']);
        if ($this->user->findByEmail($email)) jsonResponse(409, ['error' => 'Email already registered.']);

        $id = $this->user->create($name, $email, $password, $role);
        jsonResponse(201, ['message' => 'User registered successfully.', 'user_id' => $id]);
    }

    public function login(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = getRequestBody();
        $err  = requireFields($data, ['email', 'password']);
        if ($err) jsonResponse(422, ['error' => $err]);

        $email    = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        $password = $data['password'];

        if (!$email) jsonResponse(422, ['error' => 'Invalid email address.']);

        $user = $this->user->findByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(401, ['error' => 'Invalid credentials.']);
        }

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];

        jsonResponse(200, [
            'message' => 'Login successful.',
            'user'    => ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']],
        ]);
    }

    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
        jsonResponse(200, ['message' => 'Logged out successfully.']);
    }
}
