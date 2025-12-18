<?php require_once 'config.php'; ?>
<?php require_once 'lib/session_utils.php'; hydrateSessionFromDB(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Layanan Kami</title>
  
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Encode+Sans:wght@100..900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/layanan.css">

    <!-- favicon -->
    <link rel="Icon" type="img/png" href="../Media/logo1.png">
</head>

<body>

<?php require_once 'includes/header.php'; ?>


    <section class="judul">
        <h1>Layanan Kami</h1>
        <p>Layanan medis lengkap, profesional, dan berstandar internasional</p>
    </section>

    <section class="service-grid">

        <div class="card">
            <div class="icon">🩺</div>
            <h3>Poli Umum</h3>
            <p>Pemeriksaan kesehatan dasar dan konsultasi dengan dokter umum berpengalaman.</p>
        </div>

        <div class="card">
            <div class="icon">🧒</div>
            <h3>Khitan Modern</h3>
            <p>Metode khitan tanpa jarum, minim nyeri, dan proses penyembuhan cepat.</p>
        </div>

        <div class="card">
            <div class="icon">🩻</div>
            <h3>Radiologi</h3>
            <p>CT-Scan, Rontgen, MRI, dan USG dengan teknologi terbaru dan hasil cepat.</p>
        </div>

        <div class="card">
            <div class="icon">⚡</div>
            <h3>Instalasi Gawat Darurat (IGD)</h3>
            <p>Layanan darurat 24 jam dengan tenaga medis profesional dan respons cepat.</p>
        </div>

        <div class="card">
            <div class="icon">🏥</div>
            <h3>Rawat Inap</h3>
            <p>Kamar rawat inap premium, bersih, nyaman, dan dilengkapi fasilitas modern.</p>
        </div>

        <div class="card">
            <div class="icon">🔬</div>
            <h3>Laboratorium</h3>
            <p>Pemeriksaan darah lengkap, kimia klinik, dan tes medis lainnya dengan akurat.</p>
        </div>

        <div class="card">
            <div class="icon">🧬</div>
            <h3>Medical Check-Up</h3>
            <p>Paket MCU lengkap untuk kebutuhan pribadi, perusahaan, dan keperluan perjalanan.</p>
        </div>

        <div class="card">
            <div class="icon">🫀</div>
            <h3>Bedah & Operasi</h3>
            <p>Dukungan ruang operasi modern dengan tim dokter spesialis berpengalaman.</p>
        </div>

    </section>

    <?php require_once 'includes/footer.php'; ?>

  <script>
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const closeBtn = document.getElementById('sidebarClose');
    const overlay = document.getElementById('overlay');
    const body = document.body;

    function openSidebar() {
      sidebar.classList.add('open');
      overlay.classList.add('active');
      sidebar.setAttribute('aria-hidden','false');
      overlay.setAttribute('aria-hidden','false');
      hamburger.setAttribute('aria-expanded','true');
      body.classList.add('has-open-sidebar');
      body.style.overflow = 'hidden';
      closeBtn.focus();
    }

    function closeSidebar() {
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
      sidebar.setAttribute('aria-hidden','true');
      overlay.setAttribute('aria-hidden','true');
      hamburger.setAttribute('aria-expanded','false');
      body.classList.remove('has-open-sidebar');
      body.style.overflow = '';
      hamburger.focus();
    }

    function toggleSidebar() {
      if (sidebar.classList.contains('open')) closeSidebar();
      else openSidebar();
    }

    hamburger.addEventListener('click', toggleSidebar);
    closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    sidebar.addEventListener('click', (e) => {
      if (e.target.closest('a')) closeSidebar();
    });
  </script>

</body>
</html>
