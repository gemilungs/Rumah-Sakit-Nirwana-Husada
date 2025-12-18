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
    
    // GET - Ambil data dokter
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            // Get dokter by ID dengan jadwalnya
            $stmt = $conn->prepare("
                SELECT d.*, 
                    GROUP_CONCAT(
                        CONCAT(j.hari, '|', j.jam_mulai, '|', j.jam_selesai, '|', j.ruangan, '|', j.kuota_pasien, '|', j.id)
                        ORDER BY 
                            FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')
                        SEPARATOR ';'
                    ) as jadwal
                FROM dokter d
                LEFT JOIN jadwal_dokter j ON d.id = j.dokter_id AND j.is_active = 1
                WHERE d.id = ?
                GROUP BY d.id
            ");
            $stmt->execute([$id]);
            $dokter = $stmt->fetch();
            
            if (!$dokter) {
                sendResponse(false, 'Dokter tidak ditemukan', null, 404);
            }
            
            // Parse jadwal
            if ($dokter['jadwal']) {
                $jadwal_arr = explode(';', $dokter['jadwal']);
                $jadwal_parsed = [];
                foreach ($jadwal_arr as $j) {
                    $parts = explode('|', $j);
                    $jadwal_parsed[] = [
                        'hari' => $parts[0],
                        'jam_mulai' => $parts[1],
                        'jam_selesai' => $parts[2],
                        'ruangan' => $parts[3],
                        'kuota_pasien' => (int)$parts[4],
                        'id' => (int)$parts[5]
                    ];
                }
                $dokter['jadwal'] = $jadwal_parsed;
                }

                sendResponse(true, 'Data dokter berhasil diambil', $dokter);
        } else {
            // Get all dokter
            $status = $_GET['status'] ?? 'aktif';
            $spesialisasi = $_GET['spesialisasi'] ?? null;
            
            $query = "
                SELECT d.*, d.tarif, d.foto, COUNT(DISTINCT j.id) as jumlah_jadwal,
                GROUP_CONCAT(DISTINCT j.hari ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu') SEPARATOR ', ') as hari_praktik
                FROM dokter d
                LEFT JOIN jadwal_dokter j ON d.id = j.dokter_id AND j.is_active = 1
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($status && $status !== 'semua') {
                $query .= " AND d.status = ?";
                $params[] = $status;
            }
            
            if ($spesialisasi) {
                $query .= " AND d.spesialisasi = ?";
                $params[] = $spesialisasi;
            }
            
            $query .= " GROUP BY d.id ORDER BY d.nama ASC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $dokter = $stmt->fetchAll();
            
            // Get list spesialisasi untuk filter
            $stmt = $conn->query("SELECT DISTINCT spesialisasi FROM dokter ORDER BY spesialisasi");
            $spesialisasi_list = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            sendResponse(true, 'Data dokter berhasil diambil', [
                'dokter' => $dokter,
                'total' => count($dokter),
                'spesialisasi_list' => $spesialisasi_list
            ]);
        }
    }
    
    // POST - Tambah dokter baru
    elseif ($method === 'POST') {
        checkAdmin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validasi input
        if (empty($input['nama']) || empty($input['spesialisasi'])) {
            sendResponse(false, 'Nama dan spesialisasi wajib diisi', null, 400);
        }
        
        // Normalize no_str (treat empty strings as NULL) before validation/insert
        $no_str = isset($input['no_str']) ? trim($input['no_str']) : null;
        if ($no_str === '') $no_str = null;
        if ($no_str !== null) {
            $stmt = $conn->prepare("SELECT id FROM dokter WHERE no_str = ?");
            $stmt->execute([$no_str]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Nomor STR sudah terdaftar', null, 400);
            }
        }
        
        $tarif = isset($input['tarif']) && is_numeric($input['tarif']) ? (float)$input['tarif'] : null;

        $stmt = $conn->prepare("
            INSERT INTO dokter (nama, spesialisasi, gelar, no_str, email, no_telepon, tarif, foto, biografi, pengalaman_tahun, pendidikan, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['nama'],
            $input['spesialisasi'],
            $input['gelar'] ?? null,
            $no_str,
            $input['email'] ?? null,
            $input['no_telepon'] ?? null,
            $tarif,
            $input['foto'] ?? null,
            $input['biografi'] ?? null,
            $input['pengalaman_tahun'] ?? 0,
            $input['pendidikan'] ?? null,
            $input['status'] ?? 'aktif'
        ]);
        
        $dokterId = $conn->lastInsertId();
        
        sendResponse(true, 'Dokter berhasil ditambahkan', ['id' => $dokterId], 201);
    }
    
    // PUT - Update dokter
    elseif ($method === 'PUT') {
        checkAdmin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            sendResponse(false, 'ID dokter wajib diisi', null, 400);
        }
        
        // Cek dokter exists
        $stmt = $conn->prepare("SELECT id FROM dokter WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            sendResponse(false, 'Dokter tidak ditemukan', null, 404);
        }
        
        // Normalize no_str (treat empty strings as NULL) before validation/update
        $no_str = isset($input['no_str']) ? trim($input['no_str']) : null;
        if ($no_str === '') $no_str = null;
        if ($no_str !== null) {
            $stmt = $conn->prepare("SELECT id FROM dokter WHERE no_str = ? AND id != ?");
            $stmt->execute([$no_str, $id]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Nomor STR sudah digunakan dokter lain', null, 400);
            }
        }
        
        $tarif = isset($input['tarif']) && is_numeric($input['tarif']) ? (float)$input['tarif'] : null;

        $stmt = $conn->prepare("
            UPDATE dokter SET 
                nama = ?, spesialisasi = ?, gelar = ?, no_str = ?, 
                email = ?, no_telepon = ?, tarif = ?, foto = ?, biografi = ?, 
                pengalaman_tahun = ?, pendidikan = ?, status = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $input['nama'],
            $input['spesialisasi'],
            $input['gelar'] ?? null,
            $no_str,
            $input['email'] ?? null,
            $input['no_telepon'] ?? null,
            $tarif,
            $input['foto'] ?? null,
            $input['biografi'] ?? null,
            $input['pengalaman_tahun'] ?? 0,
            $input['pendidikan'] ?? null,
            $input['status'] ?? 'aktif',
            $id
        ]);
        
        sendResponse(true, 'Data dokter berhasil diupdate');
    }
    
    // DELETE - Hapus dokter
    elseif ($method === 'DELETE') {
        checkAdmin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            sendResponse(false, 'ID dokter wajib diisi', null, 400);
        }
        
        // Cek apakah ada booking aktif
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM booking 
            WHERE dokter_id = ? AND status IN ('pending', 'dikonfirmasi')
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            sendResponse(false, 'Tidak dapat menghapus dokter yang masih memiliki booking aktif', null, 400);
        }
        
        // Hapus dokter (akan otomatis hapus jadwal karena ON DELETE CASCADE)
        $stmt = $conn->prepare("DELETE FROM dokter WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse(true, 'Dokter berhasil dihapus');
        } else {
            sendResponse(false, 'Dokter tidak ditemukan', null, 404);
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
