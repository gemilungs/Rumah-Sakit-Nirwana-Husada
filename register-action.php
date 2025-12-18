<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function flash($key, $value = null) {
    if ($value === null) return $_SESSION[$key] ?? null;
    $_SESSION[$key] = $value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['fullName'] ?? '');
    $email = strtolower(trim($_POST['regEmail'] ?? ''));
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $alamat = trim($_POST['address'] ?? '');
    $password = $_POST['regPassword'] ?? '';
    $confirm = $_POST['regConfirm'] ?? '';

    if (!$nama || !$email || !$dob || !$gender || !$alamat || !$password || !$confirm) {
        flash('flash_error', 'Semua field wajib diisi.');
        header('Location: register.php?tab=register');
        exit;
    }
    if ($password !== $confirm) {
        flash('flash_error', 'Password dan konfirmasi tidak sama.');
        header('Location: register.php?tab=register');
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('flash_error', 'Format email tidak valid.');
        header('Location: register.php?tab=register');
        exit;
    }
    if (strlen($password) < 8) {
        flash('flash_error', 'Password minimal 8 karakter.');
        header('Location: register.php?tab=register');
        exit;
    }

    $usernameBase = strtolower(preg_replace('/\s+/', '', $nama));
    $username = $usernameBase . rand(100,999);

    try {
        $conn = getConnection();
        // check email
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            flash('flash_error', 'Email sudah terdaftar.');
            header('Location: register.php?tab=register');
            exit;
        }
        // ensure username unique
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        while ($stmt->fetch()) {
            $username = $usernameBase . rand(100,999);
            $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
        }

        // insert
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (username, email, password, nama_lengkap, alamat, tanggal_lahir, jenis_kelamin, role) VALUES (?, ?, ?, ?, ?, ?, ?, "pasien")');
        $stmt->execute([$username, $email, $hash, $nama, $alamat, $dob, $gender]);
        $newUserId = $conn->lastInsertId();
        // After register, auto-login: set session
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['id'] = $newUserId; // legacy
        $_SESSION['username'] = $username;
        $_SESSION['nama_lengkap'] = $nama;
        $_SESSION['foto_profil'] = null;
        $_SESSION['role'] = 'pasien';
        $_SESSION['email'] = $email;

        // If AJAX request, return JSON
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Registrasi berhasil', 'data' => ['user_id' => $newUserId]]);
            exit;
        } else {
            flash('flash_success', 'Registrasi berhasil! Silakan login. Username: ' . $username);
            header('Location: index.php');
        }
        exit;
    } catch (PDOException $e) {
        flash('flash_error', 'Database error: ' . $e->getMessage());
        header('Location: register.php?tab=register');
        exit;
    }
}

header('Location: register.php');
exit;
?>
