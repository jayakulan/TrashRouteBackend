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
    // Get current user from session or JWT
    $currentUser = SessionAuthMiddleware::getCurrentUser();
    
    if (!$currentUser) {
        echo json_encode([
            'success' => false,
            'message' => 'No valid session found',
            'data' => null
        ]);
        exit();
    }
    
    // Check if session is still valid
    if (!SessionAuthMiddleware::isSessionValid()) {
        echo json_encode([
            'success' => false,
            'message' => 'Session expired',
            'data' => null
        ]);
        exit();
    }
    
    // Return user information
    echo json_encode([
        'success' => true,
        'message' => 'Session is valid',
        'data' => [
            'user_id' => $currentUser['user_id'],
            'role' => $currentUser['role'],
            'email' => $currentUser['email'] ?? null,
            'name' => $currentUser['name'] ?? null,
            'session_valid' => true
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Session verification failed',
        'error' => $e->getMessage()
    ]);
}
?> 