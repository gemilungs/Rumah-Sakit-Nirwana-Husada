<?php
// DISABLED: moved to php/dev_disabled on 2025-12-17 for security.
// To restore, copy from php/dev_disabled back to php/dev.
exit('This dev helper has been disabled for security. See php/dev_disabled/ for backup.');
try {
    $conn = getConnection();
    $stmt = $conn->prepare('SELECT id, username, email, nama_lengkap, foto_profil, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        echo "User not found in DB.";
        exit;
    }
    // Update session fields
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['foto_profil'] = $user['foto_profil'];
    $_SESSION['role'] = $user['role'];
    echo "Session refreshed. <a href=\"../dashboard-admin.php\">Back to dashboard</a>";
} catch (PDOException $e) {
    echo 'DB error: ' . $e->getMessage();
}
