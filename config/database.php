<?php
/**
 * =====================================================================
 * KONEKSI DATABASE GLOBAL - GBI SALEMBA
 * =====================================================================
 * File ini menyediakan koneksi database yang digunakan oleh seluruh
 * aplikasi (frontend dan admin).
 * 
 * Penggunaan:
 *   - Dari root: require_once __DIR__ . '/';
 *   - Dari admin: require_once __DIR__ . '/';
 *   - Query: $conn->query(); atau prepared statement
 * =====================================================================
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gbi_salemba');
define('DB_CHARSET', 'utf8mb4');

// Sambungkan ke database menggunakan MySQLi OOP
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    // Log error ke file (opsional)
    error_log("Database Connection Error: " . $conn->connect_error);
    
    // Tampilkan pesan error (bisa di-disable di production)
    die("Maaf, koneksi ke database gagal. Silakan hubungi administrator.<br>");
}

// Set charset ke UTF-8 MB4 untuk mendukung emoji dan karakter spesial
$conn->set_charset(DB_CHARSET);

// Set timezone ke UTC (opsional, bisa disesuaikan)
date_default_timezone_set('UTC');

/**
 * Helper function untuk escape string (backup untuk persiapan)
 * Preferensi: gunakan PREPARED STATEMENT untuk keamanan maksimal
 */
if (!function_exists('escape_string')) {
    function escape_string($string) {
        global $conn;
        return $conn->real_escape_string($string);
    }
}

/**
 * Helper function untuk close koneksi saat diperlukan
 */
if (!function_exists('close_connection')) {
    function close_connection() {
        global $conn;
        if ($conn) {
            $conn->close();
        }
    }
}

// Register shutdown function untuk menutup koneksi otomatis
register_shutdown_function('close_connection');

