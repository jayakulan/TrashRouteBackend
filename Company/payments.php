<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require_once '../config/database.php';
require_once '../utils/session_auth_middleware.php';
require_once '../utils/helpers.php';
require_once '../classes/Payment.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check company authentication
try {
    $companyUser = SessionAuthMiddleware::requireCompanyAuth();
    // Debug: Log successful authentication
    error_log("Company authentication successful for user: " . $companyUser['user_id']);
} catch (Exception $e) {
    error_log("Company authentication failed: " . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication failed',
        'message' => $e->getMessage()
    ]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    // Make DB connection available to helper methods that expect a global
    $GLOBALS['db'] = $db;
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Get POST data
    $company_id = $_POST['company_id'] ?? null;
    $card_number = $_POST['card_number'] ?? null;
    $cardholder_name = $_POST['cardholder_name'] ?? null;
    $expiry_date = $_POST['expiry_date'] ?? null;
    $pin_number = $_POST['pin_number'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $waste_type = $_POST['waste_type'] ?? null;

    // Initialize Payment class
    $payment = new Payment($db);

    // Process payment using the class method
    $result = $payment->paymentMethod($company_id, $card_number, $cardholder_name, $expiry_date, $pin_number, $amount, $waste_type);

    if (!$result['success']) {
        echo json_encode($result);
        exit;
    }

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
} 
?> 