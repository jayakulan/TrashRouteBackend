<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../utils/company_validator.php';
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
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    Helpers::sendError('Invalid JSON input');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        Helpers::sendError('Database connection failed');
    }
    
    // Use CompanyValidator to validate and sanitize data
    $validation_result = CompanyValidator::processCompanyRegistration($input, $db);
    
    if (!$validation_result['is_valid']) {
        // Log validation errors for debugging
        error_log('Company registration validation failed: ' . json_encode($validation_result['errors']));
        error_log('Input data: ' . json_encode($input));
        
        // Return detailed validation errors
        $error_messages = [];
        foreach ($validation_result['errors'] as $field => $errors) {
            $error_messages[$field] = $errors[0]; // Take the first error for each field
        }
        Helpers::sendError('Validation failed', 400, $error_messages);
    }
    
    $sanitized_data = $validation_result['sanitized_data'];
    
    // Insert user as pending
    $password_hash = password_hash($sanitized_data['password'], PASSWORD_DEFAULT);
    $role = 'company';
    
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
    
    // Insert into companies table
    $stmt = $db->prepare('INSERT INTO companies (company_id, company_reg_number) VALUES (?, ?)');
    $stmt->execute([$user_id, $sanitized_data['company_reg_number']]);
    
    // Generate and store OTP
    $otp = Helpers::generateOTP(6);
    $expiration_time = date('Y-m-d H:i:s', time() + 36000); // 10 hours = 36000 seconds
    
    $stmt = $db->prepare('INSERT INTO otp (user_id, otp_code, expiration_time) VALUES (?, ?, ?)');
    $stmt->execute([$user_id, $otp, $expiration_time]);
    
    // Send OTP email
    $mail = new PHPMailer(true);
    // Configure SMTP using the new email config
    EmailConfig::configurePHPMailer($mail);
    
    // Set sender
    $mail->setFrom(EmailConfig::EMAIL_USERNAME, EmailConfig::EMAIL_FROM_NAME . ' OTP');
    $mail->addAddress($sanitized_data['email'], $sanitized_data['name']);
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Verification Code';
    $mail->Body = "<p>Hello <strong>{$sanitized_data['name']}</strong>,</p><p>Your OTP code is: <strong style='color:green;'>$otp</strong></p><p>This code will expire in 10 hours.</p>";
    $mail->send();
    
    Helpers::sendResponse(null, 200, 'OTP sent successfully');
    
} catch (Exception $e) {
    Helpers::sendError('Failed: ' . $e->getMessage());
}
?> 