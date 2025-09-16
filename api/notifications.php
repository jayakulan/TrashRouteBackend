<?php
// Notifications API: GET to fetch, POST to mark as seen

// Disable all error reporting and output buffering
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../utils/helpers.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    // Make DB available to helper methods that expect a global
    $GLOBALS['db'] = $db;

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'user_id is required']);
            exit;
        }
        
        // Simple test response first
        if (isset($_GET['test'])) {
            echo json_encode(['success' => true, 'message' => 'API is working', 'user_id' => $user_id]);
            exit;
        }
        $seen = isset($_GET['seen']) ? $_GET['seen'] : null; // '0' | '1' | null
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;

        $sql = "SELECT n.notification_id,
                       n.user_id,
                       n.request_id,
                       n.company_id,
                       n.customer_id,
                       n.message,
                       n.seen,
                       n.dismissed_at,
                       n.created_at,
                       pr.waste_type AS request_waste_type,
                       pr.quantity AS request_quantity,
                       pr.status AS request_status,
                       pr.otp AS request_otp,
                       pr.timestamp AS request_timestamp,
                       pr.otp_verified AS request_otp_verified,
                       ru.name AS customer_name
                FROM notifications n
                LEFT JOIN pickup_requests pr ON pr.request_id = n.request_id
                LEFT JOIN customers c ON c.customer_id = n.customer_id
                LEFT JOIN registered_users ru ON ru.user_id = c.customer_id
                WHERE n.user_id = :user_id 
                AND n.dismissed_at IS NULL";
        $params = [':user_id' => $user_id];
        if ($seen === '0' || $seen === '1') {
            $sql .= " AND seen = :seen";
            $params[':seen'] = (int)$seen;
        }
        $sql .= " ORDER BY created_at DESC, notification_id DESC LIMIT :limit";
        try {
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $val) {
                if ($key === ':seen') {
                    $stmt->bindValue($key, $val, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $val, PDO::PARAM_INT);
                }
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Filter out company notifications older than 3 days
            $filteredRows = array_filter($rows, function($row) {
                try {
                    // Keep customer notifications (company_id is NULL)
                    if ($row['company_id'] === null) {
                        return true;
                    }
                    // Keep company notifications only if they're within 3 days
                    if (empty($row['created_at'])) {
                        return false;
                    }
                    $createdAt = new DateTime($row['created_at']);
                    $threeDaysAgo = new DateTime('-3 days');
                    return $createdAt >= $threeDaysAgo;
                } catch (Exception $e) {
                    // If date parsing fails, keep the notification
                    return true;
                }
            });
            
            echo json_encode(['success' => true, 'data' => array_values($filteredRows)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = isset($payload['action']) ? $payload['action'] : '';

        if ($action === 'mark_seen') {
            $user_id = isset($payload['user_id']) ? (int)$payload['user_id'] : 0;
            $ids = isset($payload['notification_ids']) && is_array($payload['notification_ids']) ? $payload['notification_ids'] : [];
            $mark_all = !empty($payload['mark_all']);

            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'user_id is required']);
                exit;
            }

            if ($mark_all) {
                $stmt = $db->prepare("UPDATE notifications SET seen = 1 WHERE user_id = :user_id AND seen = 0");
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'All notifications marked as seen']);
                exit;
            }

            if (empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'notification_ids is required when mark_all is false']);
                exit;
            }

            // Sanitize IDs
            $ids = array_values(array_unique(array_map('intval', $ids)));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE notifications SET seen = 1 WHERE user_id = ? AND notification_id IN ($placeholders)";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $idx = 2;
            foreach ($ids as $id) {
                $stmt->bindValue($idx++, $id, PDO::PARAM_INT);
            }
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Selected notifications marked as seen']);
            exit;
        }

        if ($action === 'delete') {
            $user_id = isset($payload['user_id']) ? (int)$payload['user_id'] : 0;
            $notification_id = isset($payload['notification_id']) ? (int)$payload['notification_id'] : 0;

            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'user_id is required']);
                exit;
            }

            if ($notification_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'notification_id is required']);
                exit;
            }

            // Delete the notification
            $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = :user_id AND notification_id = :notification_id");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':notification_id', $notification_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Notification deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Notification not found or already deleted']);
            }
            exit;
        }

        if ($action === 'dismiss') {
            $user_id = isset($payload['user_id']) ? (int)$payload['user_id'] : 0;
            $notification_id = isset($payload['notification_id']) ? (int)$payload['notification_id'] : 0;

            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'user_id is required']);
                exit;
            }

            if ($notification_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'notification_id is required']);
                exit;
            }

            // Dismiss the notification by setting dismissed_at timestamp
            $stmt = $db->prepare("UPDATE notifications SET dismissed_at = NOW() WHERE user_id = :user_id AND notification_id = :notification_id");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':notification_id', $notification_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Notification dismissed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Notification not found or already dismissed']);
            }
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unsupported action']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unsupported method']);
    exit;
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

// Clean up any output buffer and ensure only JSON is sent
ob_end_clean();
?>

