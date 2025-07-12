<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Get POST data
    $company_id = $_POST['company_id'] ?? null;
    $card_number = $_POST['card_number'] ?? null;
    $cardholder_name = $_POST['cardholder_name'] ?? null;
    $expiry_date = $_POST['expiry_date'] ?? null;
    $pin_number = $_POST['pin_number'] ?? null;
    $amount = $_POST['amount'] ?? null;

    // Validate required fields
    if (!$company_id || !$card_number || !$cardholder_name || !$expiry_date || !$pin_number || !$amount) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Fetch the latest pickup request_id (for demo: just get the latest overall)
    $queryPickup = "SELECT request_id FROM pickup_requests ORDER BY request_id DESC LIMIT 1";
    $stmtPickup = $db->prepare($queryPickup);
    $stmtPickup->execute();
    $pickup = $stmtPickup->fetch(PDO::FETCH_ASSOC);

    if (!$pickup) {
        echo json_encode(['success' => false, 'message' => 'No pickup request found to associate with route']);
        exit;
    }
    $request_id = $pickup['request_id'];

    // 1. Create a new route for this company, associated with the pickup request
    $insertRoute = "INSERT INTO routes (request_id, company_id, no_of_customers, route_details) VALUES (:request_id, :company_id, 0, 'Auto-generated route after payment')";
    $stmtRoute = $db->prepare($insertRoute);
    $stmtRoute->bindParam(':request_id', $request_id, PDO::PARAM_INT);
    $stmtRoute->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    if (!$stmtRoute->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to create route']);
        exit;
    }
    $route_id = $db->lastInsertId();

    // 2. Insert payment record
    $query = "INSERT INTO payments (company_id, route_id, card_number, cardholder_name, expiry_date, pin_number, amount, payment_status) VALUES (:company_id, :route_id, :card_number, :cardholder_name, :expiry_date, :pin_number, :amount, 'Paid')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindParam(':route_id', $route_id, PDO::PARAM_INT);
    $stmt->bindParam(':card_number', $card_number, PDO::PARAM_STR);
    $stmt->bindParam(':cardholder_name', $cardholder_name, PDO::PARAM_STR);
    $stmt->bindParam(':expiry_date', $expiry_date, PDO::PARAM_STR);
    $stmt->bindParam(':pin_number', $pin_number, PDO::PARAM_STR);
    $stmt->bindParam(':amount', $amount);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Payment and route recorded successfully', 'route_id' => $route_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to record payment']);
    }
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
} 