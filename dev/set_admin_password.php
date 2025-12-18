<?php
// DISABLED: moved to php/dev_disabled on 2025-12-17 for security.
// To restore, copy from php/dev_disabled back to php/dev.
exit('This dev helper has been disabled for security. See php/dev_disabled/ for backup.');

// Original file (moved to backup) would have started like this:
// require_once __DIR__ . '/../config.php';
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', ['127.0.0.1', '::1'])) {
    http_response_code(403);
    exit('Access denied.');
}
if (session_status() === PHP_SESSION_NONE) session_start();

$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    if (!$pw) $error = 'Password required';
    if (!$error) {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        try {
            $conn = getConnection();
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE username = ?');
            $stmt->execute([$hash, 'admin']);
            $success = 'Admin password updated.';
        } catch (PDOException $e) {
            $error = 'DB error: ' . $e->getMessage();
        }
    }
}
?><!doctype html>
<html>
<head><meta charset="utf-8"><title>Reset Admin Password</title></head>
<body>
<h1>Reset Admin Password</h1>
<?php if ($error): ?><div style="color:red"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div style="color:green"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<form method="post">
<label>New password: <input name="password" type="password" required></label>
<button type="submit">Set</button>
</form>
<p><a href="show_admin_user.php">Back to admin list</a></p>
</body>
</html>
