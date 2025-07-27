<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Test session functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && $input['action'] === 'set') {
        $_SESSION['test_data'] = $input['data'] ?? 'test_value';
        $_SESSION['test_time'] = time();
        
        echo json_encode([
            'success' => true,
            'message' => 'Session data set',
            'session_id' => session_id(),
            'session_data' => $_SESSION
        ]);
    } elseif (isset($input['action']) && $input['action'] === 'clear') {
        session_unset();
        session_destroy();
        
        echo json_encode([
            'success' => true,
            'message' => 'Session cleared',
            'session_id' => session_id()
        ]);
    }
} else {
    // GET request - return current session info
    echo json_encode([
        'success' => true,
        'session_id' => session_id(),
        'session_data' => $_SESSION,
        'session_status' => session_status(),
        'session_name' => session_name(),
        'session_save_path' => session_save_path(),
        'cookies' => $_COOKIE,
        'server_info' => [
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]
    ]);
}
?> 