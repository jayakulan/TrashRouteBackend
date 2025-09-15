<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5175');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

// Check if this is a delete action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle DELETE request for customer deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
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
            // Instead of deleting, disable the customer
            $result = Admin::disableCustomer($pdo, $customerId);
            if ($result) {
                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Customer status set to disabled',
                    'disabledCustomerId' => $formattedId
                ]);
            } else {
                $pdo->rollBack();
                throw new Exception("Failed to disable customer or customer not found");
            }

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
        exit();
    } catch (Exception $e) {
        // Return error response
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Error: ' . $e->getMessage(),
            'details' => 'General Exception occurred'
        ]);
        exit();
    }
}

try {
    // Create database connection using the Database class
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }

    // First, let's check if the tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'registered_users'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("Table 'registered_users' does not exist");
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'customers'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("Table 'customers' does not exist");
    }

    // Check if there are any customers or pending users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM registered_users WHERE role = 'customer'");
    $customerCount = $stmt->fetch()['count'];

    if ($customerCount == 0) {
        // Return empty array if no customers
        echo json_encode([
            'success' => true,
            'data' => [],
            'count' => 0,
            'message' => 'No customers found in database'
        ]);
        exit();
    }

    // Fetch customers using OOP method
    $customers = Admin::getAllCustomers($pdo);

    // Format the response
    $formattedCustomers = [];
    foreach ($customers as $customer) {
        $formattedCustomers[] = [
            'id' => '#' . str_pad($customer['CustomerID'], 3, '0', STR_PAD_LEFT),
            'name' => $customer['Name'],
            'email' => $customer['Email'],
            'phone' => $customer['Phone'] ?: 'N/A',
            'location' => $customer['Location'] ?: 'N/A',
            'status' => ucfirst($customer['Status']),
            'joinDate' => date('Y-m-d', strtotime($customer['JoinDate'])),
            'role' => $customer['Role']
        ];
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $formattedCustomers,
        'count' => count($formattedCustomers)
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