<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/helpers.php';

class ProductController {
    private Product $product;

    public function __construct() {
        $this->product = new Product();
    }

    public function index(): void {
        AuthMiddleware::handle();
        ['limit' => $limit, 'offset' => $offset] = getPagination();
        $search   = sanitize($_GET['search'] ?? '');
        $products = $this->product->getAll($limit, $offset, $search);
        jsonResponse(200, ['data' => $products, 'limit' => $limit, 'offset' => $offset]);
    }

    public function store(): void {
        AuthMiddleware::handle();
        $data = getRequestBody();
        $err  = requireFields($data, ['name', 'price', 'stock_quantity']);
        if ($err) jsonResponse(422, ['error' => $err]);

        $name  = sanitize($data['name']);
        $price = filter_var($data['price'], FILTER_VALIDATE_FLOAT);
        $stock = filter_var($data['stock_quantity'], FILTER_VALIDATE_INT);

        if ($price === false || $price < 0) jsonResponse(422, ['error' => 'Invalid price.']);
        if ($stock === false || $stock < 0) jsonResponse(422, ['error' => 'Invalid stock quantity.']);

        $id = $this->product->create($name, $price, $stock);
        jsonResponse(201, ['message' => 'Product created.', 'id' => $id]);
    }

    public function update(int $id): void {
        AuthMiddleware::handle();
        $data = getRequestBody();
        $err  = requireFields($data, ['name', 'price', 'stock_quantity']);
        if ($err) jsonResponse(422, ['error' => $err]);

        if (!$this->product->findById($id)) jsonResponse(404, ['error' => 'Product not found.']);

        $price = filter_var($data['price'], FILTER_VALIDATE_FLOAT);
        $stock = filter_var($data['stock_quantity'], FILTER_VALIDATE_INT);
        if ($price === false || $price < 0) jsonResponse(422, ['error' => 'Invalid price.']);
        if ($stock === false || $stock < 0) jsonResponse(422, ['error' => 'Invalid stock quantity.']);

        $this->product->update($id, sanitize($data['name']), $price, $stock);
        jsonResponse(200, ['message' => 'Product updated.']);
    }

    public function destroy(int $id): void {
        AuthMiddleware::requireAdmin();
        if (!$this->product->findById($id)) jsonResponse(404, ['error' => 'Product not found.']);
        try {
            $this->product->delete($id);
            jsonResponse(200, ['message' => 'Product deleted.']);
        } catch (PDOException $e) {
            // FK violation — product is used in sales
            if (str_contains($e->getMessage(), '23503') || str_contains($e->getMessage(), 'foreign key')) {
                jsonResponse(409, ['error' => 'Cannot delete product — it is referenced in existing sales.']);
            }
            jsonResponse(500, ['error' => 'Failed to delete product.']);
        }
    }
}
