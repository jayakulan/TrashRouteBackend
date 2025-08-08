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

    // Debug: Test query to see all requests
    $testQuery = "SELECT request_id, customer_id, waste_type, status FROM pickup_requests ORDER BY request_id DESC LIMIT 10";
    $testStmt = $db->prepare($testQuery);
    $testStmt->execute();
    $allRequests = $testStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("trackPickup.php - All requests in table: " . json_encode($allRequests));

    // Get all pickup requests for this customer
    $query = "SELECT 
                pr.request_id,
                pr.waste_type,
                pr.quantity,
                pr.latitude,
                pr.longitude,
                pr.status,
                pr.timestamp,
                pr.otp,
                pr.otp_verified,
                ru.name as customer_name,
                ru.contact_number as customer_phone,
                ru.address as customer_address
              FROM pickup_requests pr
              LEFT JOIN registered_users ru ON pr.customer_id = ru.user_id
              WHERE pr.customer_id = :customer_id 
              ORDER BY pr.timestamp DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();

    $pickup_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the number of requests found
    error_log("trackPickup.php - Found " . count($pickup_requests) . " requests for customer_id: " . $customer_id);
    
    // Debug: Log the first few requests
    if (!empty($pickup_requests)) {
        error_log("trackPickup.php - First request: " . json_encode($pickup_requests[0]));
    } else {
        // Debug: Check if there are any requests in the table at all
        $checkQuery = "SELECT COUNT(*) as total_requests FROM pickup_requests";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute();
        $totalRequests = $checkStmt->fetch(PDO::FETCH_ASSOC);
        error_log("trackPickup.php - Total requests in table: " . $totalRequests['total_requests']);
        
        // Debug: Check if the customer_id exists in the table
        $checkCustomerQuery = "SELECT COUNT(*) as customer_requests FROM pickup_requests WHERE customer_id = :customer_id";
        $checkCustomerStmt = $db->prepare($checkCustomerQuery);
        $checkCustomerStmt->bindParam(':customer_id', $customer_id);
        $checkCustomerStmt->execute();
        $customerRequests = $checkCustomerStmt->fetch(PDO::FETCH_ASSOC);
        error_log("trackPickup.php - Requests for customer_id " . $customer_id . ": " . $customerRequests['customer_requests']);
    }

    if (empty($pickup_requests)) {
        echo json_encode([
            'success' => true,
            'data' => [
                'pickup_requests' => [],
                'waste_types' => [],
                'has_requests' => false
            ]
        ]);
        exit();
    }

    // Group requests by waste type and determine status progression
    $waste_types_data = [];
    $waste_type_statuses = [];

    foreach ($pickup_requests as $request) {
        // Normalize waste type to lowercase for frontend mapping
        $waste_type = strtolower($request['waste_type']);
        
        // Map database status to frontend step
        $currentStep = 0; // Default: Request Received
        $status = $request['status'];
        
        switch ($status) {
            case 'Request received':
                $currentStep = 0; // Request Received - always step 0
                break;
            case 'Accepted':
                // Check if pickup is ongoing (has OTP but not verified)
                if ($request['otp'] && !$request['otp_verified']) {
                    $currentStep = 2; // Ongoing - OTP generated but not verified
                } else {
                    $currentStep = 1; // Scheduled - Accepted but no OTP yet
                }
                break;
            case 'Completed':
                $currentStep = 3; // Completed
                break;
            default:
                $currentStep = 0;
        }

        // Only keep the MOST RECENT entry per waste type (query is DESC by timestamp)
        if (!isset($waste_types_data[$request['waste_type']])) {
            $waste_types_data[$request['waste_type']] = [
                'request_id' => $request['request_id'],
                'waste_type' => $request['waste_type'],
                'quantity' => $request['quantity'],
                'status' => $status,
                'current_step' => $currentStep,
                'timestamp' => $request['timestamp'],
                'otp' => $request['otp'],
                'otp_verified' => $request['otp_verified'],
                'latitude' => $request['latitude'],
                'longitude' => $request['longitude']
            ];

            $waste_type_statuses[$request['waste_type']] = $currentStep;
        }
    }

    // Prepare response data
    $response_data = [
        'pickup_requests' => $pickup_requests,
        'waste_types' => $waste_types_data,
        'waste_type_statuses' => $waste_type_statuses,
        'has_requests' => true
    ];

    echo json_encode([
        'success' => true,
        'data' => $response_data
    ]);

} catch (Exception $e) {
    error_log("Error in trackPickup.php: " . $e->getMessage());
    Helpers::sendError('Failed to fetch pickup tracking data: ' . $e->getMessage(), 500);
}
?>
