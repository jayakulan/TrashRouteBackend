<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../utils/session_auth_middleware.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check company authentication
SessionAuthMiddleware::requireCompanyAuth();

try {
    $input = json_decode(file_get_contents('php://input'), true);



    // Validate required fields
    if (
        !isset($input['request_id']) ||
        !isset($input['company_id']) ||
        !isset($input['pickup_completed']) ||
        !isset($input['rating'])
    ) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $request_id = intval($input['request_id']);
    $company_id = intval($input['company_id']);
    $pickup_completed = (bool)$input['pickup_completed'];
    $rating = intval($input['rating']);
    $comment = isset($input['comment']) ? trim($input['comment']) : null;
    $entered_otp = isset($input['entered_otp']) ? trim($input['entered_otp']) : null;

    // Validate that company_id exists in companies table
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT company_id FROM companies WHERE company_id = :company_id");
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        echo json_encode(['success' => false, 'message' => 'Invalid company_id: ' . $company_id . '. Company does not exist in database.']);
        exit;
    }

    // Optional: Check if OTP was verified in pickup_requests
    $pickup_verified = false;
    if ($entered_otp) {
        $stmt = $db->prepare("SELECT otp, otp_verified FROM pickup_requests WHERE request_id = :request_id");
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->execute();
        $pickup = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pickup && $pickup['otp_verified']) {
            $pickup_verified = true;
        }
    }

    // Insert feedback
    $stmt = $db->prepare("INSERT INTO company_feedback 
        (request_id, company_id, entered_otp, pickup_verified, pickup_completed, rating, comment)
        VALUES (:request_id, :company_id, :entered_otp, :pickup_verified, :pickup_completed, :rating, :comment)
    ");
    $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindParam(':entered_otp', $entered_otp);
    $stmt->bindParam(':pickup_verified', $pickup_verified, PDO::PARAM_BOOL);
    $stmt->bindParam(':pickup_completed', $pickup_completed, PDO::PARAM_BOOL);
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindParam(':comment', $comment);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit feedback']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 