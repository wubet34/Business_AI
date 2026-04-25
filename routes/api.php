<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../controllers/SaleController.php';
require_once __DIR__ . '/../controllers/AIController.php';

$requestUri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Auto-detect base path: works on XAMPP (/Business-Ai/api) and Railway (/api)
$possibleBases = ['/Business-Ai/api', '/api'];
$basePath = '/api';
foreach ($possibleBases as $base) {
    if (str_starts_with($requestUri, $base)) {
        $basePath = $base;
        break;
    }
}
$path = '/' . ltrim(substr($requestUri, strlen($basePath)), '/');

// Remove trailing slash except root
$path = $path !== '/' ? rtrim($path, '/') : $path;

function matchRoute(string $method, string $pattern, string $reqMethod, string $path, ?int &$id = null): bool {
    if ($method !== $reqMethod) return false;
    $regex = preg_replace('#/:id#', '/(\d+)', '#^' . $pattern . '$#');
    if (preg_match($regex, $path, $matches)) {
        if (isset($matches[1])) $id = (int)$matches[1];
        return true;
    }
    return false;
}

$id = null;

// ── Auth ──────────────────────────────────────────────────────────────────
if (matchRoute('POST', '/register', $requestMethod, $path)) {
    (new AuthController())->register();
}
if (matchRoute('POST', '/login', $requestMethod, $path)) {
    (new AuthController())->login();
}
if (matchRoute('POST', '/logout', $requestMethod, $path)) {
    (new AuthController())->logout();
}

// ── Customers ─────────────────────────────────────────────────────────────
if (matchRoute('GET',    '/customers',     $requestMethod, $path)) {
    (new CustomerController())->index();
}
if (matchRoute('POST',   '/customers',     $requestMethod, $path)) {
    (new CustomerController())->store();
}
if (matchRoute('PUT',    '/customers/:id', $requestMethod, $path, $id)) {
    (new CustomerController())->update($id);
}
if (matchRoute('DELETE', '/customers/:id', $requestMethod, $path, $id)) {
    (new CustomerController())->destroy($id);
}

// ── Products ──────────────────────────────────────────────────────────────
if (matchRoute('GET',    '/products',     $requestMethod, $path)) {
    (new ProductController())->index();
}
if (matchRoute('POST',   '/products',     $requestMethod, $path)) {
    (new ProductController())->store();
}
if (matchRoute('PUT',    '/products/:id', $requestMethod, $path, $id)) {
    (new ProductController())->update($id);
}
if (matchRoute('DELETE', '/products/:id', $requestMethod, $path, $id)) {
    (new ProductController())->destroy($id);
}

// ── Sales (export before :id to avoid conflict) ───────────────────────────
if (matchRoute('GET',  '/sales/export', $requestMethod, $path)) {
    (new SaleController())->export();
}
if (matchRoute('GET',  '/sales',        $requestMethod, $path)) {
    (new SaleController())->index();
}
if (matchRoute('GET',  '/sales/:id',    $requestMethod, $path, $id)) {
    (new SaleController())->show($id);
}
if (matchRoute('POST', '/sales',        $requestMethod, $path)) {
    (new SaleController())->store();
}

// ── AI ────────────────────────────────────────────────────────────────────
if (matchRoute('POST', '/ai/reply',    $requestMethod, $path)) {
    (new AIController())->reply();
}
if (matchRoute('GET',  '/ai/insights', $requestMethod, $path)) {
    (new AIController())->insights();
}
if (matchRoute('GET',  '/ai/report',   $requestMethod, $path)) {
    (new AIController())->report();
}

// ── Health check ─────────────────────────────────────────────────────────
if (matchRoute('GET', '/health', $requestMethod, $path)) {
    jsonResponse(200, ['status' => 'ok']);
}

// ── DB test ───────────────────────────────────────────────────────────────
if (matchRoute('GET', '/dbtest', $requestMethod, $path)) {
    try {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT version()");
        jsonResponse(200, ['db' => 'connected', 'version' => $stmt->fetchColumn()]);
    } catch (Exception $e) {
        jsonResponse(500, ['db' => 'failed', 'error' => $e->getMessage()]);
    }
}

// ── 404 ───────────────────────────────────────────────────────────────────
jsonResponse(404, ['error' => 'Endpoint not found.', 'path' => $path, 'method' => $requestMethod]);
