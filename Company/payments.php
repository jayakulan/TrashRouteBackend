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
    $waste_type = $_POST['waste_type'] ?? null;

    // Validate required fields
    if (!$company_id || !$card_number || !$cardholder_name || !$expiry_date || !$pin_number || !$amount || !$waste_type) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // First, let's check the actual column name in pickup_requests table
    $checkColumnsQuery = "SHOW COLUMNS FROM pickup_requests LIKE '%request%'";
    $stmtCheck = $db->prepare($checkColumnsQuery);
    $stmtCheck->execute();
    $columns = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);
    
    // Determine the correct column name
    $requestIdColumn = 'request_id'; // default
    foreach ($columns as $column) {
        if (strpos($column['Field'], 'request') !== false) {
            $requestIdColumn = $column['Field'];
            break;
        }
    }

    // Fetch all pickup requests for the specific waste type
    $queryPickup = "SELECT $requestIdColumn, customer_id, waste_type, quantity FROM pickup_requests WHERE waste_type = :waste_type AND status IN ('Request received', 'Pending', 'Accepted') ORDER BY $requestIdColumn ASC";
    $stmtPickup = $db->prepare($queryPickup);
    $stmtPickup->bindParam(':waste_type', $waste_type, PDO::PARAM_STR);
    $stmtPickup->execute();
    $pickupRequests = $stmtPickup->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pickupRequests)) {
        echo json_encode(['success' => false, 'message' => 'No pickup requests found for waste type: ' . $waste_type]);
        exit;
    }

    $totalCustomers = count($pickupRequests);
    $totalQuantity = array_sum(array_column($pickupRequests, 'quantity'));
    
    // Create a single route entry (routes table doesn't have request_id column)
    $routeDetails = "Route for {$waste_type} waste collection - Total Customers: {$totalCustomers}, Total Quantity: {$totalQuantity} kg";
    
    $insertRoute = "INSERT INTO routes (company_id, no_of_customers, route_details, is_accepted, accepted_at) VALUES (:company_id, :no_of_customers, :route_details, true, NOW())";
    $stmtRoute = $db->prepare($insertRoute);
    $stmtRoute->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmtRoute->bindParam(':no_of_customers', $totalCustomers, PDO::PARAM_INT);
    $stmtRoute->bindParam(':route_details', $routeDetails, PDO::PARAM_STR);
    
    if (!$stmtRoute->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to create route']);
        exit;
    }
    
    $route_id = $db->lastInsertId();

    // Create route_request_mapping table if it doesn't exist
    $createMappingTable = "
        CREATE TABLE IF NOT EXISTS route_request_mapping (
            mapping_id INT AUTO_INCREMENT PRIMARY KEY,
            route_id INT NOT NULL,
            request_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE CASCADE,
            FOREIGN KEY (request_id) REFERENCES pickup_requests($requestIdColumn) ON DELETE CASCADE,
            UNIQUE KEY unique_route_request (route_id, request_id)
        )
    ";
    $db->exec($createMappingTable);

    // Insert mapping entries for all pickup requests
    $mappingSuccess = true;
    foreach ($pickupRequests as $request) {
        $insertMapping = "INSERT INTO route_request_mapping (route_id, request_id) VALUES (:route_id, :request_id)";
        $stmtMapping = $db->prepare($insertMapping);
        $stmtMapping->bindParam(':route_id', $route_id, PDO::PARAM_INT);
        $stmtMapping->bindParam(':request_id', $request[$requestIdColumn], PDO::PARAM_INT);
        
        if (!$stmtMapping->execute()) {
            $mappingSuccess = false;
            break;
        }
    }

    if (!$mappingSuccess) {
        echo json_encode(['success' => false, 'message' => 'Failed to create route mappings']);
        exit;
    }

    // Insert payment record
    $query = "INSERT INTO payments (company_id, route_id, card_number, cardholder_name, expiry_date, pin_number, amount, payment_status) VALUES (:company_id, :route_id, :card_number, :cardholder_name, :expiry_date, :pin_number, :amount, 'Paid')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindParam(':route_id', $route_id, PDO::PARAM_INT);
    $stmt->bindParam(':card_number', $card_number, PDO::PARAM_STR);
    $stmt->bindParam(':cardholder_name', $cardholder_name, PDO::PARAM_STR);
    $stmt->bindParam(':expiry_date', $expiry_date, PDO::PARAM_STR);
    $stmt->bindParam(':pin_number', $pin_number, PDO::PARAM_STR);
    $stmt->bindParam(':amount', $amount);

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to record payment']);
        exit;
    }

    // Update pickup request status to 'Accepted' for all requests
    $requestIds = array_column($pickupRequests, $requestIdColumn);
    $placeholders = str_repeat('?,', count($requestIds) - 1) . '?';
    $updateQuery = "UPDATE pickup_requests SET status = 'Accepted' WHERE $requestIdColumn IN ($placeholders)";
    $stmtUpdate = $db->prepare($updateQuery);
    
    if (!$stmtUpdate->execute($requestIds)) {
        echo json_encode(['success' => false, 'message' => 'Failed to update pickup request status']);
        exit;
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Payment and route recorded successfully', 
        'route_id' => $route_id,
        'total_customers' => $totalCustomers,
        'total_quantity' => $totalQuantity,
        'waste_type' => $waste_type,
        'request_ids' => $requestIds,
        'column_used' => $requestIdColumn
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
?> 