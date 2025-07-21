<?php
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Company.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
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

// Include database configuration
require_once '../config/database.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Extract data
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $address = trim($input['address'] ?? '');
    $currentPassword = $input['currentPassword'] ?? '';
    $newPassword = $input['newPassword'] ?? '';
    $confirmPassword = $input['confirmPassword'] ?? '';
    
    // Validate required fields
    if (empty($name)) {
        throw new Exception('Name is required');
    }
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Start session to get user ID
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }
    
    $userId = $_SESSION['user_id'];
    
    // Get current user data
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Check if email is already taken by another user
    if ($email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            throw new Exception('Email is already taken by another user');
        }
    }
    
    // Handle password change if provided
    $passwordUpdate = '';
    $passwordParams = [];
    
    if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
        // All password fields must be provided
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            throw new Exception('All password fields are required for password change');
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }
        
        // Validate new password
        if (strlen($newPassword) < 6) {
            throw new Exception('New password must be at least 6 characters long');
        }
        
        if ($newPassword !== $confirmPassword) {
            throw new Exception('New password and confirm password do not match');
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $passwordUpdate = ', password = ?';
        $passwordParams[] = $hashedPassword;
    }
    
    // Prepare update query
    $updateFields = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'address' => $address
    ];
    
    $setClause = [];
    $params = [];
    
    foreach ($updateFields as $field => $value) {
        $setClause[] = "$field = ?";
        $params[] = $value;
    }
    
    // Add password parameters if password is being updated
    if (!empty($passwordParams)) {
        $params = array_merge($params, $passwordParams);
    }
    
    // Add user ID for WHERE clause
    $params[] = $userId;
    
    $sql = "UPDATE customers SET " . implode(', ', $setClause) . $passwordUpdate . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if (!$result) {
        throw new Exception('Failed to update profile');
    }
    
    $customer = new Customer($userId, $email, $name, $address);
    $updatedUser = $customer->updateProfile($pdo, $userId, $name, $email, $phone, $address, $currentPassword, $newPassword, $confirmPassword);
    // Update session data
    $_SESSION['user_name'] = $updatedUser['name'];
    $_SESSION['user_email'] = $updatedUser['email'];
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => $updatedUser
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