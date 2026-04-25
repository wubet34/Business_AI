<?php
class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $url = getenv('DATABASE_URL');
                if ($url) {
                    // parse_url breaks on usernames with dots — use regex instead
                    preg_match('#^postgresql://([^:]+):([^@]+)@([^:]+):(\d+)/(.+)$#', $url, $m);
                    $user   = $m[1];
                    $pass   = $m[2];
                    $host   = $m[3];
                    $port   = $m[4];
                    $dbname = $m[5];
                } else {
                    $host   = getenv('PGHOST')     ?: 'localhost';
                    $port   = getenv('PGPORT')     ?: '5432';
                    $dbname = getenv('PGDATABASE') ?: 'sbms_db';
                    $user   = getenv('PGUSER')     ?: 'postgres';
                    $pass   = getenv('PGPASSWORD') ?: 'root';
                }

                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
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
