<?php
// Send a JSON response and exit
function jsonResponse(int $status, array $data): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Get decoded JSON body from request
function getRequestBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// Sanitize a string value
function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)));
}

// Validate required fields exist and are non-empty
function requireFields(array $data, array $fields): ?string {
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            return "Field '$field' is required.";
        }
    }
    return null;
}

// Paginate helper: returns limit/offset from query params
function getPagination(): array {
    $limit  = isset($_GET['limit'])  ? max(1, (int)$_GET['limit'])  : 20;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
    return ['limit' => $limit, 'offset' => $offset];
}
