<?php
// Trashroutefinal/TrashRouteBackend/classes/RouteRequestMapping.php

class RouteRequestMapping {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Map a pickup request to a route
     * @param int $request_id
     * @param int $route_id
     * @return array
     */
    public function mapRequestRoute($request_id, $route_id) {
        try {
            // Validate input parameters
            if (!$request_id || !$route_id) {
                return ['success' => false, 'message' => 'Request ID and Route ID are required'];
            }

            // Check if request exists
            $checkRequestQuery = "SELECT request_id FROM pickup_requests WHERE request_id = :request_id";
            $checkRequestStmt = $this->conn->prepare($checkRequestQuery);
            $checkRequestStmt->bindParam(':request_id', $request_id);
            $checkRequestStmt->execute();

            if (!$checkRequestStmt->fetch()) {
                return ['success' => false, 'message' => 'Pickup request not found'];
            }

            // Check if route exists
            $checkRouteQuery = "SELECT route_id FROM routes WHERE route_id = :route_id";
            $checkRouteStmt = $this->conn->prepare($checkRouteQuery);
            $checkRouteStmt->bindParam(':route_id', $route_id);
            $checkRouteStmt->execute();

            if (!$checkRouteStmt->fetch()) {
                return ['success' => false, 'message' => 'Route not found'];
            }

            // Check if mapping already exists
            $checkMappingQuery = "SELECT mapping_id FROM route_request_mapping WHERE request_id = :request_id";
            $checkMappingStmt = $this->conn->prepare($checkMappingQuery);
            $checkMappingStmt->bindParam(':request_id', $request_id);
            $checkMappingStmt->execute();

            if ($checkMappingStmt->fetch()) {
                return ['success' => false, 'message' => 'Request is already mapped to a route'];
            }

            // Insert mapping
            $insertQuery = "INSERT INTO route_request_mapping (request_id, route_id, created_at) VALUES (:request_id, :route_id, NOW())";
            $insertStmt = $this->conn->prepare($insertQuery);
            $insertStmt->bindParam(':request_id', $request_id);
            $insertStmt->bindParam(':route_id', $route_id);

            if ($insertStmt->execute()) {
                $mapping_id = $this->conn->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Request successfully mapped to route',
                    'data' => [
                        'mapping_id' => $mapping_id,
                        'request_id' => $request_id,
                        'route_id' => $route_id,
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create mapping'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error mapping request to route: ' . $e->getMessage()];
        }
    }

    /**
     * Get mapped requests for a company with optional waste type filter
     * @param int $company_id
     * @param string $waste_type
     * @return array
     */
    public function getMappedRequests($company_id, $waste_type = null) {
        try {
            if (!$company_id) {
                return ['success' => false, 'message' => 'Company ID is required'];
            }

            // Query to fetch customer details using the route_request_mapping table
            $query = "
                SELECT 
                    pr.request_id,
                    pr.waste_type,
                    pr.quantity,
                    pr.latitude,
                    pr.longitude,
                    pr.status,
                    pr.timestamp,
                    ru.name as customer_name,
                    ru.contact_number as customer_phone,
                    ru.address as customer_address,
                    c.customer_id,
                    r.route_id
                FROM pickup_requests pr
                INNER JOIN customers c ON pr.customer_id = c.customer_id
                INNER JOIN registered_users ru ON c.customer_id = ru.user_id
                INNER JOIN route_request_mapping rrm ON pr.request_id = rrm.request_id
                INNER JOIN routes r ON rrm.route_id = r.route_id
                WHERE pr.status IN ('Request received', 'Pending', 'Accepted')
                AND r.company_id = :company_id
            ";

            $params = ['company_id' => $company_id];
            
            // Add waste type filter if provided
            if ($waste_type) {
                $query .= " AND pr.waste_type = :waste_type";
                $params['waste_type'] = $waste_type;
            }
            
            $query .= " ORDER BY pr.timestamp ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format the response
            $formattedCustomers = [];
            foreach ($customers as $customer) {
                $formattedCustomers[] = [
                    'id' => $customer['request_id'],
                    'request_id' => $customer['request_id'],
                    'route_id' => $customer['route_id'],
                    'address' => $customer['customer_address'] ?: 'Address not provided',
                    'contact' => $customer['customer_name'],
                    'notes' => "Waste Type: {$customer['waste_type']}, Quantity: {$customer['quantity']} kg",
                    'collected' => false,
                    'latitude' => floatval($customer['latitude']),
                    'longitude' => floatval($customer['longitude']),
                    'waste_type' => $customer['waste_type'],
                    'quantity' => intval($customer['quantity']),
                    'status' => $customer['status'],
                    'timestamp' => $customer['timestamp'],
                    'customer_phone' => $customer['customer_phone'] ?: 'Phone not provided'
                ];
            }

            return [
                'success' => true,
                'data' => $formattedCustomers,
                'count' => count($formattedCustomers),
                'waste_type' => $waste_type,
                'company_id' => $company_id
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error getting mapped requests: ' . $e->getMessage()];
        }
    }

    /**
     * Remove mapping between request and route
     * @param int $request_id
     * @return array
     */
    public function unmapRequestRoute($request_id) {
        try {
            if (!$request_id) {
                return ['success' => false, 'message' => 'Request ID is required'];
            }

            // Check if mapping exists
            $checkQuery = "SELECT mapping_id FROM route_request_mapping WHERE request_id = :request_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':request_id', $request_id);
            $checkStmt->execute();

            if (!$checkStmt->fetch()) {
                return ['success' => false, 'message' => 'No mapping found for this request'];
            }

            // Delete mapping
            $deleteQuery = "DELETE FROM route_request_mapping WHERE request_id = :request_id";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':request_id', $request_id);

            if ($deleteStmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Request successfully unmapped from route',
                    'data' => [
                        'request_id' => $request_id
                    ]
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to remove mapping'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error unmapping request from route: ' . $e->getMessage()];
        }
    }

    /**
     * Get mapping details by request ID
     * @param int $request_id
     * @return array
     */
    public function getMappingByRequestId($request_id) {
        try {
            if (!$request_id) {
                return ['success' => false, 'message' => 'Request ID is required'];
            }

            $query = "
                SELECT 
                    rrm.mapping_id,
                    rrm.request_id,
                    rrm.route_id,
                    rrm.created_at,
                    r.route_name,
                    c.company_name
                FROM route_request_mapping rrm
                INNER JOIN routes r ON rrm.route_id = r.route_id
                INNER JOIN companies c ON r.company_id = c.company_id
                WHERE rrm.request_id = :request_id
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':request_id', $request_id);
            $stmt->execute();

            $mapping = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$mapping) {
                return ['success' => false, 'message' => 'No mapping found for this request'];
            }

            return [
                'success' => true,
                'data' => $mapping
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error getting mapping details: ' . $e->getMessage()];
        }
    }

    /**
     * Get all mappings for a route
     * @param int $route_id
     * @return array
     */
    public function getMappingsByRouteId($route_id) {
        try {
            if (!$route_id) {
                return ['success' => false, 'message' => 'Route ID is required'];
            }

            $query = "
                SELECT 
                    rrm.mapping_id,
                    rrm.request_id,
                    rrm.route_id,
                    rrm.created_at,
                    pr.waste_type,
                    pr.quantity,
                    pr.status,
                    ru.name as customer_name,
                    ru.contact_number as customer_phone
                FROM route_request_mapping rrm
                INNER JOIN pickup_requests pr ON rrm.request_id = pr.request_id
                INNER JOIN customers c ON pr.customer_id = c.customer_id
                INNER JOIN registered_users ru ON c.customer_id = ru.user_id
                WHERE rrm.route_id = :route_id
                ORDER BY rrm.created_at ASC
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':route_id', $route_id);
            $stmt->execute();

            $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'data' => $mappings,
                'count' => count($mappings)
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error getting mappings for route: ' . $e->getMessage()];
        }
    }

    /**
     * Update mapping (move request from one route to another)
     * @param int $request_id
     * @param int $new_route_id
     * @return array
     */
    public function updateMapping($request_id, $new_route_id) {
        try {
            if (!$request_id || !$new_route_id) {
                return ['success' => false, 'message' => 'Request ID and new Route ID are required'];
            }

            // Check if new route exists
            $checkRouteQuery = "SELECT route_id FROM routes WHERE route_id = :route_id";
            $checkRouteStmt = $this->conn->prepare($checkRouteQuery);
            $checkRouteStmt->bindParam(':route_id', $new_route_id);
            $checkRouteStmt->execute();

            if (!$checkRouteStmt->fetch()) {
                return ['success' => false, 'message' => 'New route not found'];
            }

            // Update mapping
            $updateQuery = "UPDATE route_request_mapping SET route_id = :new_route_id, updated_at = NOW() WHERE request_id = :request_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':new_route_id', $new_route_id);
            $updateStmt->bindParam(':request_id', $request_id);

            if ($updateStmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Mapping updated successfully',
                    'data' => [
                        'request_id' => $request_id,
                        'route_id' => $new_route_id,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update mapping'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating mapping: ' . $e->getMessage()];
        }
    }
}
?>
