<?php
class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            // Supports Railway (PGHOST etc.) and custom env vars (DB_HOST etc.)
            // Falls back to XAMPP localhost defaults
            $host   = getenv('PGHOST')     ?: getenv('DB_HOST')     ?: 'localhost';
            $port   = getenv('PGPORT')     ?: getenv('DB_PORT')     ?: '5432';
            $dbname = getenv('PGDATABASE') ?: getenv('DB_NAME')     ?: 'sbms_db';
            $user   = getenv('PGUSER')     ?: getenv('DB_USER')     ?: 'postgres';
            $pass   = getenv('PGPASSWORD') ?: getenv('DB_PASSWORD') ?: 'root';

            try {
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Database connection failed.', 'detail' => $e->getMessage()]);
                exit;
            }
        }
        return self::$instance;
    }
}
