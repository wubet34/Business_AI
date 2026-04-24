<?php
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/helpers.php';

class CustomerController {
    private Customer $customer;

    public function __construct() {
        $this->customer = new Customer();
    }

    public function index(): void {
        AuthMiddleware::handle();
        ['limit' => $limit, 'offset' => $offset] = getPagination();
        $search    = sanitize($_GET['search'] ?? '');
        $customers = $this->customer->getAll($limit, $offset, $search);
        jsonResponse(200, ['data' => $customers, 'limit' => $limit, 'offset' => $offset]);
    }

    public function store(): void {
        AuthMiddleware::handle();
        $data = getRequestBody();
        $err  = requireFields($data, ['name']);
        if ($err) jsonResponse(422, ['error' => $err]);

        $name  = sanitize($data['name']);
        $phone = sanitize($data['phone'] ?? '');
        $email = sanitize($data['email'] ?? '');
        $notes = sanitize($data['notes'] ?? '');

        $id = $this->customer->create($name, $phone, $email, $notes);
        jsonResponse(201, ['message' => 'Customer created.', 'id' => $id]);
    }

    public function update(int $id): void {
        AuthMiddleware::handle();
        $data = getRequestBody();
        $err  = requireFields($data, ['name']);
        if ($err) jsonResponse(422, ['error' => $err]);

        if (!$this->customer->findById($id)) jsonResponse(404, ['error' => 'Customer not found.']);

        $this->customer->update(
            $id,
            sanitize($data['name']),
            sanitize($data['phone'] ?? ''),
            sanitize($data['email'] ?? ''),
            sanitize($data['notes'] ?? '')
        );
        jsonResponse(200, ['message' => 'Customer updated.']);
    }

    public function destroy(int $id): void {
        AuthMiddleware::requireAdmin();
        if (!$this->customer->findById($id)) jsonResponse(404, ['error' => 'Customer not found.']);
        try {
            $this->customer->delete($id);
            jsonResponse(200, ['message' => 'Customer deleted.']);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), '23503') || str_contains($e->getMessage(), 'foreign key')) {
                jsonResponse(409, ['error' => 'Cannot delete customer — they are referenced in existing sales.']);
            }
            jsonResponse(500, ['error' => 'Failed to delete customer.']);
        }
    }
}
