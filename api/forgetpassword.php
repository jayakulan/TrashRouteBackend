<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

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

if ($action === 'send_otp') {
    // Check if email exists
    $query = "SELECT user_id FROM registered_users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();
    if (!$user) {
        Helpers::sendError('Email not registered');
    }
    // Generate OTP
    $otp = Helpers::generateOTP(6);
    $_SESSION['forgot_otp_' . $email] = $otp;
    $_SESSION['forgot_email_' . $email] = $email;
    // Send OTP email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'trashroute.wastemanagement@gmail.com';
        $mail->Password = 'axlgbzwognxntkrl';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('trashroute.wastemanagement@gmail.com', 'TrashRoute Password Reset');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Password Reset';
        $mail->Body = "<p>Your OTP for password reset is: <strong style='color:green;'>$otp</strong></p><p>This code will expire in 5 minutes.</p>";
        $mail->send();
        Helpers::sendResponse(null, 200, 'OTP sent to your email');
    } catch (Exception $e) {
        Helpers::sendError('Failed to send OTP email: ' . $mail->ErrorInfo);
    }
} elseif ($action === 'verify_otp') {
    $otp = isset($input['otp']) ? Helpers::sanitize($input['otp']) : '';
    if (empty($otp)) {
        Helpers::sendError('OTP is required');
    }
    if (!isset($_SESSION['forgot_otp_' . $email]) || $_SESSION['forgot_otp_' . $email] !== $otp) {
        Helpers::sendError('Invalid or expired OTP');
    }
    // OTP is valid
    Helpers::sendResponse(null, 200, 'OTP verified');
} elseif ($action === 'verify_otp_and_reset') {
    $otp = isset($input['otp']) ? Helpers::sanitize($input['otp']) : '';
    $new_password = $input['new_password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';
    if (empty($otp) || empty($new_password) || empty($confirm_password)) {
        Helpers::sendError('OTP and new passwords are required');
    }
    if ($new_password !== $confirm_password) {
        Helpers::sendError('Passwords do not match');
    }
    if (!isset($_SESSION['forgot_otp_' . $email]) || $_SESSION['forgot_otp_' . $email] !== $otp) {
        Helpers::sendError('Invalid or expired OTP');
    }
    // Update password
    $hashed_password = Helpers::hashPassword($new_password);
    $query = "UPDATE registered_users SET password_hash = :password_hash WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':password_hash', $hashed_password);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    unset($_SESSION['forgot_otp_' . $email]);
    unset($_SESSION['forgot_email_' . $email]);
    Helpers::sendResponse(null, 200, 'Password reset successful');
} else {
    Helpers::sendError('Invalid action');
} 