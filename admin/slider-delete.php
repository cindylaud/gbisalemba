<?php
require_once '../config/database.php';
require_once 'includes/auth.php';

$slider_id = intval($_GET['id'] ?? 0);

if ($slider_id == 0) {
    $_SESSION['error'] = 'ID slider tidak valid';
    header('Location: /gbisalemba/admin/slider.php');
    exit;
}

// Ambil data slider
$stmt = $conn->prepare("SELECT gambar FROM slider WHERE id = ?");
$stmt->bind_param("i", $slider_id);
$stmt->execute();
$result = $stmt->get_result();
$slider = $result->fetch_assoc();

if (!$slider) {
    $_SESSION['error'] = 'Slider tidak ditemukan';
    header('Location: /gbisalemba/admin/slider.php');
    exit;
}

// Hapus file fisik
$file_path = __DIR__ . '/../uploads/slider/' . $slider['gambar'];
if (file_exists($file_path)) {
    unlink($file_path);
}

// Hapus dari database
$stmt = $conn->prepare("DELETE FROM slider WHERE id = ?");
$stmt->bind_param("i", $slider_id);

if ($stmt->execute()) {
    $_SESSION['message'] = 'Slider berhasil dihapus';
} else {
    $_SESSION['error'] = 'Gagal menghapus slider: ' . $conn->error;
}

header('Location: /gbisalemba/admin/slider.php');
exit;
