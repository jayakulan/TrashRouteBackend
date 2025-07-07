<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Include database configuration
require_once '../config/database.php';

try {
    // Test database connection
    $stmt = $pdo->query("SELECT 1");
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
}
?> 