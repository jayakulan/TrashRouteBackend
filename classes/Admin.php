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

    // Fetch all customers including pending ones
    public static function getAllCustomers($db) {
        $query = "
            SELECT 
                COALESCE(c.customer_id, ru.user_id) as CustomerID,
                ru.name as Name,
                ru.email as Email,
                ru.contact_number as Phone,
                ru.address as Location,
                ru.disable_status as Status,
                ru.created_at as JoinDate,
                ru.role as Role
            FROM registered_users ru
            LEFT JOIN customers c ON c.customer_id = ru.user_id
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

    // Fetch all companies including pending ones
    public static function getAllCompanies($db) {
        $query = "
            SELECT 
                COALESCE(c.company_id, ru.user_id) as CompanyID,
                ru.name as Name,
                ru.email as Email,
                ru.contact_number as Phone,
                ru.address as Location,
                ru.disable_status as Status,
                ru.created_at as JoinDate,
                ru.role as Role,
                COALESCE(c.company_reg_number, 'N/A') as ComRegno
            FROM registered_users ru
            LEFT JOIN companies c ON c.company_id = ru.user_id
            WHERE ru.role = 'company'
            ORDER BY ru.created_at DESC
        ";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Disable a company (set status to 'disabled')
    public static function disableCompany($db, $companyId) {
        $query = "UPDATE registered_users SET disable_status = 'disabled' WHERE user_id = :company_id AND role = 'company'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Fetch all pickup requests
    public static function getAllPickupRequests($db) {
        $query = "
            SELECT 
                pr.request_id as RequestID,
                ru.name as CustomerName,
                COALESCE(ru.address, 'N/A') as Location,
                pr.status as Status,
                pr.timestamp as Timestamp,
                pr.waste_type as WasteType,
                pr.quantity as Quantity,
                pr.latitude as Latitude,
                pr.longitude as Longitude,
                pr.otp as OTP,
                pr.otp_verified as OTPVerified
            FROM pickup_requests pr
            LEFT JOIN registered_users ru ON pr.customer_id = ru.user_id
            ORDER BY pr.timestamp DESC
        ";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Delete a pickup request
    public static function deletePickupRequest($db, $requestId) {
        $query = "DELETE FROM pickup_requests WHERE request_id = :request_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Fetch all contact us submissions
    public static function getAllContactSubmissions($db) {
        $query = "
            SELECT 
                contact_id,
                name,
                email,
                subject,
                message,
                created_at,
                admin_id
            FROM contact_us 
            ORDER BY created_at DESC
        ";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Delete a contact submission
    public static function deleteContactSubmission($db, $contactId) {
        $query = "DELETE FROM contact_us WHERE contact_id = :contact_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':contact_id', $contactId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Fetch all routes with company information
    public static function getAllRoutes($db) {
        $query = "
            SELECT 
                r.route_id,
                r.company_id,
                r.no_of_customers,
                r.is_accepted,
                r.generated_at,
                r.is_disabled,
                r.route_details,
                ru.name as company_name
            FROM routes r
            LEFT JOIN companies c ON r.company_id = c.company_id
            LEFT JOIN registered_users ru ON c.company_id = ru.user_id
            ORDER BY r.generated_at DESC
        ";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 