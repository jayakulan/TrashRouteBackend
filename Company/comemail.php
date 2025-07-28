<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../utils/session_auth_middleware.php';
SessionAuthMiddleware::requireCompanyAuth();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../config/database.php';

// Get POST data: customer_email, company_name
$customer_email = $_POST['customer_email'] ?? null;
$company_name = $_POST['company_name'] ?? null;

if (!$customer_email || !$company_name) {
    // Do not echo or set response code when included
    return;
}

// Generate a collection date within 3 days from now
$collection_date = date('Y-m-d', strtotime('+'.rand(0,2).' days'));
// Generate a random time between 4pm and 6pm
$hour = rand(16, 17); // 16 = 4pm, 17 = 5pm
$minute = rand(0, 59);
$collection_time = sprintf('%02d:%02d', $hour, $minute);
$collection_datetime = $collection_date . ' ' . $collection_time;

// Email subject and body
$subject = "Your Trash Collection Request Has Been Accepted!";
$body = "Dear Customer,<br><br>"
      . "Your trash collection request has been accepted by <b>$company_name</b>.<br>"
      . "The collection will take place within 3 days from now on <b>$collection_date</b> at <b>$collection_time</b> (between 4pm and 6pm).<br><br>"
      . "Thank you for using TrashRoute!<br>";

$mail = new PHPMailer(true);
try {
    //Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; // Set your SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'trashroute.wastemanagement@gmail.com'; // Your SMTP username
    $mail->Password   = 'axlgbzwognxntkrl'; // Your SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    //Recipients
    $mail->setFrom('trashroute.wastemanagement@gmail.com', 'TrashRoute');
    $mail->addAddress($customer_email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;

    $mail->send();
    // Do not echo anything when included
    // Optionally, set a variable or log success
} catch (Exception $e) {
    // Do not echo or set response code when included
    // Optionally, log error
} 