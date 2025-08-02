<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5175');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
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
require_once '../classes/Admin.php';

// Admin JWT authentication using integrated middleware
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
    // Create database connection using the Database class
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }

    // First, let's check if the routes table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'routes'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("Table 'routes' does not exist");
    }

    // Check if there are any routes
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM routes");
    $routeCount = $stmt->fetch()['count'];

    if ($routeCount == 0) {
        // Return empty array if no routes
        echo json_encode([
            'success' => true,
            'data' => [],
            'count' => 0,
            'message' => 'No routes found in database'
        ]);
        exit();
    }

    // Fetch all routes using Admin class method
    $routes = Admin::getAllRoutes($pdo);

    // Format the response
    $formattedRoutes = [];
    foreach ($routes as $route) {
        // Convert acceptance status
        $acceptanceStatus = $route['is_accepted'] == 1 ? 'Accepted' : 
                          ($route['is_accepted'] == 0 ? 'Pending' : 'Rejected');
        
        // Convert disabled status
        $disabledStatus = $route['is_disabled'] == 1 ? 'Disabled' : 'Enabled';
        
        $formattedRoutes[] = [
            'route_id' => $route['route_id'],
            'company_id' => $route['company_id'],
            'company_name' => $route['company_name'] ?: 'Unknown Company',
            'no_of_customers' => $route['no_of_customers'],
            'is_accepted' => $acceptanceStatus,
            'generated_at' => date('Y-m-d', strtotime($route['generated_at'])),
            'is_disabled' => $disabledStatus,
            'route_details' => $route['route_details'] ?: 'No details available'
        ];
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $formattedRoutes,
        'count' => count($formattedRoutes)
    ]);

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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'details' => 'General Exception occurred'
    ]);
}
?> 