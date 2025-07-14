<?php
// Set CORS headers first, before any output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5175');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // 24 hours

// Handle preflight OPTIONS request immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Allow POST method for delete operations
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Only POST requests are accepted.',
        'method' => $_SERVER['REQUEST_METHOD']
    ]);
    exit();
}

// Check if this is a delete action via query parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action !== 'delete') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid action. Query parameter "action" must be set to "delete".',
        'action' => $action
    ]);
    exit();
}

// Include database configuration
require_once '../config/database.php';

try {
    // Get the customer ID from the request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['customerId'])) {
        throw new Exception("Customer ID is required");
    }
    
    // Extract the numeric ID from the formatted ID (e.g., "#001" -> "1")
    $formattedId = $input['customerId'];
    $customerId = intval(str_replace('#', '', $formattedId));
    
    if ($customerId <= 0) {
        throw new Exception("Invalid customer ID format");
    }

    // Create database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // First, verify the customer exists and get user_id
        $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        
        if ($stmt->rowCount() == 0) {
            throw new Exception("Customer not found");
        }

        // Check if customer has any related records that would prevent deletion
        // Check pickup_requests
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pickup_requests WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        $pickupCount = $stmt->fetch()['count'];

        // Check customer_feedback
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM customer_feedback WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        $feedbackCount = $stmt->fetch()['count'];

        // Check notifications
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
        $stmt->execute([$customerId]);
        $notificationCount = $stmt->fetch()['count'];

        // Check OTP records
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM otp WHERE user_id = ?");
        $stmt->execute([$customerId]);
        $otpCount = $stmt->fetch()['count'];

        // Check contact_us
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM contact_us WHERE user_id = ?");
        $stmt->execute([$customerId]);
        $contactCount = $stmt->fetch()['count'];

        // Delete related records first (due to foreign key constraints)
        
        // Delete from customer_feedback
        if ($feedbackCount > 0) {
            $stmt = $pdo->prepare("DELETE FROM customer_feedback WHERE customer_id = ?");
            $stmt->execute([$customerId]);
        }

        // Delete from pickup_requests
        if ($pickupCount > 0) {
            $stmt = $pdo->prepare("DELETE FROM pickup_requests WHERE customer_id = ?");
            $stmt->execute([$customerId]);
        }

        // Delete from notifications
        if ($notificationCount > 0) {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->execute([$customerId]);
        }

        // Delete from otp
        if ($otpCount > 0) {
            $stmt = $pdo->prepare("DELETE FROM otp WHERE user_id = ?");
            $stmt->execute([$customerId]);
        }

        // Delete from contact_us
        if ($contactCount > 0) {
            $stmt = $pdo->prepare("DELETE FROM contact_us WHERE user_id = ?");
            $stmt->execute([$customerId]);
        }

        // Delete from customers table
        $stmt = $pdo->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->execute([$customerId]);

        // Delete from registered_users table
        $stmt = $pdo->prepare("DELETE FROM registered_users WHERE user_id = ?");
        $stmt->execute([$customerId]);

        // Commit transaction
        $pdo->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Customer deleted successfully',
            'deletedCustomerId' => $formattedId,
            'deletedRecords' => [
                'pickup_requests' => $pickupCount,
                'customer_feedback' => $feedbackCount,
                'notifications' => $notificationCount,
                'otp' => $otpCount,
                'contact_us' => $contactCount
            ]
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        throw $e;
    }

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
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage(),
        'details' => 'General Exception occurred'
    ]);
}
?> 