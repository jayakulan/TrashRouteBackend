<?php
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Company.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once '../config/database.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $required_fields = ['name', 'email', 'subject', 'message'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Sanitize and validate input
    $name = trim($input['name']);
    $email = trim($input['email']);
    $subject = trim($input['subject']);
    $message = trim($input['message']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Validate name length
    if (strlen($name) > 100) {
        throw new Exception('Name is too long (maximum 100 characters)');
    }
    
    // Validate subject length
    if (strlen($subject) > 200) {
        throw new Exception('Subject is too long (maximum 200 characters)');
    }
    
    // Validate message length
    if (strlen($message) > 65535) {
        throw new Exception('Message is too long');
    }
    
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Prepare SQL statement
    $sql = "INSERT INTO contact_us (name, email, subject, message) VALUES (:name, :email, :subject, :message)";
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    
    // Bind parameters
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':subject', $subject);
    $stmt->bindParam(':message', $message);
    
    // Execute the statement
    if ($stmt->execute()) {
        $contact_id = $db->lastInsertId();
        
        // Log the contact submission (optional)
        error_log("Contact form submitted - ID: $contact_id, Name: $name, Email: $email, Subject: $subject");
        
        echo json_encode([
            'success' => true,
            'message' => 'Your message has been sent successfully! We will get back to you soon.',
            'contact_id' => $contact_id
        ]);
    } else {
        throw new Exception('Failed to save contact message');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again later.'
    ]);
    error_log("Contact form database error: " . $e->getMessage());
}
?> 