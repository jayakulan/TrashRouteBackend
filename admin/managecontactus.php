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

    // First, let's check if the contact_us table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'contact_us'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("Table 'contact_us' does not exist");
    }

    // Check if there are any contact submissions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contact_us");
    $contactCount = $stmt->fetch()['count'];

    if ($contactCount == 0) {
        // Return empty array if no contact submissions
        echo json_encode([
            'success' => true,
            'data' => [],
            'count' => 0,
            'message' => 'No contact submissions found in database'
        ]);
        exit();
    }

    // Fetch all contact submissions using Admin class method
    $contacts = Admin::getAllContactSubmissions($pdo);

    // Format the response
    $formattedContacts = [];
    foreach ($contacts as $contact) {
        $formattedContacts[] = [
            'id' => '#' . str_pad($contact['contact_id'], 3, '0', STR_PAD_LEFT),
            'name' => $contact['name'],
            'email' => $contact['email'],
            'subject' => $contact['subject'],
            'message' => $contact['message'],
            'created_at' => date('Y-m-d H:i:s', strtotime($contact['created_at'])),
            'admin_id' => $contact['admin_id'] ?: 'N/A',
            'status' => 'New' // Default status, can be enhanced later
        ];
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $formattedContacts,
        'count' => count($formattedContacts)
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