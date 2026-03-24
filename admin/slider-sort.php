<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['slider_id']) && isset($_POST['urutan'])) {
    $slider_id = intval($_POST['slider_id']);
    $new_urutan = intval($_POST['urutan']);
    
    if ($slider_id > 0 && $new_urutan > 0) {
        $stmt = $conn->prepare("UPDATE slider SET urutan = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_urutan, $slider_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Urutan slider berhasil diubah';
        } else {
            $_SESSION['error'] = 'Gagal mengubah urutan: ' . $conn->error;
        }
    } else {
        $_SESSION['error'] = 'Data tidak valid';
    }
    
    header('Location: slider.php');
    exit;
}

// Jika akses langsung, redirect
header('Location: slider.php');
exit;



