<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

session_start();

echo json_encode([
    'success' => true,
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? 'NOT_SET',
    'user_name' => $_SESSION['user_name'] ?? 'NOT_SET',
    'user_email' => $_SESSION['user_email'] ?? 'NOT_SET',
    'all_session_data' => $_SESSION
]);
?> 