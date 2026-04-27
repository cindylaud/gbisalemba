<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/renungan-richtext.php';
require_once __DIR__ . '/../includes/image-helper.php';

$admin_page_title = 'Tambah Renungan';
$uploadDir = __DIR__ . '/../uploads/renungan/';
$allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
$maxSize = 8 * 1024 * 1024;
$serverLimit = getServerUploadLimit();
$effectiveMaxSize = $maxSize;
if ($serverLimit > 0 && $serverLimit < $effectiveMaxSize) {
    $effectiveMaxSize = $serverLimit;
}
$effectiveMaxMb = max(1, (int) floor($effectiveMaxSize / 1024 / 1024));

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

$error = '';
$judul = '';
$ayat = '';
$isi = '';
$tanggal = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    if ($serverLimit > 0 && $contentLength > $serverLimit) {
        $error = 'Upload gagal: ukuran request melebihi batas server (' . $effectiveMaxMb . 'MB). Kecilkan ukuran gambar lalu coba lagi.';
    }

    $judul = trim($_POST['judul'] ?? '');
    $ayat = trim($_POST['ayat'] ?? '');
    $isi = gbi_sanitize_renungan_html($_POST['isi'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? '');

    if ($error === '' && ($judul === '' || $isi === '' || $tanggal === '')) {
        $error = 'Judul, isi, dan tanggal wajib diisi. Ayat boleh dikosongkan.';
    }

    $gambarName = null;

    if ($error === '' && isset($_FILES['gambar']) && (int) $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ((int) $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload gambar gagal. Silakan coba lagi.';
        } else {
            $ext = strtolower(pathinfo((string) $_FILES['gambar']['name'], PATHINFO_EXTENSION));
            $validation = validateImageUpload($_FILES['gambar'], $effectiveMaxSize);
            if (!in_array($ext, $allowedExt, true)) {
                $error = 'Format gambar harus JPG, JPEG, PNG, atau WEBP.';
            } elseif (!$validation['valid']) {
                $error = (string) ($validation['error'] ?? 'Upload gambar gagal.');
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
        $stmt = $conn->prepare('INSERT INTO renungan (judul, isi, ayat, tanggal, gambar) VALUES (?, ?, NULLIF(?, \'\'), ?, ?)');
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
            <label for="ayat">Ayat (Opsional)</label>
            <input type="text" id="ayat" name="ayat" class="form-control" placeholder="Contoh: Yohanes 3:16" value="<?php echo htmlspecialchars($ayat); ?>">
        </div>

        <div class="form-group">
            <label for="tanggal">Tanggal</label>
            <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?php echo htmlspecialchars($tanggal); ?>" required>
        </div>

        <div class="form-group">
            <label for="gambar">Gambar (Opsional)</label>
            <input type="file" id="gambar" name="gambar" class="form-control-file" accept=".jpg,.jpeg,.png,.webp">
            <small class="form-text text-muted">Format: JPG/JPEG/PNG/WEBP. Max upload: <?php echo (int) $effectiveMaxMb; ?>MB.</small>
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
                <div id="isiEditor" class="renungan-editor" contenteditable="true" data-placeholder="Tulis isi renungan di sini..."><?php echo gbi_editor_initial_html($isi); ?></div>
            </div>
            <textarea id="isi" name="isi" class="d-none"><?php echo htmlspecialchars($isi); ?></textarea>
            <small class="form-text text-muted">Anda bisa gunakan bold, italic, underline, heading, quote, dan list.</small>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Simpan Renungan
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
