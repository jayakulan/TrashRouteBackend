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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../utils/session_auth_middleware.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check company authentication
SessionAuthMiddleware::requireCompanyAuth();

try {
    $input = json_decode(file_get_contents('php://input'), true);



    // Validate required fields
    if (
        !isset($input['request_id']) ||
        !isset($input['company_id']) ||
        !isset($input['pickup_completed']) ||
        !isset($input['rating'])
    ) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $request_id = intval($input['request_id']);
    $company_id = intval($input['company_id']);
    $pickup_completed = (bool)$input['pickup_completed'];
    $rating = intval($input['rating']);
    $comment = isset($input['comment']) ? trim($input['comment']) : null;
    $entered_otp = isset($input['entered_otp']) ? trim($input['entered_otp']) : null;

    // Validate that company_id exists in companies table
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT company_id FROM companies WHERE company_id = :company_id");
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        echo json_encode(['success' => false, 'message' => 'Invalid company_id: ' . $company_id . '. Company does not exist in database.']);
        exit;
    }

    // Optional: Check if OTP was verified in pickup_requests
    $pickup_verified = false;
    if ($entered_otp) {
        $stmt = $db->prepare("SELECT otp, otp_verified FROM pickup_requests WHERE request_id = :request_id");
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->execute();
        $pickup = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pickup && $pickup['otp_verified']) {
            $pickup_verified = true;
        }
    }

    // Insert feedback
    $stmt = $db->prepare("INSERT INTO company_feedback 
        (request_id, company_id, entered_otp, pickup_verified, pickup_completed, rating, comment)
        VALUES (:request_id, :company_id, :entered_otp, :pickup_verified, :pickup_completed, :rating, :comment)
    ");
    $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindParam(':entered_otp', $entered_otp);
    $stmt->bindParam(':pickup_verified', $pickup_verified, PDO::PARAM_BOOL);
    $stmt->bindParam(':pickup_completed', $pickup_completed, PDO::PARAM_BOOL);
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindParam(':comment', $comment);

    if ($stmt->execute()) {
        // Check if this completion means the entire route is now complete
        if ($pickup_completed) {
            // Get route_id for this request
            $stmt = $db->prepare("
                SELECT rrm.route_id, r.company_id, r.is_disabled
                FROM route_request_mapping rrm
                JOIN routes r ON rrm.route_id = r.route_id
                WHERE rrm.request_id = :request_id
            ");
            $stmt->bindParam(':request_id', $request_id);
            $stmt->execute();
            $route_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($route_info && !$route_info['is_disabled']) {
                // Check if all requests in this route are now completed
                $stmt = $db->prepare("
                    SELECT COUNT(*) as total_requests,
                           SUM(CASE WHEN cf.pickup_completed = 1 THEN 1 ELSE 0 END) as completed_requests
                    FROM route_request_mapping rrm
                    LEFT JOIN company_feedback cf ON rrm.request_id = cf.request_id AND cf.company_id = :company_id
                    WHERE rrm.route_id = :route_id
                ");
                $stmt->bindParam(':route_id', $route_info['route_id']);
                $stmt->bindParam(':company_id', $company_id);
                $stmt->execute();
                $completion_status = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // If all requests are completed, dismiss route notifications
                if ($completion_status['total_requests'] > 0 && 
                    $completion_status['total_requests'] == $completion_status['completed_requests']) {
                    
                    // Mark route as completed
                    $stmt = $db->prepare("UPDATE routes SET is_disabled = 1 WHERE route_id = :route_id");
                    $stmt->bindParam(':route_id', $route_info['route_id']);
                    $stmt->execute();
                    
                    // Dismiss all notifications for this route
                    $stmt = $db->prepare("
                        UPDATE notifications 
                        SET seen = 1, dismissed_at = NOW()
                        WHERE company_id = :company_id 
                        AND request_id IN (
                            SELECT request_id 
                            FROM route_request_mapping 
                            WHERE route_id = :route_id
                        )
                        AND dismissed_at IS NULL
                    ");
                    $stmt->bindParam(':company_id', $company_id);
                    $stmt->bindParam(':route_id', $route_info['route_id']);
                    $stmt->execute();
                    
                    // Update pickup request statuses to 'Completed'
                    $stmt = $db->prepare("
                        UPDATE pickup_requests 
                        SET status = 'Completed' 
                        WHERE request_id IN (
                            SELECT request_id 
                            FROM route_request_mapping 
                            WHERE route_id = :route_id
                        )
                    ");
                    $stmt->bindParam(':route_id', $route_info['route_id']);
                    $stmt->execute();
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit feedback']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 