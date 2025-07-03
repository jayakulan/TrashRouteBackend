<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'vjayakulan@gmail.com'; // Your Gmail address
    $mail->Password = 'atvnribkexkvghbl'; // Your Gmail app password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('vjayakulan@gmail.com', 'PHPMailer Test');
    $mail->addAddress('vjayakulan@gmail.com'); // Send to your own email for testing
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test Email';
    $mail->Body    = '<p>This is a <strong>test email</strong> sent using PHPMailer.</p>';

    $mail->send();
    echo 'Test email sent successfully!';
} catch (Exception $e) {
    echo 'Mailer Error: ' . $mail->ErrorInfo;
}
?> 