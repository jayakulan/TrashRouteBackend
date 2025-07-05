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

// --- CORS setup ---
$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:5175',
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

// Generate OTP (6 digit)
$otp = Helpers::generateOTP(6);

// Save OTP and user data in session for later verification
$_SESSION['otp_' . $email] = $otp;
$_SESSION['pending_registration_' . $email] = [
    'name' => $name,
    'email' => $email,
    'password' => $password,
    'contact_number' => $contact_number,
    'address' => $address,
    'role' => 'customer',
];

// Send OTP email using PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'trashroute.wastemanagement@gmail.com';
    $mail->Password = 'axlgbzwognxntkrl';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('trashroute.wastemanagement@gmail.com', 'TrashRoute OTP');
    $mail->addAddress($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Verification Code';
    $mail->Body = "<p>Hello <strong>$name</strong>,</p><p>Your OTP code is: <strong style='color:green;'>$otp</strong></p><p>This code will expire in 5 minutes.</p>";

    $mail->send();

    Helpers::sendResponse(null, 200, 'OTP sent successfully');
} catch (Exception $e) {
    Helpers::sendError('Failed to send OTP email: ' . $mail->ErrorInfo);
}
