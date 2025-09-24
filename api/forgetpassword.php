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
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Company.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
$action = $input['action'] ?? '';
$email = isset($input['email']) ? Helpers::sanitize($input['email']) : '';

if (empty($email) || !Helpers::validateEmail($email)) {
    Helpers::sendError('Valid email is required');
}

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    Helpers::sendError('Database connection failed', 500);
}

try {
    // Find user by email
    $stmt = $db->prepare('SELECT user_id FROM registered_users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        Helpers::sendError('User not found');
    }
    $user_id = $user['user_id'];
    if ($action === 'send_otp') {
        // Generate OTP and store with 1 hour expiry
        $otp = rand(100000, 999999);
        // Use current timestamp + 1 hour for proper expiration calculation
        $current_time = time();
        $expiration_time = date('Y-m-d H:i:s', $current_time + 3600); // 3600 seconds = 1 hour
        
        // Debug logging
        error_log("OTP Debug - Current time: " . date('Y-m-d H:i:s', $current_time));
        error_log("OTP Debug - Expiration time: " . $expiration_time);
        error_log("OTP Debug - User ID: " . $user_id . ", OTP: " . $otp);
        
        $stmt = $db->prepare('INSERT INTO otp (user_id, otp_code, expiration_time) VALUES (?, ?, ?)');
        $stmt->execute([$user_id, $otp, $expiration_time]);
        // Send OTP email
        $mail = new PHPMailer(true);
        try {
            // Configure SMTP using the new email config
            EmailConfig::configurePHPMailer($mail);
            
            // Set sender and recipient
            $mail->setFrom(EmailConfig::EMAIL_USERNAME, EmailConfig::EMAIL_FROM_NAME . ' Password Reset');
            $mail->addAddress($email);
            
            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP for Password Reset';
            $mail->Body = "<p>Your OTP for password reset is: <strong style='color:green;'>$otp</strong></p><p>This code will expire in 1 hour.</p>";
            
            $mail->send();
            Helpers::sendResponse(null, 200, 'OTP sent to your email');
        } catch (Exception $e) {
            // Enhanced error logging
            error_log("SMTP Error in forgetpassword.php: " . $e->getMessage());
            error_log("SMTP Debug Info: " . $mail->ErrorInfo);
            Helpers::sendError('Failed to send OTP email. Please check your email configuration.');
        }
    } elseif ($action === 'verify_otp') {
        $otp_code = isset($input['otp']) ? Helpers::sanitize($input['otp']) : '';
        if (empty($otp_code)) {
            Helpers::sendError('OTP is required');
        }
        // Check OTP
        $stmt = $db->prepare('SELECT * FROM otp WHERE user_id = ? AND otp_code = ? AND is_used = 0 AND expiration_time > NOW()');
        $stmt->execute([$user_id, $otp_code]);
        $otpRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug logging for OTP verification
        error_log("OTP Verification Debug - User ID: " . $user_id . ", OTP Code: " . $otp_code);
        if ($otpRow) {
            error_log("OTP Verification Debug - Found OTP with expiration: " . $otpRow['expiration_time']);
        } else {
            // Check if OTP exists but is expired
            $stmt_check = $db->prepare('SELECT * FROM otp WHERE user_id = ? AND otp_code = ? AND is_used = 0');
            $stmt_check->execute([$user_id, $otp_code]);
            $expiredOtp = $stmt_check->fetch(PDO::FETCH_ASSOC);
            if ($expiredOtp) {
                error_log("OTP Verification Debug - OTP exists but expired. Expiration: " . $expiredOtp['expiration_time'] . ", Current DB time: " . date('Y-m-d H:i:s'));
            } else {
                error_log("OTP Verification Debug - No OTP found for user_id: " . $user_id . ", otp_code: " . $otp_code);
            }
        }
        
        if (!$otpRow) {
            Helpers::sendError('Invalid or expired OTP');
        }
        // Mark OTP as used
        $db->prepare('UPDATE otp SET is_used = 1 WHERE otp_id = ?')->execute([$otpRow['otp_id']]);
        Helpers::sendResponse(null, 200, 'OTP verified');
    } elseif ($action === 'verify_otp_and_reset') {
        $otp_code = isset($input['otp']) ? Helpers::sanitize($input['otp']) : '';
        $new_password = $input['new_password'] ?? '';
        $confirm_password = $input['confirm_password'] ?? '';
        if (empty($otp_code) || empty($new_password) || empty($confirm_password)) {
            Helpers::sendError('OTP and new passwords are required');
        }
        if ($new_password !== $confirm_password) {
            Helpers::sendError('Passwords do not match');
        }
        // Check OTP
        $stmt = $db->prepare('SELECT * FROM otp WHERE user_id = ? AND otp_code = ? AND is_used = 0 AND expiration_time > NOW()');
        $stmt->execute([$user_id, $otp_code]);
        $otpRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$otpRow) {
            Helpers::sendError('Invalid or expired OTP');
        }
        // Mark OTP as used
        $db->prepare('UPDATE otp SET is_used = 1 WHERE otp_id = ?')->execute([$otpRow['otp_id']]);
        // Update password
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $db->prepare('UPDATE registered_users SET password_hash = ? WHERE user_id = ?')->execute([$new_password_hash, $user_id]);
        Helpers::sendResponse(null, 200, 'Password reset successful');
    } else {
        Helpers::sendError('Invalid action');
    }
} catch (Exception $e) {
    Helpers::sendError($e->getMessage());
} 