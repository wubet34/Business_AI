<?php
require_once __DIR__ . '/../config/database.php';

class Sale {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(int $limit, int $offset, string $dateFrom = '', string $dateTo = ''): array {
        $sql = "SELECT s.*, c.name AS customer_name FROM sales s LEFT JOIN customers c ON c.id = s.customer_id WHERE 1=1";
        $params = [];
        if ($dateFrom) { $sql .= " AND s.created_at >= :from"; $params[':from'] = $dateFrom; }
        if ($dateTo)   { $sql .= " AND s.created_at <= :to";   $params[':to']   = $dateTo; }
        $sql .= " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT s.*, c.name AS customer_name FROM sales s
            LEFT JOIN customers c ON c.id = s.customer_id
            WHERE s.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $sale = $stmt->fetch();
        if (!$sale) return null;
        $sale['items'] = $this->getItems($id);
        return $sale;
    }

    public function getItems(int $saleId): array {
        $stmt = $this->db->prepare("
            SELECT si.*, p.name AS product_name FROM sale_items si
            JOIN products p ON p.id = si.product_id
            WHERE si.sale_id = :sale_id
        ");
        $stmt->execute([':sale_id' => $saleId]);
        return $stmt->fetchAll();
    }

    public function create(int $customerId, float $total): int {
        $stmt = $this->db->prepare(
            "INSERT INTO sales (customer_id, total_amount) VALUES (:customer_id, :total) RETURNING id"
        );
        $stmt->execute([':customer_id' => $customerId, ':total' => $total]);
        return (int)$stmt->fetchColumn();
    }

    public function addItem(int $saleId, int $productId, int $qty, float $price): void {
        $stmt = $this->db->prepare(
            "INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (:sale_id, :product_id, :qty, :price)"
        );
        $stmt->execute([':sale_id' => $saleId, ':product_id' => $productId, ':qty' => $qty, ':price' => $price]);
    }

    public function getTotalRevenue(): float {
        $stmt = $this->db->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales");
        return (float)$stmt->fetchColumn();
    }

    public function getTodayRevenue(): float {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE created_at::date = CURRENT_DATE");
        $stmt->execute();
        return (float)$stmt->fetchColumn();
    }

    public function getTodayCount(): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM sales WHERE created_at::date = CURRENT_DATE");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getAllForExport(string $dateFrom = '', string $dateTo = ''): array {
        $sql = "SELECT s.id, c.name AS customer, s.total_amount, s.created_at FROM sales s LEFT JOIN customers c ON c.id = s.customer_id WHERE 1=1";
        $params = [];
        if ($dateFrom) { $sql .= " AND s.created_at >= :from"; $params[':from'] = $dateFrom; }
        if ($dateTo)   { $sql .= " AND s.created_at <= :to";   $params[':to']   = $dateTo; }
        $sql .= " ORDER BY s.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
