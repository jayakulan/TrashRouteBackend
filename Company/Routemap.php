<?php
ob_clean(); // Clear any accidental output buffer
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

    // Get company_id (and optionally route_id)
    $company_id = $_POST['company_id'] ?? $_GET['company_id'] ?? null;
    $route_id = $_POST['route_id'] ?? $_GET['route_id'] ?? null;
    if (!$company_id) {
        echo json_encode(['success' => false, 'message' => 'Missing company_id']);
        exit;
    }

    // Find the latest route for this company if route_id not provided
    if (!$route_id) {
        $query = "SELECT route_id FROM routes WHERE company_id = :company_id ORDER BY generated_at DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
        $stmt->execute();
        $route = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$route) {
            echo json_encode(['success' => false, 'message' => 'No route found for this company']);
            exit;
        }
        $route_id = $route['route_id'];
    }

    // Get all pickup requests for this route (using route_request_mapping)
    $query = "SELECT pr.request_id, pr.customer_id, pr.waste_type, pr.quantity, pr.status, ru.name as customer_name, ru.address, ru.contact_number
              FROM route_request_mapping rrm
              INNER JOIN pickup_requests pr ON rrm.request_id = pr.request_id
              INNER JOIN customers c ON pr.customer_id = c.customer_id
              INNER JOIN registered_users ru ON c.customer_id = ru.user_id
              WHERE rrm.route_id = :route_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':route_id', $route_id, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$requests || count($requests) === 0) {
        echo json_encode(['success' => false, 'message' => 'No pickup requests found for this route']);
        exit;
    }

    $households = [];
    $totalQuantity = 0;
    foreach ($requests as $row) {
        $households[] = [
            'id' => $row['customer_id'],
            'request_id' => $row['request_id'], // Add request_id for frontend use
            'address' => $row['address'] ?? 'N/A',
            'contact' => $row['customer_name'] ?? 'N/A',
            'notes' => "Waste Type: {$row['waste_type']}, Quantity: {$row['quantity']} kg",
            'collected' => $row['status'] === 'Completed',
            'status' => $row['status'],
            'contact_number' => $row['contact_number'],
        ];
        $totalQuantity += (int)$row['quantity'];
    }
    $customerCount = count($households);
    $approximateQuantity = $totalQuantity;

    echo json_encode([
        'success' => true,
        'households' => $households,
        'customerCount' => $customerCount,
        'approximateQuantity' => $approximateQuantity
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
} 