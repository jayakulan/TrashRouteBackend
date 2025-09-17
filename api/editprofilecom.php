<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../utils/company_validator.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Company.php';

$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:5174',
    'http://localhost:5175',
    'http://localhost:5180',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost:5173");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::sendError('Invalid request method', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    Helpers::sendError('Invalid JSON input');
}

// Extract user_id from multiple possible field names
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
if (!$user_id && isset($input['company_id'])) {
    $user_id = intval($input['company_id']);
}

if (!$user_id) {
    Helpers::sendError('User ID is required');
}

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    Helpers::sendError('Database connection failed', 500);
}

try {
    // Debug: Log the input data
    error_log("Company Edit Profile Input: " . json_encode($input));
    error_log("Extracted user_id: " . $user_id);
    
    // Validate input data
    $validation_errors = CompanyValidator::validateEditProfileData($input, $db, $user_id);
    
    if (!empty($validation_errors)) {
        error_log("Company Validation Errors: " . json_encode($validation_errors));
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validation_errors
        ]);
        exit();
    }
    
    // Sanitize data
    $sanitized_data = CompanyValidator::sanitizeEditProfileData($input);
    
    // Get current user email from database
    $query = "SELECT email FROM registered_users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        Helpers::sendError('User not found');
    }

    $company = new Company($user_id, $user['email'], $sanitized_data['name'], null);
    $company->updateProfile($db, $user_id, $sanitized_data['name'], $user['email'], $sanitized_data['contact_number'], $sanitized_data['address']);
    Helpers::sendResponse(null, 200, 'Profile updated successfully');
} catch (Exception $e) {
    Helpers::sendError('Profile update failed: ' . $e->getMessage(), 500);
} 