<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Fetch all notifications
            $stmt = $pdo->prepare("SELECT notification_id, request_id, customer_id, company_id, message, created_at, seen FROM notifications ORDER BY created_at DESC");
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $notifications
            ]);
            break;
            
        case 'POST':
            // Create new notification
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("INSERT INTO notifications (request_id, customer_id, company_id, message, seen) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $data['request_id'],
                $data['customer_id'],
                $data['company_id'],
                $data['message'],
                $data['seen'] ?? 0
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification created successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create notification'
                ]);
            }
            break;
            
        case 'PUT':
            // Update notification
            $data = json_decode(file_get_contents('php://input'), true);
            $notificationId = $_GET['notification_id'] ?? null;
            
            if (!$notificationId) {
                throw new Exception("Notification ID is required");
            }
            
            $stmt = $pdo->prepare("UPDATE notifications SET request_id = ?, customer_id = ?, company_id = ?, message = ?, seen = ? WHERE notification_id = ?");
            $result = $stmt->execute([
                $data['request_id'],
                $data['customer_id'],
                $data['company_id'],
                $data['message'],
                $data['seen'],
                $notificationId
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update notification'
                ]);
            }
            break;
            
        case 'DELETE':
            // Delete notification
            $notificationId = $_GET['notification_id'] ?? null;
            
            if (!$notificationId) {
                throw new Exception("Notification ID is required");
            }
            
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE notification_id = ?");
            $result = $stmt->execute([$notificationId]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete notification'
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            break;
    }

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
