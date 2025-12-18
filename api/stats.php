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

    // Total active doctors
    $stmt = $conn->query("SELECT COUNT(*) as total_active FROM dokter WHERE status = 'aktif'");
    $totalDoctorsRow = $stmt->fetch();
    $total_dokter = (int)($totalDoctorsRow['total_active'] ?? 0);

    // Jadwal hari ini
    $weekdays = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $hariNama = $weekdays[date('w')];
    // Count only jadwal where the doctor is active too
    $stmt = $conn->prepare("SELECT COUNT(*) as hari_count FROM jadwal_dokter j INNER JOIN dokter d ON j.dokter_id = d.id WHERE j.hari = ? AND j.is_active = 1 AND d.status = 'aktif'");
    $stmt->execute([$hariNama]);
    $jadwalRow = $stmt->fetch();
    $jadwal_today = (int)($jadwalRow['hari_count'] ?? 0);

    // Bookings today
    $stmt = $conn->query("SELECT COUNT(*) as hari_ini FROM booking WHERE tanggal_booking = CURDATE()");
    $bTodayRow = $stmt->fetch();
    $bookings_today = (int)($bTodayRow['hari_ini'] ?? 0);

    // Bookings series last 7 days (by created_at date)
    $stmt = $conn->prepare("SELECT DATE(created_at) as d, COUNT(*) as c FROM booking WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at) ORDER BY DATE(created_at)");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build contiguous 7-day array (oldest -> newest)
    $series = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $series[$date] = 0;
    }
    foreach ($rows as $r) {
        $d = $r['d'];
        if (isset($series[$d])) $series[$d] = (int)$r['c'];
    }
    $seriesArr = [];
    foreach ($series as $d => $c) { $seriesArr[] = ['date' => $d, 'count' => $c]; }

    // bookings: last7 total and previous7 total for percent change
    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM booking WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)");
    $last7 = (int)($stmt->fetchColumn() ?: 0);
    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM booking WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) AND created_at < DATE_SUB(CURDATE(), INTERVAL 6 DAY)");
    $prev7 = (int)($stmt->fetchColumn() ?: 0);
    $bookings_pct_change = ($prev7 === 0) ? ($last7 === 0 ? 0 : 100) : (int)round((($last7 - $prev7) / max(1, $prev7)) * 100);

    // doctors added: last7 vs previous7
    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM dokter WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $doctors_last7 = (int)($stmt->fetchColumn() ?: 0);
    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM dokter WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $doctors_prev7 = (int)($stmt->fetchColumn() ?: 0);
    $doctors_pct_change = ($doctors_prev7 === 0) ? ($doctors_last7 === 0 ? 0 : 100) : (int)round((($doctors_last7 - $doctors_prev7) / max(1, $doctors_prev7)) * 100);

    sendResponse(true, 'Stats fetched', [
        'total_dokter' => $total_dokter,
        'jadwal_today' => $jadwal_today,
        'bookings_today' => $bookings_today,
        'bookings_last7' => $seriesArr,
        'bookings_last7_total' => $last7,
        'bookings_prev7_total' => $prev7,
        'bookings_pct_change' => $bookings_pct_change,
        'doctors_new_last7' => $doctors_last7,
        'doctors_prev7' => $doctors_prev7,
        'doctors_pct_change' => $doctors_pct_change
    ]);

} catch (PDOException $e) {
    sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    sendResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}
?>