<?php
require_once __DIR__ . '/User.php';

class Admin extends User {
    public function getRole() {
        return 'admin';
    }

    public static function login($db, $email, $password) {
        $query = "SELECT * FROM registered_users WHERE email = :email AND role = 'admin'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            throw new Exception('Invalid email or password');
        }
        // Allow both plain and hashed password for testing
        if (
            $user['password_hash'] === $password || // allow plain text match
            password_verify($password, $user['password_hash']) // allow hashed match
        ) {
            // OK
        } else {
            throw new Exception('Invalid email or password');
        }
        return [
            'user' => $user,
            'profile' => null
        ];
    }

    // Fetch all customers
    public static function getAllCustomers($db) {
        $query = "
            SELECT 
                c.customer_id as CustomerID,
                ru.name as Name,
                ru.email as Email,
                ru.contact_number as Phone,
                ru.address as Location,
                ru.disable_status as Status,
                ru.created_at as JoinDate,
                ru.role as Role
            FROM customers c
            INNER JOIN registered_users ru ON c.customer_id = ru.user_id
            WHERE ru.role = 'customer'
            ORDER BY ru.created_at DESC
        ";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Disable a customer (set status to 'disabled')
    public static function disableCustomer($db, $customerId) {
        $query = "UPDATE registered_users SET disable_status = 'disabled' WHERE user_id = :customer_id AND role = 'customer'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
} 