<?php
require_once __DIR__ . '/../config/database.php';

class Product {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(int $limit, int $offset, string $search = ''): array {
        $sql = "SELECT * FROM products";
        $params = [];
        if ($search !== '') {
            $sql .= " WHERE name ILIKE :search";
            $params[':search'] = '%' . $search . '%';
        }
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $name, float $price, int $stock): int {
        $stmt = $this->db->prepare(
            "INSERT INTO products (name, price, stock_quantity) VALUES (:name, :price, :stock) RETURNING id"
        );
        $stmt->execute([':name' => $name, ':price' => $price, ':stock' => $stock]);
        return (int)$stmt->fetchColumn();
    }

    public function update(int $id, string $name, float $price, int $stock): bool {
        $stmt = $this->db->prepare(
            "UPDATE products SET name=:name, price=:price, stock_quantity=:stock WHERE id=:id"
        );
        return $stmt->execute([':name' => $name, ':price' => $price, ':stock' => $stock, ':id' => $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function reduceStock(int $id, int $qty): bool {
        $stmt = $this->db->prepare(
            "UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :id AND stock_quantity >= :qty"
        );
        $stmt->execute([':qty' => $qty, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function getLowStock(int $threshold = 5): array {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE stock_quantity <= :threshold ORDER BY stock_quantity ASC");
        $stmt->execute([':threshold' => $threshold]);
        return $stmt->fetchAll();
    }

    public function getTopSelling(int $limit = 5): array {
        $stmt = $this->db->prepare("
            SELECT p.id, p.name, SUM(si.quantity) AS total_sold
            FROM sale_items si
            JOIN products p ON p.id = si.product_id
            GROUP BY p.id, p.name
            ORDER BY total_sold DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
