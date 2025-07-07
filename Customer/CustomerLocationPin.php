<?php
// Disable error display to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost:5175");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Max-Age: 86400"); // 24 hours
    http_response_code(200);
    exit();
}

// Set CORS headers for actual requests
header("Access-Control-Allow-Origin: http://localhost:5175");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Include database configuration and helpers
try {
    require_once '../config/database.php';
    require_once '../utils/helpers.php';
} catch (Exception $e) {
    error_log("Error including files: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    exit();
}

try {
    // Get JSON input
    $rawInput = file_get_contents('php://input');
    error_log("Raw input received: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input. Raw input: ' . $rawInput);
    }
    
    error_log("Decoded input: " . print_r($input, true));
    
    // Extract location data
    $latitude = $input['latitude'] ?? null;
    $longitude = $input['longitude'] ?? null;
    
    error_log("Location data - Latitude: $latitude, Longitude: $longitude");
    
    if ($latitude === null || $longitude === null) {
        throw new Exception('Latitude and longitude are required');
    }
    
    // Validate coordinates
    if (!is_numeric($latitude) || !is_numeric($longitude)) {
        throw new Exception('Invalid coordinates provided');
    }
    
    if ($latitude < -90 || $latitude > 90) {
        throw new Exception('Invalid latitude value (must be between -90 and 90)');
    }
    
    if ($longitude < -180 || $longitude > 180) {
        throw new Exception('Invalid longitude value (must be between -180 and 180)');
    }
    
    // Get current user from JWT token
    $currentUser = Helpers::getCurrentUser();
    
    error_log("Current user from token: " . print_r($currentUser, true));
    
    if (!$currentUser) {
        throw new Exception('User not authenticated. Please login again.');
    }
    
    $customerId = $currentUser['user_id'];
    error_log("Customer ID: " . $customerId);
    
    // Test database connection
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection not available. Please check your database configuration.');
    }
    
    // Verify customer exists in customers table
    $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE customer_id = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare customer query: ' . print_r($pdo->errorInfo(), true));
    }
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        throw new Exception('Customer not found in customers table. Customer ID: ' . $customerId);
    }
    
    // Find all pickup requests for this customer that don't have coordinates
    $stmt = $pdo->prepare("
        SELECT request_id 
        FROM pickup_requests 
        WHERE customer_id = ? 
        AND (latitude IS NULL OR longitude IS NULL)
        ORDER BY timestamp DESC
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare pickup request query: ' . print_r($pdo->errorInfo(), true));
    }
    
    $stmt->execute([$customerId]);
    $pickupRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pickupRequests)) {
        throw new Exception('No pending pickup requests found for this customer. Please submit waste types first.');
    }
    
    error_log("Found " . count($pickupRequests) . " pickup requests to update");
    
    // Update all pickup requests with coordinates
    $stmt = $pdo->prepare("
        UPDATE pickup_requests 
        SET latitude = ?, longitude = ? 
        WHERE customer_id = ? 
        AND (latitude IS NULL OR longitude IS NULL)
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare update query: ' . print_r($pdo->errorInfo(), true));
    }
    
    $result = $stmt->execute([$latitude, $longitude, $customerId]);
    
    if (!$result) {
        throw new Exception('Failed to update pickup requests with coordinates');
    }
    
    $rowsAffected = $stmt->rowCount();
    error_log("Rows affected: " . $rowsAffected);
    
    if ($rowsAffected === 0) {
        throw new Exception('No pickup requests were updated. The requests may have already been processed.');
    }
    
    // Get the updated request IDs for the response
    $stmt = $pdo->prepare("
        SELECT request_id 
        FROM pickup_requests 
        WHERE customer_id = ? 
        AND latitude = ? 
        AND longitude = ?
        ORDER BY timestamp DESC
    ");
    
    $stmt->execute([$customerId, $latitude, $longitude]);
    $updatedRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requestIds = array_column($updatedRequests, 'request_id');
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Location coordinates saved successfully for " . count($requestIds) . " pickup request(s)",
        'data' => [
            'request_ids' => $requestIds,
            'total_updated' => count($requestIds),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'customer_id' => $customerId
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in CustomerLocationPin.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in CustomerLocationPin.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}
?> 