<?php
require_once 'config.php';

class Database {
    private $pdo;
    
    public function __construct() {
        try {
            // Build DSN with SSL mode
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=" . DB_SSLMODE;
            
            // Set connection options
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            // For Neon PostgreSQL, we might need additional SSL options
            if (getenv('DATABASE_URL') && strpos(getenv('DATABASE_URL'), 'neon') !== false) {
                // Neon PostgreSQL uses SSL by default, no additional SSL options needed
                // The sslmode=require in DSN is sufficient
            }
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}
?>
