<?php
// Endpoint for Midtrans server-to-server notifications (webhook)
require_once 'config.php';
header('Content-Type: application/json');

try{
    $midtransPath = dirname(__FILE__) . '/midtrans-php-master/midtrans-php-master/Midtrans.php';
    if(!file_exists($midtransPath)){
        http_response_code(500); echo json_encode(['success'=>false,'message'=>'Midtrans library not found on server: ' . $midtransPath]); exit;
    }
    require_once $midtransPath;
    \Midtrans\Config::$serverKey = 'Mid-server-4xTdi3CWEUHws4NZGPpoWmEQ';
    \Midtrans\Config::$isProduction = false;

    // Read notification payload
    $notif = new \Midtrans\Notification();

    $orderId = $notif->order_id ?? null;
    $transactionId = $notif->transaction_id ?? null;
    $transactionStatus = $notif->transaction_status ?? null;
    $fraudStatus = $notif->fraud_status ?? null;

    // find payment by our stored order id
    $conn = getConnection();
    if(!$orderId){
        http_response_code(400); echo json_encode(['success'=>false,'message'=>'order_id missing']); exit;
    }

    $stmt = $conn->prepare('SELECT * FROM payments WHERE transaction_no = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$orderId]);
    $p = $stmt->fetch();
    if(!$p){
        // nothing to update
        http_response_code(404); echo json_encode(['success'=>false,'message'=>'payment record not found']); exit;
    }

    // update payment transaction_no to real transaction id
    $upd = $conn->prepare('UPDATE payments SET transaction_no = ?, amount = ? WHERE id = ?');
    $gross = $notif->gross_amount ?? $notif->gross_amount ?? $p['amount'];
    $upd->execute([$transactionId ?: $orderId, $gross, $p['id']]);

    // check final statuses to mark booking
    $finalStatuses = ['capture','settlement','success'];
    if(in_array($transactionStatus, $finalStatuses)){
        // optionally check fraud status
        if($transactionStatus === 'capture' && $fraudStatus === 'deny'){
            // denied, do not mark as paid
        } else {
            $stmt = $conn->prepare('UPDATE booking SET payment_status = ?, payment_method = ? WHERE id = ?');
            $stmt->execute(['sudah_bayar','midtrans_snap',$p['booking_id']]);
        }
    }

    // Respond OK
    http_response_code(200);
    echo json_encode(['success'=>true,'message'=>'notification processed']);
    exit;

}catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    exit;
}
?>