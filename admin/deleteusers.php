<?php
// Set CORS headers first, before any output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5175');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // 24 hours
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Handle preflight OPTIONS request immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Allow POST method for delete operations
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Only POST requests are accepted.',
        'method' => $_SERVER['REQUEST_METHOD']
    ]);
    exit();
}

// Check if this is a delete action via query parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action !== 'delete') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid action. Query parameter "action" must be set to "delete".',
        'action' => $action
    ]);
    exit();
}

// Include database configuration
require_once '../config/database.php';
require_once '../utils/session_auth_middleware.php';
require_once '../classes/Admin.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enhanced admin authentication with multiple security checks
try {
    $adminUser = SessionAuthMiddleware::requireAdminAuth();
    
    // Additional security: Check for session timeout
    if (!SessionAuthMiddleware::isAdminAuthenticated()) {
        throw new Exception('Session expired');
    }
    
    // Refresh session to extend timeout
    SessionAuthMiddleware::refreshSession();
    
} catch (Exception $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied',
        'message' => $e->getMessage()
    ]);
    exit();
}

try {
    // Get the customer ID from the request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['customerId'])) {
        throw new Exception("Customer ID is required");
    }
    
    // Extract the numeric ID from the formatted ID (e.g., "#001" -> "1")
    $formattedId = $input['customerId'];
    $customerId = intval(str_replace('#', '', $formattedId));
    
    if ($customerId <= 0) {
        throw new Exception("Invalid customer ID format");
    }

    // Create database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Instead of deleting, disable the customer
        $result = Admin::disableCustomer($pdo, $customerId);
        if ($result) {
            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Customer status set to disabled',
                'disabledCustomerId' => $formattedId
            ]);
        } else {
            $pdo->rollBack();
            throw new Exception("Failed to disable customer or customer not found");
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        throw $e;
    }

} catch (PDOException $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'details' => 'PDO Exception occurred'
    ]);
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage(),
        'details' => 'General Exception occurred'
    ]);
}
?> 