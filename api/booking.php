<?php
require_once '../config.php';

header('Content-Type: application/json');

// Helper function untuk response
function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Cek autentikasi
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, 'Unauthorized. Please login first.', null, 401);
    }
}

// Cek autentikasi admin
function checkAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        sendResponse(false, 'Unauthorized. Admin access required.', null, 403);
    }
}

try {
    $conn = getConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Ambil data booking
    if ($method === 'GET') {
        checkAuth();
        
        $id = $_GET['id'] ?? null;
        $isAdmin = $_SESSION['role'] === 'admin';
        
        if ($id) {
            // Get booking by ID
            $query = "
                SELECT b.*, 
                    d.nama as nama_dokter, d.spesialisasi, d.foto as foto_dokter,
                    j.hari, j.jam_mulai, j.jam_selesai, j.ruangan,
                    u.nama_lengkap as nama_user, u.email as email_user
                FROM booking b
                INNER JOIN dokter d ON b.dokter_id = d.id
                INNER JOIN jadwal_dokter j ON b.jadwal_id = j.id
                INNER JOIN users u ON b.user_id = u.id
                WHERE b.id = ?
            ";
            
            // Pasien hanya bisa lihat booking sendiri
            if (!$isAdmin) {
                $query .= " AND b.user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$id, $_SESSION['user_id']]);
            } else {
                $stmt = $conn->prepare($query);
                $stmt->execute([$id]);
            }
            
            $booking = $stmt->fetch();
            
            if (!$booking) {
                sendResponse(false, 'Booking tidak ditemukan', null, 404);
            }
            // Add registration_number and payment deadline for convenience
            $booking['registration_number'] = 'REG' . date('Ymd', strtotime($booking['created_at'])) . str_pad($booking['id'], 6, '0', STR_PAD_LEFT);
            // Provide ISO timestamp and millisecond deadline to avoid timezone ambiguities on client
            $booking['created_at_iso'] = date('c', strtotime($booking['created_at']));
            if ($booking['status'] === 'pending' && $booking['payment_status'] === 'belum_bayar') {
                $deadline_ts = strtotime($booking['created_at'] . ' +10 minutes');
                $booking['payment_deadline'] = date('d M Y H:i', $deadline_ts);
                $booking['payment_deadline_ts'] = $deadline_ts * 1000; // milliseconds since epoch
            }
            sendResponse(true, 'Data booking berhasil diambil', $booking);
        } else {
            // Get all booking
            $status = $_GET['status'] ?? null;
            $tanggal = $_GET['tanggal'] ?? null;
            $dokter_id = $_GET['dokter_id'] ?? null;
            
            $query = "
                SELECT b.*, 
                    d.nama as nama_dokter, d.spesialisasi,
                    j.hari, j.jam_mulai, j.jam_selesai,
                    u.nama_lengkap as nama_user
                FROM booking b
                INNER JOIN dokter d ON b.dokter_id = d.id
                INNER JOIN jadwal_dokter j ON b.jadwal_id = j.id
                INNER JOIN users u ON b.user_id = u.id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Pasien hanya bisa lihat booking sendiri
            if (!$isAdmin) {
                $query .= " AND b.user_id = ?";
                $params[] = $_SESSION['user_id'];
            }
            
            if ($status) {
                $query .= " AND b.status = ?";
                $params[] = $status;
            }
            
            if ($tanggal) {
                $query .= " AND b.tanggal_booking = ?";
                $params[] = $tanggal;
            }
            
            if ($dokter_id) {
                $query .= " AND b.dokter_id = ?";
                $params[] = $dokter_id;
            }
            
            $query .= " ORDER BY b.tanggal_booking DESC, b.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $bookings = $stmt->fetchAll();
            
            // Get statistik untuk admin
            $stats = null;
            if ($isAdmin) {
                $stmt = $conn->query("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'dikonfirmasi' THEN 1 ELSE 0 END) as dikonfirmasi,
                        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
                        SUM(CASE WHEN status = 'dibatalkan' THEN 1 ELSE 0 END) as dibatalkan,
                        SUM(CASE WHEN tanggal_booking = CURDATE() THEN 1 ELSE 0 END) as hari_ini
                    FROM booking
                ");
                $stats = $stmt->fetch();
            }
            
            sendResponse(true, 'Data booking berhasil diambil', [
                'bookings' => $bookings,
                'total' => count($bookings),
                'stats' => $stats
            ]);
        }
    }
    
    // POST - Buat booking baru
    elseif ($method === 'POST') {
        checkAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validasi input
        if (empty($input['dokter_id']) || empty($input['jadwal_id']) || empty($input['tanggal_booking'])) {
            sendResponse(false, 'Dokter, jadwal, dan tanggal booking wajib diisi', null, 400);
        }
        
        if (empty($input['nama_pasien']) || empty($input['no_telepon'])) {
            sendResponse(false, 'Nama pasien dan nomor telepon wajib diisi', null, 400);
        }
        
        // Validasi tipe pasien
        $tipe_pasien = $input['tipe_pasien'] ?? 'UMUM';
        if (!in_array($tipe_pasien, ['UMUM', 'ASURANSI', 'BPJS'])) {
            sendResponse(false, 'Tipe pasien tidak valid', null, 400);
        }
        
        // Validasi tanggal tidak boleh masa lalu
        if (strtotime($input['tanggal_booking']) < strtotime(date('Y-m-d'))) {
            sendResponse(false, 'Tanggal booking tidak boleh di masa lalu', null, 400);
        }
        
        // Cek jadwal exists dan aktif
        $stmt = $conn->prepare("
            SELECT j.*, d.nama as nama_dokter, d.status as status_dokter, d.tarif as tarif_dokter, d.spesialisasi as spesialisasi_dokter
            FROM jadwal_dokter j
            INNER JOIN dokter d ON j.dokter_id = d.id
            WHERE j.id = ? AND j.is_active = 1
        ");
        $stmt->execute([$input['jadwal_id']]);
        $jadwal = $stmt->fetch();
        
        if (!$jadwal) {
            sendResponse(false, 'Jadwal tidak ditemukan atau tidak aktif', null, 404);
        }
        
        if ($jadwal['status_dokter'] !== 'aktif') {
            sendResponse(false, 'Dokter sedang tidak aktif', null, 400);
        }
        
        // Validasi hari sesuai dengan jadwal
        $hari_booking = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];
        $hari = $hari_booking[date('l', strtotime($input['tanggal_booking']))];
        
        if ($jadwal['hari'] !== $hari) {
            sendResponse(false, "Tanggal yang dipilih bukan hari {$jadwal['hari']}", null, 400);
        }
        
        // Cek kuota
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM booking 
            WHERE jadwal_id = ? AND tanggal_booking = ? AND status IN ('pending', 'dikonfirmasi')
        ");
        $stmt->execute([$input['jadwal_id'], $input['tanggal_booking']]);
        $result = $stmt->fetch();
        
        if ($result['count'] >= $jadwal['kuota_pasien']) {
            sendResponse(false, 'Kuota booking untuk jadwal ini sudah penuh', null, 400);
        }
        
        // Generate nomor antrian
        $nomor_antrian = $result['count'] + 1;
        
        // Hitung biaya konsultasi berdasarkan tipe pasien dan spesialisasi dokter
        $tarif_by_spesialisasi = [
            'Umum' => 150000,
            'Penyakit Dalam' => 180000,
            'Anak' => 150000,
            'Bedah' => 300000,
            'Kandungan' => 250000,
            'Jantung' => 350000,
            'Oftalmologi' => 200000,
            'Ortopedi & Traumatologi' => 220000,
        ];

        // Prefer explicit doctor tariff if present (tarif_dokter), otherwise fall back to specialization mapping
        $dokter_spesialisasi = $jadwal['spesialisasi_dokter'] ?? '';
        $tarif_dokter = isset($jadwal['tarif_dokter']) ? (float)$jadwal['tarif_dokter'] : null;
        $default_fee = 150000;

        if ($tipe_pasien === 'ASURANSI' || $tipe_pasien === 'BPJS') {
            $biaya_konsultasi = 0;
        } else {
            if ($tarif_dokter !== null) {
                $biaya_konsultasi = $tarif_dokter;
            } elseif (!empty($dokter_spesialisasi) && isset($tarif_by_spesialisasi[$dokter_spesialisasi])) {
                $biaya_konsultasi = $tarif_by_spesialisasi[$dokter_spesialisasi];
            } else {
                $found = false;
                foreach ($tarif_by_spesialisasi as $k => $v) {
                    if (stripos($dokter_spesialisasi, $k) !== false) { $biaya_konsultasi = $v; $found = true; break; }
                }
                if (!$found) $biaya_konsultasi = $default_fee;
            }
        }
        
        // Insert booking
        $stmt = $conn->prepare("
            INSERT INTO booking (
                user_id, dokter_id, jadwal_id, tanggal_booking, nomor_antrian, 
                tipe_pasien, nama_pasien, no_ktp, no_telepon, tanggal_lahir, 
                jenis_kelamin, alamat, provider_asuransi, nomor_polis, nomor_bpjs, 
                kelas_bpjs, keluhan, catatan, biaya_konsultasi, status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending'
            )
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $input['dokter_id'],
            $input['jadwal_id'],
            $input['tanggal_booking'],
            $nomor_antrian,
            $tipe_pasien,
            $input['nama_pasien'],
            $input['no_ktp'] ?? null,
            $input['no_telepon'],
            $input['tanggal_lahir'] ?? null,
            $input['jenis_kelamin'] ?? null,
            $input['alamat'] ?? null,
            $input['provider_asuransi'] ?? null,
            $input['nomor_polis'] ?? null,
            $input['nomor_bpjs'] ?? null,
            $input['kelas_bpjs'] ?? null,
            $input['keluhan'] ?? null,
            $input['catatan'] ?? null,
            $biaya_konsultasi
        ]);
        
        $bookingId = $conn->lastInsertId();

        // Generate human-friendly registration number
        $registration_number = 'REG' . date('Ymd') . str_pad($bookingId, 6, '0', STR_PAD_LEFT);

        // Payment deadline (configurable in future) — default 10 minutes from now
        $payment_deadline_ts = strtotime('+10 minutes');
        $payment_deadline_human = date('d M Y H:i', $payment_deadline_ts);
        $payment_deadline_ts_ms = $payment_deadline_ts * 1000;
        $created_at_iso = date('c');

        // Rincian pendaftaran
        $breakdown = [
            'biaya_konsultasi' => (float)$biaya_konsultasi,
            'admin_fee' => 0.0,
            'total' => (float)$biaya_konsultasi
        ];

        sendResponse(true, 'Booking berhasil dibuat', [
            'id' => $bookingId,
            'registration_number' => $registration_number,
            'nomor_antrian' => $nomor_antrian,
            'biaya_konsultasi' => (float)$biaya_konsultasi,
            'breakdown' => $breakdown,
            'payment_deadline' => $payment_deadline_human,
            'payment_deadline_ts' => $payment_deadline_ts_ms,
            'created_at_iso' => $created_at_iso
        ], 201);
    }
    
    // PUT - Update booking (untuk admin konfirmasi/batal, atau pasien batal)
    elseif ($method === 'PUT') {
        checkAuth();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $action = $input['action'] ?? null;
        
        if (!$id) {
            sendResponse(false, 'ID booking wajib diisi', null, 400);
        }
        
        $isAdmin = $_SESSION['role'] === 'admin';
        
        // Cek booking exists
        $stmt = $conn->prepare("SELECT * FROM booking WHERE id = ?");
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            sendResponse(false, 'Booking tidak ditemukan', null, 404);
        }
        
        // Pasien hanya bisa update booking sendiri
        if (!$isAdmin && $booking['user_id'] != $_SESSION['user_id']) {
            sendResponse(false, 'Anda tidak memiliki akses ke booking ini', null, 403);
        }
        
        // Handle actions
        if ($action === 'konfirmasi') {
            checkAdmin(); // Hanya admin
            
            if ($booking['status'] !== 'pending') {
                sendResponse(false, 'Hanya booking dengan status pending yang bisa dikonfirmasi', null, 400);
            }
            
            $stmt = $conn->prepare("UPDATE booking SET status = 'dikonfirmasi' WHERE id = ?");
            $stmt->execute([$id]);
            
            sendResponse(true, 'Booking berhasil dikonfirmasi');
            
        } elseif ($action === 'selesai') {
            checkAdmin(); // Hanya admin
            
            if ($booking['status'] !== 'dikonfirmasi') {
                sendResponse(false, 'Hanya booking dengan status dikonfirmasi yang bisa diselesaikan', null, 400);
            }
            
            $stmt = $conn->prepare("UPDATE booking SET status = 'selesai' WHERE id = ?");
            $stmt->execute([$id]);
            
            sendResponse(true, 'Booking berhasil diselesaikan');
            
        } elseif ($action === 'batal') {
            // Admin atau pasien sendiri bisa batal
            if ($booking['status'] === 'selesai') {
                sendResponse(false, 'Booking yang sudah selesai tidak bisa dibatalkan', null, 400);
            }
            
            if ($booking['status'] === 'dibatalkan') {
                sendResponse(false, 'Booking sudah dibatalkan', null, 400);
            }
            
            $stmt = $conn->prepare("UPDATE booking SET status = 'dibatalkan', catatan = ? WHERE id = ?");
            $stmt->execute([
                $input['alasan_batal'] ?? 'Dibatalkan oleh ' . ($_SESSION['role'] === 'admin' ? 'admin' : 'pasien'),
                $id
            ]);
            
            sendResponse(true, 'Booking berhasil dibatalkan');
            
        } else {
            // Update data booking (hanya jika masih pending)
            if ($booking['status'] !== 'pending') {
                sendResponse(false, 'Hanya booking dengan status pending yang bisa diubah', null, 400);
            }
            
            $stmt = $conn->prepare("
                UPDATE booking SET 
                    nama_pasien = ?, no_telepon = ?, tanggal_lahir = ?, 
                    jenis_kelamin = ?, alamat = ?, keluhan = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $input['nama_pasien'] ?? $booking['nama_pasien'],
                $input['no_telepon'] ?? $booking['no_telepon'],
                $input['tanggal_lahir'] ?? $booking['tanggal_lahir'],
                $input['jenis_kelamin'] ?? $booking['jenis_kelamin'],
                $input['alamat'] ?? $booking['alamat'],
                $input['keluhan'] ?? $booking['keluhan'],
                $id
            ]);
            
            sendResponse(true, 'Data booking berhasil diupdate');
        }
    }
    
    // DELETE - Hapus booking (hanya admin)
    elseif ($method === 'DELETE') {
        checkAdmin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            sendResponse(false, 'ID booking wajib diisi', null, 400);
        }
        
        $stmt = $conn->prepare("DELETE FROM booking WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse(true, 'Booking berhasil dihapus');
        } else {
            sendResponse(false, 'Booking tidak ditemukan', null, 404);
        }
    }
    
    else {
        sendResponse(false, 'Method not allowed', null, 405);
    }
    
} catch (PDOException $e) {
    sendResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    sendResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}
?>
