<?php
// API: Riwayat Kunjungan Pasien
require_once '../config.php';
header('Content-Type: application/json');

function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'Unauthorized. Please login first.', null, 401);
}

try {
    $conn = getConnection();
    $user_id = $_SESSION['user_id'];

    // Pagination params
    $limit = (int)($_GET['limit'] ?? 10);
    $offset = (int)($_GET['offset'] ?? 0);
    $statusFilter = $_GET['status'] ?? null; // e.g., 'pending','dikonfirmasi','selesai','dibatalkan'

    $params = [$user_id];
    $whereStatus = '';
    // Support composite status filters (e.g., 'pending|dikonfirmasi') and special 'unpaid'
    if ($statusFilter) {
        if ($statusFilter === 'unpaid') {
            $whereStatus = ' AND b.payment_status = ? ';
            $params[] = 'belum_bayar';
        } elseif (strpos($statusFilter, '|') !== false) {
            $parts = explode('|', $statusFilter);
            $placeholders = implode(',', array_fill(0, count($parts), '?'));
            $whereStatus = ' AND b.status IN (' . $placeholders . ') ';
            foreach ($parts as $p) $params[] = $p;
        } else {
            $whereStatus = ' AND b.status = ? ';
            $params[] = $statusFilter;
        }
    }

    // Main query with limit/offset
    // Note: interpolate limit/offset directly to avoid driver issues with binding LIMIT/OFFSET
    $safeLimit = max(1, (int)$limit);
    $safeOffset = max(0, (int)$offset);
    $query = "SELECT b.id, b.tanggal_booking, b.nomor_antrian, b.status, b.tipe_pasien, b.nama_pasien, b.no_telepon, b.jenis_kelamin, b.alamat, b.keluhan, b.catatan, b.biaya_konsultasi, b.payment_status, b.payment_method, b.created_at, d.nama AS nama_dokter, d.spesialisasi, j.hari, j.jam_mulai, j.jam_selesai, j.ruangan
        FROM booking b
        JOIN dokter d ON b.dokter_id = d.id
        JOIN jadwal_dokter j ON b.jadwal_id = j.id
        WHERE b.user_id = ? " . $whereStatus . " ORDER BY b.tanggal_booking DESC, b.created_at DESC LIMIT {$safeLimit} OFFSET {$safeOffset}";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $riwayat = $stmt->fetchAll();

    // Total count (without limit) — reuse same WHERE clause to avoid parameter mismatch
    $countQuery = "SELECT COUNT(*) as total FROM booking b WHERE b.user_id = ? " . $whereStatus;
    $stmt2 = $conn->prepare($countQuery);
    // For count we should use the same params (user_id plus any status/payment_status values)
    $stmt2->execute($params);
    $total = (int)$stmt2->fetch()['total'];

    // Counts by status
    $countsStmt = $conn->prepare("SELECT status, COUNT(*) as cnt FROM booking WHERE user_id = ? GROUP BY status");
    $countsStmt->execute([$user_id]);
    $countsRaw = $countsStmt->fetchAll();
    $counts = [ 'pending'=>0, 'dikonfirmasi'=>0, 'selesai'=>0, 'dibatalkan'=>0 ];
    foreach ($countsRaw as $r) { $counts[$r['status']] = (int)$r['cnt']; }

    sendResponse(true, 'Data riwayat kunjungan ditemukan', [ 'bookings' => $riwayat, 'total' => $total, 'counts' => $counts ]);
} catch (PDOException $e) {
    // log for debugging
    file_put_contents(__DIR__.'/history_error.log', '['.date('c').'] PDOException: '.$e->getMessage()."\n", FILE_APPEND);
    sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    file_put_contents(__DIR__.'/history_error.log', '['.date('c').'] Exception: '.$e->getMessage()."\n", FILE_APPEND);
    sendResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}
