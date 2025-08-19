<?php
// Customer notifications endpoint
// Accepts POST JSON: { user_id, message, request_id?, company_id?, customer_id? }

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../utils/helpers.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    // Make DB connection available to helper methods that expect a global
    $GLOBALS['db'] = $db;
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new Exception('Invalid JSON');
    }

    $user_id    = isset($data['user_id']) ? (int)$data['user_id'] : null;
    $message    = isset($data['message']) ? trim($data['message']) : '';
    $request_id = isset($data['request_id']) ? (int)$data['request_id'] : null;
    $company_id = isset($data['company_id']) ? (int)$data['company_id'] : null;
    $customer_id= isset($data['customer_id']) ? (int)$data['customer_id'] : null;

    if (!$user_id || $message === '') {
        throw new Exception('user_id and message are required');
    }

    // Insert notification
    if (!Helpers::createNotification($user_id, $message, $request_id, $company_id, $customer_id)) {
        throw new Exception('Failed to create notification');
    }

    echo json_encode(['success' => true, 'message' => 'Notification stored']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

