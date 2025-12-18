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
    $orderId = $input['order_id'] ?? null;
    $transactionId = $input['transaction_id'] ?? null;
    $amount = isset($input['amount']) ? (float)$input['amount'] : null;

    if(!$bookingId || !$orderId || !$transactionId) sendResponse(false, 'booking_id, order_id and transaction_id are required', null, 400);

    // fetch booking and ensure it belongs to user
    $stmt = $conn->prepare('SELECT * FROM booking WHERE id = ?');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    if(!$booking) sendResponse(false, 'Booking not found', null, 404);
    if($booking['user_id'] != $_SESSION['user_id']) sendResponse(false, 'Access denied', null, 403);

    // verify transaction status with Midtrans
    $midtransPath = dirname(__FILE__) . '/../midtrans-php-master/midtrans-php-master/Midtrans.php';
    if(!file_exists($midtransPath)){
        sendResponse(false, 'Midtrans PHP library not found on server. Expected at ' . $midtransPath, null, 500);
    }
    require_once $midtransPath;
    \Midtrans\Config::$serverKey = 'Mid-server-4xTdi3CWEUHws4NZGPpoWmEQ';
    \Midtrans\Config::$isProduction = false;

    try{
        $statusResp = \Midtrans\Transaction::status($orderId);
    }catch(Exception $e){
        sendResponse(false, 'Failed to verify transaction: ' . $e->getMessage(), null, 500);
    }

    $transaction_status = $statusResp->transaction_status ?? null;

    // Accept only final statuses
    $finalStatuses = ['capture','settlement','success'];
    $isFinal = in_array($transaction_status, $finalStatuses);

    // find payment record we earlier created with transaction_no == orderId
    $stmt = $conn->prepare('SELECT * FROM payments WHERE booking_id = ? AND transaction_no = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$bookingId, $orderId]);
    $p = $stmt->fetch();

    if(!$p) sendResponse(false, 'Payment record not found for this order', null, 404);

    // update payment record: set transaction_no to transactionId (Midtrans transaction id) and amount if provided
    $upd = $conn->prepare('UPDATE payments SET transaction_no = ?, amount = ? WHERE id = ?');
    $upd->execute([$transactionId, $amount ?: $p['amount'], $p['id']]);

    if($isFinal){
        // mark booking as paid
        $stmt = $conn->prepare('UPDATE booking SET payment_status = ?, payment_method = ? WHERE id = ?');
        $stmt->execute(['sudah_bayar', 'midtrans_snap', $bookingId]);
        sendResponse(true, 'Payment confirmed and booking marked as paid', ['transaction_status' => $transaction_status, 'transaction_id' => $transactionId]);
    } else {
        sendResponse(true, 'Payment pending', ['transaction_status' => $transaction_status]);
    }

}catch(PDOException $e){
    sendResponse(false, 'DB error: ' . $e->getMessage(), null, 500);
}catch(Exception $e){
    sendResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}
?>