<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Find all pickup requests with status 'Accepted' that are older than 3 days and not completed
    $query = "
        UPDATE pickup_requests pr
        INNER JOIN route_request_mapping rrm ON pr.request_id = rrm.request_id
        INNER JOIN routes r ON rrm.route_id = r.route_id
        SET pr.status = 'Request received'
        WHERE pr.status = 'Accepted'
        AND pr.otp_verified = 0
        AND r.accepted_at IS NOT NULL
        AND r.accepted_at < (NOW() - INTERVAL 3 DAY)
    ";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $affected = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => "Reverted $affected pickup requests to 'Request received' after 3 days."
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
} 