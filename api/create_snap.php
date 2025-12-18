<?php
require_once '../config.php';
header('Content-Type: application/json');

function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function checkAuth(){
    if(!isset($_SESSION['user_id'])) sendResponse(false, 'Unauthorized', null, 401);
}

try{
    checkAuth();
    $conn = getConnection();
    $input = json_decode(file_get_contents('php://input'), true);
    $bookingId = $input['booking_id'] ?? null;

    if(!$bookingId) sendResponse(false, 'booking_id is required', null, 400);

    // fetch booking and ensure it belongs to user
    $stmt = $conn->prepare('SELECT * FROM booking WHERE id = ?');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    if(!$booking) sendResponse(false, 'Booking not found', null, 404);
    if($booking['user_id'] != $_SESSION['user_id']) sendResponse(false, 'Access denied', null, 403);
    if($booking['payment_status'] === 'sudah_bayar') sendResponse(false, 'Booking already paid', null, 400);

    // compute amount using same rules as frontend
    $adminFee = 75000;
    $procedureFee = 79000;
    $dokterFee = (float)($booking['biaya_konsultasi'] ?? 0);
    $subtotal = $adminFee + $procedureFee + $dokterFee;
    $gol = $booking['tipe_pasien'] ?? 'UMUM';
    $covered = 0;
    if($gol === 'UMUM'){
        $covered = 0; $patientPays = $subtotal;
    }else if($gol === 'ASURANSI'){
        $coverable = $procedureFee + $dokterFee;
        $covered = round($coverable * 0.9);
        $patientPays = $subtotal - $covered;
    }else if($gol === 'BPJS'){
        $covered = $procedureFee + $dokterFee;
        $patientPays = $adminFee;
    } else {
        $patientPays = $subtotal;
    }

    $amount = (int)round($patientPays); // Midtrans expects integer amounts without decimals for some payment types

    // generate unique order id
    $orderId = 'MIDTRX_' . time() . '_' . $bookingId;

    // Insert a pending payment record so we can reconcile later (transaction_no stores our order id for now)
    $insert = $conn->prepare('INSERT INTO payments (booking_id, amount, method, transaction_no) VALUES (?, ?, ?, ?)');
    $insert->execute([$bookingId, $amount, 'midtrans_snap', $orderId]);
    $paymentId = $conn->lastInsertId();

    // load user to attach customer details
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$booking['user_id']]);
    $user = $stmt->fetch();

    // Midtrans SDK
    $midtransPath = dirname(__FILE__) . '/../midtrans-php-master/midtrans-php-master/Midtrans.php';
    if(!file_exists($midtransPath)){
        sendResponse(false, 'Midtrans PHP library not found on server. Expected at ' . $midtransPath, null, 500);
    }
    require_once $midtransPath;
    \Midtrans\Config::$serverKey = 'Mid-server-4xTdi3CWEUHws4NZGPpoWmEQ';
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;

    // prepare params
    $item_details = [];
    $item_details[] = [
        'id' => 'admin',
        'price' => $adminFee,
        'quantity' => 1,
        'name' => 'Administrasi'
    ];
    $item_details[] = [
        'id' => 'procedure',
        'price' => $procedureFee,
        'quantity' => 1,
        'name' => 'Manual Akupunktur'
    ];
    if($dokterFee > 0){
        $item_details[] = [
            'id' => 'dokter',
            'price' => $dokterFee,
            'quantity' => 1,
            'name' => 'Jasa Dokter'
        ];
    }

    $params = [
        'transaction_details' => [
            'order_id' => $orderId,
            'gross_amount' => $amount,
        ],
        'customer_details' => [
            'first_name' => $user['nama_lengkap'] ?? ($booking['nama_pasien'] ?? 'Pasien'),
            'last_name' => '',
            'email' => $user['email'] ?? '',
            'phone' => $user['no_telepon'] ?? $booking['no_telepon'] ?? ''
        ],
        'item_details' => $item_details
    ];

    try{
        $snapToken = \Midtrans\Snap::getSnapToken($params);
        sendResponse(true, 'Snap token created', ['snap_token' => $snapToken, 'order_id' => $orderId, 'amount' => $amount]);
    }catch(Exception $e){
        sendResponse(false, 'Midtrans error: ' . $e->getMessage(), null, 500);
    }

}catch(PDOException $e){
    sendResponse(false, 'DB error: ' . $e->getMessage(), null, 500);
}catch(Exception $e){
    sendResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}
?>