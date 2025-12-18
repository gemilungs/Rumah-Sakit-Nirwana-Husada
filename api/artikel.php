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
    
    // GET - Ambil artikel
    if ($method === 'GET') {
        $slug = $_GET['slug'] ?? null;
        $id = $_GET['id'] ?? null;
        
        if ($slug) {
            // Get artikel by slug
            $stmt = $conn->prepare("SELECT * FROM artikel WHERE slug = ? AND is_published = 1");
            $stmt->execute([$slug]);
            $artikel = $stmt->fetch();
            
            if (!$artikel) {
                sendResponse(false, 'Artikel tidak ditemukan', null, 404);
            }
            
            // Update views
            $stmt = $conn->prepare("UPDATE artikel SET views = views + 1 WHERE id = ?");
            $stmt->execute([$artikel['id']]);
            
            sendResponse(true, 'Artikel berhasil diambil', $artikel);
            
        } elseif ($id) {
            // Get artikel by ID (untuk admin edit)
            checkAdmin();
            
            $stmt = $conn->prepare("SELECT * FROM artikel WHERE id = ?");
            $stmt->execute([$id]);
            $artikel = $stmt->fetch();
            
            if (!$artikel) {
                sendResponse(false, 'Artikel tidak ditemukan', null, 404);
            }
            
            sendResponse(true, 'Artikel berhasil diambil', $artikel);
            
        } else {
            // Get all artikel
            $kategori = $_GET['kategori'] ?? null;
            $limit = $_GET['limit'] ?? 10;
            $offset = $_GET['offset'] ?? 0;
            $isAdmin = isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin';
            
            $query = "SELECT * FROM artikel WHERE 1=1";
            $params = [];
            
            // Non-admin hanya bisa lihat yang published
            if (!$isAdmin) {
                $query .= " AND is_published = 1";
            }
            
            if ($kategori) {
                $query .= " AND kategori = ?";
                $params[] = $kategori;
            }
            
            $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $artikel = $stmt->fetchAll();
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM artikel WHERE 1=1";
            $countParams = [];
            
            if (!$isAdmin) {
                $countQuery .= " AND is_published = 1";
            }
            
            if ($kategori) {
                $countQuery .= " AND kategori = ?";
                $countParams[] = $kategori;
            }
            
            $stmt = $conn->prepare($countQuery);
            $stmt->execute($countParams);
            $total = $stmt->fetch()['total'];
            
            // Get kategori list
            $stmt = $conn->query("SELECT DISTINCT kategori FROM artikel WHERE is_published = 1 ORDER BY kategori");
            $kategori_list = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            sendResponse(true, 'Artikel berhasil diambil', [
                'artikel' => $artikel,
                'total' => $total,
                'kategori_list' => $kategori_list
            ]);
        }
    }
    
    // POST - Tambah artikel baru
    elseif ($method === 'POST') {
        checkAdmin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validasi input
        if (empty($input['judul']) || empty($input['konten'])) {
            sendResponse(false, 'Judul dan konten wajib diisi', null, 400);
        }
        
        // Generate slug dari judul
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['judul'])));
        
        // Cek slug sudah ada
        $stmt = $conn->prepare("SELECT id FROM artikel WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= '-' . time();
        }
        
        // Generate excerpt jika tidak ada
        $excerpt = $input['excerpt'] ?? substr(strip_tags($input['konten']), 0, 200) . '...';
        
        $stmt = $conn->prepare("
            INSERT INTO artikel (judul, slug, konten, excerpt, gambar_utama, kategori, penulis, is_published, published_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $is_published = $input['is_published'] ?? 1;
        
        $stmt->execute([
            $input['judul'],
            $slug,
            $input['konten'],
            $excerpt,
            $input['gambar_utama'] ?? null,
            $input['kategori'] ?? 'Umum',
            $input['penulis'] ?? $_SESSION['nama_lengkap'],
            $is_published,
            $is_published ? date('Y-m-d H:i:s') : null
        ]);
        
        sendResponse(true, 'Artikel berhasil ditambahkan', [
            'id' => $conn->lastInsertId(),
            'slug' => $slug
        ], 201);
    }
    
    // PUT - Update artikel
    elseif ($method === 'PUT') {
        checkAdmin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            sendResponse(false, 'ID artikel wajib diisi', null, 400);
        }
        
        // Cek artikel exists
        $stmt = $conn->prepare("SELECT * FROM artikel WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            sendResponse(false, 'Artikel tidak ditemukan', null, 404);
        }
        
        // Update slug jika judul berubah
        $slug = $existing['slug'];
        if (!empty($input['judul']) && $input['judul'] !== $existing['judul']) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['judul'])));
            
            // Cek slug baru sudah ada
            $stmt = $conn->prepare("SELECT id FROM artikel WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id]);
            if ($stmt->fetch()) {
                $slug .= '-' . time();
            }
        }
        
        $stmt = $conn->prepare("
            UPDATE artikel SET 
                judul = ?, slug = ?, konten = ?, excerpt = ?, gambar_utama = ?, 
                kategori = ?, penulis = ?, is_published = ?, 
                published_at = CASE WHEN is_published = 0 AND ? = 1 THEN NOW() ELSE published_at END
            WHERE id = ?
        ");
        
        $stmt->execute([
            $input['judul'] ?? $existing['judul'],
            $slug,
            $input['konten'] ?? $existing['konten'],
            $input['excerpt'] ?? $existing['excerpt'],
            $input['gambar_utama'] ?? $existing['gambar_utama'],
            $input['kategori'] ?? $existing['kategori'],
            $input['penulis'] ?? $existing['penulis'],
            $input['is_published'] ?? $existing['is_published'],
            $input['is_published'] ?? $existing['is_published'],
            $id
        ]);
        
        sendResponse(true, 'Artikel berhasil diupdate');
    }
    
    // DELETE - Hapus artikel
    elseif ($method === 'DELETE') {
        checkAdmin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            sendResponse(false, 'ID artikel wajib diisi', null, 400);
        }
        
        $stmt = $conn->prepare("DELETE FROM artikel WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse(true, 'Artikel berhasil dihapus');
        } else {
            sendResponse(false, 'Artikel tidak ditemukan', null, 404);
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
