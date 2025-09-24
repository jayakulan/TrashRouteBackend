<?php
// Disable all error reporting and output buffering
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

session_start();

// CORS headers
$allowed_origins = ['http://localhost:5173', 'http://localhost:5175'];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
} else {
    header("Access-Control-Allow-Origin: http://localhost:5173");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../utils/helpers.php';
require_once '../utils/session_auth_middleware.php';
require_once '../classes/PickupRequest.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Helpers::sendError('Method not allowed', 405);
}

// Check customer authentication (session + JWT fallback)
try {
    $customerUser = SessionAuthMiddleware::requireCustomerAuth();
    $customer_id = $customerUser['user_id'];
} catch (Exception $e) {
    Helpers::sendError('Customer authentication failed: ' . $e->getMessage(), 401);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        Helpers::sendError('Database connection failed', 500);
    }

    // Initialize PickupRequest class
    $pickupRequest = new PickupRequest($db);

    // Get pickup summary using the class method
    $result = $pickupRequest->getPickupSummary($customer_id);

    if (!$result['success']) {
        Helpers::sendError($result['message'], 404);
    }

    Helpers::sendResponse($result['data'], 200, $result['message']);

} catch (Exception $e) {
    Helpers::sendError('Failed to retrieve pickup summary: ' . $e->getMessage(), 500);
}

// Clean up any output buffer and ensure only JSON is sent
ob_end_clean();
?> 