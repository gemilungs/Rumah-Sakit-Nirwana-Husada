<?php require_once 'config.php'; ?>
<?php require_once 'lib/session_utils.php'; hydrateSessionFromDB(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Dokter</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Encode+Sans:wght@100..900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/jadwal.css">

    <!-- favicon -->
    <link rel="Icon" type="img/png" href="../Media/favicon.PNG">
</head>

<body>

    <?php require_once 'includes/header.php'; ?>


    <div class="container mt-5 pt-5">
        <div class="card p-4 shadow-sm">

        <!-- 1. DOKTER UMUM -->
        <div class="dropdown-header" data-bs-toggle="collapse" data-bs-target="#umum">
            <span><img src="../Media/pic15.png" width="26"> Dokter Umum</span>
            <span class="toggle-icon">+</span>
        </div>
        
        <div id="umum" class="collapse">
            <div class="doctor-item">
                <img src="../Media/doc (1).png" class="doctor-photo">
                <div class="doctor-info">
                    <div class="name">dr. Alya Prameswari</div>
                    <div class="schedule">Senin & Kamis — 08:00–12:00</div>
                </div>
            </div>

            <div class="doctor-item">
                <img src="../Media/doc (2).png" class="doctor-photo">
                <div class="doctor-info">
                    <div class="name">dr. Nadira Maheswari</div>
                    <div class="schedule">Rabu & Sabtu — 08:00–12:00</div>
                </div>
            </div>
        </div>

    <!-- 2. PENYAKIT DALAM -->
    <div class="dropdown-header" data-bs-toggle="collapse" data-bs-target="#dalam">
        <span><img src="../Media/pic14.png" width="26"> Penyakit Dalam</span>
        <span class="toggle-icon">+</span>
    </div>
    <div id="dalam" class="collapse">
        <div class="doctor-item">
            <img src="../Media/doc (3).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Bayu Wirawan, Sp.PD</div>
                <div class="schedule">Senin & Rabu — 10:00–14:00</div>
            </div>
        </div>

        <div class="doctor-item">
            <img src="../Media/doc (4).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Raka Pratama, Sp.PD</div>
                <div class="schedule"> Jumat — 10:00–14:00</div>
            </div>
        </div>
    </div>

    <!-- 3. BEDAH UMUM -->
    <div class="dropdown-header" data-bs-toggle="collapse" data-bs-target="#bedahumum">
        <span><img src="../Media/pic13.png" width="26"> Bedah Umum</span>
        <span class="toggle-icon">+</span>
    </div>
    <div id="bedahumum" class="collapse">
        <div class="doctor-item">
            <img src="../Media/doc (5).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Salsabila Putriandi, Sp.B</div>
                <div class="schedule">Selasa & Kamis — 09:00–13:00</div>
            </div>
        </div>

        <div class="doctor-item">
            <img src="../Media/doc (6).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Kayla Ramadhanti, Sp.B</div>
                <div class="schedule">Jumat — 09:00–13:00</div>
            </div>
        </div>
    </div>

    <!-- 4. ANAK -->
    <div class="dropdown-header" data-bs-toggle="collapse" data-bs-target="#anak">
        <span><img src="https://img.icons8.com/color/96/baby.png" width="26"> Anak</span>
        <span class="toggle-icon">+</span>
    </div>
    <div id="anak" class="collapse">
        <div class="doctor-item">
            <img src="../Media/doc (7).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Amanda Putri, Sp.A</div>
                <div class="schedule">Senin & Kamis — 08:00–12:00</div>
            </div>
        </div>

        <div class="doctor-item">
            <img src="../Media/doc (8).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Reza Novriansyah, Sp.A</div>
                <div class="schedule">Sabtu — 08:00–12:00</div>
            </div>
        </div>
    </div>

    <!-- 5. KANDUNGAN -->
    <div class="dropdown-header" data-bs-toggle="collapse" data-bs-target="#kandungan">
        <span><img src="../Media/pic16.png" width="26"> Obstetri & Ginekologi</span>
        <span class="toggle-icon">+</span>
    </div>
    <div id="kandungan" class="collapse">
        <div class="doctor-item">
            <img src="../Media/doc (9).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Maya Shafira, Sp.OG</div>
                <div class="schedule">Senin & Jumat — 14:00–17:00</div>
            </div>
        </div>

        <div class="doctor-item">
            <img src="../Media/doc (10).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Gilang Setiadharma, Sp.OG</div>
                <div class="schedule">Rabu — 14:00–17:00</div>
            </div>
        </div>
    </div>

    <!-- 6. JANTUNG -->
    <div class="dropdown-header" data-bs-toggle="collapse" data-bs-target="#jantung">
        <span><img src="../Media/pic17.png" width="26"> Kardiologi (Jantung)</span>
        <span class="toggle-icon">+</span>
    </div>
    <div id="jantung" class="collapse">
        <div class="doctor-item">
            <img src="../Media/doc (11).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Susilo Hartono, Sp.JP</div>
                <div class="schedule">Selasa & Kamis — 10:00–14:00</div>
            </div>
        </div>

        <div class="doctor-item">
            <img src="../Media/doc (12).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Mira Setyaningsih, Sp.JP</div>
                <div class="schedule">Jumat — 10:00–14:00</div>
            </div>
        </div>
    </div>

    <!-- 7. SARAF -->
    <div class="dropdown-header" data-bs-toggle="collapse" data-bs-target="#saraf">
        <span><img src="../Media/pic18.png" width="26"> Neurologi (Saraf)</span>
        <span class="toggle-icon">+</span>
    </div>
    <div id="saraf" class="collapse">
        <div class="doctor-item">
            <img src="../Media/doc (13).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Rizky Mahendra, Sp.N</div>
                <div class="schedule">Senin & Jumat — 13:00–17:00</div>
            </div>
        </div>

        <div class="doctor-item">
            <img src="../Media/doc (14).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Hafizh Ramdhan, Sp.N</div>
                <div class="schedule">Rabu — 13:00–17:00</div>
            </div>
        </div>
    </div>

    <!-- 8. ORTOPEDI -->
    <div class="dropdown-header" data-bs-toggle="collapse" data-bs-target="#ortopedi">
        <span><img src="../Media/pic19.png" width="26"> Ortopedi & Traumatologi</span>
        <span class="toggle-icon">+</span>
    </div>
    <div id="ortopedi" class="collapse">
        <div class="doctor-item">
            <img src="../Media/doc (15).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Yudi Setiawan, Sp.OT</div>
                <div class="schedule">Rabu — 09:00–12:00</div>
            </div>
        </div>

        <div class="doctor-item">
            <img src="../Media/doc (16).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Intan Dewandari, Sp.OT</div>
                <div class="schedule">Sabtu — 09:00–12:00</div>
            </div>
        </div>
    </div>

    <!-- 9. UROLOGI -->
    <div class="dropdown-header" data-bs-toggle="collapse" data-bs-target="#urologi">
        <span><img src="../Media/pic20.png" width="26"> Urologi</span>
        <span class="toggle-icon">+</span>
    </div>
    <div id="urologi" class="collapse">
        <div class="doctor-item">
            <img src="../Media/doc (17).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Yudha Permadi, Sp.U</div>
                <div class="schedule">Kamis — 10:00–14:00</div>
            </div>
        </div>

        <div class="doctor-item">
            <img src="../Media/doc (18).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Samuel Widodo, Sp.U</div>
                <div class="schedule">Rabu — 10:00–14:00</div>
            </div>
        </div>
    </div>

    <!-- 10. THT -->
    <div class="dropdown-header" data-bs-toggle="collapse" data-bs-target="#tht">
        <span><img src="../Media/pic21.png" width="26"> THT – KL</span>
        <span class="toggle-icon">+</span>
    </div>
    <div id="tht" class="collapse">
        <div class="doctor-item">
            <img src="../Media/doc (19).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Satria Wijaya, Sp.THT</div>
                <div class="schedule">Selasa — 10:00–14:00</div>
            </div>
        </div>

        <div class="doctor-item">
            <img src="../Media/doc (20).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Kevin Wiratmaja, Sp.THT</div>
                <div class="schedule">Jumat — 08:00–12:00</div>
            </div>
        </div>
    </div>

    <!-- 11. MATA -->
    <div class="dropdown-header" data-bs-toggle="collapse" data-bs-target="#mata">
        <span><img src="../Media/pic22.png" width="26"> Oftalmologi (Mata)</span>
        <span class="toggle-icon">+</span>
    </div>
    <div id="mata" class="collapse">
        <div class="doctor-item">
            <img src="../Media/doc (21).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Naufal Saputro, Sp.M</div>
                <div class="schedule">Selasa — 08:00–12:00</div>
            </div>
        </div>

        <div class="doctor-item">
            <img src="../Media/doc (22).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Ardiansyah Putrawan, Sp.M</div>
                <div class="schedule">Kamis — 08:00–12:00</div>
            </div>
        </div>
    </div>

    <!-- 12. BEDAH SARAF -->
    <div class="dropdown-header" data-bs-toggle="collapse" data-bs-target="#bedahsaraf">
        <span><img src="../Media/pic23.png" width="26"> Bedah Saraf</span>
        <span class="toggle-icon">+</span>
    </div>
    <div id="bedahsaraf" class="collapse">
        <div class="doctor-item">
            <img src="../Media/doc (23).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Tiara Nurcahyani, Sp.BS</div>
                <div class="schedule">Rabu — 14:00–17:00</div>
            </div>
        </div>

        <div class="doctor-item">
            <img src="../Media/doc (24).png" class="doctor-photo">
            <div class="doctor-info">
                <div class="name">dr. Galih Putrawan, Sp.BS</div>
                <div class="schedule">Jumat — 14:00–17:00</div>
            </div>
        </div>
    </div>

    </div>
    </div>


  <?php require_once 'includes/footer.php'; ?>
  </footer>


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

        document.querySelectorAll('.dropdown-header').forEach(header => {
            const icon = header.querySelector('.toggle-icon');
            const target = document.querySelector(header.getAttribute('data-bs-target'));

            target.addEventListener('shown.bs.collapse', () => icon.textContent = '−');
            target.addEventListener('hidden.bs.collapse', () => icon.textContent = '+');
        });
    </script>

</body>
</html>
