<?php
require_once 'helpers.php';

class AuthMiddleware {
    public static function authenticate() {
        // Get headers
        $headers = getallheaders();
        
        // Check for Authorization header
        if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
            Helpers::sendError('No authorization token provided', 401);
        }

        // Get token from header
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
        $token = str_replace('Bearer ', '', $authHeader);

        // Verify token
        $userData = Helpers::getUserFromToken($token);
        if (!$userData) {
            Helpers::sendError('Invalid or expired token', 401);
        }

        return $userData;
    }

    public static function requireRole($requiredRole) {
        $userData = self::authenticate();
        if ($userData['role'] !== $requiredRole) {
            Helpers::sendError('Unauthorized access', 403);
        }
        return $userData;
    }
} 