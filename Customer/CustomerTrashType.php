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
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

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
    require_once '../utils/session_auth_middleware.php';
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
    
    // Extract waste type data
    $wasteTypes = $input['wasteTypes'] ?? [];
    
    error_log("Waste types extracted: " . print_r($wasteTypes, true));
    
    if (empty($wasteTypes)) {
        throw new Exception('No waste types provided');
    }
    
    // Check customer authentication (session + JWT fallback)
    try {
        $customerUser = SessionAuthMiddleware::requireCustomerAuth();
        $customerId = $customerUser['user_id'];
        error_log("Customer authenticated via session/JWT: " . $customerId);
    } catch (Exception $e) {
        error_log("Customer authentication failed: " . $e->getMessage());
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentication failed',
            'message' => $e->getMessage()
        ]);
        exit();
    }
    error_log("Customer ID: " . $customerId);
    
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    // Make DB connection available to helper methods that expect a global
    $GLOBALS['db'] = $db;
    
    if (!$db) {
        throw new Exception('Database connection not available. Please check your database configuration.');
    }
    
    // Verify customer exists in customers table
    $stmt = $db->prepare("SELECT customer_id FROM customers WHERE customer_id = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare customer query: ' . print_r($db->errorInfo(), true));
    }
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        throw new Exception('Customer not found in customers table. Customer ID: ' . $customerId);
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    $insertedRequests = [];
    
    // Process each waste type
    foreach ($wasteTypes as $wasteType) {
        $type = $wasteType['type'] ?? '';
        $quantity = $wasteType['quantity'] ?? 0;
        $selected = $wasteType['selected'] ?? false;
        
        // Only insert if waste type is selected and has quantity
        if ($selected && $quantity > 0) {
            // Insert into pickup_requests table (note: plural form)
            $stmt = $db->prepare("
                INSERT INTO pickup_requests (customer_id, waste_type, quantity, status, timestamp) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $customerId,
                $type,
                $quantity,
                'Request received' // Default status
            ]);
            
            if (!$result) {
                throw new Exception('Failed to insert pickup request for ' . $type);
            }
            
            $requestId = $db->lastInsertId();

            // Create a notification for the customer about the scheduled pickup
            $message = "Pickup scheduled: {$type} (qty: {$quantity}). Request #{$requestId}.";
            Helpers::createNotification((int)$customerId, $message, (int)$requestId, null, (int)$customerId);
            
            $insertedRequests[] = [
                'request_id' => $requestId,
                'waste_type' => $type,
                'quantity' => $quantity
            ];
        }
    }
    
    // Commit transaction
    $db->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Pickup requests saved successfully',
        'data' => [
            'total_requests' => count($insertedRequests),
            'requests' => $insertedRequests
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    // Rollback transaction on database error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}
?> 