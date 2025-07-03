<?php
// Allow CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost:5175"); // ✅ Use your frontend's exact origin
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../config/database.php';
require_once '../../utils/helpers.php';

$allowed_origins = [
    "http://localhost:3000",
    "http://localhost:5173"
];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::sendError('Method not allowed', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    Helpers::sendError('Invalid JSON input');
}

// Validate required fields
$required_fields = ['name', 'email', 'password', 'role'];
$missing_fields = Helpers::validateRequired($input, $required_fields);

if (!empty($missing_fields)) {
    Helpers::sendError('Missing required fields: ' . implode(', ', $missing_fields));
}

// Sanitize input
$name = Helpers::sanitize($input['name']);
$email = Helpers::sanitize($input['email']);
$password = $input['password']; // Don't sanitize password
$role = Helpers::sanitize($input['role']);

// Validate role
if (!in_array($role, ['customer', 'company'])) {
    Helpers::sendError('Invalid role. Must be customer or company');
}

// Validate email
if (!Helpers::validateEmail($email)) {
    Helpers::sendError('Invalid email format');
}

// Validate password strength
if (strlen($password) < 6) {
    Helpers::sendError('Password must be at least 6 characters long');
}

// Database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    Helpers::sendError('Database connection failed', 500);
}

try {
    // Check if email already exists
    $query = "SELECT user_id FROM registered_users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        Helpers::sendError('Email already registered');
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Hash password
    $hashed_password = Helpers::hashPassword($password);
    
    // Get contact number and address
    $contact_number = isset($input['contact_number']) ? Helpers::sanitize($input['contact_number']) : null;
    $address = isset($input['address']) ? Helpers::sanitize($input['address']) : null;
    
    // Create user in registered_users table
    $query = "INSERT INTO registered_users (name, email, password_hash, contact_number, address, role) 
              VALUES (:name, :email, :password_hash, :contact_number, :address, :role)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $hashed_password);
    $stmt->bindParam(':contact_number', $contact_number);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':role', $role);
    $stmt->execute();
    
    $user_id = $db->lastInsertId();
    
    // Create role-specific record
    if ($role === 'customer') {
        $query = "INSERT INTO customers (customer_id) VALUES (:user_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
    } elseif ($role === 'company') {
        // Validate company registration number
        if (!isset($input['company_reg_number']) || empty($input['company_reg_number'])) {
            $db->rollBack();
            Helpers::sendError('Company registration number is required');
        }
        
        $company_reg_number = Helpers::sanitize($input['company_reg_number']);
        
        // Check if company registration number already exists
        $query = "SELECT company_id FROM companies WHERE company_reg_number = :company_reg_number";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':company_reg_number', $company_reg_number);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            $db->rollBack();
            Helpers::sendError('Company registration number already exists');
        }
        
        $query = "INSERT INTO companies (company_id, company_reg_number) VALUES (:user_id, :company_reg_number)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':company_reg_number', $company_reg_number);
        $stmt->execute();
    }
    
    // Commit transaction
    $db->commit();
    
    // Generate token
    $token = Helpers::generateToken($user_id, $role);
    
    // Get created user data
    $query = "SELECT user_id, name, email, role, disable_status FROM registered_users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch();
    
    // Get profile data
    $profile = null;
    if ($role === 'customer') {
        $query = "SELECT c.customer_id, ru.name, ru.email, ru.contact_number, ru.address 
                  FROM customers c 
                  JOIN registered_users ru ON c.customer_id = ru.user_id 
                  WHERE ru.user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $profile = $stmt->fetch();
        
    } elseif ($role === 'company') {
        $query = "SELECT c.company_id, c.company_reg_number, ru.name, ru.email, ru.contact_number, ru.address 
                  FROM companies c 
                  JOIN registered_users ru ON c.company_id = ru.user_id 
                  WHERE ru.user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $profile = $stmt->fetch();
    }
    
    $response_data = [
        'user' => $user,
        'profile' => $profile,
        'token' => $token
    ];
    
    Helpers::sendResponse($response_data, 201, 'Registration successful');
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    Helpers::sendError('Registration failed: ' . $e->getMessage(), 500);
}
?> 