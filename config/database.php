<?php
class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                // Option 1: single DATABASE_URL env var (easiest for Render)
                $url = getenv('DATABASE_URL');
                if ($url) {
                    $parts  = parse_url($url);
                    $host   = $parts['host'];
                    $port   = $parts['port']   ?? 5432;
                    $dbname = ltrim($parts['path'], '/');
                    $user   = $parts['user'];
                    $pass   = $parts['pass'];
                } else {
                    // Option 2: individual env vars (PGHOST etc. from Supabase/Render)
                    // Falls back to XAMPP localhost defaults
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
