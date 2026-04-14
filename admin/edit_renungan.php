<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/renungan-richtext.php';

$admin_page_title = 'Edit Renungan';
$uploadDir = __DIR__ . '/../uploads/renungan/';
$allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
$maxSize = 25 * 1024 * 1024;

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
    $isi = gbi_sanitize_renungan_html($_POST['isi'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? '');
    $oldGambar = $renungan['gambar'] ?? '';
    $newGambar = $oldGambar;

    if ($judul === '' || $isi === '' || $tanggal === '') {
        $error = 'Judul, isi, dan tanggal wajib diisi. Ayat boleh dikosongkan.';
    }

    if ($error === '' && isset($_FILES['gambar']) && (int) $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ((int) $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload gambar gagal. Silakan coba lagi.';
        } elseif ((int) $_FILES['gambar']['size'] > $maxSize) {
            $error = 'Ukuran gambar maksimal 25MB.';
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
        $stmtUpdate = $conn->prepare('UPDATE renungan SET judul = ?, isi = ?, ayat = NULLIF(?, \'\'), tanggal = ?, gambar = ? WHERE id = ?');
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
            <label for="ayat">Ayat (Opsional)</label>
            <input type="text" id="ayat" name="ayat" class="form-control" value="<?php echo htmlspecialchars($renungan['ayat'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="tanggal">Tanggal</label>
            <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?php echo htmlspecialchars($renungan['tanggal'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="gambar">Gambar (Opsional)</label>
            <input type="file" id="gambar" name="gambar" class="form-control-file" accept=".jpg,.jpeg,.png,.webp">
            <small class="form-text text-muted">Format: JPG/JPEG/PNG/WEBP, maksimal 25MB.</small>

            <?php if (!empty($renungan['gambar'])): ?>
                <div class="mt-2">
                    <img src="../uploads/renungan/<?php echo htmlspecialchars($renungan['gambar']); ?>" alt="Preview" style="width: 160px; border-radius: 10px;">
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="isi">Isi Renungan</label>
            <div class="renungan-editor-wrap" data-renungan-editor>
                <div class="renungan-editor-toolbar" role="toolbar" aria-label="Format isi renungan">
                    <button type="button" class="btn btn-light btn-sm" data-cmd="bold" title="Bold"><strong>B</strong></button>
                    <button type="button" class="btn btn-light btn-sm" data-cmd="italic" title="Italic"><em>I</em></button>
                    <button type="button" class="btn btn-light btn-sm" data-cmd="underline" title="Underline"><u>U</u></button>
                    <button type="button" class="btn btn-light btn-sm" data-cmd="insertUnorderedList" title="Bullet List">• List</button>
                    <button type="button" class="btn btn-light btn-sm" data-cmd="insertOrderedList" title="Number List">1. List</button>
                    <button type="button" class="btn btn-light btn-sm" data-block="blockquote" title="Kutipan">Quote</button>
                    <button type="button" class="btn btn-light btn-sm" data-block="h3" title="Heading">H3</button>
                    <button type="button" class="btn btn-light btn-sm" data-cmd="removeFormat" title="Hapus Format">Clear</button>
                </div>
                <div id="isiEditor" class="renungan-editor" contenteditable="true" data-placeholder="Tulis isi renungan di sini..."><?php echo gbi_editor_initial_html($renungan['isi'] ?? ''); ?></div>
            </div>
            <textarea id="isi" name="isi" class="d-none"><?php echo htmlspecialchars($renungan['isi'] ?? ''); ?></textarea>
            <small class="form-text text-muted">Anda bisa gunakan bold, italic, underline, heading, quote, dan list.</small>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Update Renungan
        </button>
    </form>
</div>

<script>
(function () {
    var editorWrap = document.querySelector('[data-renungan-editor]');
    if (!editorWrap) {
        return;
    }

    var form = editorWrap.closest('form');
    var editor = document.getElementById('isiEditor');
    var textarea = document.getElementById('isi');

    if (!form || !editor || !textarea) {
        return;
    }

    var syncContent = function () {
        textarea.value = editor.innerHTML.trim();
    };

    editor.addEventListener('input', syncContent);
    syncContent();

    editorWrap.querySelectorAll('[data-cmd]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.execCommand(button.getAttribute('data-cmd'), false, null);
            syncContent();
            editor.focus();
        });
    });

    editorWrap.querySelectorAll('[data-block]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.execCommand('formatBlock', false, button.getAttribute('data-block'));
            syncContent();
            editor.focus();
        });
    });

    form.addEventListener('submit', function () {
        syncContent();
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
