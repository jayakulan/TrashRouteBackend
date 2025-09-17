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
require_once __DIR__ . '/../config/email_config.php';

// Get POST data: customer_email, company_name, request_id
$customer_email = $_POST['customer_email'] ?? null;
$company_name = $_POST['company_name'] ?? null;
$request_id = $_POST['request_id'] ?? null;

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

// Fetch OTP from database if request_id is provided
$otp_code = null;
if ($request_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            $stmt = $db->prepare("SELECT otp FROM pickup_requests WHERE request_id = :request_id");
            $stmt->bindParam(':request_id', $request_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $otp_code = $result ? $result['otp'] : null;
            
            // If no OTP exists, generate one and store it
            if (!$otp_code) {
                $otp_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $update_stmt = $db->prepare("UPDATE pickup_requests SET otp = :otp_code, otp_verified = FALSE WHERE request_id = :request_id");
                $update_stmt->bindParam(':otp_code', $otp_code);
                $update_stmt->bindParam(':request_id', $request_id);
                $update_stmt->execute();
                error_log("Generated new OTP $otp_code for request $request_id");
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching/generating OTP for request $request_id: " . $e->getMessage());
    }
}

// Email subject and body
$subject = "Your Trash Collection Request Has Been Accepted!";
$body = "Dear Customer,<br><br>"
      . "Your trash collection request has been accepted by company <b>$company_name</b>.<br>"
      . "The collection will take place within 3 days from now on <b>$collection_date</b> at <b>$collection_time</b> (between 4pm and 6pm).<br><br>";

// Add OTP information if available
if ($otp_code) {
    $body .= "<b>Important:</b> Your pickup verification code is: <span style='color: #3a5f46; font-size: 18px; font-weight: bold;'>$otp_code</span><br>"
          . "Please provide this code to the collection team when they arrive for pickup.<br><br>";
}

$body .= "Thank you for using TrashRoute!<br>";

$mail = new PHPMailer(true);
try {
    // Configure SMTP using the new email config
    EmailConfig::configurePHPMailer($mail);
    
    // Recipients
    $mail->setFrom(EmailConfig::EMAIL_USERNAME, EmailConfig::EMAIL_FROM_NAME);
    $mail->addAddress($customer_email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;

    $mail->send();
    // Email sent successfully - log success
    $otp_info = $otp_code ? " with OTP: $otp_code" : " without OTP";
    error_log("Collection notification email sent successfully to: $customer_email$otp_info");
} catch (Exception $e) {
    // Enhanced error logging
    error_log("SMTP Error in comemail.php: " . $e->getMessage());
    error_log("SMTP Debug Info: " . $mail->ErrorInfo);
} 