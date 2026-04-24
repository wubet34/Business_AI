<?php
require_once __DIR__ . '/../config/database.php';

class Message {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(int $customerId, string $message, string $aiReply): int {
        $stmt = $this->db->prepare(
            "INSERT INTO messages (customer_id, message, ai_reply) VALUES (:cid, :msg, :reply) RETURNING id"
        );
        $stmt->execute([':cid' => $customerId, ':msg' => $message, ':reply' => $aiReply]);
        return (int)$stmt->fetchColumn();
    }

    public function getByCustomer(int $customerId): array {
        $stmt = $this->db->prepare("SELECT * FROM messages WHERE customer_id = :cid ORDER BY created_at DESC");
        $stmt->execute([':cid' => $customerId]);
        return $stmt->fetchAll();
    }
}
