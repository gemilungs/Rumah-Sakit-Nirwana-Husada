<?php
// generate_admin_token.php
// Creates a short-lived signed token for the logged-in admin (localhost/secure sessions only)
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}
try {
    $uid = (int)$_SESSION['user_id'];
    $exp = time() + 300; // token valid for 5 minutes
    $payload = json_encode(['uid' => $uid, 'exp' => $exp]);
    $payload_b64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $payload_b64, APP_SECRET);
    $token = $payload_b64 . '.' . $sig;
    echo json_encode(['token' => $token, 'expires_at' => $exp]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
}
