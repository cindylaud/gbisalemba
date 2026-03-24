<?php
/**
 * =====================================================================
 * DOWNLOAD FORMULIR PDF - GBI SALEMBA
 * =====================================================================
 * File ini menangani download PDF formulir dengan nama file yang 
 * sesuai dengan nama_formulir dari database.
 * 
 * Penggunaan:
 *   - Link: download-formulir.php?id=1
 *   - File fisik: formulir_1770906667_xxx.pdf (random name)
 *   - Download name: Formulir Permohonan Doa.pdf (dari database)
 * 
 * Security:
 *   - Prepared statement untuk prevent SQL injection
 *   - Validasi id (integer)
 *   - Check status = 'aktif'
 *   - Validasi file exists
 *   - Validasi file path (prevent directory traversal)
 * =====================================================================
 */

require_once __DIR__ . '/config/database.php';

// =====================================================================
// VALIDASI INPUT
// =====================================================================
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    die('ID formulir tidak valid.');
}

// =====================================================================
// KONFIGURASI
// =====================================================================
define('UPLOAD_DIR', __DIR__ . '/uploads/formulir/');

// Validasi folder ada
if (!is_dir(UPLOAD_DIR)) {
    http_response_code(500);
    die('Folder upload tidak ditemukan.');
}

// =====================================================================
// QUERY DATABASE (PREPARED STATEMENT)
// =====================================================================
$stmt = $conn->prepare("SELECT nama_formulir, file FROM formulir WHERE id = ? AND status = 'aktif'");

if (!$stmt) {
    http_response_code(500);
    die('Database error: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// =====================================================================
// CEK DATA DITEMUKAN
// =====================================================================
if ($result->num_rows === 0) {
    http_response_code(404);
    die('Formulir tidak ditemukan atau tidak aktif.');
}

$row = $result->fetch_assoc();
$nama_formulir = $row['nama_formulir'];
$file_name = $row['file'];
$stmt->close();

// =====================================================================
// VALIDASI FILE
// =====================================================================
// Validasi nama file (prevent directory traversal)
if (empty($file_name) || strpos($file_name, '..') !== false || strpos($file_name, '/') !== false || strpos($file_name, '\\') !== false) {
    http_response_code(400);
    die('Nama file tidak valid.');
}

// Full path ke file
$file_path = UPLOAD_DIR . $file_name;

// Validasi file exists dan berada dalam folder uploads/formulir/
if (!file_exists($file_path)) {
    http_response_code(404);
    die('File tidak ditemukan di server.');
}

// Validasi real path (prevent symlink traversal)
$real_path = realpath($file_path);
$real_upload_dir = realpath(UPLOAD_DIR);

if ($real_path === false || strpos($real_path, $real_upload_dir) !== 0) {
    http_response_code(403);
    die('Akses file ditolak.');
}

// =====================================================================
// SANITASI NAMA DOWNLOAD
// =====================================================================
// Hapus extension .pdf jika sudah ada
$download_name = $nama_formulir;
if (strtolower(substr($download_name, -4)) === '.pdf') {
    $download_name = substr($download_name, 0, -4);
}

// Bersihkan nama file (remove special characters)
$download_name = preg_replace('/[^a-zA-Z0-9\s\-_()]/u', '', $download_name);
$download_name = trim($download_name);

// Fallback jika nama kosong
if (empty($download_name)) {
    $download_name = 'formulir_' . $id;
}

// Add .pdf extension
$download_name = $download_name . '.pdf';

// =====================================================================
// SET HEADERS & STREAM FILE
// =====================================================================
// Clear any output buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Get file size
$file_size = filesize($file_path);

// Set headers untuk download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . $file_size);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// =====================================================================
// OUTPUT FILE
// =====================================================================
// Use readfile untuk streaming file
if (readfile($file_path) === false) {
    http_response_code(500);
    die('Error reading file.');
}

exit;

?>


