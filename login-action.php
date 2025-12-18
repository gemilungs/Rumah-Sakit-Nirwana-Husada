<?php
require_once 'config.php';

function flash($key, $value = null) {
    if ($value === null) return $_SESSION[$key] ?? null;
    $_SESSION[$key] = $value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginId = trim($_POST['loginId'] ?? '');
    $password = $_POST['loginPassword'] ?? '';

    if (!$loginId || !$password) {
        flash('flash_error', 'Email/Username dan password wajib diisi.');
        header('Location: login.php?tab=login');
        exit;
    }

    try {
        $conn = getConnection();
        $stmt = $conn->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1');
        $stmt->execute([$loginId, $loginId]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            // compatibility with legacy pages using 'id' and 'login' flags
            $_SESSION['id'] = $user['id'];
            $_SESSION['login'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['foto_profil'] = $user['foto_profil'] ?? null;
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            // update last_login optionally
            $stmt = $conn->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
            $stmt->execute([$user['id']]);
            // redirect or JSON (AJAX)
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Login berhasil', 'data' => ['user' => $user, 'redirect' => $user['role'] === 'admin' ? 'dashboard-admin.php' : 'index.php']]);
                exit;
            } else {
                header('Location: ' . ($user['role'] === 'admin' ? 'dashboard-admin.php' : 'index.php'));
            }
            exit;
        } else {
            flash('flash_error', 'Username/email atau password salah.');
            header('Location: login.php?tab=login');
            exit;
        }
    } catch (PDOException $e) {
        flash('flash_error', 'Database error: ' . $e->getMessage());
        header('Location: login.php?tab=login');
        exit;
    }
}

header('Location: login.php');
exit;
?>
