<?php
require_once '../config/database.php';
require_once 'includes/auth.php';

$slider_id = intval($_GET['id'] ?? 0);

if ($slider_id == 0) {
    header('Location: /gbisalemba/admin/slider.php');
    exit;
}

// Ambil data slider
$stmt = $conn->prepare("SELECT id, title, subtitle, gambar, is_active, urutan FROM slider WHERE id = ?");
$stmt->bind_param("i", $slider_id);
$stmt->execute();
$result = $stmt->get_result();
$slider = $result->fetch_assoc();

if (!$slider) {
    header('Location: /gbisalemba/admin/slider.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $is_active = intval($_POST['is_active'] ?? 0);
    $urutan = intval($_POST['urutan'] ?? 1);
    $new_gambar = $slider['gambar'];
    
    // Validasi
    if (empty($title)) {
        $error = 'Title tidak boleh kosong';
    } else {
        // Cek apakah ada upload gambar baru
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $file = $_FILES['gambar'];
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed_ext)) {
                $error = 'Format file tidak diizinkan. Gunakan: jpg, jpeg, png, webp';
            } elseif ($file['size'] > $max_size) {
                $error = 'Ukuran file terlalu besar (max 2MB)';
            } else {
                // Generate nama file unik
                $unique_name = 'slider_' . date('YmdHis') . '_' . rand(10000, 99999) . '.' . $ext;
                $upload_path = __DIR__ . '/../uploads/slider/' . $unique_name;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Hapus file lama
                    $old_file_path = __DIR__ . '/../uploads/slider/' . $slider['gambar'];
                    if (file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                    $new_gambar = $unique_name;
                } else {
                    $error = 'Gagal upload gambar';
                }
            }
        }
        
        if (empty($error)) {
            // Cek batas 5 slider aktif jika mengubah status menjadi aktif
            if ($is_active == 1 && $slider['is_active'] == 0) {
                $count_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM slider WHERE is_active = 1");
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                $count_row = $count_result->fetch_assoc();
                
                if ($count_row['cnt'] >= 5) {
                    $error = 'Sudah ada 5 slider aktif. Nonaktifkan salah satu terlebih dahulu.';
                }
            }
            
            if (empty($error)) {
                // Update database
                $stmt = $conn->prepare("UPDATE slider SET title = ?, subtitle = ?, gambar = ?, is_active = ?, urutan = ? WHERE id = ?");
                $stmt->bind_param("sssiii", $title, $subtitle, $new_gambar, $is_active, $urutan, $slider_id);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Slider berhasil diperbarui';
                    header('Location: /gbisalemba/admin/slider.php');
                    exit;
                } else {
                    $error = 'Gagal menyimpan data: ' . $conn->error;
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Edit Slider</h1>
        <a href="/gbisalemba/admin/slider.php" class="btn-secondary">Kembali</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form">
        <div class="form-group">
            <label>Gambar Sekarang</label>
            <p>
                <img src="/gbisalemba/uploads/slider/<?php echo htmlspecialchars($slider['gambar']); ?>" 
                     alt="<?php echo htmlspecialchars($slider['title']); ?>" 
                     style="max-width: 200px; height: auto;">
            </p>
            <label>Upload Gambar Baru (opsional)</label>
            <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" required maxlength="100" value="<?php echo htmlspecialchars($slider['title']); ?>">
        </div>

        <div class="form-group">
            <label>Subtitle</label>
            <textarea name="subtitle" maxlength="255" rows="3"><?php echo htmlspecialchars($slider['subtitle']); ?></textarea>
        </div>

        <div class="form-group">
            <label>Urutan</label>
            <input type="number" name="urutan" value="<?php echo htmlspecialchars($slider['urutan']); ?>" min="1">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo $slider['is_active'] == 1 ? 'checked' : ''; ?>>
                Aktif
            </label>
        </div>

        <button type="submit" class="btn-primary">Update</button>
        <a href="/gbisalemba/admin/slider.php" class="btn-secondary">Batal</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
