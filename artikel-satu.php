<?php require_once 'config.php'; ?>
<?php require_once 'lib/session_utils.php'; hydrateSessionFromDB(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cegah Anemia Sejak Sekolah: Pentingnya Tablet Tambah Darah untuk Remaja Putri</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Encode+Sans:wght@100..900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/artikel-satu.css">

    <!-- favicon -->
    <link rel="Icon" type="img/png" href="../Media/logo1.png">
</head>
<body>
    
  <?php require_once 'includes/header.php'; ?>

    
    <div class="container">

        <h1 class="title">
            Cegah Anemia Sejak Sekolah: Pentingnya Tablet Tambah Darah untuk Remaja Putri
        </h1>

        <p class="meta">
            By Rumah Sakit Nirwana Husada • September 24, 2025
        </p>

        <img src="../Media/pic4.jpg" class="featured-img" alt="Gambar Artikel">

        <p>
            Anemia masih menjadi masalah kesehatan yang tinggi di kalangan remaja putri di Indonesia.
            Berdasarkan data Kementerian Kesehatan, satu dari tiga remaja putri mengalami anemia,
            yang sebagian besar disebabkan oleh kekurangan zat besi.
        </p>

        <p>
            Kondisi ini sering kali dianggap sepele karena gejalanya tidak selalu terlihat jelas.
            Padahal, anemia dapat berdampak jangka panjang terhadap produktivitas, kecerdasan,
            hingga masa depan generasi muda. Salah satu solusi yang efektif adalah dengan konsumsi
            <b>tablet tambah darah secara rutin sejak usia sekolah</b>.
        </p>

        <hr>

        <h2>Apa Itu Anemia?</h2>
        <p>
            Anemia adalah kondisi saat tubuh kekurangan sel darah merah atau hemoglobin,
            sehingga oksigen tidak terdistribusi dengan baik ke seluruh tubuh.
            Jenis anemia yang paling umum pada remaja putri adalah 
            <b>anemia defisiensi besi</b>.
        </p>

        <h2>Mengapa Remaja Putri Rentan Anemia?</h2>
        <ul>
            <li>Menstruasi rutin setiap bulan menyebabkan kehilangan zat besi.</li>
            <li>Pola makan tidak seimbang atau kurang konsumsi makanan kaya zat besi.</li>
            <li>Gaya hidup diet berlebihan demi alasan penampilan.</li>
            <li>Kurangnya edukasi kesehatan sejak usia sekolah.</li>
        </ul>

        <h2>Dampak Anemia pada Remaja Putri</h2>
        <ul>
            <li>Mudah lelah dan lesu.</li>
            <li>Susah konsentrasi dan prestasi belajar menurun.</li>
            <li>Kulit pucat, pusing, dan jantung berdebar.</li>
            <li>Produktivitas jangka panjang menurun, memengaruhi kualitas sumber daya manusia.</li>
        </ul>

        <h2>Peran Tablet Tambah Darah dalam Pencegahan Anemia</h2>
        <p>Tablet tambah darah mengandung zat besi dan asam folat yang dibutuhkan tubuh untuk memproduksi sel darah merah. 
            Pemerintah telah menggalakkan program pemberian tablet tambah darah secara gratis di sekolah-sekolah, terutama 
            untuk remaja putri usia 10–18 tahun.</p>

        <h2>Dukungan Rumah Sakit Nirwana Husada</h2>
        <h6>Rumah Sakit Nirwana Husada mendukung program pencegahan anemia pada remaja dengan:</h6>
        <ul>
            <li>Pemeriksaan darah lengkap untuk deteksi anemia.</li>
            <li>Konsultasi gizi remaja dan edukasi kesehatan reproduksi.</li>
            <li>Sosialisasi pentingnya tablet tambah darah ke sekolah dan komunitas.</li>
        </ul>

    </div>

    <section class="related-posts">
        <div class="container">
            <h2>Artikel Serupa</h2>
            <div class="related-grid">

                <a href="artikel-dua.php" class="related-card">
                    <img src="Media/pic5.jpg" alt="">
                    <p>Juni 24, 2025</p>
                    <h3>Dampak Ritme Kerja Buruk pada Kesehatan</h3>
                </a>

                <a href="artikel-tiga.php" class="related-card">
                    <img src="Media/pic6.jpg" alt="">
                    <p>Juni 23, 2025</p>
                    <h3>Mengapa Olahraga Ringan Lebih Baik daripada Tidak Sama Sekali?</h3>
                </a>

            </div>
        </div>
    </section>

  <?php require_once 'includes/footer.php'; ?>

      <div class="rs-footer-col">
        <h4>Alamat Rumah Sakit</h4>
        <p>
          Pakuwon Imperial Boulevard No. 9 – 11<br>
          Surabaya Barat, Jawa Timur<br>
          Indonesia
        </p>
      </div>

      <div class="rs-footer-col">
        <h4>Ikuti Kami</h4>

        <div class="rs-socials">
          <a href="#"><i class="bi bi-facebook"></i></a>
          <a href="#"><i class="bi bi-instagram"></i></a>
          <a href="#"><i class="bi bi-twitter-x"></i></a>
          <a href="#"><i class="bi bi-youtube"></i></a>
        </div>
      </div>

    </div>

    <div class="rs-footer-bottom">
      © 1945 - 2025 | Rumah Sakit Nirwana Husada. All rights reserved.
    </div>
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