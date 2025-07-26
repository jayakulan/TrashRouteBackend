# Enhanced Admin Session Protection System

This document explains how the enhanced admin session protection system works in the TrashRoute application.

## Overview

The enhanced session protection system provides multiple layers of security to prevent unauthorized access to admin pages. It includes session timeout, IP validation, user agent checking, rate limiting, and comprehensive audit logging. This ensures that admin URLs cannot be accessed in other windows without proper authentication, even if an admin is logged in.

## Files Created/Modified

### 1. Enhanced Session Authentication Middleware
**File:** `utils/session_auth_middleware.php`

This middleware provides comprehensive session-based authentication for admin users with multiple security layers. Key features:
- `requireAdminAuth()` - Requires admin authentication with enhanced security checks
- `requireAuth()` - Requires any user authentication
- `requireRole($role)` - Requires specific role authentication
- `setSessionData()` - Sets session data after login with security metadata
- `clearSession()` - Clears session on logout
- `refreshSession()` - Extends session timeout
- `isSessionValid()` - Checks if session has expired
- `validateAdminAccess()` - Comprehensive admin access validation
- `hasSuspiciousActivity()` - Detects suspicious access patterns

### 2. Updated Login System
**File:** `api/auth/login.php`

Modified to set session data when admin users log in:
```php
// Set session data for admin users
if ($user['role'] === 'admin') {
    SessionAuthMiddleware::setSessionData($user);
}
```

### 3. Protected Admin Pages
**Files:** 
- `admin/managecustomers.php`
- `admin/deleteusers.php`

Both files now include session protection:
```php
// Check admin authentication
SessionAuthMiddleware::requireAdminAuth();
```

### 4. Enhanced Admin Pages
**Files:** 
- `admin/managecustomers.php` - Enhanced with multiple security checks
- `admin/deleteusers.php` - Enhanced with multiple security checks
- `admin/secure_admin_access.php` - Comprehensive security validation endpoint
- `admin/session_monitor.php` - Session monitoring and audit logging

### 5. New API Endpoints
**Files:**
- `api/auth/logout.php` - Logout endpoint
- `api/auth/check_session.php` - Session verification endpoint

## How It Works

### 1. Enhanced Login Process
1. Admin user logs in through the frontend
2. Backend validates credentials
3. If valid admin, comprehensive session data is set:
   - `$_SESSION['user_id']`
   - `$_SESSION['role']` = 'admin'
   - `$_SESSION['email']`
   - `$_SESSION['name']`
   - `$_SESSION['login_time']` - Session creation timestamp
   - `$_SESSION['ip_address']` - IP address at login
   - `$_SESSION['user_agent']` - Browser/device information
   - `$_SESSION['session_id']` - Unique session identifier

### 2. Multi-Layer Access Protection
When admin pages are accessed:
1. `SessionAuthMiddleware::validateAdminAccess()` performs comprehensive checks:
   - Basic authentication (user_id and role)
   - Session validity (not expired)
   - IP address validation
   - User agent validation
   - Rate limiting (prevents rapid successive requests)
   - Referrer validation (optional)
2. If any check fails, returns 401 Unauthorized error
3. If all checks pass, session is refreshed and access is granted

### 3. Session Management
- Sessions are managed server-side
- Session data persists across browser tabs/windows
- Logout clears all session data
- Session check endpoint verifies current authentication status

## Usage Examples

### Protecting Admin Pages
```php
<?php
require_once '../utils/session_auth_middleware.php';

// Check admin authentication
SessionAuthMiddleware::requireAdminAuth();
?>
```

### Checking Authentication Status
```php
<?php
$currentUser = SessionAuthMiddleware::getCurrentUser();
if ($currentUser) {
    // User is logged in
    echo "Welcome, " . $currentUser['name'];
} else {
    // User is not logged in
    echo "Please log in";
}
?>
```

### Logout Process
```php
<?php
SessionAuthMiddleware::clearSession();
// Redirect to login page
header('Location: /login');
exit;
?>
```

## Enhanced Security Features

1. **Multi-Layer Authentication**: Comprehensive session validation with multiple checks
2. **Session Timeout**: Automatic session expiration after 30 minutes of inactivity
3. **IP Address Validation**: Prevents access from different IP addresses
4. **User Agent Validation**: Ensures consistent browser/device access
5. **Rate Limiting**: Prevents rapid successive requests and potential attacks
6. **Referrer Validation**: Optional validation of request origin
7. **Audit Logging**: Comprehensive logging of all admin access attempts
8. **Suspicious Activity Detection**: Monitors for unusual access patterns
9. **Session Refresh**: Automatic extension of valid sessions
10. **Force Logout**: Automatic logout for invalid or expired sessions

## Testing

### Test Enhanced Authentication
Visit: `http://localhost/Trashroutefinal1/Trashroutefinal/TrashRouteBackend/admin/secure_admin_access.php`

This will:
- Return success with security checks if admin is properly authenticated
- Return 401 error with detailed reason if any security check fails

### Test Session Monitoring
Visit: `http://localhost/Trashroutefinal1/Trashroutefinal/TrashRouteBackend/admin/session_monitor.php`

This will:
- Show comprehensive session information
- Display security issues if detected
- Show access audit log

### Test Basic Authentication
Visit: `http://localhost/Trashroutefinal1/Trashroutefinal/TrashRouteBackend/admin/test_auth.php`

This will:
- Return success if admin is logged in
- Return 401 error if not authenticated

### Test Session Check
Visit: `http://localhost/Trashroutefinal1/Trashroutefinal/TrashRouteBackend/api/auth/check_session.php`

This will return current session status.

## Frontend Integration

The frontend should:
1. Call the session check endpoint on app startup
2. Redirect to login if session is invalid
3. Call logout endpoint when user logs out
4. Handle 401/403 errors appropriately

## Error Handling

The system returns appropriate HTTP status codes:
- `401 Unauthorized` - Not authenticated
- `403 Forbidden` - Wrong role
- `405 Method Not Allowed` - Wrong HTTP method
- `500 Internal Server Error` - Server errors

## Configuration

No additional configuration is required. The system uses PHP's built-in session management.

## Troubleshooting

### Common Issues

1. **Session not persisting**: Check PHP session configuration
2. **CORS errors**: Verify allowed origins in CORS headers
3. **401 errors**: Ensure user is logged in as admin
4. **Session timeout**: Sessions may expire; implement refresh logic if needed

### Debug Mode

To debug session issues, add this to any admin page:
```php
<?php
echo json_encode([
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'is_admin' => SessionAuthMiddleware::isAdminAuthenticated()
]);
?>
```

## Best Practices

1. Always call `SessionAuthMiddleware::requireAdminAuth()` at the top of admin pages
2. Use the logout endpoint to properly clear sessions
3. Handle authentication errors gracefully in the frontend
4. Implement session timeout if needed for security
5. Use HTTPS in production for secure session transmission 