<?php
// Script to delete admin table (singular) from trashroute database

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        die("Database connection failed\n");
    }
    
    echo "Connected to database successfully\n";
    
    // Check if admin table (singular) exists
    $check_table = "SHOW TABLES LIKE 'admin'";
    $stmt = $db->prepare($check_table);
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "Admin table (singular) exists. Showing current contents...\n";
        
        // Show current admin table contents
        $query = "SELECT * FROM admin";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $admin_records = $stmt->fetchAll();
        
        if (empty($admin_records)) {
            echo "Admin table is empty\n";
        } else {
            echo "Found " . count($admin_records) . " records in admin table:\n";
            foreach ($admin_records as $record) {
                echo "- Record: " . print_r($record, true) . "\n";
            }
        }
        
        // Delete the admin table
        echo "\nDeleting admin table (singular)...\n";
        $drop_table = "DROP TABLE admin";
        $stmt = $db->prepare($drop_table);
        $stmt->execute();
        echo "Admin table (singular) deleted successfully!\n";
        
    } else {
        echo "Admin table (singular) does not exist\n";
    }
    
    // Also check for admins table (plural) to show the difference
    $check_admins_table = "SHOW TABLES LIKE 'admins'";
    $stmt = $db->prepare($check_admins_table);
    $stmt->execute();
    $admins_table_exists = $stmt->fetch();
    
    if ($admins_table_exists) {
        echo "\nAdmins table (plural) exists and will remain untouched\n";
    } else {
        echo "\nAdmins table (plural) does not exist\n";
    }
    
    // Show all remaining tables
    echo "\nAll tables in database:\n";
    $query = "SHOW TABLES";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tables = $stmt->fetchAll();
    
    foreach ($tables as $table) {
        echo "- " . $table[0] . "\n";
    }
    
    echo "\nAdmin table (singular) deletion completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 