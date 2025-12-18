<?php require_once 'config.php'; ?>
<?php require_once 'lib/session_utils.php'; hydrateSessionFromDB(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mengapa Olahraga Ringan Lebih Baik daripada Tidak Sama Sekali?</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Encode+Sans:wght@100..900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/artikel-tiga.css">

    <!-- favicon -->
    <link rel="Icon" type="img/png" href="../Media/logo1.png">
</head>
<body>
    
  <?php require_once 'includes/header.php'; ?>

    
    <div class="container">

        <h1 class="title">
            Mengapa Olahraga Ringan Lebih Baik daripada Tidak Sama Sekali?
        </h1>

        <p class="meta">
            By Rumah Sakit Nirwana Husada • Juni 23, 2025
        </p>

        <img src="../Media/pic6.jpg" class="featured-img" alt="Gambar Artikel">

        <p>
            Di tengah kesibukan sehari-hari, banyak orang merasa sulit menyempatkan diri untuk berolahraga.
             Alasan klasiknya: tidak ada waktu, terlalu lelah, atau merasa olahraga harus selalu intens di gym.
              Padahal, olahraga ringan sekalipun sudah terbukti membawa manfaat besar bagi tubuh.
        </p>

        <p>#SobatNirwana, yuk pahami kenapa olahraga ringan tetap lebih baik daripada tidak olahraga sama 
            sekali!
        </p>

        <hr>

        <h2>Olahraga Ringan = Aktivitas yang Bisa Dilakukan Siapa Saja</h2>
        <h6>Olahraga ringan bukan berarti tidak efektif. 
            Beberapa contoh olahraga ringan yang bisa dilakukan sehari-hari antara lain:</h6>
        <ul>
            <li>Jalan kaki 20–30 menit</li>
            <li>Naik turun tangga</li>
            <li>Bersepeda santai</li>
            <li>Peregangan atau yoga ringan</li>
            <li>Senam peregangan di rumah</li>
        </ul>
        <p>Meski terlihat sederhana, aktivitas ini mampu menjaga tubuh tetap aktif dan sehat.</p>

        <h2>Manfaat Olahraga Ringan untuk Tubuh</h2>
        <h6>Olahraga ringan secara rutin memberikan dampak positif bagi kesehatan, antara lain:</h6>
        <ul>
            <li>Melancarkan peredaran darah dan oksigen ke seluruh tubuh</li>
            <li>Membantu menjaga berat badan tetap stabil</li>
            <li>Mengurangi risiko penyakit kronis seperti hipertensi dan diabetes</li>
            <li>Meningkatkan kualitas tidur</li>
            <li>Membantu mengurangi stres dan meningkatkan mood</li>
        </ul>

        <h2>Lebih Mudah Konsisten Dibanding Olahraga Berat</h2>
        <p>Banyak orang gagal menjaga rutinitas olahraga karena merasa terbebani dengan latihan berat. 
            Olahraga ringan lebih fleksibel, mudah dilakukan, dan bisa dimasukkan ke dalam aktivitas harian, 
            sehingga konsistensi lebih terjaga.</p>

        <h2>Tips Memulai Olahraga Ringan</h2>
        <h6>#SobatNirwana bisa mulai dengan langkah sederhana berikut ini:</h6>
        <ul>
            <li>Jalan kaki setelah makan siang atau sore hari</li>
            <li>Lakukan peregangan 5–10 menit sebelum tidur</li>
            <li>Ajak teman atau keluarga bersepeda santai di akhir pekan</li>
            <li>Gerakkan tubuh sambil mendengarkan musik favorit di rumah</li>
            <li>Jadwalkan aktivitas olahraga ringan agar menjadi kebiasaan harian</li>
        </ul>
        <p>Ingat, sedikit aktivitas fisik jauh lebih baik dibanding tidak bergerak sama sekali.</p>

    </div>


    <section class="related-posts">
        <div class="container">
            <h2>Artikel Serupa</h2>
            <div class="related-grid">

                <a href="artikel-dua.php" class="related-card">
                    <img src="../Media/pic5.jpg" alt="">
                    <p>Juni 24, 2025</p>
                    <h3>Dampak Ritme Kerja Buruk pada Kesehatan</h3>
                </a>

                <a href="artikel-satu.php" class="related-card">
                    <img src="../Media/pic4.jpg" alt="">
                    <p>September 24, 2025</p>
                    <h3>Cegah Anemia Sejak Sekolah: Pentingnya Tablet Tambah Darah untuk Remaja Putri</h3>
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