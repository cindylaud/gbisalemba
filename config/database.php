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

if (!function_exists('gbi_env')) {
    function gbi_env($key, $default = null) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        return $default;
    }
}

// Konfigurasi Database (default lokal, bisa dioverride via environment hosting)
$dbHost = (string) gbi_env('DB_HOST', 'localhost');
$dbUser = (string) gbi_env('DB_USER', 'root');
$dbPass = (string) gbi_env('DB_PASS', '');
$dbName = (string) gbi_env('DB_NAME', 'gbi_salemba');
$dbCharset = (string) gbi_env('DB_CHARSET', 'utf8mb4');
$dbPort = (int) gbi_env('DB_PORT', 3306);

// Opsi tambahan: DATABASE_URL (contoh: mysql://user:pass@host:3306/dbname)
$databaseUrl = (string) gbi_env('DATABASE_URL', '');
if ($databaseUrl !== '') {
    $parsedUrl = @parse_url($databaseUrl);
    if (is_array($parsedUrl)) {
        if (!empty($parsedUrl['host'])) {
            $dbHost = (string) $parsedUrl['host'];
        }
        if (!empty($parsedUrl['user'])) {
            $dbUser = (string) $parsedUrl['user'];
        }
        if (isset($parsedUrl['pass'])) {
            $dbPass = (string) $parsedUrl['pass'];
        }
        if (!empty($parsedUrl['path'])) {
            $dbName = ltrim((string) $parsedUrl['path'], '/');
        }
        if (!empty($parsedUrl['port'])) {
            $dbPort = (int) $parsedUrl['port'];
        }
    }
}

define('DB_HOST', $dbHost);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_NAME', $dbName);
define('DB_CHARSET', $dbCharset);
define('DB_PORT', $dbPort);

// Sambungkan ke database menggunakan MySQLi OOP
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Cek koneksi
if ($conn->connect_error) {
    // Log error ke file (opsional)
    error_log("Database Connection Error: " . $conn->connect_error);
    
    // Tampilkan pesan error (bisa di-disable di production)
    die("Maaf, koneksi ke database gagal. Silakan hubungi administrator.<br>");
}

// Set charset ke UTF-8 MB4 untuk mendukung emoji dan karakter spesial
$conn->set_charset(DB_CHARSET);

// Set timezone (default Asia/Jakarta, bisa dioverride di environment APP_TIMEZONE)
$appTimezone = (string) gbi_env('APP_TIMEZONE', 'Asia/Jakarta');
if ($appTimezone === '') {
    $appTimezone = 'Asia/Jakarta';
}
date_default_timezone_set($appTimezone);

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

