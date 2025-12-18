<?php
require_once 'config.php';
require_once 'lib/session_utils.php';
hydrateSessionFromDB();
// Require login to access reservation/booking page
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk melakukan reservasi.';
    header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Reservasi</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Encode+Sans:wght@100..900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/booking.css">

    <!-- Favicon -->
    <link rel="icon" type="img/png" href="../Media/logo1.png">

</head>
<body>
  <div class="container" role="application" aria-label="Form Booking">

    <div class="top-tabs">
      <div class="tab-wrap">
        <div class="big-tab active" data-tab="UMUM" role="tab" aria-selected="true">Umum</div>
        <div class="big-tab" data-tab="ASURANSI" role="tab" aria-selected="false">Asuransi</div>
        <div class="big-tab" data-tab="BPJS" role="tab" aria-selected="false">BPJS</div>
      </div>
    </div>

    <div>
      <h2>Pendaftaran — Pilih Tipe & Jadwal</h2>
      <div class="helper">Pilih jenis pasien untuk menampilkan input yang sesuai</div>
    </div>

    <div class="row">
      <div class="col-left">
        <div id="condAsur" class="condensed" style="display:none;">
          <div style="font-weight:700;color:#233049;margin-bottom:8px">Provider Asuransi</div>
          <div class="cond-row">
            <div class="cond-left">
              <select id="provider" style="width:100%;height:46px;border-radius:10px;border:1px solid #e6eefc;padding:0 12px;background:#fff">
                <option value="">Pilih Provider</option>
                <option>FWD</option>
                <option>ACA</option>
                <option>General</option>
                <option>IntraAsia</option>
                <option>Mandiri In Life</option>
                <option>ManuLife</option>
                <option>Prudential</option>
                <option>Reliance</option>
                <option>Medlink</option>
                <option>Meditap</option>
              </select>
            </div>
          </div>

          <div style="height:12px"></div>

          <div class="cond-row">
            <div class="cond-left">
              <label style="font-weight:700;color:#233049">No. Polis</label>
              <input id="noPolis" type="text" placeholder="" style="width:100%;" />
            </div>
          </div>
        </div>

        <div id="condBPJS" class="condensed" style="display:none;">
          <div style="font-weight:700;color:#233049;margin-bottom:8px">No. BPJS</div>
          <div class="cond-row">
            <div class="cond-left">
              <input id="noBpjs" type="text" placeholder="13 digit" style="width:100%;" />
            </div>
          </div>

          <div style="height:12px"></div>

          <div style="font-weight:700;color:#233049;margin-bottom:6px">Upload Surat Rujukan / Kontrol</div>
          <div class="cond-row">
            <div class="cond-left">
              <input id="rujukan" type="file" accept="image/*,application/pdf" style="width:100%" />
            </div>
          </div>
        </div>

        <div class="date-row">
          <div class="field" style="flex:1;min-width:300px;margin-bottom:0">
            <label for="date">Tanggal Periksa</label>
            <input id="date" type="date" />
          </div>
          <div class="field" style="width:180px;margin-bottom:0">
            <label for="poli">Poli</label>
            <select id="poli">
              <option value="ALL">Semua Poli</option>
              <option value="THT">THT</option>
              <option value="UMUM">Umum</option>
              <option value="KANDUNGAN">Kandungan</option>
              <option value="JANTUNG">Jantung</option>
              <option value="PENYAKIT DALAM">Penyakit Dalam</option>
              <option value="ANAK">Anak</option>
              <option value="OBSTETRI & GINEKOLOGI">Obstetri & Ginekologi</option>
              <option value="BEDAH">Bedah</option>
            </select>
          </div>
        </div>

        <div id="cards" class="cards" aria-live="polite"></div>

        <div class="helper-sm" id="noteLine" style="margin-top:12px">Pilih dokter untuk melihat detail & melanjutkan booking.</div>

        <div class="confirm" id="confirmPanel" style="display:none">
          <div style="display:flex;gap:12px;align-items:center">
            <div style="width:44px;height:44px;border-radius:50%;overflow:hidden;background:#eef6ee" id="confirmAvatar"></div>
            <div>
              <div id="confirmTitle" style="font-weight:700;color:#07204a">dr. Nama — Poli</div>
              <div id="confirmSlot" style="font-size:13px;color:var(--muted)">Senin, 09:00 - 09:30</div>
            </div>
          </div>
            <div style="margin-top:10px">
            <label style="font-weight:700;color:#233049">Nama Pasien</label>
            <input id="confirmNama" type="text" placeholder="Nama lengkap" style="width:100%;margin-top:6px" />
            <label style="font-weight:700;color:#233049;margin-top:8px;display:block">No. Telepon</label>
            <input id="confirmPhone" type="text" placeholder="08xxxxxxxx" style="width:100%;margin-top:6px" />
          </div>

          <div>
            <button class="btn-outline" id="btnCancel">Batal</button>
            <button class="btn-primary" id="btnProceed">Lanjutkan</button>
          </div>
        </div>

        <div id="errorBox" class="error">Error</div>

      </div>

      <script src="../js/booking.js"></script>

</body>
</html>
