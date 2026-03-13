<?php
/**
 * BACKWARD COMPATIBILITY - Redirect ke Config Database Global
 * File lama ini tidak lagi digunakan. Semua koneksi database sekarang
 * berpusat di /config/database.php
 */

require_once dirname(__DIR__) . '/config/database.php';

// $conn sudah tersedia dari config/database.php
?>
