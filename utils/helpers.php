<?php
class Helpers {
    private static $secretKey = "trashroute_secret_key_2024"; // You should store this in an environment variable in production
    
    // Send JSON response
    public static function sendResponse($data, $status = 200, $message = '') {
        http_response_code($status);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        $response = [
            'success' => $status < 400,
            'message' => $message,
            'data' => $data
        ];
        
        echo json_encode($response);
        exit;
    }
    
    // Send error response
    public static function sendError($message, $status = 400) {
        self::sendResponse(null, $status, $message);
    }
    
    // Validate email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    // Hash password
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    // Verify password
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Generate JWT token (simple implementation)
    public static function generateToken($user_id, $role) {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // Token valid for 1 hour

        $payload = [
            'user_id' => $user_id,
            'role' => $role,
            'iat' => $issuedAt,
            'exp' => $expirationTime
        ];

        // Create JWT Header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        // Create JWT Payload
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

        // Create Signature
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secretKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        
        return $jwt;
    }
    
    // Verify JWT token
    public static function verifyToken($token) {
        try {
            $tokenParts = explode('.', $token);
            if (count($tokenParts) != 3) {
                return false;
            }

            $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
            $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
            $signatureProvided = $tokenParts[2];

            // Check the expiration time
            $payloadData = json_decode($payload, true);
            if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
                return false;
            }

            // Verify signature
            $base64UrlHeader = $tokenParts[0];
            $base64UrlPayload = $tokenParts[1];
            $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secretKey, true);
            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            if ($base64UrlSignature !== $signatureProvided) {
                return false;
            }

            return $payloadData;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Get authorization header
    public static function getAuthorizationHeader() {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        return $headers;
    }
    
    // Get bearer token
    public static function getBearerToken() {
        $headers = self::getAuthorizationHeader();
        
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    // Validate required fields
    public static function validateRequired($data, $required_fields) {
        $missing = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        return $missing;
    }
    
    // Sanitize input
    public static function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize($value);
            }
        } else {
            $data = htmlspecialchars(strip_tags(trim($data)));
        }
        
        return $data;
    }
    
    // Get current user from token
    public static function getCurrentUser() {
        $token = self::getBearerToken();
        
        if (!$token) {
            return null;
        }
        
        $payload = self::verifyToken($token);
        
        if (!$payload) {
            return null;
        }
        
        return $payload;
    }
    
    // Check if user is authenticated
    public static function isAuthenticated() {
        return self::getCurrentUser() !== null;
    }
    
    // Check if user has specific role
    public static function hasRole($required_role) {
        $user = self::getCurrentUser();
        
        if (!$user) {
            return false;
        }
        
        return $user['role'] === $required_role;
    }
    
    // Generate OTP
    public static function generateOTP($length = 6) {
        return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
    
    // Create notification
    public static function createNotification($user_id, $message, $request_id = null, $company_id = null) {
        global $db;
        
        $query = "INSERT INTO notifications (user_id, request_id, company_id, message) 
                  VALUES (:user_id, :request_id, :company_id, :message)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':request_id', $request_id);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->bindParam(':message', $message);
        
        return $stmt->execute();
    }
    
    // Get user role from database
    public static function getUserRole($user_id) {
        global $db;
        
        $query = "SELECT role FROM registered_users WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? $result['role'] : null;
    }
    
    // Check if user exists in specific role table
    public static function checkUserRole($user_id, $role) {
        global $db;
        
        $table_map = [
            'customer' => 'customers',
            'company' => 'companies',
            'admin' => 'admins'
        ];
        
        if (!isset($table_map[$role])) {
            return false;
        }
        
        $table = $table_map[$role];
        $id_column = $role . '_id';
        
        $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$id_column} = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    // Get user from token
    public static function getUserFromToken($token) {
        if (!$token) {
            return null;
        }
        
        $payload = self::verifyToken($token);
        if (!$payload) {
            return null;
        }
        
        return [
            'user_id' => $payload['user_id'],
            'role' => $payload['role']
        ];
    }
}
?>