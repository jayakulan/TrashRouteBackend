<?php
session_start();

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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../utils/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::sendError('Method not allowed', 405);
}

// Get customer ID from token
$token = Helpers::getBearerToken();
if (!$token) {
    Helpers::sendError('No token provided', 401);
}

$payload = Helpers::verifyToken($token);
if (!$payload || $payload['role'] !== 'customer') {
    Helpers::sendError('Invalid token or unauthorized access', 401);
}

$customer_id = $payload['user_id'];

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        Helpers::sendError('Database connection failed', 500);
    }

    // Get all pickup requests for this customer that don't have OTP yet
    $query = "SELECT pr.request_id, pr.waste_type, pr.quantity, pr.latitude, pr.longitude, pr.status, pr.timestamp
              FROM pickup_requests pr
              WHERE pr.customer_id = :customer_id 
              AND pr.otp IS NULL
              ORDER BY pr.timestamp DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();

    $pickup_requests = $stmt->fetchAll();

    if (empty($pickup_requests)) {
        Helpers::sendError('No pending pickup requests found for this customer', 404);
    }

    $otp_data = [];

    // Generate OTP for each waste type
    foreach ($pickup_requests as $request) {
        // Generate a random 6-digit OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Update pickup request with OTP (keep status as 'Request received')
        $update_query = "UPDATE pickup_requests SET 
                        otp = :otp_code,
                        otp_verified = FALSE
                        WHERE request_id = :request_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':otp_code', $otp);
        $update_stmt->bindParam(':request_id', $request['request_id']);
        $update_stmt->execute();

        $otp_data[] = [
            'request_id' => $request['request_id'],
            'waste_type' => $request['waste_type'],
            'quantity' => $request['quantity'],
            'otp' => $otp
        ];
    }

    // Format the response
    $response_data = [
        'otp_list' => $otp_data,
        'total_otps' => count($otp_data),
        'message' => 'OTPs generated successfully for all waste types!'
    ];

    Helpers::sendResponse($response_data, 200, 'Pickup scheduled successfully');

} catch (Exception $e) {
    Helpers::sendError('Failed to schedule pickup: ' . $e->getMessage(), 500);
}
?> 