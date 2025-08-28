<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require_once '../config/database.php';
require_once '../utils/session_auth_middleware.php';
require_once '../utils/helpers.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check company authentication
try {
    $companyUser = SessionAuthMiddleware::requireCompanyAuth();
    // Debug: Log successful authentication
    error_log("Company authentication successful for user: " . $companyUser['user_id']);
} catch (Exception $e) {
    error_log("Company authentication failed: " . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication failed',
        'message' => $e->getMessage()
    ]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    // Make DB connection available to helper methods that expect a global
    $GLOBALS['db'] = $db;
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
    $queryPickup = "SELECT $requestIdColumn, customer_id, waste_type, quantity FROM pickup_requests WHERE waste_type = :waste_type AND status = 'Request received' ORDER BY $requestIdColumn ASC";
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

    // Update customer status to 'active' for all customers involved in accepted requests
    $customerUpdateQuery = "
        UPDATE registered_users 
        SET disable_status = 'active' 
        WHERE user_id IN (
            SELECT DISTINCT pr.customer_id 
            FROM pickup_requests pr 
            WHERE pr.$requestIdColumn IN ($placeholders)
        ) AND role = 'customer'
    ";
    $stmtCustomerUpdate = $db->prepare($customerUpdateQuery);
    
    if (!$stmtCustomerUpdate->execute($requestIds)) {
        echo json_encode(['success' => false, 'message' => 'Failed to update customer status']);
        exit;
    }

    // Fetch company name from registered_users using company_id
    $stmtCompany = $db->prepare("
        SELECT name AS company_name
        FROM registered_users
        WHERE user_id = :company_id AND role = 'company'
    ");
    $stmtCompany->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmtCompany->execute();
    $companyRow = $stmtCompany->fetch(PDO::FETCH_ASSOC);
    $company_name = $companyRow ? $companyRow['company_name'] : '';

    // For each accepted pickup request, get customer email and send notification
    foreach ($requestIds as $request_id) {
        $stmtEmail = $db->prepare("
            SELECT ru.email AS customer_email
            FROM pickup_requests pr
            INNER JOIN customers c ON pr.customer_id = c.customer_id
            INNER JOIN registered_users ru ON c.customer_id = ru.user_id
            WHERE pr.$requestIdColumn = :request_id
        ");
        $stmtEmail->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmtEmail->execute();
        $row = $stmtEmail->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['customer_email']) {
            $customer_email = $row['customer_email'];

            // --- Direct include method (default) ---
            $_POST['customer_email'] = $customer_email;
            $_POST['company_name'] = $company_name;
            include __DIR__ . '/comemail.php';

            // --- OR cURL HTTP POST method (uncomment to use) ---
            /*
            $postData = [
                'customer_email' => $customer_email,
                'company_name' => $company_name
            ];
            $ch = curl_init('http://localhost/Trashroutefinal/TrashRouteBackend/Company/comemail.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            $response = curl_exec($ch);
            curl_close($ch);
            */
        }
    }

    // Create notifications:
    // 1) Per-customer: pickup accepted by company
    foreach ($pickupRequests as $reqRow) {
        $customer_id_n = (int)$reqRow['customer_id'];
        $request_id_n = (int)$reqRow[$requestIdColumn];
        $msg = "Your pickup request #{$request_id_n} has been accepted by {$company_name}.";
        Helpers::createNotification($customer_id_n, $msg, $request_id_n, (int)$company_id, $customer_id_n);
    }

    // 2) Company: payment successful / route activated (include route details)
    $companyMsg = "Payment successful. Route #{$route_id} for {$waste_type} activated. Customers: {$totalCustomers}, Total Qty: {$totalQuantity} kg.";
    Helpers::createNotification((int)$company_id, $companyMsg, null, (int)$company_id, null);

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