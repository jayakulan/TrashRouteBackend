<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once '../config/database.php';
require_once '../utils/session_auth_middleware.php';

// Check company authentication
SessionAuthMiddleware::requireCompanyAuth();

try {
    // Get parameters from request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['company_id'])) {
        throw new Exception("Company ID is required");
    }
    
    $company_id = intval($input['company_id']);
    $waste_type = isset($input['waste_type']) ? $input['waste_type'] : null;
    
    if ($company_id <= 0) {
        throw new Exception("Invalid company ID");
    }

    // Create database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }

    // Query to fetch customer details using the route_request_mapping table
    $query = "
        SELECT 
            pr.request_id,
            pr.waste_type,
            pr.quantity,
            pr.latitude,
            pr.longitude,
            pr.status,
            pr.timestamp,
            ru.name as customer_name,
            ru.contact_number as customer_phone,
            ru.address as customer_address,
            c.customer_id,
            r.route_id
        FROM pickup_requests pr
        INNER JOIN customers c ON pr.customer_id = c.customer_id
        INNER JOIN registered_users ru ON c.customer_id = ru.user_id
        INNER JOIN route_request_mapping rrm ON pr.request_id = rrm.request_id
        INNER JOIN routes r ON rrm.route_id = r.route_id
        WHERE pr.status IN ('Request received', 'Pending', 'Accepted')
        AND r.company_id = :company_id
    ";

    $params = ['company_id' => $company_id];
    
    // Add waste type filter if provided
    if ($waste_type) {
        $query .= " AND pr.waste_type = :waste_type";
        $params['waste_type'] = $waste_type;
    }
    
    $query .= " ORDER BY pr.timestamp ASC";

    $stmt = $pdo->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare database query");
    }
    
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $formattedCustomers = [];
    foreach ($customers as $customer) {
        $formattedCustomers[] = [
            'id' => $customer['request_id'],
            'request_id' => $customer['request_id'],
            'route_id' => $customer['route_id'],
            'address' => $customer['customer_address'] ?: 'Address not provided',
            'contact' => $customer['customer_name'],
            'notes' => "Waste Type: {$customer['waste_type']}, Quantity: {$customer['quantity']} kg",
            'collected' => false,
            'latitude' => floatval($customer['latitude']),
            'longitude' => floatval($customer['longitude']),
            'waste_type' => $customer['waste_type'],
            'quantity' => intval($customer['quantity']),
            'status' => $customer['status'],
            'timestamp' => $customer['timestamp'],
            'customer_phone' => $customer['customer_phone'] ?: 'Phone not provided'
        ];
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $formattedCustomers,
        'count' => count($formattedCustomers),
        'waste_type' => $waste_type,
        'company_id' => $company_id
    ]);

} catch (PDOException $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'details' => 'PDO Exception occurred',
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage(),
        'details' => 'General Exception occurred',
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?> 