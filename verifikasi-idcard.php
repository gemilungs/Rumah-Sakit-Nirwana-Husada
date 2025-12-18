<?php
// verifikasi-idcard.php
// This endpoint verifies admin ID card (POST JSON {idcard}) or form POST 'idcard' and sets a session flag.
require_once 'config.php';
$conn = getConnection();
if (session_status() === PHP_SESSION_NONE) session_start();

// Helper: logging
function logAttempt($status, $message, $token = null) {
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $fn = $dir . '/verification.log';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    // Use DateTime with microseconds for precise timestamp (includes hour:minute:second)
    $dt = new DateTime();
    $time = $dt->format('Y-m-d H:i:s.u');
    $masked = '';
    if ($token) {
        $t = $token;
        $masked = substr($t,0,6) . (strlen($t) > 12 ? '...' : '') . substr($t, -6);
    }
    $line = "[$time] $ip - $status - $message - token=$masked\n";
    @file_put_contents($fn, $line, FILE_APPEND | LOCK_EX);
}

// Accept JSON body or form POST fallback
$data = json_decode(file_get_contents('php://input'), true);
$idcard = $data['idcard'] ?? ($_POST['idcard'] ?? ($_GET['idcard'] ?? ''));

// If accessed via browser GET without token, show a simple verification UI page
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$idcard) {
    // Simple HTML page to paste/scan token
    header('Content-Type: text/html; charset=utf-8');
    echo <<<'HTML'
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Verifikasi ID Card Admin</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;padding:24px}
.box{max-width:620px;margin:30px auto;padding:18px;border-radius:8px;background:#fff;border:1px solid #eee}
input{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px}
button{margin-top:8px;padding:8px 12px}
.msg{margin-top:12px;font-weight:600}
</style>
</head>
<body>
<div class="box">
<h2>Verifikasi ID Card Admin</h2>
<p>Tempel token QR atau masukkan ID card di bawah lalu klik <strong>Verifikasi</strong>.</p>
<input id="tok" placeholder="Paste token atau ID card">
<div><button onclick="doVerify()">Verifikasi</button></div>
<div class="msg" id="msg"></div>
</div>
<script>
function doVerify(){
  var t = document.getElementById("tok").value.trim();
  if(!t){
    document.getElementById("msg").innerText = 'Masukkan token atau ID card.';
    return;
  }
  document.getElementById("msg").innerText = 'Memverifikasi...';
  fetch('verifikasi-idcard.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({idcard:t})
  })
  .then(function(r){ return r.json(); })
  .then(function(j){
    if (j.success) {
      document.getElementById('msg').innerText = 'Verifikasi berhasil.';
      setTimeout(function(){ location.href = 'dashboard-admin.php'; }, 600);
    } else {
      document.getElementById('msg').innerText = j.message || j.error || 'Verifikasi gagal.';
    }
  })
  .catch(function(e){ document.getElementById('msg').innerText = 'Terjadi kesalahan.'; });
}
</script>
</body>
</html>
HTML;
    exit;
}

try {
    // Ensure the id_card column exists
    $colStmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'id_card'");
    $colStmt->execute();
    if (!$colStmt->fetch()) {
        // Helpful error for developer: migration not applied yet
        logAttempt('missing_id_card_column', 'Kolom id_card tidak ditemukan pada tabel users.');
        echo json_encode(['success' => false, 'error' => 'missing_id_card_column', 'message' => 'Kolom id_card tidak ditemukan pada tabel users. Jalankan migrasi.']);
        exit;
    }

    // If the value looks like a signed token (base64.payload.sig), validate signature & expiry
    if (strpos($idcard, '.') !== false) {
        list($payload_b64, $sig) = explode('.', $idcard, 2) + [NULL, NULL];
        if (!$payload_b64 || !$sig) {
            logAttempt('invalid_token', 'Token tidak valid', $idcard);
            echo json_encode(['success' => false, 'error' => 'invalid_token', 'message' => 'Token tidak valid.']);
            exit;
        }
        $expected = hash_hmac('sha256', $payload_b64, APP_SECRET);
        if (!hash_equals($expected, $sig)) {
            logAttempt('signature_mismatch', 'Signature token tidak cocok', $idcard);
            echo json_encode(['success' => false, 'error' => 'signature_mismatch', 'message' => 'Signature token tidak cocok.']);
            exit;
        }
        $json = base64_decode(strtr($payload_b64, '-_', '+/'));
        $payload = json_decode($json, true);
        if (!$payload || empty($payload['uid']) || empty($payload['exp'])) {
            logAttempt('invalid_payload', 'Payload token tidak valid', $idcard);
            echo json_encode(['success' => false, 'error' => 'invalid_payload', 'message' => 'Payload token tidak valid.']);
            exit;
        }
        if ($payload['exp'] < time()) {
            logAttempt('expired', 'Token sudah kadaluarsa', $idcard);
            echo json_encode(['success' => false, 'error' => 'expired', 'message' => 'Token sudah kadaluarsa.']);
            exit;
        }
        // confirm that uid exists and is admin
        $stmt = $conn->prepare('SELECT id, username, role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$payload['uid']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            logAttempt('not_admin', 'Token tidak terkait dengan akun admin', $idcard);
            echo json_encode(['success' => false, 'error' => 'not_admin', 'message' => 'Token tidak terkait dengan akun admin.']);
            exit;
        }
        // Set session verifikasi id card admin
        $_SESSION['idcard_verified'] = true;
        logAttempt('success', 'Token valid untuk admin '.$user['username'], $idcard);
        echo json_encode(['success' => true, 'user' => ['id' => $user['id'], 'username' => $user['username']]]);
        exit;
    }

    // fallback: plain id_card lookup
    $stmt = $conn->prepare('SELECT * FROM users WHERE id_card = ? AND role = "admin"');
    $stmt->execute([$idcard]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Set session verifikasi id card admin
        $_SESSION['idcard_verified'] = true;
        logAttempt('success', 'ID card match untuk admin '.$user['username'], $idcard);
        echo json_encode(['success' => true, 'user' => ['id' => $user['id'], 'username' => $user['username']]]);
    } else {
        logAttempt('not_found', 'ID Card tidak cocok', $idcard);
        echo json_encode(['success' => false, 'error' => 'not_found', 'message' => 'ID Card tidak cocok dengan admin manapun.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    logAttempt('db_error', $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'db_error', 'message' => $e->getMessage()]);
}
