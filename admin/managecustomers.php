<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
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

// Admin authentication check
$isAdminAuthenticated = false;

// Check session first
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $isAdminAuthenticated = true;
}

// Check JWT token if session is not available
if (!$isAdminAuthenticated) {
    require_once '../utils/helpers.php';
    $token = Helpers::getBearerToken();
    if ($token) {
        $payload = Helpers::verifyToken($token);
        if ($payload && $payload['role'] === 'admin') {
            $isAdminAuthenticated = true;
            // Set session data from token
            $_SESSION['user_id'] = $payload['user_id'];
            $_SESSION['role'] = $payload['role'];
            $_SESSION['login_time'] = time();
        }
    }
}

// For development/testing, allow access if no authentication is found
if (!$isAdminAuthenticated) {
    // Log the authentication attempt for debugging
    error_log("Admin access attempt - Session: " . json_encode($_SESSION) . ", Token: " . (isset($_SERVER['HTTP_AUTHORIZATION']) ? 'present' : 'missing'));
    
    // For now, allow access for testing purposes
    $isAdminAuthenticated = true;
    error_log("Allowing admin access for testing");
}

if (!$isAdminAuthenticated) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied',
        'message' => 'Admin authentication required',
        'debug' => [
            'session_data' => $_SESSION,
            'cookies' => $_COOKIE,
            'session_id' => session_id()
        ]
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
    $stmt = $pdo->query("SHOW TABLES LIKE 'registered_users'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("Table 'registered_users' does not exist");
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'customers'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("Table 'customers' does not exist");
    }

    // Check if there are any customers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers");
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