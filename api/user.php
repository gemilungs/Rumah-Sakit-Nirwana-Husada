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
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Ambil profil user
    if ($method === 'GET') {
        checkAuth();
        
        $stmt = $conn->prepare("
            SELECT id, username, email, nama_lengkap, no_telepon, alamat, 
                   tanggal_lahir, jenis_kelamin, foto_profil, role, created_at, last_login
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendResponse(false, 'User tidak ditemukan', null, 404);
        }
        
        sendResponse(true, 'Profil berhasil diambil', $user);
    }
    
    // PUT - Update profil
    elseif ($method === 'PUT') {
        checkAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        // Debug logging for PUT profile updates (temporary)
        try {
            $dbgDir = __DIR__ . '/../logs'; if (!is_dir($dbgDir)) @mkdir($dbgDir, 0755, true);
            $dbgLine = date('Y-m-d H:i:s') . " | user_id=" . ($_SESSION['user_id'] ?? 'anon') . " | payload=" . json_encode($input) . " | ip=" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
            @file_put_contents($dbgDir . '/user_put_debug.log', $dbgLine, FILE_APPEND | LOCK_EX);
            // Also record a received notification for easier tracing
            @file_put_contents($dbgDir . '/user_update_received.log', date('Y-m-d H:i:s') . " | PUT received | user_id=" . ($_SESSION['user_id'] ?? 'anon') . " | ip=" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " | payload_preview=" . substr(json_encode($input),0,512) . "\n", FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {}
        
        // Cek email jika diubah
        if (!empty($input['email'])) {
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                sendResponse(false, 'Format email tidak valid', null, 400);
            }
            
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$input['email'], $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Email sudah digunakan user lain', null, 400);
            }
        }
        
        // Cek username jika diubah
        if (!empty($input['username'])) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$input['username'], $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Username sudah digunakan user lain', null, 400);
            }
        }
        
        $stmt = $conn->prepare("
            UPDATE users SET 
                username = COALESCE(?, username),
                email = COALESCE(?, email),
                nama_lengkap = COALESCE(?, nama_lengkap),
                no_telepon = ?,
                alamat = ?,
                tanggal_lahir = ?,
                jenis_kelamin = ?,
                foto_profil = ?
            WHERE id = ?
        ");
        
        $params = [
            $input['username'] ?? null,
            $input['email'] ?? null,
            $input['nama_lengkap'] ?? null,
            $input['no_telepon'] ?? null,
            $input['alamat'] ?? null,
            $input['tanggal_lahir'] ?? null,
            $input['jenis_kelamin'] ?? null,
            $input['foto_profil'] ?? null,
            $_SESSION['user_id']
        ];
        $stmt->execute($params);

        $affected = $stmt->rowCount();

        // Log update attempt and result for debugging
        try {
            $dbgDir = __DIR__ . '/../logs'; if (!is_dir($dbgDir)) @mkdir($dbgDir, 0755, true);
            $logLine = date('Y-m-d H:i:s') . " | user_update | user_id=" . ($_SESSION['user_id'] ?? 'anon') . " | affected=" . $affected . " | params=" . json_encode($params) . "\n";
            @file_put_contents($dbgDir . '/user_update_success.log', $logLine, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {}

        // Update session jika username atau nama berubah
        if (!empty($input['username'])) {
            $_SESSION['username'] = $input['username'];
        }
        if (!empty($input['nama_lengkap'])) {
            $_SESSION['nama_lengkap'] = $input['nama_lengkap'];
        }
        
        // Ambil user terbaru dan kembalikan di response
        $stmt = $conn->prepare("SELECT id, username, email, nama_lengkap, no_telepon, alamat, tanggal_lahir, jenis_kelamin, foto_profil, role, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $updatedUser = $stmt->fetch();
        $msg = $affected > 0 ? 'Profil berhasil diupdate' : 'Tidak ada perubahan pada profil';
        sendResponse(true, $msg, array_merge(['affected' => $affected, 'db_ok' => true], $updatedUser));
    }
    // POST - some clients may not support PUT; accept POST with action=update as fallback
    elseif ($method === 'POST') {
        checkAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['action']) || $input['action'] !== 'update') {
            sendResponse(false, 'Method not allowed', null, 405);
        }
        // Log received POST update for easier debugging
        try {
            $dbgDir = __DIR__ . '/../logs'; if (!is_dir($dbgDir)) @mkdir($dbgDir, 0755, true);
            @file_put_contents($dbgDir . '/user_update_received.log', date('Y-m-d H:i:s') . " | POST received | user_id=" . ($_SESSION['user_id'] ?? 'anon') . " | ip=" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " | payload_preview=" . substr(json_encode($input),0,512) . "\n", FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {}

        // Reuse PUT logic: validate email/username and update record
        if (!empty($input['email'])) {
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                sendResponse(false, 'Format email tidak valid', null, 400);
            }
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$input['email'], $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Email sudah digunakan user lain', null, 400);
            }
        }
        if (!empty($input['username'])) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$input['username'], $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Username sudah digunakan user lain', null, 400);
            }
        }

        $stmt = $conn->prepare("\n            UPDATE users SET \n                username = COALESCE(?, username),\n                email = COALESCE(?, email),\n                nama_lengkap = COALESCE(?, nama_lengkap),\n                no_telepon = ?,\n                alamat = ?,\n                tanggal_lahir = ?,\n                jenis_kelamin = ?,\n                foto_profil = ?\n            WHERE id = ?\n        ");

        $params = [
            $input['username'] ?? null,
            $input['email'] ?? null,
            $input['nama_lengkap'] ?? null,
            $input['no_telepon'] ?? null,
            $input['alamat'] ?? null,
            $input['tanggal_lahir'] ?? null,
            $input['jenis_kelamin'] ?? null,
            $input['foto_profil'] ?? null,
            $_SESSION['user_id']
        ];
        $stmt->execute($params);

        $affected = $stmt->rowCount();
        // Log update attempt and result for debugging
        try {
            $dbgDir = __DIR__ . '/../logs'; if (!is_dir($dbgDir)) @mkdir($dbgDir, 0755, true);
            $logLine = date('Y-m-d H:i:s') . " | user_update(post) | user_id=" . ($_SESSION['user_id'] ?? 'anon') . " | affected=" . $affected . " | params=" . json_encode($params) . "\n";
            @file_put_contents($dbgDir . '/user_update_success.log', $logLine, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {}

        if (!empty($input['username'])) { $_SESSION['username'] = $input['username']; }
        if (!empty($input['nama_lengkap'])) { $_SESSION['nama_lengkap'] = $input['nama_lengkap']; }

        $stmt = $conn->prepare("SELECT id, username, email, nama_lengkap, no_telepon, alamat, tanggal_lahir, jenis_kelamin, foto_profil, role, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $updatedUser = $stmt->fetch();
        $msg = $affected > 0 ? 'Profil berhasil diupdate' : 'Tidak ada perubahan pada profil';
        sendResponse(true, $msg, array_merge(['affected' => $affected, 'db_ok' => true], $updatedUser));
    }
    
    else {
        sendResponse(false, 'Method not allowed', null, 405);
    }
    
} catch (PDOException $e) {
    // Log DB error (do not expose internals to client)
    try {
        $dbgDir = __DIR__ . '/../logs'; if (!is_dir($dbgDir)) @mkdir($dbgDir, 0755, true);
        $msg = date('Y-m-d H:i:s') . " | PDOException | user_id=" . ($_SESSION['user_id'] ?? 'anon') . " | message=" . $e->getMessage() . "\n";
        @file_put_contents($dbgDir . '/user_update_error.log', $msg, FILE_APPEND | LOCK_EX);
    } catch (Exception $ee) {}
    sendResponse(false, 'Tidak dapat terhubung ke database. Silakan coba lagi nanti.', null, 500);
} catch (Exception $e) {
    try {
        $dbgDir = __DIR__ . '/../logs'; if (!is_dir($dbgDir)) @mkdir($dbgDir, 0755, true);
        $msg = date('Y-m-d H:i:s') . " | Exception | user_id=" . ($_SESSION['user_id'] ?? 'anon') . " | message=" . $e->getMessage() . "\n";
        @file_put_contents($dbgDir . '/user_update_error.log', $msg, FILE_APPEND | LOCK_EX);
    } catch (Exception $ee) {}
    sendResponse(false, 'Terjadi kesalahan pada server. Silakan coba lagi nanti.', null, 500);
}
?>
