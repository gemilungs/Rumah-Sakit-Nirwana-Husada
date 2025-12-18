// === LOAD JADWAL ===
document.addEventListener('DOMContentLoaded', function() {
  const jadwalList = document.getElementById('jadwalList');
  if (jadwalList) {
    try {
      fetch('api/jadwal.php?action=list')
        .then(res => res.json())
        .then(data => {
          if (data.success && Array.isArray(data.data)) {
            jadwalList.innerHTML = '';
            data.data.forEach(item => {
              const li = document.createElement('li');
              li.textContent = `${item.hari} - ${item.jam_mulai} s/d ${item.jam_selesai} - ${item.nama_dokter}`;
              jadwalList.appendChild(li);
            });
          } else {
            jadwalList.innerHTML = '<li>Tidak ada jadwal.</li>';
          }
        });
    } catch (error) {
      jadwalList.innerHTML = '<li>Gagal memuat jadwal.</li>';
    }
  }
});
