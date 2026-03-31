<?php
require_once __DIR__ . '/../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$target = '../jadwal_ibadah/delete.php';
if ($id > 0) {
    $target .= '?id=' . $id;
}

header('Location: ' . $target);
exit;

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=ID tidak valid");
    exit;
}

$id = (int)$_GET['id'];

// Check if jadwal exists
$stmt_check = $conn->prepare("SELECT id, judul FROM jadwal WHERE id = ?");
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows == 0) {
    $stmt_check->close();
    header("Location: index.php?error=Jadwal tidak ditemukan");
    exit;
}

$jadwal_data = $result_check->fetch_assoc();
$stmt_check->close();

// Delete jadwal
$stmt_delete = $conn->prepare("DELETE FROM jadwal WHERE id = ?");
$stmt_delete->bind_param("i", $id);

if ($stmt_delete->execute()) {
    $stmt_delete->close();
    header("Location: index.php?success=Jadwal '{$jadwal_data['judul']}' berhasil dihapus");
    exit;
} else {
    $stmt_delete->close();
    header("Location: index.php?error=Gagal menghapus jadwal");
    exit;
}



