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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

    // 1. Get the latest timestamp for this customer
    $query = "SELECT MAX(timestamp) as latest_time FROM pickup_requests WHERE customer_id = :customer_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);
    $latest_time = $latest['latest_time'];

    if (!$latest_time) {
        Helpers::sendError('No pickup request found for this customer', 404);
    }

    // 2. Get all requests with that timestamp
    $query = "SELECT request_id, waste_type, quantity, latitude, longitude, status, timestamp 
              FROM pickup_requests 
              WHERE customer_id = :customer_id AND timestamp = :latest_time";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->bindParam(':latest_time', $latest_time);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($requests)) {
        Helpers::sendError('No pickup request found for this customer', 404);
    }

    // 3. Aggregate waste types and quantities
    $waste_types = [];
    $total_weight = 0;
    foreach ($requests as $req) {
        $waste_types[] = $req['waste_type'];
        $total_weight += $req['quantity'];
    }
    $unique_waste_types = array_unique($waste_types);
    $waste_types_string = implode(', ', $unique_waste_types);

    // 4. Use the first request for location/status
    $first = $requests[0];
    $response_data = [
        'request_id' => $first['request_id'],
        'waste_types' => $waste_types_string,
        'approximate_total_weight' => $total_weight . ' kg',
        'pickup_location' => 'Lat: ' . $first['latitude'] . ', Long: ' . $first['longitude'],
        'status' => $first['status'],
        'timestamp' => $first['timestamp'],
        'total_requests' => count($requests)
    ];

    Helpers::sendResponse($response_data, 200, 'Pickup summary retrieved successfully');

} catch (Exception $e) {
    Helpers::sendError('Failed to retrieve pickup summary: ' . $e->getMessage(), 500);
}
?> 