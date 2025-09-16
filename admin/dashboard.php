<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../config/database.php';

try {
    // Use the Database class to get connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }
    
    // Log successful connection
    error_log("Dashboard: Database connection established successfully");
    
    $dashboardStats = [];
    
    // Get total customers (users with role = 'customer')
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM registered_users WHERE role = 'customer'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $dashboardStats['total_customers'] = $result['total'];
    } catch (Exception $e) {
        error_log("Error counting customers: " . $e->getMessage());
        $dashboardStats['total_customers'] = 0;
    }
    
    // Get total companies (users with role = 'company')
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM registered_users WHERE role = 'company'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $dashboardStats['total_companies'] = $result['total'];
    } catch (Exception $e) {
        error_log("Error counting companies: " . $e->getMessage());
        $dashboardStats['total_companies'] = 0;
    }
    
    // Get total requests (all pickup requests)
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pickup_requests");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $dashboardStats['total_requests'] = $result['total'];
    } catch (Exception $e) {
        error_log("Error counting requests: " . $e->getMessage());
        $dashboardStats['total_requests'] = 0;
    }
    
    // Get pending requests (status = 'Request received')
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pickup_requests WHERE status = 'Request received'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $dashboardStats['pending'] = $result['total'];
    } catch (Exception $e) {
        error_log("Error counting pending requests: " . $e->getMessage());
        $dashboardStats['pending'] = 0;
    }
    
    // Get active requests (status = 'Accepted')
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pickup_requests WHERE status = 'Accepted'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $dashboardStats['active'] = $result['total'];
    } catch (Exception $e) {
        error_log("Error counting active requests: " . $e->getMessage());
        $dashboardStats['active'] = 0;
    }
    
    // Get completed requests (status = 'Completed')
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pickup_requests WHERE status = 'Completed'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $dashboardStats['completed'] = $result['total'];
    } catch (Exception $e) {
        error_log("Error counting completed requests: " . $e->getMessage());
        $dashboardStats['completed'] = 0;
    }
    
    // Get recent activity (last 5 activities)
    try {
        $stmt = $pdo->prepare("
            SELECT 
                'user_registered' as type,
                CONCAT('New user registered: ', name) as title,
                created_at as time
            FROM registered_users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            UNION ALL
            SELECT 
                'pickup_request' as type,
                CONCAT('Pickup request #', request_id, ' ', 
                    CASE 
                        WHEN status = 'Completed' THEN 'completed'
                        WHEN status = 'Accepted' THEN 'accepted'
                        ELSE 'created'
                    END
                ) as title,
                timestamp as time
            FROM pickup_requests 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY time DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the time for recent activity
        foreach ($recentActivity as &$activity) {
            $timestamp = strtotime($activity['time']);
            $now = time();
            $diff = abs($now - $timestamp); // Use abs() to ensure positive value
            
            if ($diff < 3600) {
                $minutes = round($diff / 60);
                $activity['time'] = $minutes . " minutes ago";
            } elseif ($diff < 86400) {
                $hours = round($diff / 3600);
                $activity['time'] = $hours . " hours ago";
            } else {
                $days = round($diff / 86400);
                $activity['time'] = $days . " days ago";
            }
        }
        
        $dashboardStats['recent_activity'] = $recentActivity;
    } catch (Exception $e) {
        error_log("Error getting recent activity: " . $e->getMessage());
        $dashboardStats['recent_activity'] = [];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $dashboardStats
    ]);
    
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
