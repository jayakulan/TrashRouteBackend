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
    // Multiple security checks
    $securityChecks = [];
    
    // 1. Basic admin authentication
    $adminUser = SessionAuthMiddleware::requireAdminAuth();
    $securityChecks['basic_auth'] = true;
    
    // 2. Check session validity
    if (!SessionAuthMiddleware::isAdminAuthenticated()) {
        throw new Exception('Session expired or invalid');
    }
    $securityChecks['session_valid'] = true;
    
    // 3. Check for suspicious activity
    $currentTime = time();
    $lastRequestTime = $_SESSION['last_request_time'] ?? 0;
    
    if (($currentTime - $lastRequestTime) < 1) {
        throw new Exception('Too many requests detected');
    }
    $securityChecks['rate_limit'] = true;
    
    // 4. Validate IP address (optional - can be disabled)
    $currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
    $sessionIP = $_SESSION['ip_address'] ?? '';
    
    if (!empty($sessionIP) && $currentIP !== $sessionIP) {
        throw new Exception('IP address mismatch detected');
    }
    $securityChecks['ip_validation'] = true;
    
    // 5. Check user agent (optional)
    $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $sessionUserAgent = $_SESSION['user_agent'] ?? '';
    
    if (!empty($sessionUserAgent) && $currentUserAgent !== $sessionUserAgent) {
        throw new Exception('User agent mismatch detected');
    }
    $securityChecks['user_agent'] = true;
    
    // 6. Refresh session to extend timeout
    SessionAuthMiddleware::refreshSession();
    
    // 7. Log access attempt
    $accessLog = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $adminUser['user_id'],
        'ip_address' => $currentIP,
        'user_agent' => $currentUserAgent,
        'session_id' => session_id(),
        'security_checks' => $securityChecks
    ];
    
    // Store access log in session for audit
    $_SESSION['access_log'][] = $accessLog;
    
    // Limit access log entries
    if (count($_SESSION['access_log']) > 10) {
        array_shift($_SESSION['access_log']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Secure admin access granted',
        'user' => $adminUser,
        'security_checks' => $securityChecks,
        'session_info' => [
            'session_id' => session_id(),
            'login_time' => date('Y-m-d H:i:s', $_SESSION['login_time']),
            'timeout' => date('Y-m-d H:i:s', $_SESSION['login_time'] + 1800)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied',
        'message' => $e->getMessage(),
        'security_issue' => true
    ]);
}
?> 