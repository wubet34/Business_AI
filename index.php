<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global exception handler — always return JSON, never HTML
set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
});

set_error_handler(function (int $errno, string $errstr) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $errstr]);
    exit;
});

// CORS headers (adjust origin for production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');

require_once __DIR__ . '/routes/api.php';
