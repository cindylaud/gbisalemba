<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Check if ID is provided and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=ID tidak valid");
    exit;
}

$id = (int)$_GET['id'];

// Check if jadwal exists and get nama_ibadah for success message
$stmt_check = $conn->prepare("SELECT id, nama_ibadah FROM jadwal_ibadah WHERE id = ?");
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows == 0) {
    $stmt_check->close();
    header("Location: index.php?error=Jadwal ibadah tidak ditemukan");
    exit;
}

$jadwal_data = $result_check->fetch_assoc();
$stmt_check->close();

// Delete jadwal
$stmt_delete = $conn->prepare("DELETE FROM jadwal_ibadah WHERE id = ?");
$stmt_delete->bind_param("i", $id);

if ($stmt_delete->execute()) {
    $stmt_delete->close();
    $nama = htmlspecialchars($jadwal_data['nama_ibadah']);
    header("Location: index.php?success=Jadwal ibadah '{$nama}' berhasil dihapus");
    exit;
} else {
    $stmt_delete->close();
    header("Location: index.php?error=Gagal menghapus jadwal ibadah");
    exit;
}



