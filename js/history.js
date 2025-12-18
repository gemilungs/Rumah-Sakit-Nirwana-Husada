// === LOAD BOOKING HISTORY ===
document.addEventListener('DOMContentLoaded', function() {
  const historyList = document.getElementById('historyList');
  if (historyList) {
    try {
      fetch('api/history.php?action=list')
        .then(res => res.json())
        .then(data => {
          if (data.success && Array.isArray(data.data)) {
            historyList.innerHTML = '';
            data.data.forEach(item => {
              const li = document.createElement('li');
              li.textContent = `${item.tanggal} - ${item.nama_dokter} - ${item.status}`;
              historyList.appendChild(li);
            });
          } else {
            historyList.innerHTML = '<li>Tidak ada riwayat.</li>';
          }
        });
    } catch (error) {
      historyList.innerHTML = '<li>Gagal memuat riwayat.</li>';
    }
  }
});
