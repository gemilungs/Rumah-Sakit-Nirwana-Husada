<?php require_once 'config.php'; ?>
<?php require_once 'lib/session_utils.php'; hydrateSessionFromDB(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dampak Ritme Kerja Buruk pada Kesehatan</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Encode+Sans:wght@100..900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/artikel-dua.css">

    <!-- favicon -->
    <link rel="Icon" type="img/png" href="../Media/logo1.png">
</head>
<body>
    
  <?php require_once 'includes/header.php'; ?>


    <div class="container">

        <h1 class="title">
            Dampak Ritme Kerja Buruk pada Kesehatan
        </h1>

        <p class="meta">
            By Rumah Sakit Nirwana Husada • Juni 24, 2025
        </p>

        <img src="../Media/pic5.jpg" class="featured-img" alt="Gambar Artikel">

        <p>
            Di tengah kesibukan hidup modern, banyak orang mengorbankan waktu tidur demi pekerjaan,
            hiburan, atau kewajiban sosial. Tanpa disadari, kebiasaan kurang tidur dan ritme kerja 
            yang tidak seimbang dapat memberikan dampak serius terhadap kesehatan, terutama sistem 
            kekebalan tubuh.
        </p>

        <p>
            #SobatNirwana, yuk pahami hubungan antara tidur yang cukup dan daya tahan tubuh, agar kita 
            bisa menjalani hidup yang produktif tanpa mengorbankan kesehatan.
        </p>

        <hr>

        <h2>Tidur: Waktu Tubuh untuk Memulihkan Diri</h2>
        <h6>Tidur bukan sekadar istirahat — saat kita tidur, tubuh bekerja keras untuk:</h6>
        <ul>
            <li>Memperbaiki sel dan jaringan yang rusak</li>
            <li>Mengatur hormon tubuh, termasuk hormon stres</li>
            <li>Menguatkan sistem imun</li>
            <li>Menyeimbangkan fungsi otak dan emosi</li>
        </ul>
        <p>Jika waktu tidur terganggu, maka seluruh proses pemulihan ini ikut terganggu.</p>

        <h2>Kurang Tidur Melemahkan Sistem Imun</h2>
        <p>Penelitian menunjukkan bahwa orang dewasa yang tidur kurang dari 6 jam per malam memiliki 
            risiko lebih tinggi terserang penyakit, terutama infeksi seperti flu, batuk pilek, hingga gangguan 
            pernapasan.</p>
        <h6>Dampak buruk kurang tidur terhadap kekebalan tubuh meliputi:</h6>
        <ul>
            <li>Menurunnya produksi sel darah putih dan antibodi</li>
            <li>Meningkatnya hormon stres (kortisol) yang menekan sistem imun</li>
            <li>Regenerasi sel imun menjadi lebih lambat</li>
            <li>Tubuh sulit melawan virus dan bakteri</li>
        </ul>

        <h2>Ritme Kerja Buruk = Risiko Kesehatan Ganda</h2>
        <h6>#SobatDelta yang bekerja dengan sistem shift malam, kerja lembur terus-menerus, 
            atau tanpa waktu istirahat teratur berisiko mengalami:</h6>
        <ul>
            <li>Gangguan metabolisme dan peningkatan berat badan</li>
            <li>Risiko lebih tinggi terkena diabetes, hipertensi, dan penyakit jantung</li>
            <li>Mood swing, stres berlebih, hingga depresi ringan</li>
            <li>Penurunan fokus, produktivitas, dan imunitas</li>
        </ul>

        <h2>Tips Menjaga Imunitas Meski Jadwal Padat</h2>
        <ul>
            <li>Prioritaskan tidur minimal 7–8 jam setiap malam</li>
            <li>Tidur dan bangun di jam yang sama setiap hari (meski hari libur)</li>
            <li>Konsumsi makanan bergizi dan cukup cairan</li>
            <li>Rutin berolahraga ringan</li>
            <li>Kelola stres dengan relaksasi atau meditasi</li>
            <li>Hindari penggunaan gadget 1 jam sebelum tidur</li>
        </ul>

    </div>


    <section class="related-posts">
        <div class="container">
            <h2>Artikel Serupa</h2>
            <div class="related-grid">

                <a href="artikel-satu.php" class="related-card">
                    <img src="../Media/pic4.jpg" alt="">
                    <p>September 24, 2025</p>
                    <h3>Cegah Anemia Sejak Sekolah: Pentingnya Tablet Tambah Darah untuk Remaja Putri</h3>
                </a>

                <a href="artikel-tiga.php" class="related-card">
                    <img src="../Media/pic6.jpg" alt="">
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