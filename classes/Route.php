<?php

require_once '../config/database.php';

class Route {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Assign a route to a company
     * 
     * @param int $route_id The route ID to assign
     * @param int $company_id The company ID to assign the route to
     * @return array Result array with success status and message
     */
    public function assignRoute($route_id, $company_id) {
        try {
            if (!$this->db) {
                return ['success' => false, 'message' => 'Database connection failed'];
            }
            
            // Validate inputs
            if (!$route_id || !$company_id) {
                return ['success' => false, 'message' => 'Route ID and Company ID are required'];
            }
            
            // Check if route exists
            $query = "SELECT route_id, company_id FROM routes WHERE route_id = :route_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':route_id', $route_id, PDO::PARAM_INT);
            $stmt->execute();
            $route = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$route) {
                return ['success' => false, 'message' => 'Route not found'];
            }
            
            // Check if route is already assigned to a company
            if ($route['company_id'] && $route['company_id'] != $company_id) {
                return ['success' => false, 'message' => 'Route is already assigned to another company'];
            }
            
            // Check if company exists
            $query = "SELECT company_id FROM companies WHERE company_id = :company_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
            $stmt->execute();
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$company) {
                return ['success' => false, 'message' => 'Company not found'];
            }
            
            // Assign route to company
            $query = "UPDATE routes SET company_id = :company_id, assigned_at = NOW() WHERE route_id = :route_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
            $stmt->bindParam(':route_id', $route_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Route assigned successfully',
                    'data' => [
                        'route_id' => $route_id,
                        'company_id' => $company_id,
                        'assigned_at' => date('Y-m-d H:i:s')
                    ]
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to assign route'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get route details by route ID
     * 
     * @param int $route_id The route ID
     * @return array Result array with route details
     */
    public function getRouteDetails($route_id) {
        try {
            if (!$this->db) {
                return ['success' => false, 'message' => 'Database connection failed'];
            }
            
            $query = "SELECT r.*, c.company_reg_number 
                      FROM routes r 
                      LEFT JOIN companies c ON r.company_id = c.company_id 
                      WHERE r.route_id = :route_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':route_id', $route_id, PDO::PARAM_INT);
            $stmt->execute();
            $route = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$route) {
                return ['success' => false, 'message' => 'Route not found'];
            }
            
            return [
                'success' => true,
                'data' => $route
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all routes for a company
     * 
     * @param int $company_id The company ID
     * @return array Result array with routes
     */
    public function getCompanyRoutes($company_id) {
        try {
            if (!$this->db) {
                return ['success' => false, 'message' => 'Database connection failed'];
            }
            
            $query = "SELECT * FROM routes WHERE company_id = :company_id ORDER BY generated_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
            $stmt->execute();
            $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $routes
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Unassign a route from a company
     * 
     * @param int $route_id The route ID to unassign
     * @return array Result array with success status and message
     */
    public function unassignRoute($route_id) {
        try {
            if (!$this->db) {
                return ['success' => false, 'message' => 'Database connection failed'];
            }
            
            $query = "UPDATE routes SET company_id = NULL, assigned_at = NULL WHERE route_id = :route_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':route_id', $route_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Route unassigned successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to unassign route'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>
