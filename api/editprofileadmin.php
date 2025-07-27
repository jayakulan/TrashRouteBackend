<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Admin.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    // Extract data
    $name = trim($input['name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $address = trim($input['address'] ?? '');

    // Validate required fields
    if (empty($name)) {
        throw new Exception('Name is required');
    }

    // Start session to get admin ID
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Admin not authenticated');
    }
    $adminId = $_SESSION['user_id'];

    // Get current admin data
    $stmt = $pdo->prepare("SELECT * FROM registered_users WHERE user_id = ? AND role = 'admin'");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        throw new Exception('Admin user not found');
    }

    // Prepare update query
    $sql = "UPDATE registered_users SET name = ?, contact_number = ?, address = ? WHERE user_id = ? AND role = 'admin'";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$name, $phone, $address, $adminId]);
    
    if (!$result) {
        throw new Exception('Failed to update profile');
    }

    // Fetch updated admin data
    $stmt = $pdo->prepare("SELECT user_id, name, email, contact_number, address FROM registered_users WHERE user_id = ? AND role = 'admin'");
    $stmt->execute([$adminId]);
    $updatedAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Update session data
    $_SESSION['user_name'] = $updatedAdmin['name'];

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => $updatedAdmin
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?> 