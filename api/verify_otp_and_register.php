<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Company.php';

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

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::sendError('Invalid request method');
}

$data = json_decode(file_get_contents('php://input'), true);
$email = isset($data['email']) ? Helpers::sanitize($data['email']) : '';
$otp = isset($data['otp']) ? Helpers::sanitize($data['otp']) : '';

if (empty($email) || empty($otp)) {
    Helpers::sendError('Email and OTP are required');
}

if (!isset($_SESSION['otp_' . $email]) || !isset($_SESSION['pending_registration_' . $email])) {
    Helpers::sendError('No OTP or registration found for this email');
}

if ($_SESSION['otp_' . $email] !== $otp) {
    Helpers::sendError('Invalid OTP');
}

$reg = $_SESSION['pending_registration_' . $email];

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        Helpers::sendError('Database connection failed', 500);
    }
    Customer::verifyOtpAndRegister($db, $email, $otp);
    Helpers::sendResponse(null, 201, 'Registration successful');
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    Helpers::sendError('Registration failed: ' . $e->getMessage(), 500);
} 