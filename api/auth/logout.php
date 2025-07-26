<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require_once '../../utils/session_auth_middleware.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Clear session data
    SessionAuthMiddleware::clearSession();
    
    // Destroy the session completely
    session_destroy();
    
    // Clear all session cookies
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Clear any other cookies that might be set
    $cookies = $_COOKIE;
    foreach ($cookies as $name => $value) {
        if (strpos($name, 'token') !== false || 
            strpos($name, 'user') !== false || 
            strpos($name, 'company') !== false || 
            strpos($name, 'customer') !== false) {
            setcookie($name, '', time() - 3600, '/');
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Logout successful',
        'data' => [
            'session_cleared' => true,
            'cookies_cleared' => true
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Logout failed',
        'error' => $e->getMessage()
    ]);
}
?> 