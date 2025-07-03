<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config/database.php';
require_once 'utils/helpers.php';

try {
    // Test database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Check if tables exist
    $tables = ['registered_users', 'customers', 'companies', 'admins'];
    $existing_tables = [];
    
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE :table";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':table', $table);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            $existing_tables[] = $table;
        }
    }
    
    // Count users in each table
    $user_counts = [];
    
    if (in_array('registered_users', $existing_tables)) {
        $query = "SELECT COUNT(*) as count FROM registered_users";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $user_counts['registered_users'] = $result['count'];
        
        // Get sample users
        $query = "SELECT user_id, name, email, role, created_at FROM registered_users ORDER BY user_id DESC LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $recent_users = $stmt->fetchAll();
    }
    
    if (in_array('customers', $existing_tables)) {
        $query = "SELECT COUNT(*) as count FROM customers";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $user_counts['customers'] = $result['count'];
    }
    
    if (in_array('companies', $existing_tables)) {
        $query = "SELECT COUNT(*) as count FROM companies";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $user_counts['companies'] = $result['count'];
    }
    
    if (in_array('admins', $existing_tables)) {
        $query = "SELECT COUNT(*) as count FROM admins";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $user_counts['admins'] = $result['count'];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'database' => 'trashroute',
        'existing_tables' => $existing_tables,
        'user_counts' => $user_counts,
        'recent_users' => $recent_users ?? [],
        'timestamp' => date('Y-m-d H:i:s'),
        'registration_endpoint' => 'http://localhost/Trashroutefinal1/Trashroutefinal/Backend/api/auth/register.php',
        'login_endpoint' => 'http://localhost/Trashroutefinal1/Trashroutefinal/Backend/api/auth/login.php'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?> 