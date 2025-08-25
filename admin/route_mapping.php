<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'Trashroute';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle different HTTP methods
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Fetch all route mappings
            $stmt = $pdo->prepare("SELECT mapping_id, route_id, request_id, created_at FROM route_request_mapping ORDER BY created_at DESC");
            $stmt->execute();
            $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $mappings
            ]);
            break;
            
        case 'POST':
            // Create new mapping
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['route_id']) || !isset($data['request_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Route ID and Request ID are required']);
                exit();
            }
            
            $stmt = $pdo->prepare("INSERT INTO route_request_mapping (route_id, request_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$data['route_id'], $data['request_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Mapping created successfully',
                'mapping_id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'PUT':
            // Update mapping
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['mapping_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Mapping ID is required']);
                exit();
            }
            
            $stmt = $pdo->prepare("UPDATE route_request_mapping SET route_id = ?, request_id = ? WHERE mapping_id = ?");
            $stmt->execute([$data['route_id'], $data['request_id'], $data['mapping_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Mapping updated successfully'
            ]);
            break;
            
        case 'DELETE':
            // Delete mapping
            $mapping_id = $_GET['mapping_id'] ?? null;
            
            if (!$mapping_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Mapping ID is required']);
                exit();
            }
            
            $stmt = $pdo->prepare("DELETE FROM route_request_mapping WHERE mapping_id = ?");
            $stmt->execute([$mapping_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Mapping deleted successfully'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
