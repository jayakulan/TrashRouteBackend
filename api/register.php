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
require_once '../classes/Customer.php';
require_once '../classes/Company.php';

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
    $otp = Customer::savePendingRegistration($input);
    // Send OTP email (keep as is)
    // ...
    Helpers::sendResponse(null, 200, 'OTP sent successfully');
} catch (Exception $e) {
    Helpers::sendError($e->getMessage());
}
?> 