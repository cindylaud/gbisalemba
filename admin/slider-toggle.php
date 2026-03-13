<?php
require_once '../config/database.php';
require_once 'includes/auth.php';

$slider_id = intval($_GET['id'] ?? 0);

if ($slider_id == 0) {
    $_SESSION['error'] = 'ID slider tidak valid';
    header('Location: /gbisalemba/admin/slider.php');
    exit;
}

// Ambil status slider
$stmt = $conn->prepare("SELECT is_active FROM slider WHERE id = ?");
$stmt->bind_param("i", $slider_id);
$stmt->execute();
$result = $stmt->get_result();
$slider = $result->fetch_assoc();

if (!$slider) {
    $_SESSION['error'] = 'Slider tidak ditemukan';
    header('Location: /gbisalemba/admin/slider.php');
    exit;
}

// Toggle status
$new_status = $slider['is_active'] == 1 ? 0 : 1;

// Cek batas 5 slider aktif jika ingin mengaktifkan
if ($new_status == 1) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM slider WHERE is_active = 1");
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    
    if ($count_row['cnt'] >= 5) {
        $_SESSION['error'] = 'Maksimal 5 slider aktif. Nonaktifkan salah satu terlebih dahulu.';
        header('Location: /gbisalemba/admin/slider.php');
        exit;
    }
}

// Update status
$stmt = $conn->prepare("UPDATE slider SET is_active = ? WHERE id = ?");
$stmt->bind_param("ii", $new_status, $slider_id);

if ($stmt->execute()) {
    $_SESSION['message'] = 'Status slider berhasil diubah';
} else {
    $_SESSION['error'] = 'Gagal mengubah status: ' . $conn->error;
}

header('Location: /gbisalemba/admin/slider.php');
exit;
