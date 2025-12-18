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

// Simple HTML mail helper
function sendHtmlMail($to, $subject, $htmlBody, $fromName = 'RS Nirwana Husada', $fromEmail = null){
    $fromEmail = $fromEmail ?: 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
    $headers .= 'From: ' . $fromName . ' <' . $fromEmail . '>' . "\r\n";
    return mail($to, $subject, $htmlBody, $headers);
}

try{
    $conn = getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    if($method !== 'POST') sendResponse(false, 'Method not allowed', null, 405);

    checkAuth();
    $input = json_decode(file_get_contents('php://input'), true);
    $bookingId = $input['booking_id'] ?? null;
    $email = isset($input['email']) ? trim($input['email']) : null;

    if(!$bookingId) sendResponse(false, 'booking_id is required', null, 400);

    // fetch booking and ensure it belongs to user
    $stmt = $conn->prepare('SELECT * FROM booking WHERE id = ?');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    if(!$booking) sendResponse(false, 'Booking not found', null, 404);
    if($booking['user_id'] != $_SESSION['user_id'] && ($_SESSION['role'] ?? '') !== 'admin') sendResponse(false, 'Access denied', null, 403);

    // fetch user email if not provided
    if(!$email){
        $u = $conn->prepare('SELECT email FROM users WHERE id = ?');
        $u->execute([$booking['user_id']]);
        $user = $u->fetch();
        if(!$user || empty($user['email'])) sendResponse(false, 'User email not found', null, 400);
        $email = $user['email'];
    }

    // fetch latest payment for this booking
    $pstmt = $conn->prepare('SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1');
    $pstmt->execute([$bookingId]);
    $payment = $pstmt->fetch();
    if(!$payment) sendResponse(false, 'No payment record found for this booking', null, 404);

    // build simple HTML receipt
    $siteName = 'RS Nirwana Husada';
    $subject = "Struk Pembayaran - " . ($payment['transaction_no'] ?? 'TRX');
    $date = date('d-m-Y H:i', strtotime($payment['created_at'] ?? date('Y-m-d H:i:s')));

    $lines = [];
    // gather details similar to UI: admin, procedure, dokter
    // Note: fixed fees in frontend; attempt to mirror those numbers
    $adminFee = 75000;
    $procedureFee = 79000;
    $dokterFee = (float)($booking['biaya_konsultasi'] ?? 0);
    $subtotal = $adminFee + $procedureFee + $dokterFee;

    $html = "<h2>{$siteName}</h2>";
    $html .= "<p>Transaksi #: <strong>" . htmlspecialchars($payment['transaction_no'] ?? '') . "</strong><br/>Tanggal: {$date}</p>";
    $html .= "<p>Nama Pasien: <strong>" . htmlspecialchars($booking['nama_pasien'] ?? '-') . "</strong><br/>Nomor Antrian: <strong>" . htmlspecialchars($booking['nomor_antrian'] ?? '-') . "</strong></p>";
    $html .= "<table style='width:100%;border-collapse:collapse'>";
    $html .= "<tr><td>Administrasi</td><td style='text-align:right'>IDR " . number_format($adminFee,0,',','.') . "</td></tr>";
    $html .= "<tr><td>Manual Akupunktur</td><td style='text-align:right'>IDR " . number_format($procedureFee,0,',','.') . "</td></tr>";
    $html .= "<tr><td>Jasa Dokter</td><td style='text-align:right'>IDR " . number_format($dokterFee,0,',','.') . "</td></tr>";
    $html .= "<tr><td style='border-top:1px solid #ddd;font-weight:700'>Jumlah Total</td><td style='text-align:right;border-top:1px solid #ddd;font-weight:700'>IDR " . number_format($payment['amount'],0,',','.') . "</td></tr>";
    $html .= "</table>";
    $html .= "<p>Terima kasih atas pembayaran Anda.</p>";

    $sent = sendHtmlMail($email, $subject, $html);
    if($sent){
        // record email sent metadata
        $upd = $conn->prepare('UPDATE payments SET email_sent_at = ?, sent_to = ? WHERE id = ?');
        $upd->execute([date('Y-m-d H:i:s'), $email, $payment['id']]);
        sendResponse(true, 'Receipt sent', ['email' => $email, 'sent_at' => date('c')]);
    } else {
        sendResponse(false, 'Failed to send email (mail server may not be configured)', null, 500);
    }

} catch(PDOException $e){
    sendResponse(false, 'DB error: '.$e->getMessage(), null, 500);
} catch(Exception $e){
    sendResponse(false, 'Error: '.$e->getMessage(), null, 500);
}

?>
