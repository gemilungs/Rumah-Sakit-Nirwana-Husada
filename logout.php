<?php
require_once 'config.php';
// Destroy session and redirect to home with message
if (session_status() === PHP_SESSION_NONE) session_start();
session_unset();
session_destroy();
header('Location: index.php');
exit;
?>
