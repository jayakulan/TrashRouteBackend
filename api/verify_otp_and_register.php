<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

    $db->beginTransaction();
    $hashed_password = Helpers::hashPassword($reg['password']);
    $query = "INSERT INTO registered_users (name, email, password_hash, contact_number, address, role) VALUES (:name, :email, :password_hash, :contact_number, :address, :role)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $reg['name']);
    $stmt->bindParam(':email', $reg['email']);
    $stmt->bindParam(':password_hash', $hashed_password);
    $stmt->bindParam(':contact_number', $reg['contact_number']);
    $stmt->bindParam(':address', $reg['address']);
    $stmt->bindParam(':role', $reg['role']);
    $stmt->execute();
    $user_id = $db->lastInsertId();
    // Insert into customers table
    $query = "INSERT INTO customers (customer_id) VALUES (:user_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $db->commit();
    unset($_SESSION['otp_' . $email]);
    unset($_SESSION['pending_registration_' . $email]);
    Helpers::sendResponse(null, 201, 'Registration successful');
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    Helpers::sendError('Registration failed: ' . $e->getMessage(), 500);
} 