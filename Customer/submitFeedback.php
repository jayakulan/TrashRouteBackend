<?php
if (session_status() === PHP_SESSION_NONE) {
    // Configure session for cross-origin requests
    ini_set('session.cookie_samesite', 'None');
    ini_set('session.cookie_secure', '0'); // Set to 1 in production with HTTPS
    ini_set('session.cookie_httponly', '1');
    session_start();
}

// CORS headers
$allowed_origins = ['http://localhost:5173', 'http://localhost:5175'];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
} else {
    header("Access-Control-Allow-Origin: http://localhost:5173");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../utils/helpers.php';
require_once '../utils/session_auth_middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::sendError('Method not allowed', 405);
}

// Check customer authentication
try {
    $customerUser = SessionAuthMiddleware::requireCustomerAuth();
    $customer_id = $customerUser['user_id'];
    error_log("submitFeedback.php - Customer authenticated: " . $customer_id);
} catch (Exception $e) {
    error_log("submitFeedback.php - Authentication failed: " . $e->getMessage());
    Helpers::sendError('Customer authentication failed: ' . $e->getMessage(), 401);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        Helpers::sendError('Database connection failed', 500);
    }

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }

    $request_id = $input['request_id'] ?? null;
    $rating = $input['rating'] ?? null;
    $comment = $input['comment'] ?? '';

    error_log("submitFeedback.php - Received data: request_id=" . $request_id . ", rating=" . $rating . ", comment=" . $comment);

    if (!$request_id || !$rating) {
        Helpers::sendError('Missing required fields: request_id and rating', 400);
    }

    // Validate rating
    if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
        Helpers::sendError('Rating must be between 1 and 5', 400);
    }

    // Check if the request belongs to this customer and is completed
    $query = "SELECT pr.request_id, pr.status, pr.customer_id, pr.waste_type 
              FROM pickup_requests pr 
              WHERE pr.request_id = :request_id AND pr.customer_id = :customer_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':request_id', $request_id);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("submitFeedback.php - Found request: " . json_encode($request));
    
    if (!$request) {
        Helpers::sendError('Pickup request not found or access denied', 404);
    }

    if ($request['status'] !== 'Completed') {
        Helpers::sendError('Feedback can only be submitted for completed pickups', 400);
    }

    // Check if feedback already exists for this request
    $checkQuery = "SELECT feedback_id FROM customer_feedback WHERE request_id = :request_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':request_id', $request_id);
    $checkStmt->execute();
    
    if ($checkStmt->fetch()) {
        Helpers::sendError('Feedback already submitted for this request', 400);
    }

    // Insert feedback with pickup_completed set to 1
    $insertQuery = "INSERT INTO customer_feedback (request_id, customer_id, pickup_completed, rating, comment, created_at) 
                    VALUES (:request_id, :customer_id, 1, :rating, :comment, NOW())";
    
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':request_id', $request_id);
    $insertStmt->bindParam(':customer_id', $customer_id);
    $insertStmt->bindParam(':rating', $rating);
    $insertStmt->bindParam(':comment', $comment);
    
    error_log("submitFeedback.php - Attempting to insert feedback for request_id: " . $request_id . ", customer_id: " . $customer_id);
    
    if ($insertStmt->execute()) {
        $feedback_id = $db->lastInsertId();
        error_log("submitFeedback.php - Feedback inserted successfully with ID: " . $feedback_id);
        
        // Verify the feedback was actually inserted
        $verifyQuery = "SELECT * FROM customer_feedback WHERE feedback_id = :feedback_id";
        $verifyStmt = $db->prepare($verifyQuery);
        $verifyStmt->bindParam(':feedback_id', $feedback_id);
        $verifyStmt->execute();
        $insertedFeedback = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        error_log("submitFeedback.php - Verified inserted feedback: " . json_encode($insertedFeedback));
        
        // Get all waste types that have feedback for this customer using JOIN
        $wasteTypesQuery = "SELECT DISTINCT pr.waste_type 
                           FROM customer_feedback cf 
                           JOIN pickup_requests pr ON cf.request_id = pr.request_id 
                           WHERE cf.customer_id = :customer_id";
        
        $wasteTypesStmt = $db->prepare($wasteTypesQuery);
        $wasteTypesStmt->bindParam(':customer_id', $customer_id);
        $wasteTypesStmt->execute();
        
        $wasteTypesWithFeedback = $wasteTypesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("submitFeedback.php - Waste types with feedback: " . json_encode($wasteTypesWithFeedback));

        echo json_encode([
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'data' => [
                'feedback_id' => $feedback_id,
                'request_id' => $request_id,
                'rating' => $rating,
                'comment' => $comment,
                'waste_type' => $request['waste_type'],
                'waste_types_with_feedback' => $wasteTypesWithFeedback
            ]
        ]);
    } else {
        error_log("submitFeedback.php - Failed to insert feedback: " . json_encode($insertStmt->errorInfo()));
        Helpers::sendError('Failed to submit feedback', 500);
    }

} catch (Exception $e) {
    error_log("Error in submitFeedback.php: " . $e->getMessage());
    Helpers::sendError('Failed to submit feedback: ' . $e->getMessage(), 500);
}
?>
