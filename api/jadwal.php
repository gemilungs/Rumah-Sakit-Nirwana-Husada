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

// Cek autentikasi admin
function checkAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        sendResponse(false, 'Unauthorized. Admin access required.', null, 403);
    }
}

try {
    $conn = getConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Ambil jadwal dokter
    if ($method === 'GET') {
        $id = $_GET['id'] ?? $_GET['jadwal_id'] ?? null;
        $dokter_id = $_GET['dokter_id'] ?? null;
        $hari = $_GET['hari'] ?? null;
        
        $query = "
            SELECT j.*, d.nama as nama_dokter, d.spesialisasi, d.foto, d.gelar 
            FROM jadwal_dokter j
            INNER JOIN dokter d ON j.dokter_id = d.id
            WHERE 1=1
        ";
        
        $params = [];
        
        // If a specific id (jadwal id) is provided, filter by it for single-record fetch
        if ($id) {
            $query .= " AND j.id = ?";
            $params[] = $id;
        }
        
        if ($dokter_id) {
            $query .= " AND j.dokter_id = ?";
            $params[] = $dokter_id;
        }
        
        if ($hari) {
            $query .= " AND j.hari = ?";
            $params[] = $hari;
            // When querying for a specific day (e.g., dashboard), only include schedules for doctors who are currently active
            $query .= " AND d.status = 'aktif'";
        }
        
        $query .= " AND j.is_active = 1 ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), j.jam_mulai";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $jadwal = $stmt->fetchAll();
        
        // If an id was requested, return at most a single record
        if ($id) {
            // Return as array (caller may expect array) but it will contain single record
            sendResponse(true, 'Jadwal berhasil diambil', $jadwal);
        } else {
            sendResponse(true, 'Jadwal berhasil diambil', $jadwal);
        }
    }
    
    // POST - Tambah jadwal baru
    elseif ($method === 'POST') {
        checkAdmin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validasi input
        if (empty($input['dokter_id']) || empty($input['hari']) || empty($input['jam_mulai']) || empty($input['jam_selesai'])) {
            sendResponse(false, 'Dokter, hari, jam mulai dan jam selesai wajib diisi', null, 400);
        }
        
        // Cek dokter exists
        $stmt = $conn->prepare("SELECT id FROM dokter WHERE id = ?");
        $stmt->execute([$input['dokter_id']]);
        if (!$stmt->fetch()) {
            sendResponse(false, 'Dokter tidak ditemukan', null, 404);
        }
        
        // Cek bentrok jadwal
        $stmt = $conn->prepare("
            SELECT id FROM jadwal_dokter 
            WHERE dokter_id = ? AND hari = ? 
            AND is_active = 1
            AND (
                (jam_mulai <= ? AND jam_selesai > ?) OR
                (jam_mulai < ? AND jam_selesai >= ?) OR
                (jam_mulai >= ? AND jam_selesai <= ?)
            )
        ");
        $stmt->execute([
            $input['dokter_id'],
            $input['hari'],
            $input['jam_mulai'], $input['jam_mulai'],
            $input['jam_selesai'], $input['jam_selesai'],
            $input['jam_mulai'], $input['jam_selesai']
        ]);
        
        if ($stmt->fetch()) {
            sendResponse(false, 'Jadwal bentrok dengan jadwal yang sudah ada', null, 400);
        }
        
        $stmt = $conn->prepare("
            INSERT INTO jadwal_dokter (dokter_id, hari, jam_mulai, jam_selesai, kuota_pasien, ruangan) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['dokter_id'],
            $input['hari'],
            $input['jam_mulai'],
            $input['jam_selesai'],
            $input['kuota_pasien'] ?? 20,
            $input['ruangan'] ?? null
        ]);
        
        sendResponse(true, 'Jadwal berhasil ditambahkan', ['id' => $conn->lastInsertId()], 201);
    }
    
    // PUT - Update jadwal
    elseif ($method === 'PUT') {
        checkAdmin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            sendResponse(false, 'ID jadwal wajib diisi', null, 400);
        }
        
        // Cek jadwal exists
        $stmt = $conn->prepare("SELECT dokter_id, hari FROM jadwal_dokter WHERE id = ?");
        $stmt->execute([$id]);
        $jadwal_existing = $stmt->fetch();
        
        if (!$jadwal_existing) {
            sendResponse(false, 'Jadwal tidak ditemukan', null, 404);
        }
        
        // Cek bentrok jadwal (exclude jadwal yang sedang diedit)
        $stmt = $conn->prepare("
            SELECT id FROM jadwal_dokter 
            WHERE dokter_id = ? AND hari = ? AND id != ?
            AND is_active = 1
            AND (
                (jam_mulai <= ? AND jam_selesai > ?) OR
                (jam_mulai < ? AND jam_selesai >= ?) OR
                (jam_mulai >= ? AND jam_selesai <= ?)
            )
        ");
        $stmt->execute([
            $input['dokter_id'] ?? $jadwal_existing['dokter_id'],
            $input['hari'] ?? $jadwal_existing['hari'],
            $id,
            $input['jam_mulai'], $input['jam_mulai'],
            $input['jam_selesai'], $input['jam_selesai'],
            $input['jam_mulai'], $input['jam_selesai']
        ]);
        
        if ($stmt->fetch()) {
            sendResponse(false, 'Jadwal bentrok dengan jadwal yang sudah ada', null, 400);
        }
        
        $stmt = $conn->prepare("
            UPDATE jadwal_dokter SET 
                hari = ?, jam_mulai = ?, jam_selesai = ?, kuota_pasien = ?, ruangan = ?, is_active = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $input['hari'],
            $input['jam_mulai'],
            $input['jam_selesai'],
            $input['kuota_pasien'] ?? 20,
            $input['ruangan'] ?? null,
            $input['is_active'] ?? 1,
            $id
        ]);
        
        sendResponse(true, 'Jadwal berhasil diupdate');
    }
    
    // DELETE - Hapus jadwal
    elseif ($method === 'DELETE') {
        checkAdmin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            sendResponse(false, 'ID jadwal wajib diisi', null, 400);
        }
        
        // Soft delete - set is_active = 0
        $stmt = $conn->prepare("UPDATE jadwal_dokter SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse(true, 'Jadwal berhasil dihapus');
        } else {
            sendResponse(false, 'Jadwal tidak ditemukan', null, 404);
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
