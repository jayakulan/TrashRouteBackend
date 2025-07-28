<?php
if (session_status() === PHP_SESSION_NONE) {
    // Configure session for cross-origin requests
    ini_set('session.cookie_samesite', 'None');
    ini_set('session.cookie_secure', '0'); // Set to 1 in production with HTTPS
    ini_set('session.cookie_httponly', '1');
    session_start();
}

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
require_once '../../utils/session_auth_middleware.php';
require_once '../../classes/Customer.php';
require_once '../../classes/Company.php';
require_once '../../classes/Admin.php';

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
    // Fetch user by email to get role
    $query = "SELECT role FROM registered_users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        Helpers::sendError('Invalid email or password');
    }
    $role = $user['role'];
    if ($role === 'customer') {
        $result = Customer::login($db, $email, $password);
    } elseif ($role === 'company') {
        $result = Company::login($db, $email, $password);
    } elseif ($role === 'admin') {
        $result = Admin::login($db, $email, $password);
    } else {
        Helpers::sendError('Invalid role in database');
    }
    $user = $result['user'];
    $profile = $result['profile'];
    unset($user['password_hash']);
    $token = Helpers::generateToken($user['user_id'], $user['role']);
    
    // Set session data for all users (admin, company, and customer)
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'] ?? null;
    $_SESSION['name'] = $user['name'] ?? null;
    $_SESSION['login_time'] = time();
    
    // Debug: Log session data after setting
    error_log("Login - Session data set: " . json_encode($_SESSION));
    error_log("Login - Session ID: " . session_id());
    
    Helpers::sendResponse([
        'user' => $user,
        'profile' => $profile,
        'token' => $token
    ], 200, 'Login successful');
} catch (Exception $e) {
    Helpers::sendError('Login failed: ' . $e->getMessage(), 401);
}
