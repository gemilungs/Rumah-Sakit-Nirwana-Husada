<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$logDir = __DIR__ . '/../logs';
$files = [
    'received' => $logDir . '/user_update_received.log',
    'put_debug' => $logDir . '/user_put_debug.log',
    'success' => $logDir . '/user_update_success.log',
    'error' => $logDir . '/user_update_error.log',
    'profile_test_errors' => $logDir . '/profile_test_errors.log'
];
$out = ['success' => true, 'files' => []];
foreach ($files as $key => $path) {
    if (!file_exists($path)) { $out['files'][$key] = null; continue; }
    // Return only last 200 lines to keep it lightweight
    $lines = [];
    $fp = fopen($path, 'r');
    if (!$fp) { $out['files'][$key] = 'unable to open'; continue; }
    $pos = -1; $line = ''; $count = 0; $maxLines = 200; $buffer = '';
    fseek($fp, 0, SEEK_END);
    $endPos = ftell($fp);
    $chunkSize = 4096;

    while ($pos > -$endPos && $count < $maxLines) {
        $seek = max(0, $endPos + $pos - $chunkSize + 1);
        fseek($fp, $seek);
        $chunk = fread($fp, min($chunkSize, $endPos - $seek + 1));
        $buffer = $chunk . $buffer;
        $pos -= $chunkSize;
        $lines = explode("\n", $buffer);
        if (count($lines) > $maxLines) break;
        if ($seek === 0) break;
    }
    // normalize to last maxLines
    $lines = array_filter($lines, function($l){ return trim($l) !== ''; });
    $lines = array_slice($lines, -$maxLines);
    fclose($fp);
    $out['files'][$key] = array_values($lines);
}
echo json_encode($out, JSON_PRETTY_PRINT);
exit;
