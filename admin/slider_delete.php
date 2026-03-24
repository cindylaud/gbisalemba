<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Validasi input
if (!isset($_GET['slot'])) {
    $_SESSION['slider_error'] = 'Data tidak valid';
    header('Location: slider.php');
    exit;
}

$slot = intval($_GET['slot']);

// Validasi slot 1-4
if ($slot < 1 || $slot > 4) {
    $_SESSION['slider_error'] = 'Slot harus antara 1-4';
    header('Location: slider.php');
    exit;
}

// Get data slider berdasarkan slot
$stmt = $conn->prepare("SELECT id, image FROM slider WHERE slot = ?");
$stmt->bind_param("i", $slot);
$stmt->execute();
$result = $stmt->get_result();
$slider_data = $result->fetch_assoc();

if (!$slider_data) {
    $_SESSION['slider_error'] = 'Slot tidak ditemukan';
    header('Location: slider.php');
    exit;
}

// Hapus file fisik jika ada
if (!empty($slider_data['image'])) {
    $file_path = __DIR__ . '/../uploads/slider/' . $slider_data['image'];
    if (file_exists($file_path)) {
        @unlink($file_path);
    }
}

// Update database - set image NULL dan is_active 0
$update_stmt = $conn->prepare("UPDATE slider SET image = NULL, is_active = 0 WHERE id = ?");
$update_stmt->bind_param("i", $slider_data['id']);

if ($update_stmt->execute()) {
    $_SESSION['slider_message'] = 'Foto ' . $slot . ' berhasil dihapus';
} else {
    $_SESSION['slider_error'] = 'Gagal menghapus foto dari database';
}

$conn->close();
header('Location: slider.php');
exit;
?>



