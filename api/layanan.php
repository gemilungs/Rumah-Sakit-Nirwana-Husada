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
    
    // GET - Ambil layanan
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM layanan WHERE id = ?");
            $stmt->execute([$id]);
            $layanan = $stmt->fetch();
            
            if (!$layanan) {
                sendResponse(false, 'Layanan tidak ditemukan', null, 404);
            }
            
            sendResponse(true, 'Layanan berhasil diambil', $layanan);
        } else {
            $kategori = $_GET['kategori'] ?? null;
            $is_active = $_GET['is_active'] ?? 1;
            
            $query = "SELECT * FROM layanan WHERE 1=1";
            $params = [];
            
            if ($is_active !== 'semua') {
                $query .= " AND is_active = ?";
                $params[] = $is_active;
            }
            
            if ($kategori) {
                $query .= " AND kategori = ?";
                $params[] = $kategori;
            }
            
            $query .= " ORDER BY nama_layanan ASC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $layanan = $stmt->fetchAll();
            
            // Get kategori list
            $stmt = $conn->query("SELECT DISTINCT kategori FROM layanan WHERE is_active = 1 ORDER BY kategori");
            $kategori_list = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            sendResponse(true, 'Layanan berhasil diambil', [
                'layanan' => $layanan,
                'total' => count($layanan),
                'kategori_list' => $kategori_list
            ]);
        }
    }
    
    // POST - Tambah layanan baru
    elseif ($method === 'POST') {
        checkAdmin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['nama_layanan'])) {
            sendResponse(false, 'Nama layanan wajib diisi', null, 400);
        }
        
        $stmt = $conn->prepare("
            INSERT INTO layanan (nama_layanan, kategori, deskripsi, harga_estimasi, icon, gambar, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['nama_layanan'],
            $input['kategori'] ?? 'Umum',
            $input['deskripsi'] ?? null,
            $input['harga_estimasi'] ?? 0,
            $input['icon'] ?? null,
            $input['gambar'] ?? null,
            $input['is_active'] ?? 1
        ]);
        
        sendResponse(true, 'Layanan berhasil ditambahkan', ['id' => $conn->lastInsertId()], 201);
    }
    
    // PUT - Update layanan
    elseif ($method === 'PUT') {
        checkAdmin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            sendResponse(false, 'ID layanan wajib diisi', null, 400);
        }
        
        $stmt = $conn->prepare("
            UPDATE layanan SET 
                nama_layanan = ?, kategori = ?, deskripsi = ?, harga_estimasi = ?, 
                icon = ?, gambar = ?, is_active = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $input['nama_layanan'],
            $input['kategori'],
            $input['deskripsi'],
            $input['harga_estimasi'],
            $input['icon'],
            $input['gambar'],
            $input['is_active'],
            $id
        ]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse(true, 'Layanan berhasil diupdate');
        } else {
            sendResponse(false, 'Layanan tidak ditemukan atau tidak ada perubahan', null, 404);
        }
    }
    
    // DELETE - Hapus layanan
    elseif ($method === 'DELETE') {
        checkAdmin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            sendResponse(false, 'ID layanan wajib diisi', null, 400);
        }
        
        $stmt = $conn->prepare("DELETE FROM layanan WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse(true, 'Layanan berhasil dihapus');
        } else {
            sendResponse(false, 'Layanan tidak ditemukan', null, 404);
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
