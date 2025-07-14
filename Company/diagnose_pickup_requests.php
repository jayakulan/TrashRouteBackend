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

    // 1. Show all pickup requests for 'Plastic'
    $query = "SELECT * FROM pickup_requests WHERE waste_type LIKE '%Plastic%' ORDER BY request_id DESC LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Show all route mappings for those requests
    $requestIds = array_column($requests, 'request_id');
    $in = str_repeat('?,', count($requestIds) - 1) . '?';
    $mappings = [];
    if (count($requestIds) > 0) {
        $query2 = "SELECT * FROM route_request_mapping WHERE request_id IN ($in)";
        $stmt2 = $db->prepare($query2);
        $stmt2->execute($requestIds);
        $mappings = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Show the status of those requests
    $statuses = [];
    if (count($requestIds) > 0) {
        $query3 = "SELECT request_id, status FROM pickup_requests WHERE request_id IN ($in)";
        $stmt3 = $db->prepare($query3);
        $stmt3->execute($requestIds);
        $statuses = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'mappings' => $mappings,
        'statuses' => $statuses
    ], JSON_PRETTY_PRINT);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
} 