<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data (expects JSON or x-www-form-urlencoded)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$user_id = isset($input['user_id']) ? intval($input['user_id']) : null;
$otp_code = isset($input['otp_code']) ? trim($input['otp_code']) : null;
$expiration_time = isset($input['expiration_time']) ? trim($input['expiration_time']) : null;

if (!$user_id || !$otp_code || !$expiration_time) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    $query = "INSERT INTO otp (user_id, otp_code, expiration_time) VALUES (:user_id, :otp_code, :expiration_time)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':otp_code', $otp_code);
    $stmt->bindParam(':expiration_time', $expiration_time);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'OTP stored successfully']);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
} 