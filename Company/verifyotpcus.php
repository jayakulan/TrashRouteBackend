<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../utils/session_auth_middleware.php';

// Check company authentication
SessionAuthMiddleware::requireCompanyAuth();

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['request_id']) || !isset($input['entered_otp'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: request_id and entered_otp']);
        exit;
    }
    
    $request_id = intval($input['request_id']);
    $entered_otp = $input['entered_otp'];
    
    if ($request_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
        exit;
    }
    
    if (empty($entered_otp)) {
        echo json_encode(['success' => false, 'message' => 'OTP is required']);
        exit;
    }

    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // First, check if the pickup request exists and get its OTP
    $query = "SELECT request_id, customer_id, waste_type, quantity, otp, otp_verified, status 
              FROM pickup_requests 
              WHERE request_id = :request_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
    $stmt->execute();
    $pickupRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pickupRequest) {
        echo json_encode(['success' => false, 'message' => 'Pickup request not found']);
        exit;
    }
    
    // Check if OTP is already verified
    if ($pickupRequest['otp_verified']) {
        echo json_encode(['success' => false, 'message' => 'OTP already verified for this request']);
        exit;
    }
    
    // Check if OTP exists for this request
    if (empty($pickupRequest['otp'])) {
        echo json_encode(['success' => false, 'message' => 'No OTP found for this pickup request. Please ask the customer to generate an OTP first.']);
        exit;
    }
    
    // Verify the entered OTP
    if ($entered_otp === $pickupRequest['otp']) {
        // OTP is correct - update the pickup request
        $updateQuery = "UPDATE pickup_requests 
                       SET otp_verified = TRUE, 
                           status = 'Completed' 
                       WHERE request_id = :request_id";
        
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        
        if ($updateStmt->execute()) {
            // Get customer details for response
            $customerQuery = "SELECT ru.name as customer_name, ru.contact_number, ru.address 
                             FROM customers c 
                             INNER JOIN registered_users ru ON c.customer_id = ru.user_id 
                             WHERE c.customer_id = :customer_id";
            
            $customerStmt = $db->prepare($customerQuery);
            $customerStmt->bindParam(':customer_id', $pickupRequest['customer_id'], PDO::PARAM_INT);
            $customerStmt->execute();
            $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'OTP verified successfully! Pickup request completed.',
                'data' => [
                    'request_id' => $request_id,
                    'customer_name' => $customer['customer_name'] ?? 'Unknown',
                    'waste_type' => $pickupRequest['waste_type'],
                    'quantity' => $pickupRequest['quantity'],
                    'status' => 'Completed',
                    'otp_verified' => true
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update pickup request status']);
        }
    } else {
        // OTP is incorrect
        echo json_encode(['success' => false, 'message' => 'Incorrect OTP. Please check and try again.']);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 