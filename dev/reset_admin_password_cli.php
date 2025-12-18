<?php
// DISABLED: moved to php/dev_disabled on 2025-12-17 for security.
// To restore, copy from php/dev_disabled back to php/dev.
exit('This dev helper has been disabled for security. See php/dev_disabled/ for backup.');
require_once __DIR__ . '/../config.php';
try {
    $conn = getConnection();
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE users SET password = ? WHERE username = ?');
    $stmt->execute([$hash, $username]);
    echo "Password updated for {$username}\n";
} catch (PDOException $e) {
    echo 'DB error: ' . $e->getMessage() . "\n";
}
