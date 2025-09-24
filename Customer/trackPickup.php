<?php
if (session_status() === PHP_SESSION_NONE) {
    // Configure session for cross-origin requests
    ini_set('session.cookie_samesite', 'None');
    ini_set('session.cookie_secure', '0'); // Set to 1 in production with HTTPS
    ini_set('session.cookie_httponly', '1');
    session_start();
}

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

// Debug: Log request details
error_log("trackPickup.php - Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
error_log("trackPickup.php - Session ID: " . session_id());
error_log("trackPickup.php - Session data: " . json_encode($_SESSION));

// Check customer authentication
try {
    $customerUser = SessionAuthMiddleware::requireCustomerAuth();
    $customer_id = $customerUser['user_id'];
    error_log("trackPickup.php - Customer authenticated: " . $customer_id);
} catch (Exception $e) {
    error_log("trackPickup.php - Authentication failed: " . $e->getMessage());
    Helpers::sendError('Customer authentication failed: ' . $e->getMessage(), 401);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        Helpers::sendError('Database connection failed', 500);
    }

    // Debug: Log the customer_id being used
    error_log("trackPickup.php - Using customer_id: " . $customer_id);

    // Initialize PickupRequest class
    $pickupRequest = new PickupRequest($db);

    // Get tracking data using the class method
    $result = $pickupRequest->trackRequest($customer_id);

    if (!$result['success']) {
        Helpers::sendError($result['message'], 500);
    }

    // Debug: Log the result
    error_log("trackPickup.php - Result: " . json_encode($result));

    echo json_encode($result);

} catch (Exception $e) {
    error_log("Error in trackPickup.php: " . $e->getMessage());
    Helpers::sendError('Failed to fetch pickup tracking data: ' . $e->getMessage(), 500);
}
?>
