<?php
require_once 'config.php';
require_once 'lib/session_utils.php';
hydrateSessionFromDB();

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) && empty($_SESSION['id'])) {
  header("Location: login.php");
  exit;
}

// Redirect admin users to admin dashboard if they accidentally load profile
if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
  header("Location: dashboard-admin.php");
  exit;
}

$conn = getConnection();
$id = $_SESSION['user_id'] ?? $_SESSION['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch booking history for this user
try {
  $stmt = $conn->prepare("SELECT b.id, b.tanggal_booking, b.nomor_antrian, b.status, d.nama AS nama_dokter, d.spesialisasi, j.ruangan
    FROM booking b
    JOIN dokter d ON b.dokter_id = d.id
    JOIN jadwal_dokter j ON b.jadwal_id = j.id
    WHERE b.user_id = ?
    ORDER BY b.tanggal_booking DESC, b.created_at DESC");
  $stmt->execute([$id]);
  $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $bookings = [];
}

// Helper: format date to Indonesian short month names
function formatDateIndo($dateStr) {
  if (!$dateStr) return '';
  $months = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
  $dt = new DateTime($dateStr);
  $d = $dt->format('d');
  $m = (int)$dt->format('n');
  $y = $dt->format('Y');
  return sprintf('%02d %s %s', $d, $months[$m] ?? $dt->format('M'), $y);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Profil Pengguna — Rumah Sakit Harmoni Sehat</title>

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
    <link rel="stylesheet" href="../css/profile.css">

    <!-- favicon -->
    <link rel="Icon" type="img/png" href="../Media/logo1.png">

</head>
<body class="profile-page">
  <div class="wrap">

    <!-- KIRI: Profil -->
    <div class="card card--left">
      <div class="section">
        <p id="profile-title-left" class="name"><?= htmlspecialchars($user['nama_lengkap'] ?? ($user['username'] ?? '')) ?></p>
        <p class="subtitle">No. Rekam: <strong><?= htmlspecialchars(isset($user['id']) ? sprintf('NH-%06d', $user['id']) : 'NH-001234') ?></strong></p>
      </div>

      <div class="card-body">
        <div class="section details">
          <div class="profile-fields">

            <div>
              <label class="field" for="name">Nama Lengkap</label>
                  <input class="field-input" id="name" value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" />
            </div>

            <div>
              <label class="field" for="phone">Telepon</label>
                  <input class="field-input" id="phone" value="<?= htmlspecialchars($user['no_telepon'] ?? '') ?>" />
            </div>

            <div>
              <label class="field" for="email">Email</label>
                  <input class="field-input" id="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" />
            </div>

            <div>
              <label class="field" for="address">Alamat</label>
                  <input class="field-input" id="address" value="<?= htmlspecialchars($user['alamat'] ?? '') ?>" />
            </div>

          </div>
        </div>
      </div>

      <div class="card-footer">
        <button id="saveBtn" class="btn btn-primary">Simpan Perubahan</button>
        <button id="cancelBtn" class="btn">Batal</button>
      </div>

    </div>

    <!-- KANAN: Riwayat & Janji Temu -->
    <div class="card card--right">
      <div class="section history-head">
        <div>
          <h3 id="history-title-right" class="title">Riwayat & Janji Temu</h3>
          <div class="sub-muted">Daftar singkat aktivitas dan janji temu Anda</div>
        </div>

        <div class="filter-controls">
          <div class="label">Filter</div>
          <select id="filter" class="filter-select">
            <option value="all">Semua</option>
            <option value="upcoming">Akan Datang</option>
            <option value="completed">Selesai</option>
            <option value="canceled">Dibatalkan</option>            <option value="unpaid">Belum Bayar</option>          </select>
        </div>

        <div id="historyCounts" style="margin-left:16px; text-align:right; color:#6b7280; font-size:13px; min-width:180px;">
          <!-- counts injected by JS -->
          <div id="countsSummary">—</div>
        </div>
      </div>

      <div class="appointments" id="apptsList">
        <?php if (empty($bookings)): ?>
          <div class="muted">Belum ada riwayat pemeriksaan.</div>
        <?php else: ?>
          <?php foreach ($bookings as $b):
            $date = htmlspecialchars(formatDateIndo($b['tanggal_booking']));
            $doc = htmlspecialchars($b['nama_dokter'] ?? '-');
            $poli = htmlspecialchars($b['spesialisasi'] ?? '');

            // Map booking status to css class and label
            $status_raw = $b['status'] ?? 'pending';
            $status_class = 'upcoming';
            $status_label = 'Menunggu';
            if ($status_raw === 'selesai') { $status_class = 'completed'; $status_label = 'Selesai'; }
            elseif ($status_raw === 'dibatalkan') { $status_class = 'canceled'; $status_label = 'Dibatalkan'; }
            elseif ($status_raw === 'dikonfirmasi') { $status_class = 'upcoming'; $status_label = 'Dikonfirmasi'; }
            elseif ($status_raw === 'pending') { $status_class = 'upcoming'; $status_label = 'Menunggu'; }
          ?>
          <div class="appt" data-status="<?= $status_class ?>">
            <div class="left">
              <div class="date-pill"><?= $date ?></div>
              <div class="meta">
                <div class="doc"><?= $doc ?></div>
                <div class="poli"><?= $poli ?></div>
              </div>
            </div>
            <div>
              <div class="status <?= $status_class ?>"><?= $status_label ?></div>
              <?php if (($b['payment_status'] ?? '') === 'belum_bayar' && ($b['status'] ?? '') !== 'dibatalkan'): ?>
                <div class="small text-danger mt-1">Belum bayar</div>
                <div style="margin-top:8px">
                  <a class="btn small btn-transparent" href="payment.php?booking_id=<?= urlencode($b['id']) ?>">Bayar Sekarang</a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="section section--spaced">
          <div class="align-right">
            <a href="booking.php"><button id="bookBtn" class="btn btn-primary">Buat Janji Baru</button></a>
          </div>
      </div>

      <div class="section">
        <div style="text-align:center; margin-top:8px;">
          <button id="loadMoreBtn" class="btn small" style="display:none">Muat lebih banyak</button>
        </div>
      </div>

    </div>
  </div>

  <!-- Toast container for notifications -->
  <div id="toast" style="display:none; position:fixed; bottom:20px; right:20px; background:#198754; color:#fff; padding:10px 14px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:9999;"></div>

  <!-- Footer removed on profile page to keep focus on primary content -->
  <script src="../js/profile.js"></script>

</body>
</html>
