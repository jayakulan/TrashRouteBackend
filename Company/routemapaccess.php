<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once '../config/database.php';
require_once '../utils/session_auth_middleware.php';
require_once '../classes/RouteRequestMapping.php';

// Check company authentication
SessionAuthMiddleware::requireCompanyAuth();

try {
    // Get parameters from request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['company_id'])) {
        throw new Exception("Company ID is required");
    }
    
    $company_id = intval($input['company_id']);
    $waste_type = isset($input['waste_type']) ? $input['waste_type'] : null;
    
    if ($company_id <= 0) {
        throw new Exception("Invalid company ID");
    }

    // Create database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }

    // Initialize RouteRequestMapping class
    $routeRequestMapping = new RouteRequestMapping($pdo);

    // Get mapped requests using the class method
    $result = $routeRequestMapping->getMappedRequests($company_id, $waste_type);

    if (!$result['success']) {
        throw new Exception($result['message']);
    }

    // Return success response
    echo json_encode($result);

} catch (PDOException $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'details' => 'PDO Exception occurred',
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage(),
        'details' => 'General Exception occurred',
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?> 