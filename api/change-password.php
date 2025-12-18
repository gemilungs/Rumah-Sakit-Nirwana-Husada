<?php
require_once '../config.php';

header('Content-Type: application/json');

// Helper function untuk response
function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Cek autentikasi
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, 'Unauthorized. Please login first.', null, 401);
    }
}

try {
    $conn = getConnection();
    
    // Hanya menerima POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Method not allowed', null, 405);
    }
    
    checkAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validasi input
    if (empty($input['password_lama']) || empty($input['password_baru'])) {
        sendResponse(false, 'Password lama dan password baru wajib diisi', null, 400);
    }
    
    if (strlen($input['password_baru']) < 6) {
        sendResponse(false, 'Password baru minimal 6 karakter', null, 400);
    }
    
    // Ambil password lama dari database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        sendResponse(false, 'User tidak ditemukan', null, 404);
    }
    
    // Verifikasi password lama
    if (!password_verify($input['password_lama'], $user['password'])) {
        sendResponse(false, 'Password lama tidak sesuai', null, 400);
    }
    
    // Hash password baru
    $hashedPassword = password_hash($input['password_baru'], PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
    
    sendResponse(true, 'Password berhasil diubah');
    
} catch (PDOException $e) {
    sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    sendResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}
?>
