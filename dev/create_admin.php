<?php
// DISABLED: moved to php/dev_disabled on 2025-12-17 for security.
// To restore, copy from php/dev_disabled back to php/dev.
exit('This dev helper has been disabled for security. See php/dev_disabled/ for backup.');

$allowed = ['127.0.0.1', '::1'];
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $allowed)) {
    http_response_code(403);
    exit('Access denied. This helper is available on localhost only.');
}

$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['nama_lengkap'] ?? 'Administrator');

    if (!$email) $errors[] = 'Email is required.';
    if (!$password) $errors[] = 'Password is required.';
    if (!$username) $username = explode('@', $email)[0];

    if (empty($errors)) {
        try {
            $conn = getConnection();

            // Check whether a user exists by email or username
            $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
            $stmt->execute([$email, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $hash = password_hash($password, PASSWORD_DEFAULT);

            if ($user) {
                // Update existing user to admin
                $stmt = $conn->prepare('UPDATE users SET username = ?, email = ?, password = ?, role = ?, is_active = 1, nama_lengkap = ? WHERE id = ?');
                $stmt->execute([$username, $email, $hash, 'admin', $name, $user['id']]);
                $success = 'Existing user updated to admin and password set.';
            } else {
                $stmt = $conn->prepare('INSERT INTO users (username, email, password, role, is_active, nama_lengkap, created_at) VALUES (?, ?, ?, ?, 1, ?, NOW())');
                $stmt->execute([$username, $email, $hash, 'admin', $name]);
                $success = 'Admin user created.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Create Admin (dev helper)</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;margin:28px}
        form{max-width:520px;padding:16px;border-radius:10px;background:#f7f7f7}
        label{display:block;margin:12px 0 6px}
        input[type=text], input[type=password]{width:100%;padding:10px;border-radius:6px;border:1px solid #ddd}
        .ok{color:green}
        .err{color:#a00}
        .note{font-size:13px;color:#555}
    </style>
</head>
<body>
    <h1>Create or Update Admin (localhost only)</h1>
    <?php if (!empty($errors)): ?>
        <div class="err"><?= htmlspecialchars(implode('<br>', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="ok"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Email</label>
        <input name="email" type="text" value="<?= htmlspecialchars($_POST['email'] ?? 'admin@admin.com') ?>" required>

        <label>Username (optional)</label>
        <input name="username" type="text" value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>">

        <label>Password</label>
        <input name="password" type="password" value="<?= htmlspecialchars($_POST['password'] ?? '12345678') ?>" required>

        <label>Nama Lengkap</label>
        <input name="nama_lengkap" type="text" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? 'Administrator') ?>">

        <div style="margin-top:12px">
            <button type="submit">Create / Update Admin</button>
        </div>
    </form>

    <p class="note">After using this, remove the file <code>php/dev/create_admin.php</code> for security.</p>
    <p><a href="set_admin_password.php">Reset password for username 'admin'</a> · <a href="show_admin_user.php">Show admin users</a></p>
</body>
</html>