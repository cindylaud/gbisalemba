<?php
// Start session hanya jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek authentication - pakai admin_id sebagai standar
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
?>