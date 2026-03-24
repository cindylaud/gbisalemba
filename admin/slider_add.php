<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: slider.php');
    exit;
}

$action = $_POST['action'] ?? 'add';
$title = trim($_POST['title'] ?? '');
$subtitle = trim($_POST['subtitle'] ?? '');
$is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
$urutan = intval($_POST['urutan'] ?? 1);
$slider_id = intval($_POST['id'] ?? 0);

$error = '';

// Validasi
if (empty($title)) {
    $error = 'Title tidak boleh kosong';
} elseif ($action == 'add' && (!isset($_FILES['image']) || $_FILES['image']['error'] != 0)) {
    $error = 'Silakan upload gambar';
}

if (!$error && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $file = $_FILES['image'];
    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed_ext)) {
        $error = 'Format file tidak diizinkan. Gunakan: jpg, jpeg, png, webp';
    } elseif ($file['size'] > $max_size) {
        $error = 'Ukuran file terlalu besar (max 2MB)';
    }
}

if (!$error) {
    // Prepare folder
    if (!is_dir(__DIR__ . '/../uploads/slider/')) {
        mkdir(__DIR__ . '/../uploads/slider/', 0755, true);
    }
    
    $new_image_name = null;
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_image_name = 'slider_' . date('YmdHis') . '_' . rand(10000, 99999) . '.' . $ext;
        $upload_path = __DIR__ . '/../uploads/slider/' . $new_image_name;
        
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            $error = 'Gagal upload gambar';
        }
    }
    
    if (!$error) {
        if ($action == 'add') {
            // Check limit 5 active sliders
            if ($is_active == 1) {
                $count_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM slider WHERE is_active = 1");
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                $count_row = $count_result->fetch_assoc();
                
                if ($count_row['cnt'] >= 5) {
                    $error = 'Maksimal 5 slider aktif. Nonaktifkan salah satu terlebih dahulu.';
                } else {
                    $stmt = $conn->prepare("INSERT INTO slider (title, subtitle, image, urutan, is_active) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssii", $title, $subtitle, $new_image_name, $urutan, $is_active);
                    
                    if ($stmt->execute()) {
                        $_SESSION['message'] = 'Slider berhasil ditambahkan';
                        header('Location: slider.php');
                        exit;
                    } else {
                        $error = 'Gagal menyimpan data: ' . $conn->error;
                        if ($new_image_name) @unlink(__DIR__ . '/../uploads/slider/' . $new_image_name);
                    }
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO slider (title, subtitle, image, urutan, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssii", $title, $subtitle, $new_image_name, $urutan, $is_active);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Slider berhasil ditambahkan';
                    header('Location: slider.php');
                    exit;
                } else {
                    $error = 'Gagal menyimpan data: ' . $conn->error;
                    if ($new_image_name) @unlink(__DIR__ . '/../uploads/slider/' . $new_image_name);
                }
            }
        } elseif ($action == 'edit' && $slider_id > 0) {
            // Get current data
            $stmt = $conn->prepare("SELECT image, is_active FROM slider WHERE id = ?");
            $stmt->bind_param("i", $slider_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $current = $result->fetch_assoc();
            
            if ($current) {
                $image_to_use = $new_image_name ? $new_image_name : $current['image'];
                
                // Check limit 5 active if changing status to 1
                if ($is_active == 1 && $current['is_active'] == 0) {
                    $count_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM slider WHERE is_active = 1");
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result();
                    $count_row = $count_result->fetch_assoc();
                    
                    if ($count_row['cnt'] >= 5) {
                        $error = 'Maksimal 5 slider aktif. Nonaktifkan salah satu terlebih dahulu.';
                        if ($new_image_name) @unlink(__DIR__ . '/../uploads/slider/' . $new_image_name);
                    }
                }
                
                if (!$error) {
                    $stmt = $conn->prepare("UPDATE slider SET title = ?, subtitle = ?, image = ?, urutan = ?, is_active = ? WHERE id = ?");
                    $stmt->bind_param("sssiii", $title, $subtitle, $image_to_use, $urutan, $is_active, $slider_id);
                    
                    if ($stmt->execute()) {
                        // Delete old image if new image uploaded
                        if ($new_image_name && $current['image'] != $new_image_name) {
                            $old_path = __DIR__ . '/../uploads/slider/' . $current['image'];
                            if (file_exists($old_path)) {
                                @unlink($old_path);
                            }
                        }
                        
                        $_SESSION['message'] = 'Slider berhasil diperbarui';
                        header('Location: slider.php');
                        exit;
                    } else {
                        $error = 'Gagal menyimpan data: ' . $conn->error;
                        if ($new_image_name) @unlink(__DIR__ . '/../uploads/slider/' . $new_image_name);
                    }
                }
            }
        }
    }
}

// If error, redirect back with error message
if ($error) {
    $_SESSION['error'] = $error;
    header('Location: slider.php' . ($action == 'edit' && $slider_id > 0 ? '?edit_id=' . $slider_id : ''));
    exit;
}

// Fallback
header('Location: slider.php');
exit;



