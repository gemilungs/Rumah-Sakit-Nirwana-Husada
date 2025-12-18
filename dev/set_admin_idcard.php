<?php
// DISABLED: moved to php/dev_disabled on 2025-12-17 for security.
// To restore, copy from php/dev_disabled back to php/dev.
exit('This dev helper has been disabled for security. See php/dev_disabled/ for backup.');

// Original file (moved to backup) would have started like this:
// require_once __DIR__ . '/../../php/config.php';
if (php_sapi_name() === 'cli') {
    // Running via PHP CLI
    $argv_id = $argv[1] ?? 'ADMIN-001';
    try {
        $conn = getConnection();
        // Add column if missing
        $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'id_card'");
        $stmt->execute();
        $exists = (bool)$stmt->fetch();
        if (!$exists) {
            $conn->exec("ALTER TABLE users ADD COLUMN id_card VARCHAR(255) NULL AFTER foto_profil");
            echo "Column id_card added.\n";
        }
        $stmt = $conn->prepare('UPDATE users SET id_card = ? WHERE username = ?');
        $stmt->execute([$argv_id, 'admin']);
        echo "Set admin id_card to {$argv_id}\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
    }
    exit;
}

// If invoked via web, ensure remote IP is localhost
$remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!in_array($remote, ['127.0.0.1', '::1'])) {
    http_response_code(403);
    echo "Access denied. This script is only for local development.";
    exit;
}
if (session_status() === PHP_SESSION_NONE) session_start();
try {
    $conn = getConnection();
    $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'id_card'");
    $stmt->execute();
    $exists = (bool)$stmt->fetch();
    if (!$exists) {
        $conn->exec("ALTER TABLE users ADD COLUMN id_card VARCHAR(255) NULL AFTER foto_profil");
        echo "Column id_card added.<br>";
    }
    $idcode = $_GET['id'] ?? 'ADMIN-001';
    $stmt = $conn->prepare('UPDATE users SET id_card = ? WHERE username = ?');
    $stmt->execute([$idcode, 'admin']);
    echo "Set admin id_card to: " . htmlspecialchars($idcode);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}

?>
