<?php
require_once __DIR__ . '/../config/database.php';

class Customer {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(int $limit, int $offset, string $search = ''): array {
        $sql = "SELECT * FROM customers";
        $params = [];
        if ($search !== '') {
            $sql .= " WHERE name ILIKE :search OR email ILIKE :search OR phone ILIKE :search";
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
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $name, string $phone, string $email, string $notes): int {
        $stmt = $this->db->prepare(
            "INSERT INTO customers (name, phone, email, notes) VALUES (:name, :phone, :email, :notes) RETURNING id"
        );
        $stmt->execute([':name' => $name, ':phone' => $phone, ':email' => $email, ':notes' => $notes]);
        return (int)$stmt->fetchColumn();
    }

    public function update(int $id, string $name, string $phone, string $email, string $notes): bool {
        $stmt = $this->db->prepare(
            "UPDATE customers SET name=:name, phone=:phone, email=:email, notes=:notes WHERE id=:id"
        );
        return $stmt->execute([':name' => $name, ':phone' => $phone, ':email' => $email, ':notes' => $notes, ':id' => $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM customers WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
