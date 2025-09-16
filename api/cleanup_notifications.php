<?php
/**
 * Notification Cleanup API
 * Automatically deletes notifications older than 3 days
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Delete notifications older than 3 days
    $stmt = $db->prepare("
        DELETE FROM notifications 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 3 DAY)
    ");
    
    $stmt->execute();
    $deleted_count = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => "Cleanup completed successfully",
        'data' => [
            'deleted_notifications' => $deleted_count,
            'cleanup_date' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

