<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5175');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../utils/session_auth_middleware.php';

try {
    // Check admin authentication
    $adminUser = SessionAuthMiddleware::requireAdminAuth();
    
    // Get session information
    $sessionInfo = [
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'name' => $_SESSION['name'] ?? null,
        'login_time' => isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : null,
        'last_request_time' => isset($_SESSION['last_request_time']) ? date('Y-m-d H:i:s', $_SESSION['last_request_time']) : null,
        'ip_address' => $_SESSION['ip_address'] ?? null,
        'user_agent' => $_SESSION['user_agent'] ?? null,
        'session_timeout' => isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time'] + 1800) : null,
        'time_remaining' => isset($_SESSION['login_time']) ? (1800 - (time() - $_SESSION['login_time'])) : null,
        'is_valid' => SessionAuthMiddleware::isAdminAuthenticated(),
        'access_log' => $_SESSION['access_log'] ?? []
    ];
    
    // Check for security issues
    $securityIssues = [];
    
    // Check if session is about to expire (less than 5 minutes)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 1500) {
        $securityIssues[] = 'Session will expire soon';
    }
    
    // Check for multiple access from different IPs
    $accessLog = $_SESSION['access_log'] ?? [];
    $uniqueIPs = array_unique(array_column($accessLog, 'ip_address'));
    if (count($uniqueIPs) > 1) {
        $securityIssues[] = 'Multiple IP addresses detected';
    }
    
    // Check for rapid requests
    $recentRequests = array_filter($accessLog, function($log) {
        return (time() - strtotime($log['timestamp'])) < 60; // Last minute
    });
    if (count($recentRequests) > 10) {
        $securityIssues[] = 'High request rate detected';
    }
    
    echo json_encode([
        'success' => true,
        'session_info' => $sessionInfo,
        'security_issues' => $securityIssues,
        'total_access_attempts' => count($accessLog),
        'current_time' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied',
        'message' => $e->getMessage()
    ]);
}
?> 