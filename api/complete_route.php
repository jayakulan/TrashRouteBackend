<?php
/**
 * Route Completion API
 * Handles route completion and dismisses related notifications
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../utils/session_auth_middleware.php';

// Check company authentication
SessionAuthMiddleware::requireCompanyAuth();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $required_fields = ['route_id', 'company_id'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $route_id = intval($input['route_id']);
    $company_id = intval($input['company_id']);
    
    if ($route_id <= 0 || $company_id <= 0) {
        throw new Exception('Invalid route_id or company_id');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // 1. Verify route belongs to company and is accepted
        $stmt = $db->prepare("
            SELECT r.route_id, r.company_id, r.is_accepted, r.is_disabled
            FROM routes r
            WHERE r.route_id = :route_id AND r.company_id = :company_id
        ");
        $stmt->bindParam(':route_id', $route_id);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        $route = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$route) {
            throw new Exception('Route not found or does not belong to company');
        }
        
        if (!$route['is_accepted']) {
            throw new Exception('Route is not accepted yet');
        }
        
        if ($route['is_disabled']) {
            throw new Exception('Route is already completed/disabled');
        }
        
        // 2. Get all requests in this route
        $stmt = $db->prepare("
            SELECT rrm.request_id, pr.customer_id, pr.status
            FROM route_request_mapping rrm
            JOIN pickup_requests pr ON rrm.request_id = pr.request_id
            WHERE rrm.route_id = :route_id
        ");
        $stmt->bindParam(':route_id', $route_id);
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($requests)) {
            throw new Exception('No requests found for this route');
        }
        
        // 3. Check if all requests are completed
        $completed_requests = 0;
        $total_requests = count($requests);
        
        foreach ($requests as $request) {
            // Check if feedback exists and pickup is completed
            $stmt = $db->prepare("
                SELECT pickup_completed 
                FROM company_feedback 
                WHERE request_id = :request_id AND company_id = :company_id
            ");
            $stmt->bindParam(':request_id', $request['request_id']);
            $stmt->bindParam(':company_id', $company_id);
            $stmt->execute();
            $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($feedback && $feedback['pickup_completed']) {
                $completed_requests++;
            }
        }
        
        // 4. Only complete route if all requests are completed
        if ($completed_requests < $total_requests) {
            throw new Exception("Cannot complete route. $completed_requests of $total_requests requests are completed.");
        }
        
        // 5. Mark route as completed (disabled)
        $stmt = $db->prepare("
            UPDATE routes 
            SET is_disabled = 1 
            WHERE route_id = :route_id AND company_id = :company_id
        ");
        $stmt->bindParam(':route_id', $route_id);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        
        // 6. Dismiss all notifications related to this route
        $stmt = $db->prepare("
            UPDATE notifications 
            SET seen = 1, dismissed_at = NOW()
            WHERE company_id = :company_id 
            AND request_id IN (
                SELECT request_id 
                FROM route_request_mapping 
                WHERE route_id = :route_id
            )
            AND seen = 0
        ");
        $stmt->bindParam(':company_id', $company_id);
        $stmt->bindParam(':route_id', $route_id);
        $stmt->execute();
        
        // 7. Create completion notification
        $company_user_id = null;
        $stmt = $db->prepare("SELECT user_id FROM registered_users WHERE user_id = :company_id AND role = 'company'");
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        $company_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($company_user) {
            $company_user_id = $company_user['user_id'];
            $completion_message = "Route #$route_id completed successfully! All pickup requests have been fulfilled.";
            
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, company_id, message, seen, created_at)
                VALUES (:user_id, :company_id, :message, 1, NOW())
            ");
            $stmt->bindParam(':user_id', $company_user_id);
            $stmt->bindParam(':company_id', $company_id);
            $stmt->bindParam(':message', $completion_message);
            $stmt->execute();
        }
        
        // 8. Update all pickup request statuses to 'Completed'
        $stmt = $db->prepare("
            UPDATE pickup_requests 
            SET status = 'Completed' 
            WHERE request_id IN (
                SELECT request_id 
                FROM route_request_mapping 
                WHERE route_id = :route_id
            )
        ");
        $stmt->bindParam(':route_id', $route_id);
        $stmt->execute();
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Route completed successfully! All notifications have been dismissed.',
            'data' => [
                'route_id' => $route_id,
                'company_id' => $company_id,
                'completed_requests' => $completed_requests,
                'total_requests' => $total_requests,
                'completion_time' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
