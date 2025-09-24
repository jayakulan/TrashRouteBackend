<?php
// Trashroutefinal/TrashRouteBackend/Customer/cancelRequest.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5175');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../utils/helpers.php';
require_once '../utils/session_auth_middleware.php';
require_once '../classes/PickupRequest.php';

// Check customer authentication
try {
    $customerUser = SessionAuthMiddleware::requireCustomerAuth();
    $customer_id = $customerUser['user_id'];
    error_log("Customer authenticated successfully: " . $customer_id);
} catch (Exception $e) {
    error_log("Customer authentication failed: " . $e->getMessage());
    Helpers::sendError('Customer authentication failed: ' . $e->getMessage(), 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::sendError('Method not allowed', 405);
}

try {
    // Debug: Log request details
    error_log("Cancel request - Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Cancel request - Headers: " . print_r(getallheaders(), true));
    
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        Helpers::sendError('Database connection failed', 500);
    }

    // Get POST data
    $rawInput = file_get_contents('php://input');
    error_log("Cancel request - Raw input: " . $rawInput);
    $input = json_decode($rawInput, true);
    
    if (!isset($input['request_id'])) {
        Helpers::sendError('Request ID is required', 400);
    }

    $request_id = (int)$input['request_id'];

    if ($request_id <= 0) {
        Helpers::sendError('Invalid request ID', 400);
    }

    // Initialize PickupRequest class
    $pickupRequest = new PickupRequest($db);

    // Cancel the request
    $result = $pickupRequest->cancelRequest($request_id, $customer_id);

    if (!$result['success']) {
        Helpers::sendError($result['message'], 400);
    }

    Helpers::sendResponse($result['data'], 200, $result['message']);

} catch (Exception $e) {
    Helpers::sendError('Failed to cancel request: ' . $e->getMessage(), 500);
}
?>
