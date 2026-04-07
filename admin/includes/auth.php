<?php
// Start session hanya jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek authentication - pakai admin_id sebagai standar
if (!isset($_SESSION['admin_id'])) {
    // Hitung base URL untuk redirect
    $admin_script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $admin_base_url = strpos($admin_script_path, '/admin/') !== false
        ? substr($admin_script_path, 0, strpos($admin_script_path, '/admin/'))
        : rtrim(str_replace('\\', '/', dirname($admin_script_path)), '/');
    if ($admin_base_url === '/') {
        $admin_base_url = '';
    }
    $login_url = $admin_base_url . '/admin/login.php';
    header('Location: ' . $login_url);
    exit;
}