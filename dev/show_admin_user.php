<?php
// DISABLED: moved to php/dev_disabled on 2025-12-17 for security.
// To restore, copy from php/dev_disabled back to php/dev.
exit('This dev helper has been disabled for security. See php/dev_disabled/ for backup.');

try {
    $conn = getConnection();
    // Check if id_card column exists; if not, instruct to run migration
    $colStmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'id_card'");
    $colStmt->execute();
    $colExists = (bool)$colStmt->fetch();
    if (!$colExists) {
        // Provide helpful info instead of querying non-existent column
        $admins = [];
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, role, is_active, id_card, foto_profil, created_at FROM users WHERE role = 'admin'");
        $stmt->execute();
        $admins = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error: ' . $e->getMessage());
}
?><!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin Users</title></head>
<body>
<h1>Admin users</h1>
<?php if (!$colExists): ?>
    <div style="padding:16px;background:#fff3cd;border:1px solid #ffeeba;border-radius:6px;margin-bottom:12px;">Kolom <strong>id_card</strong> tidak ditemukan di tabel <code>users</code>. Jalankan migrasi untuk menambah kolom, lalu set ID untuk admin.</div>
    <div style="margin-bottom:12px">Perintah CLI (di folder project):</div>
    <pre style="background:#f6f8fa;padding:10px;border-radius:4px">php php/migrations/add_id_card_column.php
php php/dev/set_admin_idcard.php ADMIN-001</pre>
    <div style="margin-bottom:12px">Atau buka (localhost only):</div>
    <ul><li><a href="../migrations/add_id_card_column.php">Jalankan migrasi (web belum ideal)</a></li>
    <li><a href="set_admin_idcard.php?id=ADMIN-001">Set admin ID (web)</a></li></ul>
    <hr />
<?php endif; ?>
<table border="1" cellpadding="6" cellspacing="0">
<thead><tr><th>id</th><th>username</th><th>email</th><th>role</th><th>is_active</th><?php if ($colExists) echo '<th>id_card</th>'; ?><th>foto_profil</th><th>created_at</th></tr></thead>
<tbody>
<?php foreach ($admins as $a): ?>
<tr>
<td><?= htmlspecialchars($a['id']) ?></td>
<td><?= htmlspecialchars($a['username']) ?></td>
<td><?= htmlspecialchars($a['email']) ?></td>
<td><?= htmlspecialchars($a['role']) ?></td>
<td><?= htmlspecialchars($a['is_active']) ?></td>
<?php if ($colExists): ?><td><?= htmlspecialchars($a['id_card']) ?></td><?php endif; ?>
<td><?= htmlspecialchars($a['foto_profil']) ?></td>
<td><?= htmlspecialchars($a['created_at']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<p>
Use this page only on localhost. If you need to reset the password, go to <a href="set_admin_password.php">Reset Admin Password</a>.
</p>
</body>
</html>
