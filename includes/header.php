<?php
// Shared header + nav + sidebar
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../lib/session_utils.php'; hydrateSessionFromDB();
?>
<nav class="navbar" id="navbar">
  <a href="index.php" class="logo">RS NIRWANA HUSADA</a>
  <button class="hamburger" id="hamburger" aria-label="Buka/Tutup menu" aria-expanded="false" aria-controls="sidebar">☰</button>
  <div class="nav-menu" id="navMenu">
    <a href="history.php" class="menu">Sejarah</a>
    <a href="layanan.php" class="menu">Layanan</a>
    <a href="jadwal.php" class="menu">Dokter</a>
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="booking.php" class="menu">Reservasi</a>
    <?php else: ?>
      <a href="login.php?next=booking.php" class="menu" title="Harus login untuk mengakses Reservasi">Reservasi</a>
    <?php endif; ?>
    <?php if (isset($_SESSION['user_id'])): ?>
      <div class="menu-user d-flex align-items-center gap-2 dropdown">
        <a href="#" class="menu-item dropdown-toggle d-flex align-items-center" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <?php if (!empty($_SESSION['foto_profil'])): ?>
            <img src="<?= htmlspecialchars($_SESSION['foto_profil']) ?>" alt="avatar" class="avatar-sm rounded-circle me-2" />
          <?php else: ?>
            <?php $name = trim($_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? ''); $parts = preg_split('/\s+/', $name); $initials = strtoupper((substr($parts[0] ?? '', 0,1) . substr($parts[1] ?? '', 0,1))); ?>
            <div class="avatar-sm avatar-initials me-2"><?= htmlspecialchars($initials ?: substr($name,0,1)) ?></div>
          <?php endif; ?>
          <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username']) ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
          <li><a class="dropdown-item" href="profile.php">Profil</a></li>
          <li><a class="dropdown-item" href="ganti-password.php">Ganti Password</a></li>
          <li><a class="dropdown-item logout-item" href="logout.php" onclick="return confirm('Yakin ingin logout?')">Logout</a></li>
        </ul>
      </div>
    <?php else: ?>
      <a href="login.php" class="menu">Akun</a>
    <?php endif; ?>
  </div>
</nav>

<div class="overlay" id="overlay" aria-hidden="true"></div>

<aside class="sidebar" id="sidebar" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="sidebarLabel">
  <button class="sidebar-close" id="sidebarClose" aria-label="Tutup menu">✕</button>

  <div class="sidebar-header">
    <div class="brand">
      <div id="sidebarLabel" class="name">RS Nirwana Husada</div>
    </div>
  </div>

  <div class="divider"></div>

  <nav class="menu-list" aria-label="Sidebar navigation">
    <a href="history.php" class="menu-item"><i class="bi bi-book" aria-hidden="true"></i>Sejarah</a>
    <a href="layanan.php" class="menu-item"><i class="bi bi-grid" aria-hidden="true"></i>Layanan</a>
    <a href="jadwal.php" class="menu-item"><i class="bi bi-person-badge" aria-hidden="true"></i>Dokter</a>
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="booking.php" class="menu-item"><i class="bi bi-calendar-check" aria-hidden="true"></i>Reservasi</a>
    <?php else: ?>
      <a href="login.php?next=booking.php" class="menu-item" title="Harus login untuk mengakses Reservasi"><i class="bi bi-calendar-check" aria-hidden="true"></i>Reservasi</a>
    <?php endif; ?>
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="profile.php" class="menu-item"><i class="bi bi-person-circle" aria-hidden="true"></i><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></a>
    <?php else: ?>
      <a href="login.php" class="menu-item"><i class="bi bi-person-circle" aria-hidden="true"></i>Akun</a>
    <?php endif; ?>
  </nav>

  <div class="footer">Butuh bantuan? <br> Hubungi +62 21 1500 225</div>
</aside>
