<?php
// Trashroutefinal/TrashRouteBackend/classes/Notification.php

class Notification {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Display notifications for a user
     * @param int $user_id
     * @param string $seen (optional: '0', '1', or null for all)
     * @param int $limit (optional: default 50)
     * @return array
     */
    public function display($user_id, $seen = null, $limit = 50) {
        try {
            if (!$user_id || $user_id <= 0) {
                return ['success' => false, 'message' => 'User ID is required'];
            }

            // Clean up notifications older than 3 days before fetching
            $this->cleanupOldNotifications();

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
                           ru.name AS customer_name,
                           COALESCE(n.request_id, pr.request_id) AS display_request_id
                    FROM notifications n
                    LEFT JOIN pickup_requests pr ON pr.request_id = n.request_id
                    LEFT JOIN customers c ON c.customer_id = n.customer_id
                    LEFT JOIN registered_users ru ON ru.user_id = c.customer_id
                    WHERE n.user_id = :user_id AND n.dismissed_at IS NULL";

            $params = [':user_id' => $user_id];

            // Add seen filter if provided
            if ($seen === '0' || $seen === '1') {
                $sql .= " AND seen = :seen";
                $params[':seen'] = (int)$seen;
            }

            $sql .= " ORDER BY created_at DESC, notification_id DESC LIMIT :limit";

            $stmt = $this->conn->prepare($sql);
            
            // Bind parameters
            foreach ($params as $key => $val) {
                if ($key === ':seen') {
                    $stmt->bindValue($key, $val, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $val, PDO::PARAM_INT);
                }
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Debug: Log notification data
            error_log("Retrieved notifications: " . print_r($notifications, true));

            return [
                'success' => true,
                'data' => $notifications,
                'count' => count($notifications)
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error displaying notifications: ' . $e->getMessage()];
        }
    }

    /**
     * Create a new notification
     * @param int $user_id
     * @param string $message
     * @param int $request_id (optional)
     * @param int $company_id (optional)
     * @param int $customer_id (optional)
     * @return array
     */
    public function create($user_id, $message, $request_id = null, $company_id = null, $customer_id = null) {
        try {
            if (!$user_id || !$message) {
                return ['success' => false, 'message' => 'User ID and message are required'];
            }

            $query = "INSERT INTO notifications (user_id, message, request_id, company_id, customer_id, seen, created_at) 
                      VALUES (:user_id, :message, :request_id, :company_id, :customer_id, 0, NOW())";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':request_id', $request_id);
            $stmt->bindParam(':company_id', $company_id);
            $stmt->bindParam(':customer_id', $customer_id);

            if ($stmt->execute()) {
                $notification_id = $this->conn->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Notification created successfully',
                    'data' => [
                        'notification_id' => $notification_id,
                        'user_id' => $user_id,
                        'message' => $message,
                        'request_id' => $request_id,
                        'company_id' => $company_id,
                        'customer_id' => $customer_id,
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create notification'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating notification: ' . $e->getMessage()];
        }
    }

    /**
     * Mark notifications as seen
     * @param int $user_id
     * @param array $notification_ids (optional: specific IDs to mark)
     * @param bool $mark_all (optional: mark all notifications for user)
     * @return array
     */
    public function markAsSeen($user_id, $notification_ids = [], $mark_all = false) {
        try {
            if (!$user_id || $user_id <= 0) {
                return ['success' => false, 'message' => 'User ID is required'];
            }

            if ($mark_all) {
                $stmt = $this->conn->prepare("UPDATE notifications SET seen = 1 WHERE user_id = :user_id AND seen = 0");
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                
                return [
                    'success' => true,
                    'message' => 'All notifications marked as seen',
                    'affected_rows' => $stmt->rowCount()
                ];
            }

            if (empty($notification_ids)) {
                return ['success' => false, 'message' => 'Notification IDs are required when mark_all is false'];
            }

            // Sanitize IDs
            $ids = array_values(array_unique(array_map('intval', $notification_ids)));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE notifications SET seen = 1 WHERE user_id = ? AND notification_id IN ($placeholders)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            
            $idx = 2;
            foreach ($ids as $id) {
                $stmt->bindValue($idx++, $id, PDO::PARAM_INT);
            }
            
            $stmt->execute();

            return [
                'success' => true,
                'message' => 'Selected notifications marked as seen',
                'affected_rows' => $stmt->rowCount()
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error marking notifications as seen: ' . $e->getMessage()];
        }
    }

    /**
     * Delete a notification
     * @param int $user_id
     * @param int $notification_id
     * @return array
     */
    public function delete($user_id, $notification_id) {
        try {
            if (!$user_id || $user_id <= 0) {
                return ['success' => false, 'message' => 'User ID is required'];
            }

            if (!$notification_id || $notification_id <= 0) {
                return ['success' => false, 'message' => 'Notification ID is required'];
            }

            $stmt = $this->conn->prepare("DELETE FROM notifications WHERE user_id = :user_id AND notification_id = :notification_id");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':notification_id', $notification_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Notification deleted successfully',
                    'deleted_id' => $notification_id
                ];
            } else {
                return ['success' => false, 'message' => 'Notification not found or already deleted'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting notification: ' . $e->getMessage()];
        }
    }

    /**
     * Dismiss a notification (soft delete)
     * @param int $user_id
     * @param int $notification_id
     * @return array
     */
    public function dismiss($user_id, $notification_id) {
        try {
            if (!$user_id || $user_id <= 0) {
                return ['success' => false, 'message' => 'User ID is required'];
            }

            if (!$notification_id || $notification_id <= 0) {
                return ['success' => false, 'message' => 'Notification ID is required'];
            }

            $stmt = $this->conn->prepare("UPDATE notifications SET dismissed_at = NOW() WHERE user_id = :user_id AND notification_id = :notification_id");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':notification_id', $notification_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Notification dismissed successfully',
                    'dismissed_id' => $notification_id
                ];
            } else {
                return ['success' => false, 'message' => 'Notification not found or already dismissed'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error dismissing notification: ' . $e->getMessage()];
        }
    }

    /**
     * Get notification count for a user
     * @param int $user_id
     * @param bool $unseen_only (optional: count only unseen notifications)
     * @return array
     */
    public function getCount($user_id, $unseen_only = false) {
        try {
            if (!$user_id || $user_id <= 0) {
                return ['success' => false, 'message' => 'User ID is required'];
            }

            $sql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = :user_id AND dismissed_at IS NULL";
            $params = [':user_id' => $user_id];

            if ($unseen_only) {
                $sql .= " AND seen = 0";
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'count' => (int)$result['total'],
                'unseen_only' => $unseen_only
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error getting notification count: ' . $e->getMessage()];
        }
    }

    /**
     * Clean up old notifications (older than 3 days)
     * @return array
     */
    public function cleanupOldNotifications() {
        try {
            $stmt = $this->conn->prepare("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 3 DAY)");
            $stmt->execute();

            return [
                'success' => true,
                'message' => 'Old notifications cleaned up',
                'deleted_count' => $stmt->rowCount()
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error cleaning up notifications: ' . $e->getMessage()];
        }
    }

    /**
     * Get notification by ID
     * @param int $notification_id
     * @return array
     */
    public function getById($notification_id) {
        try {
            if (!$notification_id || $notification_id <= 0) {
                return ['success' => false, 'message' => 'Notification ID is required'];
            }

            $sql = "SELECT n.*, 
                           pr.waste_type AS request_waste_type,
                           pr.quantity AS request_quantity,
                           pr.status AS request_status,
                           ru.name AS customer_name
                    FROM notifications n
                    LEFT JOIN pickup_requests pr ON pr.request_id = n.request_id
                    LEFT JOIN customers c ON c.customer_id = n.customer_id
                    LEFT JOIN registered_users ru ON ru.user_id = c.customer_id
                    WHERE n.notification_id = :notification_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':notification_id', $notification_id, PDO::PARAM_INT);
            $stmt->execute();

            $notification = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$notification) {
                return ['success' => false, 'message' => 'Notification not found'];
            }

            return [
                'success' => true,
                'data' => $notification
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error getting notification: ' . $e->getMessage()];
        }
    }
}
?>
