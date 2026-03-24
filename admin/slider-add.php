<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $is_active = intval($_POST['is_active'] ?? 1);
    $urutan = intval($_POST['urutan'] ?? 1);
    
    // Validasi
    if (empty($title)) {
        $error = 'Title tidak boleh kosong';
    } elseif (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] != 0) {
        $error = 'Silakan upload gambar';
    } else {
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
            
            // Cek folder exist
            if (!is_dir(__DIR__ . '/../uploads/slider/')) {
                mkdir(__DIR__ . '/../uploads/slider/', 0755, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Cek batas 5 slider aktif
                if ($is_active == 1) {
                    $count_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM slider WHERE is_active = 1");
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result();
                    $count_row = $count_result->fetch_assoc();
                    
                    if ($count_row['cnt'] >= 5) {
                        unlink($upload_path); // Hapus file yang sudah di-upload
                        $error = 'Sudah ada 5 slider aktif. Nonaktifkan salah satu terlebih dahulu.';
                    }
                }
                
                if (empty($error)) {
                    // Insert ke database
                    $stmt = $conn->prepare("INSERT INTO slider (title, subtitle, gambar, is_active, urutan) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssiii", $title, $subtitle, $unique_name, $is_active, $urutan);
                    
                    if ($stmt->execute()) {
                        $_SESSION['message'] = 'Slider berhasil ditambahkan';
                        header('Location: slider.php');
                        exit;
                    } else {
                        unlink($upload_path); // Hapus file jika insert gagal
                        $error = 'Gagal menyimpan data: ' . $conn->error;
                    }
                }
            } else {
                $error = 'Gagal upload gambar';
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Tambah Slider</h1>
        <a href="slider.php" class="btn-secondary">Kembali</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form">
        <div class="form-group">
            <label>Gambar (jpg, jpeg, png, webp - max 2MB)</label>
            <input type="file" name="gambar" required accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" required maxlength="100" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Subtitle</label>
            <textarea name="subtitle" maxlength="255" rows="3"><?php echo htmlspecialchars($_POST['subtitle'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>Urutan</label>
            <input type="number" name="urutan" value="<?php echo htmlspecialchars($_POST['urutan'] ?? '1'); ?>" min="1">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo (isset($_POST['is_active']) && $_POST['is_active'] == 1) ? 'checked' : 'checked'; ?>>
                Aktif
            </label>
        </div>

        <button type="submit" class="btn-primary">Simpan</button>
        <a href="slider.php" class="btn-secondary">Batal</a>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


