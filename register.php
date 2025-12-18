<?php
require_once 'config.php';
$error = '';
$success = '';
$activeTab = 'register';
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
if (isset($_GET['tab']) && $_GET['tab'] === 'register') {
        $activeTab = 'register';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Register</title>
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
                <h2>Register</h2>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form id="registerForm" method="post" action="register-action.php">
                    <div class="mb-3">
                        <label for="fullName">Nama Lengkap</label>
                        <input id="fullName" name="fullName" type="text" placeholder="Nama sesuai KTP" required>
                    </div>
                    <div class="mb-3">
                        <label for="regEmail">Alamat Email</label>
                        <input type="email" id="regEmail" name="regEmail" placeholder="yourname@domain.com" required>
                    </div>
                    <div class="mb-3">
                        <label for="dob">Tanggal Lahir</label>
                        <input id="dob" name="dob" type="date" required>
                    </div>
                    <div class="mb-3">
                        <label>Jenis Kelamin</label>
                        <div class="gender-group" role="radiogroup" aria-label="Jenis Kelamin">
                            <div class="gender-option">
                                <input type="radio" id="gender_m" name="gender" value="L" required>
                                <label for="gender_m">Laki-laki</label>
                            </div>
                            <div class="gender-option">
                                <input type="radio" id="gender_f" name="gender" value="P">
                                <label for="gender_f">Perempuan</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address">Alamat</label>
                        <textarea id="address" name="address" required></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="regPassword">Password</label>
                            <input id="regPassword" name="regPassword" type="password" required>
                        </div>
                        <div class="col">
                            <label for="regConfirm">Konfirmasi Password</label>
                            <input id="regConfirm" name="regConfirm" type="password" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-12">
                        <p class="form-text text-muted mb-0">Tip: Buat kata sandi unik (minimal 8 karakter). Gunakan huruf besar, angka, dan simbol agar kata sandi lebih aman.</p>
                      </div>
                    </div>
                    <button class="btn-full" type="submit">Daftar</button>
                </form>
                <div class="below-links small mt-2">Sudah punya akun? <a href="login.php" id="gotoLogin" role="link" tabindex="0" onclick="event.stopImmediatePropagation(); event.preventDefault(); window.location.href='login.php';">Login</a></div> 
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/login-register.js"></script>
</body>
</html>
