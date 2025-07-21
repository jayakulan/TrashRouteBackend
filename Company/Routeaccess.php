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

    // Get company_id from POST (or GET for testing)
    $company_id = $_POST['company_id'] ?? $_GET['company_id'] ?? null;
    if (!$company_id) {
        echo json_encode(['success' => false, 'message' => 'Missing company_id']);
        exit;
    }

    // Get optional waste_type from POST or GET
    $waste_type = $_POST['waste_type'] ?? $_GET['waste_type'] ?? null;

    // Build query with optional waste_type filter
    if ($waste_type) {
        $query = "SELECT customer_id, quantity FROM pickup_requests WHERE status = 'Request received' AND waste_type = :waste_type";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':waste_type', $waste_type);
    } else {
        $query = "SELECT customer_id, quantity FROM pickup_requests WHERE status = 'Request received'";
        $stmt = $db->prepare($query);
    }
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$requests || count($requests) === 0) {
        echo json_encode(['success' => false, 'message' => 'No pickup requests found']);
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