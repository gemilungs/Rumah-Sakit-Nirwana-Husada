<?php
// Session utility functions
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';

function hydrateSessionFromDB() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) return;
    if (!empty($_SESSION['nama_lengkap']) && !empty($_SESSION['email'])) return;
    try {
        $conn = getConnection();
        $stmt = $conn->prepare('SELECT username, nama_lengkap, email, foto_profil, role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dbUser) {
            if (empty($_SESSION['username'])) $_SESSION['username'] = $dbUser['username'] ?? null;
            if (!empty($dbUser['nama_lengkap'])) $_SESSION['nama_lengkap'] = $dbUser['nama_lengkap'];
            if (!empty($dbUser['email'])) $_SESSION['email'] = $dbUser['email'];
            if (!empty($dbUser['foto_profil'])) $_SESSION['foto_profil'] = $dbUser['foto_profil'];
            if (!empty($dbUser['role'])) $_SESSION['role'] = $dbUser['role'];
        }
    } catch (PDOException $e) {
        // ignore
    }
}
