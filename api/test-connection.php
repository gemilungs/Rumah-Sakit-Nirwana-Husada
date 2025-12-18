<?php
// Enable error reporting untuk development
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Koneksi Database</title>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";
echo "</head><body>";

echo "<h1>🔍 Test Koneksi Database RS Nirwana Husada</h1>";

// Test 1: Cek file config.php
echo "<h2>Test 1: File Config</h2>";
if (file_exists('../config.php')) {
    echo "<p class='success'>✅ File config.php ditemukan</p>";
    require_once '../config.php';
} else {
    echo "<p class='error'>❌ File config.php tidak ditemukan di: " . realpath('../config.php') . "</p>";
    exit;
}

// Test 2: Cek konstanta database
echo "<h2>Test 2: Konfigurasi Database</h2>";
echo "<p class='info'>Host: " . DB_HOST . "</p>";
echo "<p class='info'>Database: " . DB_NAME . "</p>";
echo "<p class='info'>User: " . DB_USER . "</p>";

// Test 3: Test koneksi
echo "<h2>Test 3: Koneksi Database</h2>";
try {
    $conn = getConnection();
    echo "<p class='success'>✅ Koneksi database BERHASIL!</p>";
    
    // Test 4: Cek database exists
    echo "<h2>Test 4: Database & Tables</h2>";
    $stmt = $conn->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    echo "<p class='success'>✅ Database aktif: " . $result['db_name'] . "</p>";
    
    // Test 5: Cek tabel
    echo "<h3>Daftar Tabel:</h3>";
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li class='success'>✅ " . $table . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ Tidak ada tabel! Silakan import file rumah_sakit.sql</p>";
    }
    
    // Test 6: Cek data admin
    echo "<h2>Test 5: Data Admin</h2>";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "<p class='success'>✅ Ditemukan " . $result['count'] . " admin user</p>";
        
        $stmt = $conn->query("SELECT username, email, nama_lengkap FROM users WHERE role = 'admin' LIMIT 1");
        $admin = $stmt->fetch();
        echo "<p class='info'>Username: " . $admin['username'] . "</p>";
        echo "<p class='info'>Email: " . $admin['email'] . "</p>";
        echo "<p class='info'>Nama: " . $admin['nama_lengkap'] . "</p>";
    } else {
        echo "<p class='error'>❌ Admin user tidak ditemukan</p>";
    }
    
    // Test 7: Cek data dokter
    echo "<h2>Test 6: Data Dokter</h2>";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM dokter");
    $result = $stmt->fetch();
    echo "<p class='success'>✅ Ditemukan " . $result['count'] . " dokter</p>";
    
    echo "<h2>✅ SEMUA TEST BERHASIL!</h2>";
    echo "<p class='success'>Backend siap digunakan.</p>";
    echo "<p><a href='../index.php'>← Kembali ke Homepage</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error koneksi database:</p>";
    echo "<pre class='error'>" . $e->getMessage() . "</pre>";
    
    echo "<h3>Solusi:</h3>";
    echo "<ol>";
    echo "<li>Pastikan XAMPP MySQL sudah running</li>";
    echo "<li>Cek kredensial di config.php (user: root, password: kosong)</li>";
    echo "<li>Pastikan database 'rumah_sakit' sudah dibuat</li>";
    echo "<li>Import file database/rumah_sakit.sql</li>";
    echo "</ol>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error:</p>";
    echo "<pre class='error'>" . $e->getMessage() . "</pre>";
}

echo "</body></html>";
?>
