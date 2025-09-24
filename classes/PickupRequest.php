<?php
// Trashroutefinal/TrashRouteBackend/classes/PickupRequest.php

class PickupRequest {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new pickup request
     * @param int $customer_id
     * @param string $waste_type
     * @param float $quantity
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function createRequest($customer_id, $waste_type, $quantity, $latitude, $longitude) {
        try {
            // Validate input parameters
            if (!$customer_id || !$waste_type || !$quantity || !$latitude || !$longitude) {
                return ['success' => false, 'message' => 'All parameters are required'];
            }

            // Validate waste type
            $valid_waste_types = ['plastic', 'paper', 'metal', 'glass'];
            if (!in_array(strtolower($waste_type), $valid_waste_types)) {
                return ['success' => false, 'message' => 'Invalid waste type'];
            }

            // Validate quantity
            if ($quantity <= 0) {
                return ['success' => false, 'message' => 'Quantity must be greater than 0'];
            }

            // Validate coordinates
            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                return ['success' => false, 'message' => 'Invalid coordinates'];
            }

            // Insert pickup request
            $query = "INSERT INTO pickup_requests (customer_id, waste_type, quantity, latitude, longitude, status, timestamp) 
                      VALUES (:customer_id, :waste_type, :quantity, :latitude, :longitude, 'Request received', NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->bindParam(':waste_type', $waste_type);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':latitude', $latitude);
            $stmt->bindParam(':longitude', $longitude);

            if ($stmt->execute()) {
                $request_id = $this->conn->lastInsertId();
                return [
                    'success' => true, 
                    'message' => 'Pickup request created successfully',
                    'data' => [
                        'request_id' => $request_id,
                        'customer_id' => $customer_id,
                        'waste_type' => $waste_type,
                        'quantity' => $quantity,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'status' => 'Request received',
                        'timestamp' => date('Y-m-d H:i:s')
                    ]
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create pickup request'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating pickup request: ' . $e->getMessage()];
        }
    }

    /**
     * Track pickup requests for a customer
     * @param int $customer_id
     * @return array
     */
    public function trackRequest($customer_id) {
        try {
            if (!$customer_id) {
                return ['success' => false, 'message' => 'Customer ID is required'];
            }

            // Get all pickup requests for this customer with company information
            $query = "SELECT 
                        pr.request_id,
                        pr.waste_type,
                        pr.quantity,
                        pr.latitude,
                        pr.longitude,
                        pr.status,
                        pr.timestamp,
                        pr.otp,
                        pr.otp_verified,
                        ru.name as customer_name,
                        ru.contact_number as customer_phone,
                        ru.address as customer_address,
                        ru_company.name as company_name,
                        ru_company.contact_number as company_phone,
                        ru_company.address as company_address
                      FROM pickup_requests pr
                      LEFT JOIN registered_users ru ON pr.customer_id = ru.user_id
                      LEFT JOIN notifications n ON pr.request_id = n.request_id AND n.company_id IS NOT NULL
                      LEFT JOIN companies c ON n.company_id = c.company_id
                      LEFT JOIN registered_users ru_company ON c.company_id = ru_company.user_id AND ru_company.role = 'company'
                      WHERE pr.customer_id = :customer_id 
                      ORDER BY pr.timestamp DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->execute();

            $pickup_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($pickup_requests)) {
                return [
                    'success' => true,
                    'data' => [
                        'pickup_requests' => [],
                        'waste_types' => [],
                        'has_requests' => false
                    ]
                ];
            }

            // Group requests by waste type and determine status progression
            $waste_types_data = [];
            $waste_type_statuses = [];

            foreach ($pickup_requests as $request) {
                // Normalize waste type to lowercase for frontend mapping
                $waste_type = strtolower($request['waste_type']);
                
                // Map database status to frontend step
                $currentStep = 0; // Default: Request Received
                $status = $request['status'];
                
                switch ($status) {
                    case 'Request received':
                        $currentStep = 0; // Request Received - always step 0
                        break;
                    case 'Accepted':
                        // Check if pickup is ongoing (has OTP but not verified)
                        if ($request['otp'] && !$request['otp_verified']) {
                            $currentStep = 2; // Ongoing - OTP generated but not verified
                        } else {
                            $currentStep = 1; // Scheduled - Accepted but no OTP yet
                        }
                        break;
                    case 'Completed':
                        $currentStep = 3; // Completed
                        break;
                    default:
                        $currentStep = 0;
                }

                // Only keep the MOST RECENT entry per waste type (query is DESC by timestamp)
                if (!isset($waste_types_data[$request['waste_type']])) {
                    $waste_types_data[$request['waste_type']] = [
                        'request_id' => $request['request_id'],
                        'waste_type' => $request['waste_type'],
                        'quantity' => $request['quantity'],
                        'status' => $status,
                        'current_step' => $currentStep,
                        'timestamp' => $request['timestamp'],
                        'otp' => $request['otp'],
                        'otp_verified' => $request['otp_verified'],
                        'latitude' => $request['latitude'],
                        'longitude' => $request['longitude'],
                        'company_name' => $request['company_name'],
                        'company_phone' => $request['company_phone'],
                        'company_address' => $request['company_address']
                    ];

                    $waste_type_statuses[$request['waste_type']] = $currentStep;
                }
            }

            // Prepare response data
            $response_data = [
                'pickup_requests' => $pickup_requests,
                'waste_types' => $waste_types_data,
                'waste_type_statuses' => $waste_type_statuses,
                'has_requests' => true
            ];

            return [
                'success' => true,
                'data' => $response_data
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error tracking pickup request: ' . $e->getMessage()];
        }
    }

    /**
     * Get pickup summary for a customer
     * @param int $customer_id
     * @return array
     */
    public function getPickupSummary($customer_id) {
        try {
            if (!$customer_id) {
                return ['success' => false, 'message' => 'Customer ID is required'];
            }

            // 1. Get the latest timestamp for this customer
            $query = "SELECT MAX(timestamp) as latest_time FROM pickup_requests WHERE customer_id = :customer_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->execute();
            $latest = $stmt->fetch(PDO::FETCH_ASSOC);
            $latest_time = $latest['latest_time'];

            if (!$latest_time) {
                return ['success' => false, 'message' => 'No pickup request found for this customer'];
            }

            // 2. Get all requests with that timestamp
            $query = "SELECT request_id, waste_type, quantity, latitude, longitude, status, timestamp 
                      FROM pickup_requests 
                      WHERE customer_id = :customer_id AND timestamp = :latest_time";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->bindParam(':latest_time', $latest_time);
            $stmt->execute();
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($requests)) {
                return ['success' => false, 'message' => 'No pickup request found for this customer'];
            }

            // 3. Aggregate waste types and quantities
            $waste_types = [];
            $total_weight = 0;
            foreach ($requests as $req) {
                $waste_types[] = $req['waste_type'];
                $total_weight += $req['quantity'];
            }
            $unique_waste_types = array_unique($waste_types);
            $waste_types_string = implode(', ', $unique_waste_types);

            // 4. Use the first request for location/status
            $first = $requests[0];
            
            // Get address from coordinates using reverse geocoding
            $address = '';
            $latitude = $first['latitude'];
            $longitude = $first['longitude'];
            
            // Simple reverse geocoding using Google Maps API
            if (!empty($latitude) && !empty($longitude)) {
                $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitude},{$longitude}&key=AIzaSyA5iEKgAwrJWVkCMAsD7_IilJ0YSVf_VGk";
                $response = file_get_contents($url);
                $data = json_decode($response, true);
                
                if ($data && $data['status'] === 'OK' && !empty($data['results'])) {
                    $address = $data['results'][0]['formatted_address'];
                }
            }
            
            $response_data = [
                'request_id' => $first['request_id'],
                'waste_types' => $waste_types_string,
                'approximate_total_weight' => $total_weight . ' kg',
                'pickup_location' => $address ? $address : 'Lat: ' . $latitude . ', Long: ' . $longitude,
                'coordinates' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ],
                'status' => $first['status'],
                'timestamp' => $first['timestamp'],
                'total_requests' => count($requests)
            ];

            return [
                'success' => true,
                'data' => $response_data,
                'message' => 'Pickup summary retrieved successfully'
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error getting pickup summary: ' . $e->getMessage()];
        }
    }

    /**
     * Update pickup request status
     * @param int $request_id
     * @param string $status
     * @return array
     */
    public function updateStatus($request_id, $status) {
        try {
            if (!$request_id || !$status) {
                return ['success' => false, 'message' => 'Request ID and status are required'];
            }

            $valid_statuses = ['Request received', 'Accepted', 'Completed', 'Cancelled'];
            if (!in_array($status, $valid_statuses)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }

            $query = "UPDATE pickup_requests SET status = :status WHERE request_id = :request_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':request_id', $request_id);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Status updated successfully',
                    'data' => [
                        'request_id' => $request_id,
                        'status' => $status
                    ]
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update status'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating status: ' . $e->getMessage()];
        }
    }

    /**
     * Get request by ID
     * @param int $request_id
     * @return array
     */
    public function getRequestById($request_id) {
        try {
            if (!$request_id) {
                return ['success' => false, 'message' => 'Request ID is required'];
            }

            $query = "SELECT * FROM pickup_requests WHERE request_id = :request_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':request_id', $request_id);
            $stmt->execute();

            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                return ['success' => false, 'message' => 'Request not found'];
            }

            return [
                'success' => true,
                'data' => $request
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error getting request: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel pickup request
     * @param int $request_id
     * @param int $customer_id
     * @return array
     */
    public function cancelRequest($request_id, $customer_id) {
        try {
            if (!$request_id || !$customer_id) {
                return ['success' => false, 'message' => 'Request ID and Customer ID are required'];
            }

            // Check if request exists and belongs to customer
            $checkQuery = "SELECT * FROM pickup_requests WHERE request_id = :request_id AND customer_id = :customer_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':request_id', $request_id);
            $checkStmt->bindParam(':customer_id', $customer_id);
            $checkStmt->execute();

            $request = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                return ['success' => false, 'message' => 'Request not found or does not belong to you'];
            }

            // Check if request can be cancelled (Request received or Pickup Scheduled status)
            if ($request['status'] !== 'Request received' && $request['status'] !== 'Pickup Scheduled') {
                return ['success' => false, 'message' => 'Can only cancel requests with "Request received" or "Pickup Scheduled" status. Current status: ' . $request['status']];
            }

            // Start transaction
            $this->conn->beginTransaction();

            // Delete the pickup request
            $deleteQuery = "DELETE FROM pickup_requests WHERE request_id = :request_id AND customer_id = :customer_id";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':request_id', $request_id);
            $deleteStmt->bindParam(':customer_id', $customer_id);

            if (!$deleteStmt->execute()) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Failed to cancel request'];
            }

            // Delete related route mappings if any
            $deleteMappingQuery = "DELETE FROM route_request_mapping WHERE request_id = :request_id";
            $deleteMappingStmt = $this->conn->prepare($deleteMappingQuery);
            $deleteMappingStmt->bindParam(':request_id', $request_id);
            $deleteMappingStmt->execute(); // Don't fail if no mappings exist

            // Delete related notifications
            $deleteNotificationQuery = "DELETE FROM notifications WHERE request_id = :request_id";
            $deleteNotificationStmt = $this->conn->prepare($deleteNotificationQuery);
            $deleteNotificationStmt->bindParam(':request_id', $request_id);
            $deleteNotificationStmt->execute(); // Don't fail if no notifications exist

            // Commit transaction
            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Request cancelled successfully',
                'data' => [
                    'request_id' => $request_id,
                    'waste_type' => $request['waste_type'],
                    'quantity' => $request['quantity'],
                    'cancelled_at' => date('Y-m-d H:i:s')
                ]
            ];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Error cancelling request: ' . $e->getMessage()];
        }
    }
}
?>
