<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

$admin_page_title = 'Edit Renungan';
$uploadDir = __DIR__ . '/../uploads/renungan/';
$allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
$maxSize = 4 * 1024 * 1024;

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: renungan.php');
    exit;
}

$stmtFind = $conn->prepare('SELECT id, judul, isi, ayat, tanggal, gambar FROM renungan WHERE id = ? LIMIT 1');
if (!$stmtFind) {
    die('Query data renungan gagal.');
}

$stmtFind->bind_param('i', $id);
$stmtFind->execute();
$result = $stmtFind->get_result();
$renungan = $result ? $result->fetch_assoc() : null;
$stmtFind->close();

if (!$renungan) {
    header('Location: renungan.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $ayat = trim($_POST['ayat'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? '');
    $oldGambar = $renungan['gambar'] ?? '';
    $newGambar = $oldGambar;

    if ($judul === '' || $ayat === '' || $isi === '' || $tanggal === '') {
        $error = 'Semua field wajib diisi kecuali gambar.';
    }

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
                $newGambar = 'renungan_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetPath = $uploadDir . $newGambar;

                if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $targetPath)) {
                    $error = 'Gagal menyimpan file gambar baru.';
                    $newGambar = $oldGambar;
                }
            }
        }
    }

    if ($error === '') {
        $stmtUpdate = $conn->prepare('UPDATE renungan SET judul = ?, isi = ?, ayat = ?, tanggal = ?, gambar = ? WHERE id = ?');
        if ($stmtUpdate) {
            $stmtUpdate->bind_param('sssssi', $judul, $isi, $ayat, $tanggal, $newGambar, $id);
            if ($stmtUpdate->execute()) {
                $stmtUpdate->close();

                if ($newGambar !== $oldGambar && $oldGambar !== '') {
                    $oldPath = $uploadDir . $oldGambar;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                header('Location: renungan.php?success=updated');
                exit;
            }

            $error = 'Gagal memperbarui data renungan.';
            $stmtUpdate->close();
        } else {
            $error = 'Query update tidak bisa dijalankan.';
        }
    }

    $renungan['judul'] = $judul;
    $renungan['ayat'] = $ayat;
    $renungan['isi'] = $isi;
    $renungan['tanggal'] = $tanggal;
    $renungan['gambar'] = $newGambar;
}

include __DIR__ . '/includes/header.php';
?>

<div class="card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Edit Renungan</h2>
        <a href="renungan.php" class="btn btn-outline-secondary mt-2 mt-sm-0">Kembali</a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="judul">Judul</label>
            <input type="text" id="judul" name="judul" class="form-control" value="<?php echo htmlspecialchars($renungan['judul'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="ayat">Ayat</label>
            <input type="text" id="ayat" name="ayat" class="form-control" value="<?php echo htmlspecialchars($renungan['ayat'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="tanggal">Tanggal</label>
            <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?php echo htmlspecialchars($renungan['tanggal'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="gambar">Gambar (Opsional)</label>
            <input type="file" id="gambar" name="gambar" class="form-control-file" accept=".jpg,.jpeg,.png,.webp">
            <small class="form-text text-muted">Format: JPG/JPEG/PNG/WEBP, maksimal 4MB.</small>

            <?php if (!empty($renungan['gambar'])): ?>
                <div class="mt-2">
                    <img src="../uploads/renungan/<?php echo htmlspecialchars($renungan['gambar']); ?>" alt="Preview" style="width: 160px; border-radius: 10px;">
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="isi">Isi Renungan</label>
            <textarea id="isi" name="isi" class="form-control" rows="8" required><?php echo htmlspecialchars($renungan['isi'] ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Update Renungan
        </button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
