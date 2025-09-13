<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// --- CORS setup ---
$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:5175'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // If origin is not allowed, deny or fallback
    header("Access-Control-Allow-Origin: http://localhost:5173");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::sendError('Invalid request method', 405);
}
// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    Helpers::sendError('Invalid JSON input');
}
// Basic required fields check
$required_fields = ['name', 'email', 'password', 'contact_number', 'address'];
$missing = [];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        $missing[] = $field;
    }
}
if (count($missing) > 0) {
    Helpers::sendError('Missing fields: ' . implode(', ', $missing));
}
$name = Helpers::sanitize($input['name']);
$email = Helpers::sanitize($input['email']);
$password = $input['password'];  // Do NOT sanitize passwords
$contact_number = Helpers::sanitize($input['contact_number']);
$address = Helpers::sanitize($input['address']);
// Validate email format
if (!Helpers::validateEmail($email)) {
    Helpers::sendError('Invalid email format');
}
// Validate password length
if (strlen($password) < 6) {
    Helpers::sendError('Password must be at least 6 characters');
}
try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        Helpers::sendError('Database connection failed');
    }
    // Check if user already exists
    $stmt = $db->prepare('SELECT user_id FROM registered_users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        Helpers::sendError('Email already registered');
    }
    // Insert user as pending
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'customer';
    $stmt = $db->prepare('INSERT INTO registered_users (name, email, password_hash, role, contact_number, address, disable_status) VALUES (?, ?, ?, ?, ?, ?, "pending")');
    $stmt->execute([$name, $email, $password_hash, $role, $contact_number, $address]);
    $user_id = $db->lastInsertId();
    // Generate and store OTP
    $otp = Helpers::generateOTP(6);
    $expiration_time = date('Y-m-d H:i:s', strtotime('+10 hours'));
    $stmt = $db->prepare('INSERT INTO otp (user_id, otp_code, expiration_time) VALUES (?, ?, ?)');
    $stmt->execute([$user_id, $otp, $expiration_time]);
    // Send OTP email
    $mail = new PHPMailer(true);
    try {
        // Configure SMTP using the new email config
        EmailConfig::configurePHPMailer($mail);
        
        // Set sender and recipient
        $mail->setFrom(EmailConfig::EMAIL_USERNAME, EmailConfig::EMAIL_FROM_NAME . ' OTP');
        $mail->addAddress($email, $name);
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Verification Code';
        $mail->Body = "<p>Hello <strong>$name</strong>,</p><p>Your OTP code is: <strong style='color:green;'>$otp</strong></p><p>This code will expire in 10 hours.</p>";
        
        $mail->send();
        Helpers::sendResponse(null, 200, 'OTP sent successfully');
    } catch (Exception $e) {
        // Enhanced error logging
        error_log("SMTP Error in request_otp.php: " . $e->getMessage());
        error_log("SMTP Debug Info: " . $mail->ErrorInfo);
        Helpers::sendError('Failed to send OTP email. Please check your email configuration.');
    }
} catch (Exception $e) {
    Helpers::sendError('Failed: ' . $e->getMessage());
}