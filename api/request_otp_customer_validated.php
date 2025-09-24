<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../utils/customer_validator.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- CORS setup ---
$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:5175',
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
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    Helpers::sendError('Invalid JSON input: ' . json_last_error_msg());
}

if (!$input) {
    Helpers::sendError('No input data received');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        Helpers::sendError('Database connection failed');
    }
    
    // Use CustomerValidator to validate and sanitize data
    $validation_result = CustomerValidator::processCustomerRegistration($input, $db);
    
    if (!$validation_result['is_valid']) {
        // Log validation errors for debugging
        error_log('Customer registration validation failed: ' . json_encode($validation_result['errors']));
        error_log('Input data: ' . json_encode($input));
        
        // Return detailed validation errors
        $error_messages = [];
        foreach ($validation_result['errors'] as $field => $errors) {
            $error_messages[$field] = $errors[0]; // Take the first error for each field
        }
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $error_messages
        ]);
        exit();
    }
    
    $sanitized_data = $validation_result['sanitized_data'];
    
    // Insert user as pending
    $password_hash = password_hash($sanitized_data['password'], PASSWORD_DEFAULT);
    $role = 'customer';
    
    $stmt = $db->prepare('INSERT INTO registered_users (name, email, password_hash, role, contact_number, address, disable_status) VALUES (?, ?, ?, ?, ?, ?, "pending")');
    $stmt->execute([
        $sanitized_data['name'],
        $sanitized_data['email'],
        $password_hash,
        $role,
        $sanitized_data['contact_number'],
        $sanitized_data['address']
    ]);
    
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
        $mail->addAddress($sanitized_data['email'], $sanitized_data['name']);
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Verification Code';
        $mail->Body = "<p>Hello <strong>{$sanitized_data['name']}</strong>,</p><p>Your OTP code is: <strong style='color:green;'>$otp</strong></p><p>This code will expire in 10 hours.</p>";
        
        $mail->send();
        Helpers::sendResponse(null, 200, 'OTP sent successfully');
    } catch (Exception $e) {
        // Enhanced error logging
        error_log("SMTP Error in request_otp_customer_validated.php: " . $e->getMessage());
        error_log("SMTP Debug Info: " . $mail->ErrorInfo);
        Helpers::sendError('Failed to send OTP email. Please check your email configuration.');
    }
    
} catch (Exception $e) {
    Helpers::sendError('Failed: ' . $e->getMessage());
}
?> 