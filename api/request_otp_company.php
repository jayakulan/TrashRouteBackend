<?php
header("Access-Control-Allow-Origin: http://localhost:5175");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email required']);
    exit;
}

$otp = rand(100000, 999999);
session_start();
$_SESSION['company_otp'] = $otp;
$_SESSION['company_email'] = $email;

require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer();
$mail->isSMTP();
$mail->Host = 'smtp.example.com'; // Change to your SMTP server
$mail->SMTPAuth = true;
$mail->Username = 'vjayakulan@gmail.com'; // Change to your SMTP username
$mail->Password = 'atvnribkexkvghbl'; // Change to your SMTP password
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom('vjayakulan@gmail.com', 'Your App');
$mail->addAddress($email);
$mail->Subject = 'Your OTP Code';
$mail->Body = "Your OTP code is: $otp";

if ($mail->send()) {
    echo json_encode(['success' => true, 'message' => 'OTP sent']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
} 