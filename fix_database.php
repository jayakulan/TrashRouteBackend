<?php
// Database fix script for TrashRoute
// This script will check and create missing tables

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        die("Database connection failed\n");
    }
    
    echo "Connected to database successfully\n";
    
    // Check if admins table exists
    $check_table = "SHOW TABLES LIKE 'admins'";
    $stmt = $db->prepare($check_table);
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        echo "Admins table does not exist. Creating it...\n";
        
        // Create admins table
        $create_table = "CREATE TABLE admins (
            admin_id INT PRIMARY KEY,
            FOREIGN KEY (admin_id) REFERENCES registered_users(user_id) ON DELETE CASCADE
        )";
        
        $stmt = $db->prepare($create_table);
        $stmt->execute();
        echo "Admins table created successfully\n";
        
        // Check if admin users exist in registered_users
        $check_admin_users = "SELECT user_id FROM registered_users WHERE role = 'admin'";
        $stmt = $db->prepare($check_admin_users);
        $stmt->execute();
        $admin_users = $stmt->fetchAll();
        
        if (empty($admin_users)) {
            echo "No admin users found. Creating default admin...\n";
            
            // Insert default admin user
            $insert_admin = "INSERT INTO registered_users (name, email, password_hash, contact_number, address, role) 
                           VALUES ('Admin User', 'admin@gmail.com', 'admin', '1234567890', 'Admin Address', 'admin')";
            $stmt = $db->prepare($insert_admin);
            $stmt->execute();
            
            $admin_id = $db->lastInsertId();
            
            // Insert into admins table
            $insert_admin_record = "INSERT INTO admins (admin_id) VALUES (:admin_id)";
            $stmt = $db->prepare($insert_admin_record);
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->execute();
            
            echo "Default admin user created with ID: $admin_id\n";
        } else {
            echo "Found " . count($admin_users) . " admin users. Adding them to admins table...\n";
            
            foreach ($admin_users as $admin) {
                // Check if admin already exists in admins table
                $check_existing = "SELECT admin_id FROM admins WHERE admin_id = :admin_id";
                $stmt = $db->prepare($check_existing);
                $stmt->bindParam(':admin_id', $admin['user_id']);
                $stmt->execute();
                
                if (!$stmt->fetch()) {
                    // Insert into admins table
                    $insert_admin_record = "INSERT INTO admins (admin_id) VALUES (:admin_id)";
                    $stmt = $db->prepare($insert_admin_record);
                    $stmt->bindParam(':admin_id', $admin['user_id']);
                    $stmt->execute();
                    echo "Added admin user ID " . $admin['user_id'] . " to admins table\n";
                }
            }
        }
    } else {
        echo "Admins table already exists\n";
    }
    
    // Check other tables
    $tables = ['customers', 'companies', 'pickup_requests', 'routes', 'payments', 'customer_feedback', 'company_feedback', 'otp', 'notifications'];
    
    foreach ($tables as $table) {
        $check_table = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($check_table);
        $stmt->execute();
        $table_exists = $stmt->fetch();
        
        if (!$table_exists) {
            echo "Warning: Table '$table' does not exist\n";
        } else {
            echo "Table '$table' exists\n";
        }
    }
    
    // Show current admin users
    echo "\nCurrent admin users:\n";
    $query = "SELECT ru.user_id, ru.name, ru.email, ru.role 
              FROM registered_users ru 
              LEFT JOIN admins a ON ru.user_id = a.admin_id 
              WHERE ru.role = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admins = $stmt->fetchAll();
    
    foreach ($admins as $admin) {
        echo "- ID: " . $admin['user_id'] . ", Name: " . $admin['name'] . ", Email: " . $admin['email'] . "\n";
    }
    
    echo "\nDatabase check completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 