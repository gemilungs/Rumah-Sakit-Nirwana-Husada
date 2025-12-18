<?php
require_once 'config.php';
$error = '';
$success = '';
$activeTab = 'login';

// read flash messages using session
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['flash_error'])) {
  $error = $_SESSION['flash_error'];
  unset($_SESSION['flash_error']);
}
if (isset($_SESSION['flash_success'])) {
  $success = $_SESSION['flash_success'];
  unset($_SESSION['flash_success']);
}
// handle active tab from querystring
if (isset($_GET['tab']) && in_array($_GET['tab'], ['login','register'])) {
  $activeTab = $_GET['tab'];
}

// Register handled in register.php (separate handler)
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login & Register</title>
  <meta name="description" content="Login & Register" />
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Google Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Encode+Sans:wght@100..900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../css/login-register.css">
  <link rel="Icon" type="img/png" href="../Media/logo1.png">
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

        <div class="tabs">
          <div class="tab <?= $activeTab === 'login' ? 'tab--active' : 'tab--muted' ?>" id="tab-login">Login</div>
          <div class="tab <?= $activeTab === 'register' ? 'tab--active' : 'tab--muted' ?>" id="tab-register">Register</div>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger mt-3"> <?= htmlspecialchars($error) ?> </div>
        <?php elseif ($success): ?>
          <div class="alert alert-success mt-3"> <?= htmlspecialchars($success) ?> </div>
        <?php endif; ?>

        <div class="row p-3">
          <div class="col-6 text-center">
            <h3>Login</h3>
            <p>Masuk untuk mengelola akun dan booking janji temu.</p>
            <a href="login.php" class="btn btn-primary">Login</a>
          </div>
          <div class="col-6 text-center">
            <h3>Register</h3>
            <p>Buat akun baru untuk akses rekam medis dan layanan lainnya.</p>
            <a href="register.php" class="btn btn-outline-success">Daftar</a>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/login-register.js"></script>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        switchTo('<?= $activeTab ?>');
      });
    </script>

</body>
</html>

