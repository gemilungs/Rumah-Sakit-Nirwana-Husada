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
    $conn = getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    if($method === 'POST'){
        checkAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $bookingId = $input['booking_id'] ?? null;
        $payMethod = $input['method'] ?? null;
        $amount = isset($input['amount']) ? (float)$input['amount'] : null;

        if(!$bookingId) sendResponse(false, 'booking_id is required', null, 400);

        // fetch booking and ensure it belongs to user
        $stmt = $conn->prepare('SELECT * FROM booking WHERE id = ?');
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        if(!$booking) sendResponse(false, 'Booking not found', null, 404);
        if($booking['user_id'] != $_SESSION['user_id']) sendResponse(false, 'Access denied', null, 403);

        if($booking['payment_status'] === 'sudah_bayar') sendResponse(false, 'Booking already paid', null, 400);

        // Use a transaction to ensure booking and payment record stay in sync
        $conn->beginTransaction();
        try {
            // Insert payment record
            $insert = $conn->prepare('INSERT INTO payments (booking_id, amount, method) VALUES (?, ?, ?)');
            $insert->execute([$bookingId, $amount ?: 0.00, $payMethod ?: null]);
            $paymentId = $conn->lastInsertId();

            // Create a predictable transaction number based on date and id
            $txnNo = sprintf('TRX%s_%06d', date('Ymd'), $paymentId);
            $upd = $conn->prepare('UPDATE payments SET transaction_no = ? WHERE id = ?');
            $upd->execute([$txnNo, $paymentId]);

            // Update booking payment status
            $stmt = $conn->prepare('UPDATE booking SET payment_status = ?, payment_method = ? WHERE id = ?');
            $stmt->execute(['sudah_bayar', $payMethod ?: null, $bookingId]);

            $conn->commit();

            sendResponse(true, 'Payment recorded', ['booking_id' => $bookingId, 'amount' => $amount, 'transaction_no' => $txnNo]);
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            sendResponse(false, 'Failed to record payment: ' . $e->getMessage(), null, 500);
        }
    }

    if($method === 'GET'){
        // return last payment record for a booking (if exists)
        $bookingId = $_GET['booking_id'] ?? null;
        if(!$bookingId) sendResponse(false, 'booking_id is required', null, 400);
        $stmt = $conn->prepare('SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$bookingId]);
        $p = $stmt->fetch();
        if(!$p) sendResponse(false, 'No payment found for this booking', null, 404);
        sendResponse(true, 'Payment found', ['payment' => $p]);
    }

    sendResponse(false, 'Method not allowed', null, 405);

}catch(PDOException $e){
    sendResponse(false, 'DB error: '.$e->getMessage(), null, 500);
}catch(Exception $e){
    sendResponse(false, 'Error: '.$e->getMessage(), null, 500);
}

?>
