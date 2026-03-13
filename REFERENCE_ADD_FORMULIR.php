<?php
/**
 * =====================================================================
 * REFERENCE CODE: TAMBAH FORMULIR DENGAN UPLOAD PDF
 * =====================================================================
 * File ini menunjukkan contoh kode minimal untuk:
 * 1. Validasi input dari form
 * 2. Validasi dan upload file PDF
 * 3. Insert ke database dengan prepared statement
 * 4. Error handling dan rollback
 * 
 * Dapat langsung di-copy for use case lain.
 * =====================================================================
 */

require_once 'config/database.php';

// Konfigurasi
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_DIR', 'uploads/formulir/');

// =====================================================================
// 1. VALIDASI INPUT
// =====================================================================
$nama_formulir = isset($_POST['nama_formulir']) ? trim($_POST['nama_formulir']) : '';
$deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
$is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

// Cek tidak kosong
if (empty($nama_formulir)) {
    die('ERROR: Nama formulir tidak boleh kosong.');
}

if (empty($deskripsi)) {
    die('ERROR: Deskripsi tidak boleh kosong.');
}

// Cek panjang sesuai schema
if (strlen($nama_formulir) > 100) {
    die('ERROR: Nama formulir maksimal 100 karakter.');
}

if (strlen($deskripsi) > 255) {
    die('ERROR: Deskripsi maksimal 255 karakter.');
}

// =====================================================================
// 2. VALIDASI FILE UPLOAD
// =====================================================================
if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    die('ERROR: File PDF harus diupload.');
}

$file = $_FILES['file'];

// Validasi ukuran file
if ($file['size'] > MAX_UPLOAD_SIZE) {
    die('ERROR: Ukuran file terlalu besar. Maksimal 10MB.');
}

// Validasi MIME type (method 1: check $_FILES['type'])
if ($file['type'] !== 'application/pdf') {
    die('ERROR: File harus berformat PDF. Tipe: ' . $file['type']);
}

// Validasi MIME type (method 2: double check dengan finfo - lebih aman)
if (function_exists('finfo_file')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $real_mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if ($real_mime !== 'application/pdf') {
        die('ERROR: File bukan PDF yang valid. MIME: ' . $real_mime);
    }
}

// =====================================================================
// 3. GENERATE UNIQUE FILENAME
// =====================================================================
// Format: formulir_TIMESTAMP_RANDOM.pdf
// Contoh: formulir_1770906667_a1b2c3d4.pdf

$timestamp = time();
$random = substr(bin2hex(random_bytes(4)), 0, 8);
$file_name = "formulir_{$timestamp}_{$random}.pdf";

// Pastikan folder ada
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// =====================================================================
// 4. UPLOAD FILE
// =====================================================================
$upload_path = UPLOAD_DIR . $file_name;

if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    die('ERROR: Gagal mengupload file.');
}

// =====================================================================
// 5. INSERT KE DATABASE (PREPARED STATEMENT)
// =====================================================================
// Query: INSERT INTO formulir (nama_formulir, file, deskripsi, is_active) 
//        VALUES (?, ?, ?, ?)
// Note: id dan created_at auto-generate

$stmt = $conn->prepare("INSERT INTO formulir (nama_formulir, file, deskripsi, is_active) VALUES (?, ?, ?, ?)");

if ($stmt === false) {
    // Rollback: hapus file yang sudah terupload
    @unlink($upload_path);
    die('ERROR: Database prepare failed. ' . $conn->error);
}

// Bind parameters
// s = string, i = integer
$stmt->bind_param("sssi", $nama_formulir, $file_name, $deskripsi, $is_active);

// Execute
if (!$stmt->execute()) {
    // Rollback: hapus file yang sudah terupload jika insert gagal
    @unlink($upload_path);
    die('ERROR: Database insert failed. ' . $stmt->error);
}

// =====================================================================
// 6. SUCCESS
// =====================================================================
$inserted_id = $stmt->insert_id;
$stmt->close();

// Success response
echo "SUCCESS: Formulir '{$nama_formulir}' berhasil ditambahkan.";
echo "\nID: {$inserted_id}";
echo "\nFile: {$file_name}";

// Atau redirect ke halaman list
// header("Location: formulir.php?success=1");
// exit;

?>
