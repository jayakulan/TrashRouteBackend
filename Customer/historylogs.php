<?php
// Disable error display to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost:5175");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Max-Age: 86400"); // 24 hours
    http_response_code(200);
    exit();
}

// Set CORS headers for actual requests
header("Access-Control-Allow-Origin: http://localhost:5175");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');



// Include database configuration
require_once '../config/database.php';
require_once '../utils/session_auth_middleware.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check customer authentication (session + JWT fallback)
try {
    $customerUser = SessionAuthMiddleware::requireCustomerAuth();
    $customer_id = $customerUser['user_id'];
    error_log("Customer authenticated via session/JWT: " . $customer_id);
} catch (Exception $e) {
    error_log("Customer authentication failed: " . $e->getMessage());
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
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    // First, let's debug by getting basic pickup request data
    $debugQuery = "
        SELECT 
            request_id,
            waste_type,
            quantity,
            timestamp,
            status
        FROM pickup_requests 
        WHERE customer_id = :customer_id 
        ORDER BY timestamp DESC
    ";
    
    $debugStmt = $pdo->prepare($debugQuery);
    $debugStmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $debugStmt->execute();
    $debugResults = $debugStmt->fetchAll();
    
    error_log("Debug query found " . count($debugResults) . " total requests for customer " . $customer_id);
    foreach ($debugResults as $row) {
        error_log("Request ID: " . $row['request_id'] . ", Waste Type: " . $row['waste_type'] . ", Quantity: " . $row['quantity'] . ", Status: " . $row['status']);
    }
    
    // Also check for completed requests specifically
    $completedQuery = "
        SELECT COUNT(*) as completed_count
        FROM pickup_requests 
        WHERE customer_id = :customer_id 
        AND status = 'Completed'
    ";
    
    $completedStmt = $pdo->prepare($completedQuery);
    $completedStmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $completedStmt->execute();
    $completedCount = $completedStmt->fetch()['completed_count'];
    error_log("Customer " . $customer_id . " has " . $completedCount . " completed requests");
    
    // Get all completed requests first, then try to get company info
    $query = "
        SELECT 
            pr.request_id,
            pr.waste_type,
            pr.quantity,
            pr.timestamp,
            pr.status,
            CASE 
                WHEN ru.name IS NOT NULL THEN ru.name
                WHEN c.company_reg_number IS NOT NULL THEN c.company_reg_number
                ELSE 'Not Assigned'
            END as accepted_company
        FROM pickup_requests pr
        LEFT JOIN route_request_mapping rrm ON pr.request_id = rrm.request_id
        LEFT JOIN routes r ON rrm.route_id = r.route_id
        LEFT JOIN companies c ON r.company_id = c.company_id
        LEFT JOIN registered_users ru ON c.company_id = ru.user_id AND ru.role = 'company'
        WHERE pr.customer_id = :customer_id 
        AND pr.status = 'Completed'
        ORDER BY pr.timestamp DESC
    ";
    
    // If no results from the complex query, try a simpler approach
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $results = $stmt->fetchAll();
    error_log("Complex query found " . count($results) . " results");
    
    // If no results, try a simpler query without route mapping
    if (empty($results)) {
        error_log("No results from complex query, trying simpler approach");
        $simpleQuery = "
            SELECT 
                request_id,
                waste_type,
                quantity,
                timestamp,
                status,
                'Not Assigned' as accepted_company
            FROM pickup_requests 
            WHERE customer_id = :customer_id 
            AND status = 'Completed'
            ORDER BY timestamp DESC
        ";
        
        $simpleStmt = $pdo->prepare($simpleQuery);
        $simpleStmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $simpleStmt->execute();
        $results = $simpleStmt->fetchAll();
        error_log("Simple query found " . count($results) . " results");
        
        // If still no results, check what statuses exist for this customer
        if (empty($results)) {
            error_log("Still no results, checking all statuses for customer " . $customer_id);
            $statusQuery = "
                SELECT DISTINCT status, COUNT(*) as count
                FROM pickup_requests 
                WHERE customer_id = :customer_id 
                GROUP BY status
            ";
            
            $statusStmt = $pdo->prepare($statusQuery);
            $statusStmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $statusStmt->execute();
            $statusResults = $statusStmt->fetchAll();
            
            foreach ($statusResults as $statusRow) {
                error_log("Status: '" . $statusRow['status'] . "' - Count: " . $statusRow['count']);
            }
        }
    }
    

    
    if ($results) {
        // Format the data for frontend consumption
        $formattedResults = array_map(function($row) {
            return [
                'request_id' => $row['request_id'],
                'waste_type' => $row['waste_type'],
                'quantity' => $row['quantity'] . ' kg',
                'timestamp' => $row['timestamp'],
                'status' => $row['status'],
                'accepted_company' => $row['accepted_company'],
                'date' => date('F j, Y', strtotime($row['timestamp'])),
                'request_date' => date('F j, Y, g:i A', strtotime($row['timestamp']))
            ];
        }, $results);
        
        echo json_encode([
            'success' => true,
            'data' => $formattedResults,
            'message' => 'History logs retrieved successfully',
            'count' => count($formattedResults)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'No completed pickup requests found',
            'count' => 0
        ]);
    }
    
    } catch (PDOException $e) {
        error_log("Database error in historylogs.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred while fetching history logs.',
            'error' => 'DATABASE_ERROR',
            'debug' => null
        ]);
    } catch (Exception $e) {
        error_log("General error in historylogs.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request.',
            'error' => 'GENERAL_ERROR',
            'debug' => null
        ]);
    }
?>
