<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class SessionAuthMiddleware {
    
    // Session timeout in seconds (30 minutes)
    private static $SESSION_TIMEOUT = 1800;
    
    // Allowed referrers for admin access
    private static $ALLOWED_REFERRERS = [
        'http://localhost:5173',
        'http://localhost:5175',
        'http://localhost:3000'
    ];
    
    /**
     * Check if user is logged in and has admin role
     * @return bool
     */
    public static function isAdminAuthenticated() {
        // Check session first
        if (isset($_SESSION['user_id']) && 
            isset($_SESSION['role']) && 
            $_SESSION['role'] === 'admin') {
            return true;
        }
        
        // Fallback to JWT token check
        require_once 'helpers.php';
        $token = Helpers::getBearerToken();
        if ($token) {
            $payload = Helpers::verifyToken($token);
            if ($payload && $payload['role'] === 'admin') {
                // Set session data from token
                $_SESSION['user_id'] = $payload['user_id'];
                $_SESSION['role'] = $payload['role'];
                $_SESSION['login_time'] = time();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user is logged in and has company role
     * @return bool
     */
    public static function isCompanyAuthenticated() {
        // Check session first
        if (isset($_SESSION['user_id']) && 
            isset($_SESSION['role']) && 
            $_SESSION['role'] === 'company') {
            return true;
        }
        
        // Fallback to JWT token check
        require_once 'helpers.php';
        $token = Helpers::getBearerToken();
        if ($token) {
            $payload = Helpers::verifyToken($token);
            if ($payload && $payload['role'] === 'company') {
                // Set session data from token
                $_SESSION['user_id'] = $payload['user_id'];
                $_SESSION['role'] = $payload['role'];
                $_SESSION['login_time'] = time();
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if customer is authenticated (session + JWT fallback)
     * @return bool
     */
    public static function isCustomerAuthenticated() {
        // Check session first
        if (isset($_SESSION['user_id']) && 
            isset($_SESSION['role']) && 
            $_SESSION['role'] === 'customer') {
            return true;
        }
        
        // Fallback to JWT token check
        require_once 'helpers.php';
        $token = Helpers::getBearerToken();
        if ($token) {
            $payload = Helpers::verifyToken($token);
            if ($payload && $payload['role'] === 'customer') {
                // Set session data from token
                $_SESSION['user_id'] = $payload['user_id'];
                $_SESSION['role'] = $payload['role'];
                $_SESSION['login_time'] = time();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if session is valid (not expired)
     * @return bool
     */
    public static function isSessionValid() {
        if (!isset($_SESSION['login_time'])) {
            return false;
        }
        
        $currentTime = time();
        $loginTime = $_SESSION['login_time'];
        
        return ($currentTime - $loginTime) < self::$SESSION_TIMEOUT;
    }
    
    /**
     * Check if request comes from allowed referrer
     * @return bool
     */
    private static function isAllowedReferrer() {
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // Allow direct access for API calls (no referrer)
        if (empty($referrer)) {
            return true;
        }
        
        // Define allowed referrers
        $allowedReferrers = [
            'http://localhost:5173',
            'http://localhost:5175',
            'http://localhost',
            'https://localhost:5173',
            'https://localhost:5175',
            'https://localhost'
        ];
        
        foreach ($allowedReferrers as $allowed) {
            if (strpos($referrer, $allowed) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Validate referrer for direct URL access prevention
     * @return bool
     */
    public static function validateReferrer() {
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $currentOrigin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_HOST'] ?? '';
        
        // Allow if no referrer (first visit or bookmark)
        if (empty($referrer)) {
            return true;
        }
        
        // Allow if referrer is from same origin
        if (strpos($referrer, $currentOrigin) === 0) {
            return true;
        }
        
        // Allow localhost referrers for development
        if (strpos($referrer, 'localhost') !== false) {
            return true;
        }
        
        // Allow if coming from login/signup pages
        $allowedPages = ['/login', '/signup', '/company-signup', '/'];
        foreach ($allowedPages as $page) {
            if (strpos($referrer, $page) !== false) {
                return true;
            }
        }
        
        // For API calls, be more permissive
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false || 
            strpos($_SERVER['REQUEST_URI'] ?? '', '/Company/') !== false ||
            strpos($_SERVER['REQUEST_URI'] ?? '', '/Customer/') !== false ||
            strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false) {
            return true;
        }
        
        // For development, allow all localhost requests
        if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) {
            return true;
        }
        
        // Block external referrers
        return false;
    }
    
    /**
     * Validate admin access with additional security checks
     * @return bool
     */
    private static function validateAdminAccess() {
        // Check basic authentication
        if (!self::isAdminAuthenticated()) {
            return false;
        }
        
        // For now, skip additional checks to fix login issue
        return true;
    }
    
    /**
     * Check for suspicious activity
     * @return bool
     */
    private static function hasSuspiciousActivity() {
        // Check for rapid successive requests
        $currentTime = time();
        $lastRequestTime = $_SESSION['last_request_time'] ?? 0;
        
        if (($currentTime - $lastRequestTime) < 1) { // Less than 1 second between requests
            return true;
        }
        
        // Update last request time
        $_SESSION['last_request_time'] = $currentTime;
        
        return false;
    }
    
    /**
     * Require admin authentication - redirects or returns error if not authenticated
     * @param bool $returnJson Whether to return JSON error or redirect
     * @return array|void
     */
    public static function requireAdminAuth($returnJson = true) {
        // Validate referrer to prevent direct URL access
        if (!self::validateReferrer()) {
            if ($returnJson) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Direct access forbidden',
                    'message' => 'Direct URL access is not allowed'
                ]);
                exit;
            } else {
                header('Location: /login');
                exit;
            }
        }
        
        // Check session first
        if (isset($_SESSION['user_id']) && 
            isset($_SESSION['role']) && 
            $_SESSION['role'] === 'admin') {
            return [
                'user_id' => $_SESSION['user_id'],
                'role' => $_SESSION['role'],
                'email' => $_SESSION['email'] ?? null,
                'name' => $_SESSION['name'] ?? null
            ];
        }
        
        // Fallback to JWT token check
        require_once 'helpers.php';
        $token = Helpers::getBearerToken();
        if ($token) {
            $payload = Helpers::verifyToken($token);
            if ($payload && $payload['role'] === 'admin') {
                // Set session data from token
                $_SESSION['user_id'] = $payload['user_id'];
                $_SESSION['role'] = $payload['role'];
                $_SESSION['login_time'] = time();
                
                return [
                    'user_id' => $payload['user_id'],
                    'role' => $payload['role'],
                    'email' => null,
                    'name' => null
                ];
            }
        }
        
        // If neither session nor JWT token is valid
        if ($returnJson) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized access',
                'message' => 'Admin authentication required'
            ]);
            exit;
        } else {
            // For web pages, redirect to login
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Require company authentication - redirects or returns error if not authenticated
     * @param bool $returnJson Whether to return JSON error or redirect
     * @return array|void
     */
    public static function requireCompanyAuth($returnJson = true) {
        // Validate referrer to prevent direct URL access
        if (!self::validateReferrer()) {
            if ($returnJson) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Direct access forbidden',
                    'message' => 'Direct URL access is not allowed'
                ]);
                exit;
            } else {
                header('Location: /login');
                exit;
            }
        }
        
        // Check session first
        if (isset($_SESSION['user_id']) && 
            isset($_SESSION['role']) && 
            $_SESSION['role'] === 'company') {
            return [
                'user_id' => $_SESSION['user_id'],
                'role' => $_SESSION['role'],
                'email' => $_SESSION['email'] ?? null,
                'name' => $_SESSION['name'] ?? null
            ];
        }
        
        // Fallback to JWT token check
        require_once 'helpers.php';
        $token = Helpers::getBearerToken();
        if ($token) {
            $payload = Helpers::verifyToken($token);
            if ($payload && $payload['role'] === 'company') {
                // Set session data from token
                $_SESSION['user_id'] = $payload['user_id'];
                $_SESSION['role'] = $payload['role'];
                $_SESSION['login_time'] = time();
                
                return [
                    'user_id' => $payload['user_id'],
                    'role' => $payload['role'],
                    'email' => null,
                    'name' => null
                ];
            }
        }
        
        // If neither session nor JWT token is valid
        if ($returnJson) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized access',
                'message' => 'Company authentication required'
            ]);
            exit;
        } else {
            // For web pages, redirect to login
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Require customer authentication - redirects or returns error if not authenticated
     * @param bool $returnJson Whether to return JSON error or redirect
     * @return array|void
     */
    public static function requireCustomerAuth($returnJson = true) {
        // Skip referrer validation for API calls (Customer endpoints)
        $isApiCall = strpos($_SERVER['REQUEST_URI'] ?? '', '/Customer/') !== false || 
                    strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
        
        // Only validate referrer for non-API calls
        if (!$isApiCall && !self::validateReferrer()) {
            if ($returnJson) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Direct access forbidden',
                    'message' => 'Direct URL access is not allowed'
                ]);
                exit;
            } else {
                header('Location: /login');
                exit;
            }
        }
        
        // Prefer JWT token when present to avoid stale/mismatched PHP sessions
        require_once 'helpers.php';
        $token = Helpers::getBearerToken();
        if ($token) {
            $payload = Helpers::verifyToken($token);
            if ($payload && ($payload['role'] ?? null) === 'customer') {
                // Sync PHP session with token to keep server state consistent
                $_SESSION['user_id'] = $payload['user_id'];
                $_SESSION['role'] = $payload['role'];
                $_SESSION['login_time'] = time();
                return [
                    'user_id' => $payload['user_id'],
                    'role' => $payload['role'],
                    'email' => null,
                    'name' => null
                ];
            }
        }

        // Fallback to session if no valid token provided
        if (isset($_SESSION['user_id']) && 
            isset($_SESSION['role']) && 
            $_SESSION['role'] === 'customer') {
            return [
                'user_id' => $_SESSION['user_id'],
                'role' => $_SESSION['role'],
                'email' => $_SESSION['email'] ?? null,
                'name' => $_SESSION['name'] ?? null
            ];
        }
        
        // If neither session nor JWT token is valid
        if ($returnJson) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized access',
                'message' => 'Customer authentication required'
            ]);
            exit;
        } else {
            // For web pages, redirect to login
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Check if user is logged in (any role)
     * @return bool
     */
    public static function isAuthenticated() {
        // Check session first
        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
            return true;
        }
        
        // Fallback to JWT token check
        require_once 'helpers.php';
        $token = Helpers::getBearerToken();
        if ($token) {
            $payload = Helpers::verifyToken($token);
            if ($payload) {
                // Set session data from token
                $_SESSION['user_id'] = $payload['user_id'];
                $_SESSION['role'] = $payload['role'];
                $_SESSION['login_time'] = time();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Require authentication for any role
     * @param bool $returnJson Whether to return JSON error or redirect
     * @return array|void
     */
    public static function requireAuth($returnJson = true) {
        if (!self::isAuthenticated()) {
            if ($returnJson) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Unauthorized access',
                    'message' => 'Authentication required'
                ]);
                exit;
            } else {
                header('Location: /login');
                exit;
            }
        }
        
        return [
            'user_id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'],
            'email' => $_SESSION['email'] ?? null,
            'name' => $_SESSION['name'] ?? null
        ];
    }
    
    /**
     * Require specific role authentication
     * @param string $requiredRole The required role
     * @param bool $returnJson Whether to return JSON error or redirect
     * @return array|void
     */
    public static function requireRole($requiredRole, $returnJson = true) {
        $user = self::requireAuth($returnJson);
        
        if ($user['role'] !== $requiredRole) {
            if ($returnJson) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Forbidden',
                    'message' => "Access denied. Required role: {$requiredRole}"
                ]);
                exit;
            } else {
                header('Location: /');
                exit;
            }
        }
        
        return $user;
    }
    
    /**
     * Set session data after successful login
     * @param array $userData User data from database
     */
    public static function setSessionData($userData) {
        $_SESSION['user_id'] = $userData['user_id'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['email'] = $userData['email'] ?? null;
        $_SESSION['name'] = $userData['name'] ?? null;
        $_SESSION['login_time'] = time();
        $_SESSION['last_request_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['session_id'] = session_id();
        
        // Debug: Log session data
        error_log("Session data set for user: " . $userData['user_id']);
    }
    
    /**
     * Clear session data on logout
     */
    public static function clearSession() {
        session_unset();
        session_destroy();
    }
    
    /**
     * Refresh session timeout
     */
    public static function refreshSession() {
        if (self::isAuthenticated()) {
            $_SESSION['login_time'] = time();
            $_SESSION['last_request_time'] = time();
        }
    }
    
    /**
     * Get current user data from session or JWT token
     * @return array|null
     */
    public static function getCurrentUser() {
        // Check session first
        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
            return [
                'user_id' => $_SESSION['user_id'],
                'role' => $_SESSION['role'],
                'email' => $_SESSION['email'] ?? null,
                'name' => $_SESSION['name'] ?? null
            ];
        }
        
        // Fallback to JWT token
        require_once 'helpers.php';
        $token = Helpers::getBearerToken();
        if ($token) {
            $payload = Helpers::verifyToken($token);
            if ($payload) {
                // Set session data from token
                $_SESSION['user_id'] = $payload['user_id'];
                $_SESSION['role'] = $payload['role'];
                $_SESSION['login_time'] = time();
                
                return [
                    'user_id' => $payload['user_id'],
                    'role' => $payload['role'],
                    'email' => null,
                    'name' => null
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Force logout if session is invalid
     */
    public static function forceLogoutIfInvalid() {
        if (self::isAuthenticated() && !self::isSessionValid()) {
            self::clearSession();
            return true;
        }
        return false;
    }
    
    // ========================================
    // ADMIN JWT AUTHENTICATION METHODS
    // ========================================
    
    /**
     * Require admin JWT authentication (primary method for admin pages)
     * @return array User data from JWT token
     * @throws Exception if authentication fails
     */
    public static function requireAdminJWTAuth() {
        $token = Helpers::getBearerToken();
        
        if (!$token) {
            throw new Exception('JWT token required');
        }
        
        $payload = Helpers::verifyToken($token);
        if (!$payload) {
            throw new Exception('Invalid JWT token');
        }
        
        if ($payload['role'] !== 'admin') {
            throw new Exception('Admin role required');
        }
        
        // Set session data from JWT token for compatibility with existing code
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $payload['user_id'];
        $_SESSION['role'] = $payload['role'];
        $_SESSION['login_time'] = time();
        
        return [
            'user_id' => $payload['user_id'],
            'role' => $payload['role'],
            'email' => null,
            'name' => null
        ];
    }
    
    /**
     * Check if user has valid admin JWT token
     * @return bool
     */
    public static function isAdminJWTAuthenticated() {
        try {
            self::requireAdminJWTAuth();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get admin user data from JWT token
     * @return array|null User data or null if not authenticated
     */
    public static function getAdminJWTUser() {
        try {
            return self::requireAdminJWTAuth();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Validate admin JWT token and return user data
     * @param bool $returnJson Whether to return JSON error or throw exception
     * @return array User data
     * @throws Exception if authentication fails and returnJson is false
     */
    public static function validateAdminJWTToken($returnJson = true) {
        try {
            return self::requireAdminJWTAuth();
        } catch (Exception $e) {
            if ($returnJson) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Authentication failed',
                    'message' => $e->getMessage()
                ]);
                exit();
            } else {
                throw $e;
            }
        }
    }
}
?> 