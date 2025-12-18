<?php
require_once 'config.php';
$error = '';
$success = '';
$activeTab = 'login';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
if (isset($_SESSION['flash_error'])) {
        $error = $_SESSION['flash_error'];
        unset($_SESSION['flash_error']);
}
if (isset($_SESSION['flash_success'])) {
        $success = $_SESSION['flash_success'];
        unset($_SESSION['flash_success']);
}
if (isset($_GET['tab']) && $_GET['tab'] === 'login') {
        $activeTab = 'login';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../css/login-register.css">
    <link rel="icon" type="img/png" href="../Media/logo1.png">
</head>
<body>
    <div class="wrap">
        <div class="side">
            <h1>RS Nirwana Husada</h1>
            <p>Terpercaya selama puluhan tahun - layanan medis lengkap dan teknologi modern.</p>
            <div class="card mt-3">
            <h3>Kenapa daftar?</h3>
            <ul>
                <li>Pesan janji temu online</li>
                <li>Akses rekam medis</li>
            </ul>
            <p class="keterangan">Data pasien tersimpan dengan aman.</p>
        </div>
      <div class="text-center mt-3">
        <small>&copy; 2025 RS Nirwana Husada</small>
      </div>
        </div>
        <main>
            <div class="card" id="authCard">
                <h2>Login</h2>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" action="login-action.php">
                    <div class="mb-3">
                        <label for="loginId">Email atau Username</label>
                        <input id="loginId" name="loginId" type="text" placeholder="yourname@domain.com" required>
                    </div>
                    <div class="mb-3">
                        <label for="loginPassword">Password</label>
                        <div class="position-relative">
                            <input id="loginPassword" name="loginPassword" type="password" placeholder="Masukkan password" required>
                            <button type="button" id="toggleLoginPwd" class="position-absolute top-50 end-0 translate-middle-y btn" aria-pressed="false" aria-label="Tampilkan password" tabindex="0">Show</button>
                        </div>
                    </div>
                    <button class="btn-full" type="submit">Masuk</button>
                </form>
                <div class="below-links small mt-2">Belum punya akun? <a href="register.php" id="gotoRegister" role="link" tabindex="0" onclick="event.stopImmediatePropagation(); event.preventDefault(); window.location.href='register.php';">Daftar</a></div> 
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/login-register.js"></script>
</body>
</html>
