<?php
// Notifications API: GET to fetch, POST to mark as seen

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
                       pr.timestamp AS request_timestamp
                FROM notifications n
                LEFT JOIN pickup_requests pr ON pr.request_id = n.request_id
                WHERE n.user_id = :user_id AND n.dismissed_at IS NULL";
        $params = [':user_id' => $user_id];
        if ($seen === '0' || $seen === '1') {
            $sql .= " AND seen = :seen";
            $params[':seen'] = (int)$seen;
        }
        $sql .= " ORDER BY created_at DESC, notification_id DESC LIMIT :limit";
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
        echo json_encode(['success' => true, 'data' => $rows]);
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

        echo json_encode(['success' => false, 'message' => 'Unsupported action']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unsupported method']);
    exit;
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Query params
    $user_id     = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $seen        = isset($_GET['seen']) ? $_GET['seen'] : null; // '0' | '1' | null
    $request_id  = isset($_GET['request_id']) ? (int)$_GET['request_id'] : null;
    $company_id  = isset($_GET['company_id']) ? (int)$_GET['company_id'] : null;
    $customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;
    $limit       = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;
    $offset      = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

    if ($user_id <= 0) {
        throw new Exception('user_id is required');
    }

    $conditions = ['user_id = :user_id'];
    $params = [':user_id' => $user_id];

    if ($seen !== null && ($seen === '0' || $seen === '1')) {
        $conditions[] = 'seen = :seen';
        $params[':seen'] = (int)$seen;
    }
    if ($request_id) {
        $conditions[] = 'request_id = :request_id';
        $params[':request_id'] = $request_id;
    }
    if ($company_id) {
        $conditions[] = 'company_id = :company_id';
        $params[':company_id'] = $company_id;
    }
    if ($customer_id) {
        $conditions[] = 'customer_id = :customer_id';
        $params[':customer_id'] = $customer_id;
    }

    $where = implode(' AND ', $conditions);
    $sql = "SELECT notification_id, user_id, request_id, company_id, customer_id, message, seen, dismissed_at, created_at
            FROM notifications
            WHERE $where AND dismissed_at IS NULL
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

