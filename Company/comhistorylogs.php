<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require_once '../config/database.php';
require_once '../utils/session_auth_middleware.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Require company authentication
    SessionAuthMiddleware::requireCompanyAuth();

    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Get company_id from POST or GET (frontend will supply from cookies)
    $company_id = $_POST['company_id'] ?? $_GET['company_id'] ?? null;
    if (!$company_id) {
        echo json_encode(['success' => false, 'message' => 'Missing company_id']);
        exit;
    }

    // Some schemas may use different request id column names; detect for safety
    $requestIdColumn = 'request_id';
    $stmtCheck = $db->prepare("SHOW COLUMNS FROM pickup_requests LIKE '%request%'");
    $stmtCheck->execute();
    $columns = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        if (strpos($column['Field'], 'request') !== false) {
            $requestIdColumn = $column['Field'];
            break;
        }
    }

    // Fetch history logs for this company by joining routes -> route_request_mapping -> pickup_requests -> registered_users
    // Only include statuses Accepted and Completed
    $sql = "
        SELECT 
            COALESCE(r.accepted_at, r.generated_at, NOW()) AS event_date,
            pr.waste_type,
            pr.quantity,
            pr.status,
            ru.name AS customer_name
        FROM routes r
        INNER JOIN route_request_mapping rrm ON rrm.route_id = r.route_id
        INNER JOIN pickup_requests pr ON pr.$requestIdColumn = rrm.request_id
        INNER JOIN registered_users ru ON ru.user_id = pr.customer_id
        WHERE r.company_id = :company_id
          AND pr.status IN ('Accepted', 'Completed')
        ORDER BY event_date DESC, r.route_id DESC, pr.$requestIdColumn DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $history = array_map(function($row) {
        return [
            'date' => $row['event_date'],
            'waste_type' => $row['waste_type'],
            'quantity' => (float)$row['quantity'],
            'status' => $row['status'],
            'customer_name' => $row['customer_name'] ?? 'N/A',
        ];
    }, $rows ?: []);

    echo json_encode([
        'success' => true,
        'data' => $history
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
?>


