<?php
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../config/database.php';

class SaleController {
    private Sale    $sale;
    private Product $product;

    public function __construct() {
        $this->sale    = new Sale();
        $this->product = new Product();
    }

    public function index(): void {
        AuthMiddleware::handle();
        ['limit' => $limit, 'offset' => $offset] = getPagination();
        $from  = sanitize($_GET['from'] ?? '');
        $to    = sanitize($_GET['to']   ?? '');
        $sales = $this->sale->getAll($limit, $offset, $from, $to);
        jsonResponse(200, ['data' => $sales, 'limit' => $limit, 'offset' => $offset]);
    }

    public function show(int $id): void {
        AuthMiddleware::handle();
        $sale = $this->sale->findById($id);
        if (!$sale) jsonResponse(404, ['error' => 'Sale not found.']);
        jsonResponse(200, ['data' => $sale]);
    }

    public function store(): void {
        AuthMiddleware::handle();
        $data = getRequestBody();
        $err  = requireFields($data, ['customer_id', 'items']);
        if ($err) jsonResponse(422, ['error' => $err]);

        $customerId = (int)$data['customer_id'];
        $items      = $data['items'];

        if (!is_array($items) || count($items) === 0) {
            jsonResponse(422, ['error' => 'At least one item is required.']);
        }

        // Validate items and check stock before touching DB
        $resolved = [];
        foreach ($items as $item) {
            if (empty($item['product_id']) || empty($item['quantity'])) {
                jsonResponse(422, ['error' => 'Each item needs product_id and quantity.']);
            }
            $productId = (int)$item['product_id'];
            $qty       = (int)$item['quantity'];
            if ($qty <= 0) jsonResponse(422, ['error' => 'Quantity must be positive.']);

            $product = $this->product->findById($productId);
            if (!$product) jsonResponse(404, ['error' => "Product ID $productId not found."]);
            if ($product['stock_quantity'] < $qty) {
                jsonResponse(409, ['error' => "Insufficient stock for '{$product['name']}'. Available: {$product['stock_quantity']}"]);
            }
            $resolved[] = ['product' => $product, 'qty' => $qty];
        }

        // Calculate total
        $total = 0.0;
        foreach ($resolved as $r) {
            $total += (float)$r['product']['price'] * $r['qty'];
        }

        // Use transaction for atomicity
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $saleId = $this->sale->create($customerId, $total);
            foreach ($resolved as $r) {
                $this->sale->addItem($saleId, (int)$r['product']['id'], $r['qty'], (float)$r['product']['price']);
                $this->product->reduceStock((int)$r['product']['id'], $r['qty']);
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(500, ['error' => 'Sale creation failed. ' . $e->getMessage()]);
        }

        jsonResponse(201, ['message' => 'Sale created.', 'sale_id' => $saleId, 'total' => $total]);
    }

    public function export(): void {
        AuthMiddleware::handle();
        $from  = sanitize($_GET['from'] ?? '');
        $to    = sanitize($_GET['to']   ?? '');
        $sales = $this->sale->getAllForExport($from, $to);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sales_export.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Customer', 'Total Amount', 'Date']);
        foreach ($sales as $row) {
            fputcsv($out, [$row['id'], $row['customer'], $row['total_amount'], $row['created_at']]);
        }
        fclose($out);
        exit;
    }
}
