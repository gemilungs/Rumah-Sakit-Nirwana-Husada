<?php require_once 'config.php'; ?>
<?php require_once 'lib/session_utils.php'; hydrateSessionFromDB(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RS Nirwana Husada</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Encode+Sans:wght@100..900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/style.css">

    <!-- Favicon -->
    <link rel="icon" type="img/png" href="../Media/logo1.png">
</head>

<body>
    <header class="hero">
        <video autoplay loop muted class="back-vid">
            <source src="../Media/video.mp4" type="video/mp4">
        </video>

        <?php require_once 'includes/header.php'; ?>

        <div class="hero-content">
            <h1 class="hero-title fw-bold">Ketenangan, Kenyamanan, Kesembuhan.</h1>
            <a href="booking.php">
                <button class="hero-btn btn btn-light">Buat Janji Temu</button>
            </a>
          <!-- User badge removed to keep hero focused -->
        </div>
    </header>

    <section class="intro ">
        <div class="intro-card row flex-md-row-reverse">
            <div class="col-md-6">
                <img src="../Media/pic2.jpg" alt="" class="intro-img">
            </div>

            <div class="col-md-6 text-white">
                <h3 class="intro-title fw-bold mb-3">LAYANAN KAMI</h3>
                <p>
                    <strong>Rumah Sakit Nirwana Husada</strong> menyediakan berbagai layanan kesehatan yang dirancang
                    untuk memenuhi kebutuhan pasien secara menyeluruh. Dengan dukungan tenaga medis yang kompeten,
                    fasilitas yang memadai, serta prosedur pelayanan yang terstandar, kami berkomitmen memberikan
                    penanganan yang aman, cepat, dan profesional.
                </p>
                <a href="layanan.php">
                    <button class="intro-btn btn btn-light mt-3">Lihat Layanan</button>
                </a>
            </div>
        </div>

        <div class="intro-card row">
            <div class="col-md-6">
                <img src="../Media/pic3.jpg" alt="" class="intro-img">
            </div>

            <div class="col-md-6 text-white">
                <h3 class="intro-title fw-bold mb-3">JADWAL PRAKTIK DOKTER</h3>
                <p>
                    Lihat daftar lengkap dokter kami beserta jadwal praktik terbaru. Fitur ini memudahkan Anda
                    mengetahui ketersediaan dokter, memilih waktu konsultasi yang ideal, dan memastikan proses
                    pemeriksaan berjalan lebih teratur tanpa harus menunggu lama di rumah sakit.
                </p>
                <a href="jadwal.php">
                    <button class="intro-btn btn btn-light mt-3">Cek Jadwal</button>
                </a>
            </div>
        </div>
    </section>

    <section class="berita-section">
        <h2 class="judul-berita">KABAR TERBARU</h2>

        <div class="berita-grid">
            <a href="artikel-satu.php" class="berita-card">
                <img src="../Media/pic4.jpg" alt="">
                <div class="berita-content">
                    <span class="berita-tgl">September 24, 2025</span>
                    <h3 class="berita-title">
                        Cegah Anemia Sejak Sekolah: Pentingnya Tablet Tambah Darah untuk Remaja Putri
                    </h3>
                </div>
            </a>

            <a href="artikel-dua.php" class="berita-card">
                <img src="../Media/pic5.jpg" alt="">
                <div class="berita-content">
                    <span class="berita-tgl">Juni 24, 2025</span>
                    <h3 class="berita-title">Dampak Ritme Kerja Buruk pada Kesehatan</h3>
                </div>
            </a>

            <a href="artikel-tiga.php" class="berita-card">
                <img src="../Media/pic6.jpg" alt="">
                <div class="berita-content">
                    <span class="berita-tgl">Juni 23, 2025</span>
                    <h3 class="berita-title">Mengapa Olahraga Ringan Lebih Baik daripada Tidak Sama Sekali?</h3>
                </div>
            </a>
        </div>
    </section>

    <section class="partner-section">
        <h2 class="partner-title">PARTNER KAMI</h2>
        <div class="partner-grid">
            <img src="../Media/Part1.png" alt="">
            <img src="../Media/part2.webp" alt="">
            <img src="../Media/part3.png" alt="">
            <img src="../Media/part4.png" alt="">
            <img src="../Media/part5.png" alt="">
            <img src="../Media/part6.png" alt="">
            <img src="../Media/part7.png" alt="">
            <img src="../Media/part8.png" alt="">
            <img src="../Media/part9.png" alt="">
            <img src="../Media/part10.png" alt="">
            <img src="../Media/part11.png" alt="">
            <img src="../Media/part12.png" alt="">
        </div>
    </section>

  <?php require_once 'includes/footer.php'; ?>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
