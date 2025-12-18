<?php require_once 'config.php'; require_once 'lib/session_utils.php'; hydrateSessionFromDB();
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) && empty($_SESSION['id'])) { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - RS Nirwana Husada</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Encode+Sans:wght@100..900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/dashboard-admin.css">

    <!-- favicon -->
    <link rel="Icon" type="img/png" href="../Media/logo1.png">


</head>
<body>

  <aside class="sidebar">
    <div class="brand">RS Nirwana Husada</div>
    <a href="#" class="nav-link active" data-view="dashboard"><i class="bi bi-speedometer"></i> Dashboard</a>
    <a href="#" class="nav-link" data-view="dokter"><i class="bi bi-people"></i> Manajemen Dokter</a>
    <a href="#" class="nav-link" data-view="jadwal"><i class="bi bi-calendar-event"></i> Jadwal Dokter</a>
    <a href="#" class="nav-link" data-view="pengaturan"><i class="bi bi-gear"></i> Pengaturan</a>
    <div class="logout-wrap"><a href="#"  class="logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a></div>
  </aside>

  <main class="main">
    <div class="topbar">
      <div>
        <h1 class="page-title" id="pageTitle">Dashboard</h1>
        <div class="subtitle" id="pageSub">Aktivitas & statistik</div>
      </div>
      <div class="topbar-right">
        <div class="user-row">
          <?php
            // Prefer a dedicated admin avatar if available in Media/admin.png (fallback to admin.jpg)
            $adminAvatarPathPng = __DIR__ . '/../Media/admin.png';
            $adminAvatarPathJpg = __DIR__ . '/../Media/admin.jpg';
            if (file_exists($adminAvatarPathPng)) {
                $avatarSrc = '../Media/admin.png';
            } elseif (file_exists($adminAvatarPathJpg)) {
                $avatarSrc = '../Media/admin.jpg';
            } else {
                $avatarSrc = $_SESSION['foto_profil'] ?? 'https://via.placeholder.com/42';
            }

            // Determine display name/email — prefer admin record from DB (role = 'admin') when possible
            $displayName = trim($_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Admin Utama');
            $displayEmail = $_SESSION['email'] ?? 'admin@rsn.husada';
            if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                try {
                    $tmpConn = getConnection();
                    $stmtAdmin = $tmpConn->prepare("SELECT nama_lengkap, email FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
                    $stmtAdmin->execute();
                    $adminRow = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
                    if ($adminRow) {
                        if (!empty($adminRow['nama_lengkap'])) $displayName = $adminRow['nama_lengkap'];
                        if (!empty($adminRow['email'])) $displayEmail = $adminRow['email'];
                    }
                } catch (Exception $e) {
                    // ignore DB errors and fall back to session values
                }
            }
          ?>
          <img src="<?= htmlspecialchars($avatarSrc) ?>" class="user-avatar" alt="avatar" />
          <div class="user-name">
            <div id="topbarName"><?= htmlspecialchars($displayName) ?></div>
            <div id="topbarEmail" class="muted"><?= htmlspecialchars($displayEmail) ?></div>
          </div>
        </div>
      </div>
    </div>

    <section id="view-dashboard">
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="stat-card stat-card--accent">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="muted">Total Dokter</div>
                <div class="stat-value"><span class="countup" id="totalDokterValue">--</span></div>
              </div>
              <div class="stat-icon bg-soft-green"><i class="bi bi-people-fill"></i></div>
            </div>
            <div class="stat-meta d-flex justify-content-between align-items-center mt-2">
              <svg class="stat-sparkline" id="sparkTotalDokter" width="120" height="28" aria-hidden="true"></svg>
              <div class="text-end"><small class="muted">vs minggu lalu</small> <div class="badge bg-success ms-1" id="totalDokterChange">+0%</div></div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="stat-card stat-card--accent">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="muted">Jadwal Hari Ini</div>
                <div class="stat-value"><span class="countup" id="jadwalHariIniValue">--</span></div>
              </div>
              <div class="stat-icon bg-soft-blue"><i class="bi bi-calendar-day-fill"></i></div>
            </div>
            <div class="stat-meta d-flex justify-content-between align-items-center mt-2">
              <svg class="stat-sparkline" id="sparkJadwal" width="120" height="28" aria-hidden="true"></svg>
              <div class="text-end"><small class="muted">aktif hari ini</small> <div class="badge bg-primary ms-1" id="jadwalChange">0</div></div>
            </div>
          </div>
        </div>
      </div>

      <div class="stat-card mb-4">
        <h5 class="mb-2">Aktivitas Terakhir</h5>
        <ul class="activity-list enhanced" id="activityList"></ul>
        <div class="muted mt-2"><small>Menampilkan 6 aktivitas terbaru</small></div>
      </div>

      <div class="stat-card mb-4" id="queueCard">
        <h5 class="mb-2">Antrian Hari Ini</h5>
        <div id="queueList" class="queue-list muted">Memuat antrian...</div>
        <div class="muted mt-2"><small>Data diperbarui secara real-time untuk admin</small></div>
      </div>

      <div class="stat-card mb-4" id="paymentsCard">
        <h5 class="mb-2">Pembayaran Menunggu Verifikasi</h5>
        <div id="paymentsList" class="payments-list muted">Memuat pembayaran...</div>
        <div class="muted mt-2"><small>Admin dapat mengonfirmasi pembayaran dari daftar ini</small></div>
      </div>
    </section>

    <section id="view-dokter" style="display:none">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="controls-flex">
          <select id="filterDoctorStatus" class="form-select form-select-sm" style="width:160px">
            <option value="semua">Semua Status</option>
            <option value="aktif">Aktif</option>
            <option value="cuti">Cuti</option>
            <option value="nonaktif">Non-Aktif</option>
          </select>
          <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#modalAddDoctor"><i class="bi bi-person-plus"></i> Tambah Dokter</button>
        </div>
      </div>
      <div class="lux-table">
        <table class="table mb-0" id="tableDoctors">
          <thead>
            <tr><th>Nama</th><th>Spesialis</th><th>No. Telepon</th><th>Status</th><th class="col-action-140">Aksi</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>

    <section id="view-jadwal" style="display:none">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div><button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#modalAddSchedule"><i class="bi bi-calendar-plus"></i> Tambah Jadwal</button></div>
      </div>
      <div class="lux-table mb-3">
        <table class="table mb-0" id="tableSchedules">
          <thead>
            <tr><th>Dokter</th><th>Hari</th><th>Jam</th><th>Ruang/Poli</th><th class="col-action-140">Aksi</th></tr>

            </thead>
            <tbody></tbody>
          </table>
        </div>
      </section>

      <!-- Modal Edit Jadwal Dokter -->
      <div class="modal fade" id="modalEditSchedule" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="mb-0">Edit Jadwal Dokter</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditSchedule">
              <input type="hidden" id="editScheduleId">
              <div class="mb-3">
                <label class="form-label">Dokter*</label>
                <select id="editDoctorSchedule" class="form-select" required disabled>
                  <option value="">-- Pilih Dokter --</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Hari*</label>
                <select id="editDay" class="form-select" required>
                  <option value="">-- Pilih Hari --</option>
                  <option value="Senin">Senin</option>
                  <option value="Selasa">Selasa</option>
                  <option value="Rabu">Rabu</option>
                  <option value="Kamis">Kamis</option>
                  <option value="Jumat">Jumat</option>
                  <option value="Sabtu">Sabtu</option>
                  <option value="Minggu">Minggu</option>
                </select>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Jam Mulai*</label>
                  <input type="time" id="editStartTime" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Jam Selesai*</label>
                  <input type="time" id="editEndTime" class="form-control" required>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Ruangan/Poli</label>
                <input type="text" id="editRoom" class="form-control" placeholder="Contoh: Ruang Poli 1">
              </div>
              <div class="text-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
              </div>
            </form>
          </div>
        </div>
      </div>

    <section id="view-pengaturan" style="display:none">
      <div class="stat-card mb-3">
        <h5>Profil Admin</h5>
        <div class="row g-3 mt-2">
          <div class="col-md-6"><label class="form-label">Nama</label><input id="settingName" class="form-control"></div>
          <div class="col-md-6"><label class="form-label">Email</label><input id="settingEmail" class="form-control"></div>
        </div>
        <div class="mt-3"><button class="btn btn-primary">Simpan Profil</button></div>
      </div>
      <div class="stat-card">
        <h5>Ganti Password</h5>
        <div class="row g-3 mt-2">
          <div class="col-md-4"><input id="oldPass" type="password" placeholder="Password lama" class="form-control"></div>
          <div class="col-md-4"><input id="newPass" type="password" placeholder="Password baru" class="form-control"></div>
          <div class="col-md-4"><input id="confirmPass" type="password" placeholder="Konfirmasi password" class="form-control"></div>
        </div>
        <div class="mt-3"><button class="btn btn-danger">Ganti Password</button></div>
      </div>
    </section>
  </main>

  <div class="modal fade" id="modalAddDoctor" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Tambah Dokter Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="formAddDoctor">
          <div class="row">
              <div class="col-12 mb-3">
              <!-- Foto dihapus; gunakan input No. Telepon pada form -->
            </div>
            <div class="col-md-12">
              <div class="row">
                <div class="col-md-12 mb-3">
                  <label class="form-label">Nama Dokter*</label>
                  <input type="text" id="inputDoctorName" class="form-control" placeholder="Nama lengkap" required>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Spesialisasi*</label>
                  <input type="text" id="inputSpecialty" class="form-control" placeholder="Contoh: Penyakit Dalam" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Status*</label>
                  <select id="inputStatus" class="form-select" required>
                    <option value="">-- Pilih Status --</option>
                    <option value="aktif">Aktif</option>
                    <option value="cuti">Cuti</option>
                    <option value="nonaktif">Non Aktif</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Gelar</label>
              <input type="text" id="inputDegree" class="form-control" placeholder="Contoh: Sp.PD">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">No. STR/SIP</label>
              <input type="text" id="inputSTR" class="form-control" placeholder="Nomor STR/SIP">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Email</label>
              <input type="email" id="inputEmail" class="form-control" placeholder="Email dokter">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">No. Telepon</label>
              <input type="tel" id="inputPhone" class="form-control" placeholder="08xx...">
            </div>
          </div>
          <div class="text-end">
            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Dokter</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Edit Dokter -->
  <div class="modal fade" id="modalEditDoctor" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Edit Data Dokter</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="formEditDoctor">
          <input type="hidden" id="editDoctorId">
          <div class="row">
            <div class="col-12">
              <div class="row">
                <div class="col-md-12 mb-3">
                  <label class="form-label">Nama Dokter*</label>
                  <input type="text" id="editDoctorName" class="form-control" placeholder="Nama lengkap" required>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Spesialisasi*</label>
                  <input type="text" id="editSpecialty" class="form-control" placeholder="Contoh: Penyakit Dalam" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Status*</label>
                  <select id="editStatus" class="form-select" required>
                    <option value="">-- Pilih Status --</option>
                    <option value="aktif">Aktif</option>
                    <option value="cuti">Cuti</option>
                    <option value="nonaktif">Non Aktif</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Gelar</label>
              <input type="text" id="editDegree" class="form-control" placeholder="Contoh: Sp.PD">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">No. STR/SIP</label>
              <input type="text" id="editSTR" class="form-control" placeholder="Nomor STR/SIP">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Email</label>
              <input type="email" id="editEmail" class="form-control" placeholder="Email dokter">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">No. Telepon</label>
              <input type="tel" id="editPhone" class="form-control" placeholder="08xx...">
            </div>
          </div>
          <div class="text-end">
            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalAddSchedule" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Tambah Jadwal Dokter</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="formAddSchedule">
          <div class="mb-3">
            <label class="form-label">Dokter*</label>
            <select id="inputDoctorSchedule" class="form-select" required>
              <option value="">-- Pilih Dokter --</option>
            </select>
            <div id="inputDoctorNote" class="form-text text-muted" style="display:none;margin-top:6px">Belum ada dokter. Tambah dokter di menu <strong>Manajemen Dokter</strong> terlebih dahulu.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Hari*</label>
            <select id="inputDay" class="form-select" required>
              <option value="">-- Pilih Hari --</option>
              <option value="Senin">Senin</option>
              <option value="Selasa">Selasa</option>
              <option value="Rabu">Rabu</option>
              <option value="Kamis">Kamis</option>
              <option value="Jumat">Jumat</option>
              <option value="Sabtu">Sabtu</option>
              <option value="Minggu">Minggu</option>
            </select>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Jam Mulai*</label>
              <input type="time" id="inputStartTime" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Jam Selesai*</label>
              <input type="time" id="inputEndTime" class="form-control" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Ruangan/Poli</label>
            <input type="text" id="inputRoom" class="form-control" placeholder="Contoh: Ruang Poli 1">
          </div>
          <div class="text-end">
            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/dashboard-admin.js"></script>

</body>
</html>
