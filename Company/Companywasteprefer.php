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

    // Only show pickup requests for this waste type that are NOT assigned to another company (not in an active route with status 'Accepted' or 'Completed')
    $query = "
        SELECT pr.customer_id, pr.quantity
        FROM pickup_requests pr
        WHERE pr.waste_type LIKE :waste_type
        AND pr.status = 'Request received'
        AND pr.request_id NOT IN (
            SELECT rrm.request_id
            FROM route_request_mapping rrm
            INNER JOIN routes r ON rrm.route_id = r.route_id
            WHERE r.is_accepted = 1 AND (
                SELECT status FROM pickup_requests WHERE request_id = rrm.request_id
            ) IN ('Accepted', 'Completed')
        )
    ";
    $stmt = $db->prepare($query);
    $likeWasteType = $waste_type . '%';
    $stmt->bindParam(':waste_type', $likeWasteType, PDO::PARAM_STR);
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