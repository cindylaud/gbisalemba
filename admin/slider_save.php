<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Validasi input
if (!isset($_POST['slot']) || !isset($_POST['id'])) {
    $_SESSION['slider_error'] = 'Data tidak valid';
    header('Location: slider.php');
    exit;
}

$slot = intval($_POST['slot']);
$id = intval($_POST['id']);
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Validasi slot 1-4
if ($slot < 1 || $slot > 4) {
    $_SESSION['slider_error'] = 'Slot harus antara 1-4';
    header('Location: slider.php');
    exit;
}

// Cek apakah ada file upload
$has_upload = isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK;

if ($has_upload) {
    $file = $_FILES['image'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    
    // Validasi ekstensi
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_extensions)) {
        $_SESSION['slider_error'] = 'Format file tidak valid. Gunakan: jpg, jpeg, png, webp';
        header('Location: slider.php');
        exit;
    }
    
    // Validasi ukuran max 2MB
    if ($file_size > 2 * 1024 * 1024) {
        $_SESSION['slider_error'] = 'Ukuran file maksimal 2MB';
        header('Location: slider.php');
        exit;
    }
    
    // Generate unique filename
    $unique_name = 'slider_' . $slot . '_' . date('YmdHis') . '_' . rand(1000, 9999) . '.' . $file_ext;
    $upload_dir = __DIR__ . '/../uploads/slider/';
    
    // Pastikan folder exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $destination = $upload_dir . $unique_name;
    
    // Get old image untuk dihapus
    $stmt = $conn->prepare("SELECT image FROM slider WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $old_data = $result->fetch_assoc();
    
    // Upload file baru
    if (move_uploaded_file($file_tmp, $destination)) {
        // Hapus file lama jika ada
        if ($old_data && !empty($old_data['image'])) {
            $old_file = $upload_dir . $old_data['image'];
            if (file_exists($old_file)) {
                @unlink($old_file);
            }
        }
        
        // Update database dengan gambar baru
        $update_stmt = $conn->prepare("UPDATE slider SET image = ?, is_active = ? WHERE id = ?");
        $update_stmt->bind_param("sii", $unique_name, $is_active, $id);
        
        if ($update_stmt->execute()) {
            $_SESSION['slider_message'] = 'Foto ' . $slot . ' berhasil diupload dan disimpan';
        } else {
            $_SESSION['slider_error'] = 'Gagal menyimpan ke database';
        }
    } else {
        $_SESSION['slider_error'] = 'Gagal upload file';
    }
} else {
    // Tidak ada upload, hanya update status is_active
    $update_stmt = $conn->prepare("UPDATE slider SET is_active = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $is_active, $id);
    
    if ($update_stmt->execute()) {
        $_SESSION['slider_message'] = 'Status Foto ' . $slot . ' berhasil diubah';
    } else {
        $_SESSION['slider_error'] = 'Gagal mengubah status';
    }
}

$conn->close();
header('Location: slider.php');
exit;
?>



