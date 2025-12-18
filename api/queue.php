<?php
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

function checkAdmin() {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        sendResponse(false, 'Unauthorized. Admin access required.', null, 403);
    }
}

try {
    $conn = getConnection();
    checkAdmin();

    // For today, compute per-doctor queue info
    $stmt = $conn->prepare("SELECT d.id as dokter_id, d.nama as dokter_nama,
        MAX(CASE WHEN b.status = 'selesai' THEN b.nomor_antrian ELSE NULL END) as last_served,
        MAX(CASE WHEN b.status = 'selesai' THEN b.id ELSE NULL END) as last_served_id,
        MIN(CASE WHEN b.status IN ('dikonfirmasi') THEN b.nomor_antrian ELSE NULL END) as next_pending,
        MIN(CASE WHEN b.status IN ('dikonfirmasi') THEN b.id ELSE NULL END) as next_pending_id,
        SUM(CASE WHEN b.status IN ('pending','dikonfirmasi') THEN 1 ELSE 0 END) as waiting_count
        FROM booking b
        INNER JOIN dokter d ON b.dokter_id = d.id
        WHERE b.tanggal_booking = CURDATE()
        AND d.status = 'aktif'
        GROUP BY d.id
        ORDER BY d.nama ASC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize values and ensure numeric/null
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'dokter_id' => (int)$r['dokter_id'],
            'dokter_nama' => $r['dokter_nama'],
            'last_served' => $r['last_served'] !== null ? (int)$r['last_served'] : null,
            'last_served_booking_id' => $r['last_served_id'] !== null ? (int)$r['last_served_id'] : null,
            'next_pending' => $r['next_pending'] !== null ? (int)$r['next_pending'] : null,
            'next_pending_booking_id' => $r['next_pending_id'] !== null ? (int)$r['next_pending_id'] : null,
            'waiting_count' => (int)($r['waiting_count'] ?? 0)
        ];
    }

    sendResponse(true, 'Queue status fetched', ['today' => date('Y-m-d'), 'per_dokter' => $out]);

} catch (PDOException $e) {
    sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    sendResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}
?>