<?php require_once 'config.php'; require_once 'lib/session_utils.php'; hydrateSessionFromDB();
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) && empty($_SESSION['id'])) { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Ganti Password — Rumah Sakit Harmoni Sehat</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Encode+Sans:wght@100..900&display=swap" rel="stylesheet">

    <!-- Global CSS -->
    <link rel="stylesheet" href="../css/style.css">

    <!-- Page CSS (overrides) -->
    <link rel="stylesheet" href="../css/ganti-password.css">

    <!-- favicon -->
    <link rel="Icon" type="img/png" href="../Media/logo1.png">

</head>
<body class="profile-page">
  <!-- navbar removed on this page as requested -->
  <div style="height:24px"></div>
  <div class="container">
    <h1>Ganti Password</h1>
    <p class="subtitle">Amankan akun Anda dengan mengganti kata sandi secara berkala.</p>

    <form id="changeForm" novalidate>
      <div>
        <label class="field" for="currentPwd">Kata Sandi Saat Ini</label>
        <div class="field-row">
          <div class="col">
            <input id="currentPwd" name="currentPwd" type="password" autocomplete="current-password" required />
          </div>
          <div class="col fixed">
            <button id="toggleCurrent" class="btn small" type="button">Tampilkan</button>
          </div>
        </div>
        <div id="currentHelp" class="help">Masukkan kata sandi yang sedang Anda gunakan.</div>
      </div>

      <div>
        <label class="field" for="newPwd">Kata Sandi Baru</label>
        <div class="field-row">
          <div class="col">
            <input id="newPwd" name="newPwd" type="password" autocomplete="new-password" required />
          </div>
          <div class="col fixed">
            <button id="toggleNew" class="btn small" type="button" >Tampilkan</button>
          </div>
        </div>
        <div id="newHelp" class="help">Minimal 8 karakter. Gunakan kombinasi huruf, angka, dan simbol untuk keamanan lebih baik.</div>
      </div>

      <div>
        <label class="field" for="confirmPwd">Konfirmasi Kata Sandi</label>
        <div class="field-row">
          <div class="col">
            <input id="confirmPwd" name="confirmPwd" type="password" autocomplete="new-password" required />
          </div>
          <div class="col fixed">
            <button id="toggleConfirm" class="btn small" type="button" >Tampilkan</button>
          </div>
        </div>
        <div id="confirmHelp" class="help">Ketik ulang kata sandi baru Anda.</div>
      </div>

      <div class="controls">
        <button id="backToProfile" type="button" class="btn small">Kembali ke Profil</button>
        <div style="flex: 1;"></div>
        <button id="cancelBtn" type="button" class="btn small">Batal</button>
        <button id="submitBtn" type="submit" class="btn btn-primary">Ganti Password</button>
      </div>
    </form>
  </div>

  <!-- Footer removed on this page to keep focus on primary content -->
  <script src="../js/ganti-password.js"></script>

</body>
</html>
