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

    // Get all pickup requests for this route
    $query = "SELECT pr.request_id, pr.customer_id, pr.waste_type, pr.quantity, pr.status, ru.name as customer_name, ru.address, ru.contact_number
              FROM pickup_requests pr
              INNER JOIN routes r ON pr.request_id = r.request_id
              INNER JOIN customers c ON pr.customer_id = c.customer_id
              INNER JOIN registered_users ru ON c.customer_id = ru.user_id
              WHERE r.route_id = :route_id";
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
            'address' => $row['address'] ?? 'N/A',
            'contact' => $row['customer_name'] ?? 'N/A',
            'notes' => $row['waste_type'] ?? '',
            'collected' => $row['status'] === 'Completed',
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