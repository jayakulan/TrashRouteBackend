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
    $stmt = $pdo->query("SHOW TABLES LIKE 'registered_users'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("Table 'registered_users' does not exist");
    }

    // Check if there are any companies
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM registered_users WHERE role = 'company'");
    $companyCount = $stmt->fetch()['count'];

    if ($companyCount == 0) {
        // Return empty array if no companies
        echo json_encode([
            'success' => true,
            'data' => [],
            'count' => 0,
            'message' => 'No companies found in database'
        ]);
        exit();
    }

    // Fetch companies using OOP method
    $companies = Admin::getAllCompanies($pdo);

    // Format the response
    $formattedCompanies = [];
    foreach ($companies as $company) {
        $formattedCompanies[] = [
            'id' => '#' . str_pad($company['CompanyID'], 3, '0', STR_PAD_LEFT),
            'name' => $company['Name'],
            'email' => $company['Email'],
            'phone' => $company['Phone'] ?: 'N/A',
            'location' => $company['Location'] ?: 'N/A',
            'status' => ucfirst($company['Status']),
            'comRegno' => $company['ComRegno'] ?: 'N/A',
            'joinDate' => date('Y-m-d', strtotime($company['JoinDate'])),
            'role' => $company['Role']
        ];
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $formattedCompanies,
        'count' => count($formattedCompanies)
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