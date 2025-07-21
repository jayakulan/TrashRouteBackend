<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
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
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
$name = isset($input['name']) ? Helpers::sanitize($input['name']) : '';
$email = isset($input['email']) ? Helpers::sanitize($input['email']) : '';
$contact_number = isset($input['contact_number']) ? Helpers::sanitize($input['contact_number']) : '';
$address = isset($input['address']) ? Helpers::sanitize($input['address']) : '';

if (!$user_id || !$name || !$email || !$contact_number || !$address) {
    Helpers::sendError('All fields are required');
}
if (!Helpers::validateEmail($email)) {
    Helpers::sendError('Invalid email format');
}

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    Helpers::sendError('Database connection failed', 500);
}

try {
    // Check if email is already used by another user
    $query = "SELECT user_id FROM registered_users WHERE email = :email AND user_id != :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    if ($stmt->fetch()) {
        Helpers::sendError('Email already in use by another account');
    }

    $company = new Company($user_id, $email, $name, null);
    $company->updateProfile($db, $user_id, $name, $email, $contact_number, $address);
    Helpers::sendResponse(null, 200, 'Profile updated successfully');
} catch (Exception $e) {
    Helpers::sendError('Profile update failed: ' . $e->getMessage(), 500);
} 