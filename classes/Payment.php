<?php
// Trashroutefinal/TrashRouteBackend/classes/Payment.php

class Payment {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Process payment method for route access
     * @param int $company_id
     * @param string $card_number
     * @param string $cardholder_name
     * @param string $expiry_date
     * @param string $pin_number
     * @param float $amount
     * @param string $waste_type
     * @return array
     */
    public function paymentMethod($company_id, $card_number, $cardholder_name, $expiry_date, $pin_number, $amount, $waste_type) {
        try {
            // Validate required fields
            if (!$company_id || !$card_number || !$cardholder_name || !$expiry_date || !$pin_number || !$amount || !$waste_type) {
                return ['success' => false, 'message' => 'Missing required fields'];
            }

            // Validate card number format (basic validation)
            if (!preg_match('/^\d{13,19}$/', $card_number)) {
                return ['success' => false, 'message' => 'Invalid card number format'];
            }

            // Validate expiry date format (MM/YY)
            if (!preg_match('/^\d{2}\/\d{2}$/', $expiry_date)) {
                return ['success' => false, 'message' => 'Invalid expiry date format (MM/YY)'];
            }

            // Validate PIN (4 digits)
            if (!preg_match('/^\d{4}$/', $pin_number)) {
                return ['success' => false, 'message' => 'Invalid PIN format (4 digits required)'];
            }

            // Validate amount
            if ($amount <= 0) {
                return ['success' => false, 'message' => 'Amount must be greater than 0'];
            }

            // Start transaction
            $this->conn->beginTransaction();

            // Check the actual column name in pickup_requests table
            $requestIdColumn = $this->getRequestIdColumn();

            // Fetch all pickup requests for the specific waste type
            $pickupRequests = $this->getPickupRequestsByWasteType($waste_type, $requestIdColumn);

            if (empty($pickupRequests)) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'No pickup requests found for waste type: ' . $waste_type];
            }

            $totalCustomers = count($pickupRequests);
            $totalQuantity = array_sum(array_column($pickupRequests, 'quantity'));

            // Create route
            $route_id = $this->createRoute($company_id, $waste_type, $totalCustomers, $totalQuantity);

            if (!$route_id) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Failed to create route'];
            }

            // Create route mappings
            $mappingSuccess = $this->createRouteMappings($route_id, $pickupRequests, $requestIdColumn);

            if (!$mappingSuccess) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Failed to create route mappings'];
            }

            // Record payment
            $payment_id = $this->recordPayment($company_id, $route_id, $card_number, $cardholder_name, $expiry_date, $pin_number, $amount);

            if (!$payment_id) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Failed to record payment'];
            }

            // Update pickup request statuses
            $requestIds = array_column($pickupRequests, $requestIdColumn);
            $updateSuccess = $this->updatePickupRequestStatuses($requestIds, $requestIdColumn);

            if (!$updateSuccess) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Failed to update pickup request status'];
            }

            // Update customer statuses
            $customerUpdateSuccess = $this->updateCustomerStatuses($requestIds, $requestIdColumn);

            if (!$customerUpdateSuccess) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Failed to update customer status'];
            }

            // Get company name
            $company_name = $this->getCompanyName($company_id);

            // Send notifications and emails
            $this->sendNotificationsAndEmails($requestIds, $pickupRequests, $company_id, $company_name, $route_id, $waste_type, $totalCustomers, $totalQuantity, $requestIdColumn);

            // Commit transaction
            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Payment and route recorded successfully',
                'data' => [
                    'payment_id' => $payment_id,
                    'route_id' => $route_id,
                    'total_customers' => $totalCustomers,
                    'total_quantity' => $totalQuantity,
                    'waste_type' => $waste_type,
                    'request_ids' => $requestIds,
                    'amount' => $amount,
                    'company_name' => $company_name
                ]
            ];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Error processing payment: ' . $e->getMessage()];
        }
    }

    /**
     * Get the correct request ID column name
     * @return string
     */
    private function getRequestIdColumn() {
        $checkColumnsQuery = "SHOW COLUMNS FROM pickup_requests LIKE '%request%'";
        $stmtCheck = $this->conn->prepare($checkColumnsQuery);
        $stmtCheck->execute();
        $columns = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);
        
        $requestIdColumn = 'request_id'; // default
        foreach ($columns as $column) {
            if (strpos($column['Field'], 'request') !== false) {
                $requestIdColumn = $column['Field'];
                break;
            }
        }
        
        return $requestIdColumn;
    }

    /**
     * Get pickup requests by waste type
     * @param string $waste_type
     * @param string $requestIdColumn
     * @return array
     */
    private function getPickupRequestsByWasteType($waste_type, $requestIdColumn) {
        $queryPickup = "SELECT $requestIdColumn, customer_id, waste_type, quantity FROM pickup_requests WHERE waste_type = :waste_type AND status = 'Request received' ORDER BY $requestIdColumn ASC";
        $stmtPickup = $this->conn->prepare($queryPickup);
        $stmtPickup->bindParam(':waste_type', $waste_type, PDO::PARAM_STR);
        $stmtPickup->execute();
        return $stmtPickup->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new route
     * @param int $company_id
     * @param string $waste_type
     * @param int $totalCustomers
     * @param float $totalQuantity
     * @return int|false
     */
    private function createRoute($company_id, $waste_type, $totalCustomers, $totalQuantity) {
        $routeDetails = "Route for {$waste_type} waste collection - Total Customers: {$totalCustomers}, Total Quantity: {$totalQuantity} kg";
        
        $insertRoute = "INSERT INTO routes (company_id, no_of_customers, route_details, is_accepted, accepted_at) VALUES (:company_id, :no_of_customers, :route_details, true, NOW())";
        $stmtRoute = $this->conn->prepare($insertRoute);
        $stmtRoute->bindParam(':company_id', $company_id, PDO::PARAM_INT);
        $stmtRoute->bindParam(':no_of_customers', $totalCustomers, PDO::PARAM_INT);
        $stmtRoute->bindParam(':route_details', $routeDetails, PDO::PARAM_STR);
        
        if ($stmtRoute->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    /**
     * Create route mappings
     * @param int $route_id
     * @param array $pickupRequests
     * @param string $requestIdColumn
     * @return bool
     */
    private function createRouteMappings($route_id, $pickupRequests, $requestIdColumn) {
        // Create route_request_mapping table if it doesn't exist
        $createMappingTable = "
            CREATE TABLE IF NOT EXISTS route_request_mapping (
                mapping_id INT AUTO_INCREMENT PRIMARY KEY,
                route_id INT NOT NULL,
                request_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE CASCADE,
                FOREIGN KEY (request_id) REFERENCES pickup_requests($requestIdColumn) ON DELETE CASCADE,
                UNIQUE KEY unique_route_request (route_id, request_id)
            )
        ";
        $this->conn->exec($createMappingTable);

        // Insert mapping entries for all pickup requests
        foreach ($pickupRequests as $request) {
            $insertMapping = "INSERT INTO route_request_mapping (route_id, request_id) VALUES (:route_id, :request_id)";
            $stmtMapping = $this->conn->prepare($insertMapping);
            $stmtMapping->bindParam(':route_id', $route_id, PDO::PARAM_INT);
            $stmtMapping->bindParam(':request_id', $request[$requestIdColumn], PDO::PARAM_INT);
            
            if (!$stmtMapping->execute()) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Record payment in database
     * @param int $company_id
     * @param int $route_id
     * @param string $card_number
     * @param string $cardholder_name
     * @param string $expiry_date
     * @param string $pin_number
     * @param float $amount
     * @return int|false
     */
    private function recordPayment($company_id, $route_id, $card_number, $cardholder_name, $expiry_date, $pin_number, $amount) {
        $query = "INSERT INTO payments (company_id, route_id, card_number, cardholder_name, expiry_date, pin_number, amount, payment_status) VALUES (:company_id, :route_id, :card_number, :cardholder_name, :expiry_date, :pin_number, :amount, 'Paid')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
        $stmt->bindParam(':route_id', $route_id, PDO::PARAM_INT);
        $stmt->bindParam(':card_number', $card_number, PDO::PARAM_STR);
        $stmt->bindParam(':cardholder_name', $cardholder_name, PDO::PARAM_STR);
        $stmt->bindParam(':expiry_date', $expiry_date, PDO::PARAM_STR);
        $stmt->bindParam(':pin_number', $pin_number, PDO::PARAM_STR);
        $stmt->bindParam(':amount', $amount);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    /**
     * Update pickup request statuses to 'Accepted'
     * @param array $requestIds
     * @param string $requestIdColumn
     * @return bool
     */
    private function updatePickupRequestStatuses($requestIds, $requestIdColumn) {
        $placeholders = str_repeat('?,', count($requestIds) - 1) . '?';
        $updateQuery = "UPDATE pickup_requests SET status = 'Accepted' WHERE $requestIdColumn IN ($placeholders)";
        $stmtUpdate = $this->conn->prepare($updateQuery);
        
        return $stmtUpdate->execute($requestIds);
    }

    /**
     * Update customer statuses to 'active'
     * @param array $requestIds
     * @param string $requestIdColumn
     * @return bool
     */
    private function updateCustomerStatuses($requestIds, $requestIdColumn) {
        $placeholders = str_repeat('?,', count($requestIds) - 1) . '?';
        $customerUpdateQuery = "
            UPDATE registered_users 
            SET disable_status = 'active' 
            WHERE user_id IN (
                SELECT DISTINCT pr.customer_id 
                FROM pickup_requests pr 
                WHERE pr.$requestIdColumn IN ($placeholders)
            ) AND role = 'customer'
        ";
        $stmtCustomerUpdate = $this->conn->prepare($customerUpdateQuery);
        
        return $stmtCustomerUpdate->execute($requestIds);
    }

    /**
     * Get company name by ID
     * @param int $company_id
     * @return string
     */
    private function getCompanyName($company_id) {
        $stmtCompany = $this->conn->prepare("
            SELECT name AS company_name
            FROM registered_users
            WHERE user_id = :company_id AND role = 'company'
        ");
        $stmtCompany->bindParam(':company_id', $company_id, PDO::PARAM_INT);
        $stmtCompany->execute();
        $companyRow = $stmtCompany->fetch(PDO::FETCH_ASSOC);
        
        return $companyRow ? $companyRow['company_name'] : '';
    }

    /**
     * Send notifications and emails
     * @param array $requestIds
     * @param array $pickupRequests
     * @param int $company_id
     * @param string $company_name
     * @param int $route_id
     * @param string $waste_type
     * @param int $totalCustomers
     * @param float $totalQuantity
     * @param string $requestIdColumn
     */
    private function sendNotificationsAndEmails($requestIds, $pickupRequests, $company_id, $company_name, $route_id, $waste_type, $totalCustomers, $totalQuantity, $requestIdColumn) {
        // Send emails to customers
        foreach ($requestIds as $request_id) {
            $customer_email = $this->getCustomerEmail($request_id, $requestIdColumn);
            if ($customer_email) {
                $this->sendCustomerEmail($customer_email, $company_name, $request_id);
            }
        }

        // Create notifications
        require_once '../utils/helpers.php';
        
        // Per-customer notifications
        foreach ($pickupRequests as $reqRow) {
            $customer_id_n = (int)$reqRow['customer_id'];
            $request_id_n = (int)$reqRow[$requestIdColumn];
            $msg = "Your pickup request #{$request_id_n} has been accepted by {$company_name}.";
            Helpers::createNotification($customer_id_n, $msg, $request_id_n, (int)$company_id, $customer_id_n);
        }

        // Company notification
        $companyMsg = "Payment successful. Route #{$route_id} for {$waste_type} activated. Customers: {$totalCustomers}, Total Qty: {$totalQuantity} kg.";
        Helpers::createNotification((int)$company_id, $companyMsg, null, (int)$company_id, null);
    }

    /**
     * Get customer email by request ID
     * @param int $request_id
     * @param string $requestIdColumn
     * @return string|null
     */
    private function getCustomerEmail($request_id, $requestIdColumn) {
        $stmtEmail = $this->conn->prepare("
            SELECT ru.email AS customer_email
            FROM pickup_requests pr
            INNER JOIN customers c ON pr.customer_id = c.customer_id
            INNER JOIN registered_users ru ON c.customer_id = ru.user_id
            WHERE pr.$requestIdColumn = :request_id
        ");
        $stmtEmail->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmtEmail->execute();
        $row = $stmtEmail->fetch(PDO::FETCH_ASSOC);
        
        return $row ? $row['customer_email'] : null;
    }

    /**
     * Send email to customer
     * @param string $customer_email
     * @param string $company_name
     * @param int $request_id
     */
    private function sendCustomerEmail($customer_email, $company_name, $request_id) {
        // Set POST data for email script
        $_POST['customer_email'] = $customer_email;
        $_POST['company_name'] = $company_name;
        $_POST['request_id'] = $request_id;
        
        // Include email script
        include __DIR__ . '/../Company/comemail.php';
    }

    /**
     * Get payment history for a company
     * @param int $company_id
     * @param int $limit
     * @return array
     */
    public function getPaymentHistory($company_id, $limit = 50) {
        try {
            if (!$company_id) {
                return ['success' => false, 'message' => 'Company ID is required'];
            }

            $query = "
                SELECT p.*, r.route_details, r.no_of_customers
                FROM payments p
                LEFT JOIN routes r ON p.route_id = r.route_id
                WHERE p.company_id = :company_id
                ORDER BY p.payment_id DESC
                LIMIT :limit
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'data' => $payments,
                'count' => count($payments)
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error getting payment history: ' . $e->getMessage()];
        }
    }

    /**
     * Get payment by ID
     * @param int $payment_id
     * @return array
     */
    public function getPaymentById($payment_id) {
        try {
            if (!$payment_id) {
                return ['success' => false, 'message' => 'Payment ID is required'];
            }

            $query = "
                SELECT p.*, r.route_details, r.no_of_customers, ru.name as company_name
                FROM payments p
                LEFT JOIN routes r ON p.route_id = r.route_id
                LEFT JOIN registered_users ru ON p.company_id = ru.user_id
                WHERE p.payment_id = :payment_id
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':payment_id', $payment_id, PDO::PARAM_INT);
            $stmt->execute();

            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                return ['success' => false, 'message' => 'Payment not found'];
            }

            return [
                'success' => true,
                'data' => $payment
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error getting payment: ' . $e->getMessage()];
        }
    }
}
?>
