// === LOAD LAYANAN ===
document.addEventListener('DOMContentLoaded', function() {
  const layananList = document.getElementById('layananList');
  if (layananList) {
    try {
      fetch('api/layanan.php?action=list')
        .then(res => res.json())
        .then(data => {
          if (data.success && Array.isArray(data.data)) {
            layananList.innerHTML = '';
            data.data.forEach(item => {
              const li = document.createElement('li');
              li.textContent = `${item.nama_layanan} - ${item.deskripsi}`;
              layananList.appendChild(li);
            });
          } else {
            layananList.innerHTML = '<li>Tidak ada layanan.</li>';
          }
        });
    } catch (error) {
      layananList.innerHTML = '<li>Gagal memuat layanan.</li>';
    }
  }
});
