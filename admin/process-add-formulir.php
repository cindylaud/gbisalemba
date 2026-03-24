<?php
/**
 * =====================================================================
 * HANDLER: TAMBAH FORMULIR BARU DENGAN UPLOAD PDF
 * =====================================================================
 * File ini menangani proses insert formulir baru dengan upload file PDF.
 * 
 * Digunakan oleh: admin/formulir.php
 * 
 * Fitur:
 * - Prepared statement (MySQLi) untuk prevent SQL injection
 * - Validasi input (tidak boleh kosong)
 * - Upload file PDF dengan validasi MIME type
 * - Max size 10MB
 * - Auto rename file: formulir_TIMESTAMP_RANDOM.pdf
 * - Rollback file jika insert database gagal
 * - Error handling yang proper
 * =====================================================================
 */

// Start output buffering untuk prevent header issues
if (ob_get_level() === 0) ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php'; // Auth check

// Jangan process jika bukan POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// =====================================================================
// KONFIGURASI
// =====================================================================
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_DIR', '../uploads/formulir/');
define('ALLOWED_MIME_TYPES', ['application/pdf']);

// Pastikan folder upload ada
if (!is_dir(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0755, true)) {
        die(json_encode(['success' => false, 'error' => 'Gagal membuat folder upload.']));
    }
}

// =====================================================================
// RESPONSE HANDLER
// =====================================================================
function sendResponse($success, $message, $redirect = null) {
    if (isset($_GET['json'])) {
        // JSON response untuk AJAX
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'redirect' => $redirect
        ]);
    } else {
        // Set session untuk flash message
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if ($success) {
            $_SESSION['success'] = $message;
        } else {
            $_SESSION['error'] = $message;
        }
        
        // Redirect ke referrer atau admin page
        if ($redirect) {
            header("Location: $redirect");
        } else {
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'formulir.php'));
        }
    }
    exit;
}

// =====================================================================
// VALIDASI INPUT
// =====================================================================
$nama_formulir = isset($_POST['nama_formulir']) ? trim($_POST['nama_formulir']) : '';
$deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
$is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

// Validasi field wajib diisi
if (empty($nama_formulir)) {
    sendResponse(false, 'Nama formulir tidak boleh kosong.');
}

if (empty($deskripsi)) {
    sendResponse(false, 'Deskripsi tidak boleh kosong.');
}

// Validasi panjang field (sesuai schema)
if (strlen($nama_formulir) > 100) {
    sendResponse(false, 'Nama formulir maksimal 100 karakter.');
}

if (strlen($deskripsi) > 255) {
    sendResponse(false, 'Deskripsi maksimal 255 karakter.');
}

// =====================================================================
// VALIDASI FILE UPLOAD
// =====================================================================
$file_name = null;

// Check apakah file diupload
if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    sendResponse(false, 'File PDF harus diupload.');
}

// Handle upload error
if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'Ukuran file terlalu besar (melebihi konfigurasi server).',
        UPLOAD_ERR_FORM_SIZE => 'Ukuran file terlalu besar (melebihi form limit).',
        UPLOAD_ERR_PARTIAL => 'File upload tidak lengkap.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan.',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
        UPLOAD_ERR_EXTENSION => 'Ekstensi file diblokir server.',
    ];
    
    $error_msg = $error_messages[$_FILES['file']['error']] ?? 'Error upload file.';
    sendResponse(false, $error_msg);
}

$file = $_FILES['file'];

// =====================================================================
// VALIDASI FILE SIZE
// =====================================================================
if ($file['size'] > MAX_UPLOAD_SIZE) {
    $max_size_mb = MAX_UPLOAD_SIZE / (1024 * 1024);
    sendResponse(false, "Ukuran file terlalu besar. Maksimal {$max_size_mb}MB.");
}

if ($file['size'] === 0) {
    sendResponse(false, 'File kosong, silakan upload file yang valid.');
}

// =====================================================================
// VALIDASI TIPE FILE
// =====================================================================
// Method 1: Check dari $_FILES['type'] (basic, bisa dispoofing)
$user_uploaded_mime = strtolower($file['type']);

if ($user_uploaded_mime !== 'application/pdf') {
    sendResponse(false, 'File harus berformat PDF. Tipe file yang diupload: ' . htmlspecialchars($user_uploaded_mime));
}

// Method 2: Double check dengan finfo (lebih aman)
if (function_exists('finfo_file')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $real_mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if ($real_mime !== 'application/pdf') {
        sendResponse(false, 'File bukan PDF yang valid. MIME type terdeteksi: ' . htmlspecialchars($real_mime));
    }
} else {
    // Fallback: check magic bytes (PDF header)
    $handle = fopen($file['tmp_name'], 'r');
    $header = fread($handle, 4);
    fclose($handle);
    
    if ($header !== "%PDF") {
        sendResponse(false, 'File bukan PDF yang valid (magic bytes check failed).');
    }
}

// =====================================================================
// GENERATE UNIQUE FILENAME
// =====================================================================
// Format: formulir_TIMESTAMP_RANDOM.pdf
// Contoh: formulir_1770906667_a1b2c3d4.pdf

$timestamp = time();
$random = substr(bin2hex(random_bytes(4)), 0, 8);
$file_name = "formulir_{$timestamp}_{$random}.pdf";

// Pastikan filename unik (extra safety, meskipun rare probability collision)
$counter = 0;
$original_file_name = $file_name;
while (file_exists(UPLOAD_DIR . $file_name) && $counter < 10) {
    $random = substr(bin2hex(random_bytes(4)), 0, 8);
    $file_name = "formulir_{$timestamp}_{$random}.pdf";
    $counter++;
}

if ($counter >= 10) {
    sendResponse(false, 'Gagal generate nama file unik. Silakan coba lagi.');
}

// =====================================================================
// UPLOAD FILE
// =====================================================================
$upload_path = UPLOAD_DIR . $file_name;

if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    sendResponse(false, 'Gagal mengupload file. Periksa permission folder uploads/formulir/');
}

// Verifikasi file berhasil terupload
if (!file_exists($upload_path)) {
    sendResponse(false, 'File tidak ditemukan setelah upload. Silakan coba lagi.');
}

// =====================================================================
// INSERT KE DATABASE (PREPARED STATEMENT)
// =====================================================================
// Query: INSERT INTO formulir (nama_formulir, file, deskripsi, is_active) VALUES (?, ?, ?, ?)
// Note: id dan created_at auto-generate di database

$stmt = $conn->prepare("INSERT INTO formulir (nama_formulir, file, deskripsi, is_active) VALUES (?, ?, ?, ?)");

if ($stmt === false) {
    // Rollback: hapus file yang sudah terupload
    @unlink($upload_path);
    
    error_log("Database prepare error: " . $conn->error);
    sendResponse(false, 'Database error. Silakan hubungi administrator.');
}

// Bind parameters
// s = string, i = integer
$stmt->bind_param("sssi", $nama_formulir, $file_name, $deskripsi, $is_active);

// Execute query
if (!$stmt->execute()) {
    // Rollback: hapus file yang sudah terupload
    @unlink($upload_path);
    
    error_log("Database execute error: " . $stmt->error);
    sendResponse(false, 'Gagal menyimpan data ke database. Silakan coba lagi.');
}

// Get inserted ID (optional, untuk feedback)
$inserted_id = $stmt->insert_id;
$stmt->close();

// =====================================================================
// SUCCESS RESPONSE
// =====================================================================
$success_message = "Formulir '{$nama_formulir}' berhasil ditambahkan.";
sendResponse(true, $success_message, 'formulir.php?tab=list');

?>




