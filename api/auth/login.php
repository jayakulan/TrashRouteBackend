<?php
session_start();

// ✅ CORS: Allow multiple frontend origins (e.g., 5173, 5175)
$allowed_origins = ['http://localhost:5173', 'http://localhost:5175'];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
} else {
    header("Access-Control-Allow-Origin: http://localhost:5173"); // default fallback
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ Core login logic begins here
require_once '../../config/database.php';
require_once '../../utils/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::sendError('Method not allowed', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    Helpers::sendError('Invalid JSON input');
}

// Validate required fields
$required_fields = ['email', 'password'];
$missing_fields = Helpers::validateRequired($input, $required_fields);
if (!empty($missing_fields)) {
    Helpers::sendError('Missing required fields: ' . implode(', ', $missing_fields));
}

// Sanitize input
$email = Helpers::sanitize($input['email']);
$password = $input['password']; // password is not sanitized to retain its value

// Validate email
if (!Helpers::validateEmail($email)) {
    Helpers::sendError('Invalid email format');
}

// Database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    Helpers::sendError('Database connection failed', 500);
}

try {
    // Check if user exists
    $query = "SELECT user_id, name, email, password_hash, role, disable_status 
              FROM registered_users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $user = $stmt->fetch();
    
    if (!$user) {
        Helpers::sendError('Invalid email or password');
    }

    // Check if account is disabled
    if ($user['disable_status'] !== 'active') {
        Helpers::sendError('Account is disabled');
    }

    // Validate password (support plain text + hashed for compatibility)
    $password_valid = false;
    if ($user['password_hash'] === $password) {
        $password_valid = true;
    } elseif (Helpers::verifyPassword($password, $user['password_hash'])) {
        $password_valid = true;
    }

    if (!$password_valid) {
        Helpers::sendError('Invalid email or password');
    }

    // Generate token
    $token = Helpers::generateToken($user['user_id'], $user['role']);

    // Fetch user profile based on role
    $profile = null;
    if ($user['role'] === 'customer') {
        $query = "SELECT c.customer_id, ru.name, ru.email, ru.contact_number, ru.address 
                  FROM customers c 
                  JOIN registered_users ru ON c.customer_id = ru.user_id 
                  WHERE ru.user_id = :user_id";
    } elseif ($user['role'] === 'company') {
        $query = "SELECT c.company_id, c.company_reg_number, ru.name, ru.email, ru.contact_number, ru.address 
                  FROM companies c 
                  JOIN registered_users ru ON c.company_id = ru.user_id 
                  WHERE ru.user_id = :user_id";
    } elseif ($user['role'] === 'admin') {
        $query = "SELECT a.admin_id, ru.name, ru.email, ru.contact_number, ru.address 
                  FROM admins a 
                  JOIN registered_users ru ON a.admin_id = ru.user_id 
                  WHERE ru.user_id = :user_id";
    }

    if ($query) {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user['user_id']);
        $stmt->execute();
        $profile = $stmt->fetch();
    }

    // Remove sensitive data
    unset($user['password_hash']);

    // Final response
    $response_data = [
        'user' => $user,
        'profile' => $profile,
        'token' => $token
    ];

    Helpers::sendResponse($response_data, 200, 'Login successful');

} catch (Exception $e) {
    Helpers::sendError('Login failed: ' . $e->getMessage(), 500);
}
