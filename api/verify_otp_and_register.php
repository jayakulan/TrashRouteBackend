<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

$allowed_origins = [
    "http://localhost:5173",
    "http://localhost:5174",
    "http://localhost:5180"
];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
} else {
    header("Access-Control-Allow-Origin: http://localhost:5180");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::sendError('Invalid request method');
}
$data = json_decode(file_get_contents('php://input'), true);
$email = isset($data['email']) ? Helpers::sanitize($data['email']) : '';
$otp_code = isset($data['otp']) ? Helpers::sanitize($data['otp']) : '';
if (empty($email) || empty($otp_code)) {
    Helpers::sendError('Email and OTP are required');
}
try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        Helpers::sendError('Database connection failed', 500);
    }
    // Find user by email
    $stmt = $db->prepare('SELECT user_id FROM registered_users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        Helpers::sendError('User not found');
    }
    $user_id = $user['user_id'];
    // Check OTP
    $stmt = $db->prepare('SELECT * FROM otp WHERE user_id = ? AND otp_code = ? AND is_used = 0 AND expiration_time > NOW()');
    $stmt->execute([$user_id, $otp_code]);
    $otpRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$otpRow) {
        Helpers::sendError('Invalid or expired OTP');
    }
    // Mark OTP as used
    $db->prepare('UPDATE otp SET is_used = 1 WHERE otp_id = ?')->execute([$otpRow['otp_id']]);
    // Activate user
    $db->prepare('UPDATE registered_users SET disable_status = "active" WHERE user_id = ?')->execute([$user_id]);
    Helpers::sendResponse(null, 201, 'Registration successful');
} catch (Exception $e) {
    Helpers::sendError('Registration failed: ' . $e->getMessage(), 500);
} 