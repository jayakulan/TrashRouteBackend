<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Get waste_type from POST or GET
    $waste_type = $_POST['waste_type'] ?? $_GET['waste_type'] ?? null;
    if (!$waste_type) {
        echo json_encode(['success' => false, 'message' => 'Missing waste_type']);
        exit;
    }

    // Fetch unique customers and total quantity for this waste type
    $query = "SELECT customer_id, quantity FROM pickup_requests WHERE waste_type = :waste_type";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':waste_type', $waste_type, PDO::PARAM_STR);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$requests || count($requests) === 0) {
        echo json_encode(['success' => false, 'message' => 'No pickup requests found for this waste type']);
        exit;
    }

    $uniqueCustomers = [];
    $totalQuantity = 0;
    foreach ($requests as $row) {
        $uniqueCustomers[$row['customer_id']] = true;
        $totalQuantity += (int)$row['quantity'];
    }
    $customerCount = count($uniqueCustomers);
    $approximateQuantity = $totalQuantity;

    echo json_encode([
        'success' => true,
        'customerCount' => $customerCount,
        'approximateQuantity' => $approximateQuantity
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
} 