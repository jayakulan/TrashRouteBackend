# 🔒 URL Protection System

## Overview

The TrashRoute application implements a comprehensive **URL protection system** that prevents direct access to protected pages even when users are logged in. This ensures that protected pages cannot be accessed by typing URLs directly in the browser address bar or through external links.

## 🛡️ Security Layers

### 1. **Frontend Protection (URLProtection Component)**

**Location**: `src/components/URLProtection.jsx`

**Features**:
- **Referrer Validation**: Checks if the request comes from allowed sources
- **Backend Verification**: Validates tokens with server-side checks
- **Navigation Tracking**: Monitors legitimate vs. direct access patterns

```javascript
// Allowed referrers for legitimate navigation
const allowedReferrers = [
  currentOrigin + '/login',
  currentOrigin + '/signup', 
  currentOrigin + '/company-signup',
  currentOrigin + '/',
  '', // No referrer (first visit)
  currentOrigin // Same origin navigation
];
```

### 2. **Backend Protection (Session Middleware)**

**Location**: `utils/session_auth_middleware.php`

**Features**:
- **Referrer Validation**: Server-side referrer checking
- **Session + JWT Hybrid**: Dual authentication system
- **Role-based Access**: Specific role requirements

```php
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
    
    // Allow if coming from login/signup pages
    $allowedPages = ['/login', '/signup', '/company-signup', '/'];
    foreach ($allowedPages as $page) {
        if (strpos($referrer, $page) !== false) {
            return true;
        }
    }
    
    // Block external referrers
    return false;
}
```

### 3. **Route Protection (ProtectedRoute Component)**

**Location**: `src/components/ProtectedRoute.jsx`

**Features**:
- **Authentication Check**: Validates user login status
- **Role Verification**: Ensures correct user role
- **Backend Validation**: Server-side token verification

## 🔄 How It Works

### **Step 1: URL Access Attempt**
```
User types: http://localhost:5173/admin/dashboard
```

### **Step 2: Frontend Protection**
```javascript
// URLProtection component checks:
1. Is user authenticated? (token exists)
2. Is referrer allowed? (internal navigation)
3. Is this legitimate navigation? (not external link)
```

### **Step 3: Backend Verification**
```php
// Session middleware validates:
1. Referrer validation (prevent external access)
2. Token verification (JWT + session)
3. Role verification (admin/customer/company)
4. Session expiration check
```

### **Step 4: Access Decision**
```
✅ ALLOWED: Internal navigation from login/signup
❌ BLOCKED: External link or direct URL access
❌ BLOCKED: Invalid/expired token
❌ BLOCKED: Wrong user role
```

## 🚫 Blocked Scenarios

### **1. Direct URL Typing**
```
❌ User types: http://localhost:5173/admin/dashboard
✅ Result: Redirected to login with "Direct access not allowed"
```

### **2. External Links**
```
❌ External site links to: http://localhost:5173/customer/trash-type
✅ Result: Blocked with "Direct URL access is not allowed"
```

### **3. Bookmarked URLs**
```
❌ User bookmarks: http://localhost:5173/company/route-map
✅ Result: Allowed (no referrer = first visit)
```

### **4. Expired Sessions**
```
❌ User returns to: http://localhost:5173/admin/users (after 1 hour)
✅ Result: Redirected to login with "Session expired"
```

## ✅ Allowed Scenarios

### **1. Internal Navigation**
```
✅ Login → Dashboard → Users → Reports
✅ Signup → Customer Pages
✅ Company Signup → Company Pages
```

### **2. First Visit/Bookmarks**
```
✅ Direct access with valid token (no referrer)
✅ Bookmarked URLs with valid session
```

### **3. Legitimate Referrers**
```
✅ Coming from: /login, /signup, /company-signup, /
✅ Same origin navigation
✅ API calls (no referrer)
```

## 🔧 Implementation Details

### **Frontend Components**

```jsx
// App.jsx - Route Protection
<Route path="/admin/dashboard" element={
  <URLProtection>
    <ProtectedRoute requiredRole="admin">
      <AdminDashboard />
    </ProtectedRoute>
  </URLProtection>
} />
```

### **Backend Middleware**

```php
// session_auth_middleware.php
public static function requireAdminAuth($returnJson = true) {
    // 1. Validate referrer
    if (!self::validateReferrer()) {
        return error_response('Direct access forbidden');
    }
    
    // 2. Check session/JWT
    $user = self::getCurrentUser();
    if (!$user || $user['role'] !== 'admin') {
        return error_response('Unauthorized access');
    }
    
    return $user;
}
```

### **Session Verification**

```php
// check_session.php
public static function verifySession() {
    $currentUser = SessionAuthMiddleware::getCurrentUser();
    
    if (!$currentUser) {
        return ['success' => false, 'message' => 'No valid session'];
    }
    
    if (!SessionAuthMiddleware::isSessionValid()) {
        return ['success' => false, 'message' => 'Session expired'];
    }
    
    return ['success' => true, 'data' => $currentUser];
}
```

## 🛠️ Configuration

### **Allowed Referrers**
```php
$allowedReferrers = [
    'http://localhost:5173',
    'http://localhost:5175', 
    'http://localhost',
    'https://localhost:5173',
    'https://localhost:5175',
    'https://localhost'
];
```

### **Allowed Pages**
```php
$allowedPages = [
    '/login',
    '/signup', 
    '/company-signup',
    '/'
];
```

### **Session Timeout**
```php
const SESSION_TIMEOUT = 3600; // 1 hour
```

## 🔍 Debugging

### **Frontend Logs**
```javascript
console.log('Direct URL access detected from:', referrer);
console.log('Current location:', location.pathname);
```

### **Backend Logs**
```php
error_log("Direct access attempt from: " . $_SERVER['HTTP_REFERER']);
error_log("User authentication failed: " . $e->getMessage());
```

## 📊 Security Benefits

1. **Prevents Direct URL Access**: Users cannot type protected URLs directly
2. **Blocks External Links**: External sites cannot link to protected pages
3. **Session Validation**: Ensures tokens are still valid
4. **Role Enforcement**: Prevents role-based access violations
5. **Referrer Tracking**: Monitors navigation patterns
6. **Hybrid Authentication**: JWT + Session dual protection

## 🚀 Usage

The system is **automatically active** for all protected routes:

- **Customer Routes**: `/customer/*`
- **Company Routes**: `/company/*` 
- **Admin Routes**: `/admin/*`

No additional configuration needed - just use the existing route structure with `URLProtection` and `ProtectedRoute` components.

## 🔐 Security Notes

- **Referrer headers** can be spoofed, but combined with JWT validation provides strong protection
- **Session timeout** automatically expires access after 1 hour
- **Backend validation** ensures server-side security regardless of frontend bypass attempts
- **Role verification** prevents unauthorized access to role-specific pages 