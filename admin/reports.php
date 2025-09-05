<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5175');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
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

// Admin authentication
try {
    $adminUser = SessionAuthMiddleware::requireAdminAuth();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied',
        'message' => $e->getMessage()
    ]);
    exit();
}

try {
    // Create database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }
    
    // Debug: Log successful connection
    error_log("Reports API: Database connection established successfully");

    // Get waste type distribution for completed pickup requests
    $query = "
        SELECT 
            waste_type,
            COUNT(*) as count
        FROM pickup_requests 
        WHERE status = 'Completed'
        GROUP BY waste_type
        ORDER BY count DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log query results
    error_log("Reports API: Waste type query returned " . count($results) . " results");
    
    // Initialize counters for each waste type
    $wasteTypeData = [
        'Paper' => 0,
        'Glass' => 0,
        'Metal' => 0,
        'Plastic' => 0
    ];
    
    // Process results and populate the data
    foreach ($results as $row) {
        $wasteType = $row['waste_type'];
        $count = (int)$row['count'];
        
        if (isset($wasteTypeData[$wasteType])) {
            $wasteTypeData[$wasteType] = $count;
        }
    }
    
    // Calculate total completed requests
    $totalCompleted = array_sum($wasteTypeData);
    
    // Calculate percentages
    $wasteTypePercentages = [];
    foreach ($wasteTypeData as $type => $count) {
        $percentage = $totalCompleted > 0 ? round(($count / $totalCompleted) * 100, 1) : 0;
        $wasteTypePercentages[$type] = [
            'count' => $count,
            'percentage' => $percentage
        ];
    }
    
    // Get sold routes data for previous month weeks based on accepted_at column
    // Calculate the start and end dates of the previous month
    $previousMonthStart = date('Y-m-01', strtotime('first day of last month'));
    $previousMonthEnd = date('Y-m-t', strtotime('last day of last month'));
    
    // Initialize weekly data for 5 weeks
    $weeklyData = [0, 0, 0, 0, 0]; // 5 weeks
    
    // Calculate sold routes for each week of the previous month
    for ($week = 1; $week <= 5; $week++) {
        // Calculate the start and end dates for each week
        $weekStart = date('Y-m-d', strtotime($previousMonthStart . ' + ' . (($week - 1) * 7) . ' days'));
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' + 6 days'));
        
        // Make sure we don't go beyond the month end
        if ($weekEnd > $previousMonthEnd) {
            $weekEnd = $previousMonthEnd;
        }
        
        // Query for routes accepted in this specific week
        $weekQuery = "
            SELECT COUNT(*) as routes_sold
            FROM routes 
            WHERE accepted_at >= :week_start 
            AND accepted_at <= :week_end 
            AND accepted_at IS NOT NULL
        ";
        
        $stmt = $pdo->prepare($weekQuery);
        $stmt->bindParam(':week_start', $weekStart);
        $stmt->bindParam(':week_end', $weekEnd);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $weeklyData[$week - 1] = (int)$result['routes_sold'];
    }
    
    // Prepare response data
    $responseData = [
        'wasteTypeData' => $wasteTypePercentages,
        'soldRoutesData' => $weeklyData,
        'totalCompleted' => $totalCompleted,
        'totalSoldRoutes' => array_sum($weeklyData)
    ];
    
    // Debug: Log response data
    error_log("Reports API: Sending response with " . $totalCompleted . " total completed requests");
    
    echo json_encode([
        'success' => true,
        'message' => 'Reports data fetched successfully',
        'data' => $responseData
    ]);
    
} catch (Exception $e) {
    error_log("Reports API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>