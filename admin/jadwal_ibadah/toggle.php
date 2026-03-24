<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Check if ID is provided and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=ID tidak valid");
    exit;
}

$id = (int)$_GET['id'];

// Get current is_active status
$stmt_check = $conn->prepare("SELECT id, is_active FROM jadwal_ibadah WHERE id = ?");
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows == 0) {
    $stmt_check->close();
    header("Location: index.php?error=Jadwal ibadah tidak ditemukan");
    exit;
}

$jadwal_data = $result_check->fetch_assoc();
$current_status = $jadwal_data['is_active'];
$stmt_check->close();

// Toggle status: if 1 make 0, if 0 make 1
$new_status = ($current_status == 1) ? 0 : 1;

// Update is_active
$stmt_update = $conn->prepare("UPDATE jadwal_ibadah SET is_active = ? WHERE id = ?");
$stmt_update->bind_param("ii", $new_status, $id);

if ($stmt_update->execute()) {
    $stmt_update->close();
    $status_text = ($new_status == 1) ? 'aktif' : 'non-aktif';
    header("Location: index.php?success=Status jadwal ibadah berhasil diubah menjadi {$status_text}");
    exit;
} else {
    $stmt_update->close();
    header("Location: index.php?error=Gagal mengubah status jadwal ibadah");
    exit;
}



