<?php require_once 'config.php'; ?>
<?php require_once 'lib/session_utils.php'; hydrateSessionFromDB(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sejarah RS Nirwana Husada</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

  <!-- Google Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Encode+Sans:wght@100..900&display=swap" rel="stylesheet">

  <!-- CSS -->
  <link rel="stylesheet" href="../css/history.css">

  <!-- favicon -->
  <link rel="Icon" type="img/png" href="../Media/logo1.png">
</head>

<body>

<?php require_once 'includes/header.php'; ?>

  <div class="hero">
    <img src="../Media/Bendera.jpg" alt="" class="hero-img">
    <div class="hero-content">
      <h1>SEJARAH KAMI</h1>
      <p>Delapan dekade pengabdian, dari pos kesehatan kecil pascaperang hingga rumah sakit berstandar internasional.</p>
    </div>
  </div>

  <div class="timeline">

    <div class="timeline-item">
      <div class="dot"></div>
      <div class="year">1945</div>
      <img src="../Media/1945.png" class="foto">
      <div class="desc">
        Perjalanan kami dimulai di sini, hadir untuk merawat mereka yang terdampak masa perang
      </div>
    </div>

    <div class="timeline-item">
      <div class="dot"></div>
      <div class="year">1955</div>
      <img src="../Media/1955.png" alt="" class="foto">
      <div class="desc">
        Di tahun ini, kami meningkatkan kenyamanan dan perawatan melalui fasilitas yang lebih baik dan tenaga medis yang lebih terlatih.
      </div>
    </div>

    <div class="timeline-item">
      <div class="dot"></div>
      <div class="year">1970</div>
      <img src="../Media/1970.png" alt="" class="foto">
      <div class="desc">
        Pada masa ini, kami membentuk tim medis yang lebih kuat untuk mendukung pelayanan yang berkembang.
      </div>
    </div>

    <div class="timeline-item">
      <div class="dot"></div>
      <div class="year">1990</div>
      <img src="../Media/1990.jpg" alt="" class="foto">
      <div class="desc">
        Peningkatan layanan dan fasilitas mulai digencarkan, menjadi titik awal perjalanan panjang menuju standar kesehatan kelas dunia.
      </div>
    </div>

    <div class="timeline-item">
      <div class="dot"></div>
      <div class="year">2005</div>
      <img src="../Media/2005.png" alt="" class="foto">
      <div class="desc">
        Kolaborasi internasional mulai mengalir deras, membawa teknologi mutakhir dan pelatihan tingkat global ke dalam layanan rumah sakit.
      </div>
    </div>

    <div class="timeline-item">
      <div class="dot"></div>
      <div class="year">2025</div>
      <img src="../Media/2025.png" alt="" class="foto">
      <div class="desc">
        Mencapai posisi sebagai pusat rujukan nasional dan salah satu yang terbaik di dunia, rumah sakit ini terus melangkah maju menghadirkan inovasi tanpa henti.
      </div>
    </div>

  </div>

  <div class="legacy">
    <h3>Warisan 80 Tahun</h3>
    <p>
      Sejak 1945, kami berdiri bersama komunitas, tumbuh, memperbaiki, dan berinovasi. Kepercayaan jutaan pasien menjadi fondasi bagi kami untuk terus memberikan pelayanan yang lebih baik. Berpijak pada sejarah yang kuat, kami berkomitmen melangkah ke masa depan dengan kualitas, etika, dan kasih sayang.
    </p>
  </div>

  <?php require_once 'includes/footer.php'; ?>
  </footer>


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
