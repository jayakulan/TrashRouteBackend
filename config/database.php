<?php
class Database {
    private $host = "localhost";
    private $db_name = "trashroute";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=3306;dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Set timezone to UTC for consistency
            $this->conn->exec("SET time_zone = '+00:00'");
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            // Don't echo here to avoid HTML output in API responses
        }

        return $this->conn;
    }
}

// Create a global PDO connection for backward compatibility
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    // Don't echo here to avoid HTML output in API responses
}
?> 