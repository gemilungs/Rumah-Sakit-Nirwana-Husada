<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/session_utils.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Silakan login dulu.']);
    exit;
}

try {
    $conn = getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal', 'error' => $e->getMessage()]);
    exit;
}

$userId = $_SESSION['user_id'];
$results = ['success' => false, 'db_ok' => false, 'update_executed' => false, 'details' => []];

try {
    // Start transaction if possible so we do not permanently modify data
    $supportsTx = true;
    try {
        $supportsTx = $conn->beginTransaction();
    } catch (Exception $e) {
        // Some setups may not support transactions; we'll fall back to explicit revert
        $supportsTx = false;
    }

    // Select original value
    $stmt = $conn->prepare('SELECT nama_lengkap, no_telepon FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        if ($supportsTx && $conn->inTransaction()) $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
        exit;
    }

    $orig = $row;
    $results['details']['orig'] = $orig;

    // Attempt an update (append a short test marker) then read back
    $marker = ' [TEST_' . date('YmdHis') . ']';
    $stmt = $conn->prepare('UPDATE users SET nama_lengkap = CONCAT(COALESCE(nama_lengkap, ""), ?) WHERE id = ?');
    $stmt->execute([$marker, $userId]);
    $affected = $stmt->rowCount();

    $stmt = $conn->prepare('SELECT nama_lengkap FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $after = $stmt->fetchColumn();

    $results['db_ok'] = true;
    $results['update_executed'] = ($affected > 0) || ($after !== $orig['nama_lengkap']);
    $results['details']['after'] = $after;
    $results['details']['affected'] = $affected;

    // Try to revert change: rollback if in transaction, else perform explicit revert
    if ($supportsTx && $conn->inTransaction()) {
        $conn->rollBack();
    } else {
        // Explicit revert - remove the exact marker we added
        $stmt = $conn->prepare('UPDATE users SET nama_lengkap = REPLACE(nama_lengkap, ?, ?) WHERE id = ?');
        $stmt->execute([$marker, $orig['nama_lengkap'], $userId]);
    }

    // Verify revert
    $stmt = $conn->prepare('SELECT nama_lengkap FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $final = $stmt->fetchColumn();
    $results['details']['final'] = $final;
    $results['details']['reverted'] = ($final === $orig['nama_lengkap']);

    $results['success'] = true;
    $results['message'] = 'Tes selesai';

    echo json_encode($results);
    exit;
} catch (PDOException $e) {
    if ($conn && $conn->inTransaction()) {
        try { $conn->rollBack(); } catch (Exception $ex) {}
    }
    $dbgDir = __DIR__ . '/../logs'; if (!is_dir($dbgDir)) @mkdir($dbgDir, 0755, true);
    @file_put_contents($dbgDir . '/profile_test_errors.log', date('Y-m-d H:i:s') . " | " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['success' => false, 'message' => 'Database error during test', 'error' => $e->getMessage()]);
    exit;
}
