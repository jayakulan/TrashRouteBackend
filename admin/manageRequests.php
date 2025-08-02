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

    // First, let's check if the tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'pickup_requests'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("Table 'pickup_requests' does not exist");
    }

    // Check if there are any pickup requests
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pickup_requests");
    $requestCount = $stmt->fetch()['count'];

    if ($requestCount == 0) {
        // Return empty array if no requests
        echo json_encode([
            'success' => true,
            'data' => [],
            'count' => 0,
            'message' => 'No pickup requests found in database'
        ]);
        exit();
    }

    // Fetch pickup requests using OOP method
    $requests = Admin::getAllPickupRequests($pdo);

    // Format the response
    $formattedRequests = [];
    foreach ($requests as $request) {
        $formattedRequests[] = [
            'id' => '#' . str_pad($request['RequestID'], 3, '0', STR_PAD_LEFT),
            'customer' => $request['CustomerName'],
            'location' => $request['Location'] ?: 'N/A',
            'status' => $request['Status'],
            'requestDate' => date('Y-m-d', strtotime($request['Timestamp'])),
            'wasteType' => $request['WasteType'],
            'amount' => $request['Quantity'] . ' kg',
            'latitude' => $request['Latitude'],
            'longitude' => $request['Longitude'],
            'otp' => $request['OTP'] ?: 'N/A',
            'otpVerified' => $request['OTPVerified'] ? 'Yes' : 'No'
        ];
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $formattedRequests,
        'count' => count($formattedRequests)
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