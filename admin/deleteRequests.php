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

// Admin JWT authentication
try {
    $adminUser = SessionAuthMiddleware::requireAdminJWTAuth();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied',
        'message' => $e->getMessage()
    ]);
    exit();
}

try {
    // Get the request ID from the request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['requestId'])) {
        throw new Exception("Request ID is required");
    }
    
    // Extract the numeric ID from the formatted ID (e.g., "#001" -> "1")
    $formattedId = $input['requestId'];
    $requestId = intval(str_replace('#', '', $formattedId));
    
    if ($requestId <= 0) {
        throw new Exception("Invalid request ID format");
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
        // Delete the pickup request
        $result = Admin::deletePickupRequest($pdo, $requestId);
        if ($result) {
            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Pickup request deleted successfully',
                'deletedRequestId' => $formattedId
            ]);
        } else {
            $pdo->rollBack();
            throw new Exception("Failed to delete pickup request or request not found");
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