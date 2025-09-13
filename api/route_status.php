<?php
/**
 * Route Status API
 * Check if a route is completed and notifications should be dismissed
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/session_auth_middleware.php';

// Check company authentication
SessionAuthMiddleware::requireCompanyAuth();

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Get company_id from session
    $company_id = $_SESSION['user_id'] ?? null;
    if (!$company_id) {
        throw new Exception('Company not authenticated');
    }
    
    // Get route_id from query parameter
    $route_id = isset($_GET['route_id']) ? intval($_GET['route_id']) : null;
    
    if (!$route_id) {
        throw new Exception('route_id is required');
    }
    
    // Check route status
    $stmt = $db->prepare("
        SELECT 
            r.route_id,
            r.company_id,
            r.is_accepted,
            r.is_disabled,
            r.no_of_customers,
            r.route_details,
            r.generated_at,
            COUNT(rrm.request_id) as total_requests,
            SUM(CASE WHEN cf.pickup_completed = 1 THEN 1 ELSE 0 END) as completed_requests,
            COUNT(n.notification_id) as active_notifications
        FROM routes r
        LEFT JOIN route_request_mapping rrm ON r.route_id = rrm.route_id
        LEFT JOIN company_feedback cf ON rrm.request_id = cf.request_id AND cf.company_id = r.company_id
        LEFT JOIN notifications n ON rrm.request_id = n.request_id 
            AND n.company_id = r.company_id 
            AND n.dismissed_at IS NULL
        WHERE r.route_id = :route_id AND r.company_id = :company_id
        GROUP BY r.route_id
    ");
    $stmt->bindParam(':route_id', $route_id);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    $route = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$route) {
        throw new Exception('Route not found or does not belong to company');
    }
    
    // Calculate completion percentage
    $completion_percentage = 0;
    if ($route['total_requests'] > 0) {
        $completion_percentage = round(($route['completed_requests'] / $route['total_requests']) * 100, 2);
    }
    
    // Determine status
    $status = 'pending';
    if ($route['is_disabled']) {
        $status = 'completed';
    } elseif ($route['is_accepted']) {
        $status = 'active';
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'route_id' => $route['route_id'],
            'company_id' => $route['company_id'],
            'status' => $status,
            'is_accepted' => (bool)$route['is_accepted'],
            'is_completed' => (bool)$route['is_disabled'],
            'total_requests' => intval($route['total_requests']),
            'completed_requests' => intval($route['completed_requests']),
            'completion_percentage' => $completion_percentage,
            'active_notifications' => intval($route['active_notifications']),
            'no_of_customers' => intval($route['no_of_customers']),
            'route_details' => $route['route_details'],
            'generated_at' => $route['generated_at']
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
