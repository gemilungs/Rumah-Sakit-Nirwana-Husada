<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rumah_sakit');

// Koneksi Database
function getConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch(PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

// Start session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application secret used for short-lived signed tokens (change in production)
// You can set APP_SECRET via environment variable APP_SECRET; otherwise a default placeholder will be used.
define('APP_SECRET', getenv('APP_SECRET') ?: 'change_this_to_a_strong_secret');

// Timezone
date_default_timezone_set("Asia/Jakarta");
?>
