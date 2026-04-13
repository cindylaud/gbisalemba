<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

$admin_page_title = 'Tambah Renungan';
$uploadDir = __DIR__ . '/../uploads/renungan/';
$allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
$maxSize = 4 * 1024 * 1024;

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

$error = '';
$judul = '';
$ayat = '';
$isi = '';
$tanggal = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $ayat = trim($_POST['ayat'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? '');

    if ($judul === '' || $ayat === '' || $isi === '' || $tanggal === '') {
        $error = 'Semua field wajib diisi kecuali gambar.';
    }

    $gambarName = null;

    if ($error === '' && isset($_FILES['gambar']) && (int) $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ((int) $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload gambar gagal. Silakan coba lagi.';
        } elseif ((int) $_FILES['gambar']['size'] > $maxSize) {
            $error = 'Ukuran gambar maksimal 4MB.';
        } else {
            $ext = strtolower(pathinfo((string) $_FILES['gambar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                $error = 'Format gambar harus JPG, JPEG, PNG, atau WEBP.';
            } else {
                $gambarName = 'renungan_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetPath = $uploadDir . $gambarName;

                if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $targetPath)) {
                    $error = 'Gagal menyimpan file gambar.';
                }
            }
        }
    }

    if ($error === '') {
        $stmt = $conn->prepare('INSERT INTO renungan (judul, isi, ayat, tanggal, gambar) VALUES (?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('sssss', $judul, $isi, $ayat, $tanggal, $gambarName);
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: renungan.php?success=created');
                exit;
            }

            $error = 'Gagal menyimpan data renungan.';
            $stmt->close();
        } else {
            $error = 'Query penyimpanan tidak bisa dijalankan.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Tambah Renungan</h2>
        <a href="renungan.php" class="btn btn-outline-secondary mt-2 mt-sm-0">Kembali</a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="judul">Judul</label>
            <input type="text" id="judul" name="judul" class="form-control" value="<?php echo htmlspecialchars($judul); ?>" required>
        </div>

        <div class="form-group">
            <label for="ayat">Ayat</label>
            <input type="text" id="ayat" name="ayat" class="form-control" placeholder="Contoh: Yohanes 3:16" value="<?php echo htmlspecialchars($ayat); ?>" required>
        </div>

        <div class="form-group">
            <label for="tanggal">Tanggal</label>
            <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?php echo htmlspecialchars($tanggal); ?>" required>
        </div>

        <div class="form-group">
            <label for="gambar">Gambar (Opsional)</label>
            <input type="file" id="gambar" name="gambar" class="form-control-file" accept=".jpg,.jpeg,.png,.webp">
            <small class="form-text text-muted">Format: JPG/JPEG/PNG/WEBP, maksimal 4MB.</small>
        </div>

        <div class="form-group">
            <label for="isi">Isi Renungan</label>
            <textarea id="isi" name="isi" class="form-control" rows="8" required><?php echo htmlspecialchars($isi); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Simpan Renungan
        </button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
