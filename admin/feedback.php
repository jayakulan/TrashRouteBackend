<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

try {
    // Use the Database class to get connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }

    // Fetch Company Feedback Data
    $companyFeedbackQuery = "
        SELECT 
            cf.request_id,
            cf.rating,
            cf.comment,
            cf.company_id,
            ru_company.name as company_name,
            ru_customer.name as customer_name
        FROM company_feedback cf
        LEFT JOIN registered_users ru_company ON cf.company_id = ru_company.user_id
        LEFT JOIN pickup_requests pr ON cf.request_id = pr.request_id
        LEFT JOIN registered_users ru_customer ON pr.customer_id = ru_customer.user_id
        ORDER BY cf.request_id DESC
    ";
    
    $companyStmt = $pdo->prepare($companyFeedbackQuery);
    $companyStmt->execute();
    $companyFeedback = $companyStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Customer Feedback Data
    $customerFeedbackQuery = "
        SELECT 
            cf.request_id,
            cf.rating,
            cf.comment,
            cf.customer_id,
            ru_customer.name as customer_name,
            ru_company.name as company_name
        FROM customer_feedback cf
        LEFT JOIN registered_users ru_customer ON cf.customer_id = ru_customer.user_id
        LEFT JOIN company_feedback comp_feedback ON cf.request_id = comp_feedback.request_id
        LEFT JOIN registered_users ru_company ON comp_feedback.company_id = ru_company.user_id
        ORDER BY cf.request_id DESC
    ";
    
    $customerStmt = $pdo->prepare($customerFeedbackQuery);
    $customerStmt->execute();
    $customerFeedback = $customerStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data for frontend
    $formattedCompanyFeedback = array_map(function($item) {
        return [
            'id' => '#' . $item['request_id'],
            'user' => $item['customer_name'] ?? 'Unknown Customer',
            'company' => $item['company_name'] ?? 'Unknown Company',
            'rating' => (int)$item['rating'],
            'comment' => $item['comment'] ?? 'No comment provided'
        ];
    }, $companyFeedback);

    $formattedCustomerFeedback = array_map(function($item) {
        return [
            'id' => '#' . $item['request_id'],
            'user' => $item['customer_name'] ?? 'Unknown Customer',
            'company' => $item['company_name'] ?? 'Unknown Company',
            'rating' => (int)$item['rating'],
            'comment' => $item['comment'] ?? 'No comment provided'
        ];
    }, $customerFeedback);

    $response = [
        'success' => true,
        'companyFeedback' => $formattedCompanyFeedback,
        'customerFeedback' => $formattedCustomerFeedback
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
