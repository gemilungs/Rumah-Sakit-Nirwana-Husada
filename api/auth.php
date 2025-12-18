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

// Cek method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method not allowed', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    $conn = getConnection();
    
    switch ($action) {
        case 'register':
            // Validasi input
            if (empty($input['username']) || empty($input['email']) || empty($input['password']) || empty($input['nama_lengkap'])) {
                sendResponse(false, 'Semua field wajib diisi', null, 400);
            }
            

            // Email ke lowercase agar validasi tidak sensitif
            $input['email'] = strtolower($input['email']);
            // Debug log
            file_put_contents(__DIR__.'/debug_email.txt', "EMAIL: ".$input['email']."\n", FILE_APPEND);
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                file_put_contents(__DIR__.'/debug_email.txt', "FILTER_VAR FAIL\n", FILE_APPEND);
                sendResponse(false, 'Format email tidak valid', null, 400);
            }
            // Cek username sudah ada
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$input['username']]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Username sudah digunakan', null, 400);
            }
            // Cek email sudah ada
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$input['email']]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Email sudah terdaftar', null, 400);
            }
            // Hash password
            $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
            // Insert user baru
            $stmt = $conn->prepare("
                INSERT INTO users (username, email, password, nama_lengkap, no_telepon, alamat, tanggal_lahir, jenis_kelamin, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pasien')
            ");
            $stmt->execute([
                $input['username'],
                $input['email'],
                $hashedPassword,
                $input['nama_lengkap'],
                $input['no_telepon'] ?? null,
                $input['alamat'] ?? null,
                $input['tanggal_lahir'] ?? null,
                $input['jenis_kelamin'] ?? null
            ]);
            sendResponse(true, 'Registrasi berhasil! Silakan login.', ['user_id' => $conn->lastInsertId()]);
            break;
            
        case 'login':
            // Validasi input
            if (empty($input['username']) || empty($input['password'])) {
                sendResponse(false, 'Username dan password wajib diisi', null, 400);
            }
            
            // Cari user berdasarkan username atau email
            $stmt = $conn->prepare("
                SELECT * FROM users 
                WHERE (username = ? OR email = ?) AND is_active = 1
            ");
            $stmt->execute([$input['username'], $input['username']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                sendResponse(false, 'Username atau password salah', null, 401);
            }
            
            // Verifikasi password
            if (!password_verify($input['password'], $user['password'])) {
                sendResponse(false, 'Username atau password salah', null, 401);
            }
            
            // Update last login
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['foto_profil'] = $user['foto_profil'] ?? null;
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            
            // Hapus password dari response
            unset($user['password']);
            
            sendResponse(true, 'Login berhasil', [
                'user' => $user,
                'redirect' => $user['role'] === 'admin' ? 'dashboard-admin.php' : 'index.php'
            ]);
            break;
            
        case 'logout':
            session_destroy();
            sendResponse(true, 'Logout berhasil');
            break;
            
        case 'check-session':
            if (isset($_SESSION['user_id'])) {
                $stmt = $conn->prepare("SELECT id, username, email, role, nama_lengkap, foto_profil FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    sendResponse(true, 'Session aktif', ['user' => $user]);
                }
            }
            sendResponse(false, 'Session tidak aktif', null, 401);
            break;
            
        default:
            sendResponse(false, 'Action tidak valid', null, 400);
    }
    
} catch (PDOException $e) {
    sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    sendResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}
?>
