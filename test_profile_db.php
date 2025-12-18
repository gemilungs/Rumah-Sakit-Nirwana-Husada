<?php
require_once 'config.php';
require_once 'lib/session_utils.php';
hydrateSessionFromDB();
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Test Koneksi Profile → Database</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container">
    <h3>Tes Koneksi Profil ke Database</h3>
    <p>Halaman ini akan mencoba melakukan update singkat pada record profil Anda (dalam transaksi bila memungkinkan) lalu membatalkannya kembali.</p>

    <?php if (empty($_SESSION['user_id'])): ?>
      <div class="alert alert-warning">Anda belum login. Silakan <a href="login.php">login</a> terlebih dahulu.</div>
    <?php else: ?>
      <div id="result" class="mb-3"></div>
      <div class="mb-2">
        <button id="runBtn" class="btn btn-primary">Jalankan Tes</button>
        <button id="logsBtn" class="btn btn-outline-secondary">Tampilkan Log Update</button>
      </div>
      <pre id="out" style="white-space:pre-wrap; margin-top:12px; background:#f8f9fa; padding:12px; border-radius:6px; display:none"></pre>
      <div id="logs" style="margin-top:12px; display:none">
        <h5>Log Update</h5>
        <div id="logsContent" style="background:#fff; border:1px solid #e9ecef; padding:12px; border-radius:6px; max-height:400px; overflow:auto"></div>
      </div>
    <?php endif; ?>
  </div>

  <script>
    document.getElementById('runBtn').addEventListener('click', async function() {
      document.getElementById('out').style.display = 'none';
      document.getElementById('result').innerHTML = 'Menjalankan tes...';
      try {
        const res = await fetch('api/profile_db_test.php', { credentials: 'same-origin' });
        const data = await res.json();
        document.getElementById('out').style.display = 'block';
        document.getElementById('out').textContent = JSON.stringify(data, null, 2);
        if (data.success) {
          document.getElementById('result').innerHTML = '<div class="alert alert-success">Tes sukses — lihat detail di bawah.</div>';
        } else {
          document.getElementById('result').innerHTML = '<div class="alert alert-danger">Tes gagal — lihat detail di bawah.</div>';
        }
      } catch (err) {
        document.getElementById('result').innerHTML = '<div class="alert alert-danger">Fetch error: ' + err.message + '</div>';
      }
    });

    document.getElementById('logsBtn').addEventListener('click', async function() {
      document.getElementById('logsContent').textContent = 'Memuat log...';
      document.getElementById('logs').style.display = 'block';
      try {
        const res = await fetch('api/user_update_logs.php', { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.success) {
          document.getElementById('logsContent').textContent = 'Tidak dapat mengambil log: ' + (data.message || 'unknown');
          return;
        }
        const parts = [];
        Object.keys(data.files).forEach(k => {
          parts.push('--- ' + k + ' ---');
          const arr = data.files[k];
          if (!arr) { parts.push('[tidak ada file]'); }
          else if (Array.isArray(arr) && arr.length === 0) { parts.push('[file kosong]'); }
          else if (Array.isArray(arr)) { parts.push(arr.join('\n')); }
          else { parts.push(String(arr)); }
        });
        document.getElementById('logsContent').textContent = parts.join('\n\n');
      } catch (err) {
        document.getElementById('logsContent').textContent = 'Fetch error: ' + err.message;
      }
    });
  </script>
</body>
</html>