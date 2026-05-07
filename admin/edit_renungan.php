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
            $error = 'Ukuran gambar maksimal 8MB.';
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

<style>
    .add-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 14px;
        border-radius: 12px;
        border: 1px solid rgba(16, 44, 87, 0.14);
        background: #f3f8fc;
        color: #1f3f6f;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .btn-back:hover {
        text-decoration: none;
        color: #1f3f6f;
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(16, 44, 87, 0.14);
    }

    .admin-alert {
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid transparent;
    }

    .admin-alert.error {
        background: #fde8e8;
        color: #8d2d2d;
        border-color: #f8c7c7;
    }

    .add-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
        align-items: start;
    }

    .panel {
        background: #ffffff;
        border: 1px solid rgba(16, 44, 87, 0.08);
        border-radius: 18px;
        box-shadow: 0 8px 18px rgba(15, 39, 66, 0.06);
    }

    .panel-form { padding: 20px; }

    .panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(16, 44, 87, 0.1);
    }

    .panel-title {
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: 9px;
        font-size: 22px;
        color: #102c57;
        font-weight: 800;
    }

    .panel-title i { color: #146c94; }

    .form-grid { display: grid; gap: 14px; }

    .form-section {
        display: grid;
        gap: 12px;
        padding-bottom: 14px;
        border-bottom: 1px solid rgba(16, 44, 87, 0.06);
    }

    .form-section:last-of-type {
        padding-bottom: 0;
        border-bottom: none;
    }

    .form-section-title {
        margin: 0 0 2px 0;
        font-size: 12px;
        font-weight: 700;
        color: #7a8fa7;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .row-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .field { display: grid; gap: 5px; }

    .field label {
        margin: 0;
        font-size: 13px;
        font-weight: 700;
        color: #234267;
    }

    .req { color: #dc3545; }

    .input, .select, .textarea {
        width: 100%;
        border: 1px solid rgba(16, 44, 87, 0.14);
        border-radius: 11px;
        padding: 11px 13px;
        background: #ffffff;
        color: #344054;
        font-size: 13px;
        font-family: inherit;
        transition: border-color 0.25s ease, box-shadow 0.25s ease, background-color 0.25s ease;
    }

    .textarea { min-height: 88px; resize: vertical; }

    .input:focus, .select:focus, .textarea:focus {
        outline: none;
        border-color: rgba(20, 108, 148, 0.7);
        box-shadow: 0 0 0 3px rgba(20, 108, 148, 0.08);
        background-color: #ffffff;
    }

    .hint {
        margin: 0;
        color: #6b7c93;
        font-size: 12px;
        line-height: 1.45;
    }

    .image-preview { margin-top: 10px; display: inline-block; }

    .image-preview img {
        width: 160px;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid rgba(16, 44, 87, 0.16);
        background: #eef4f8;
    }

    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        border-top: 1px solid rgba(16, 44, 87, 0.1);
        padding-top: 14px;
        margin-top: 2px;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 11px;
        padding: 11px 16px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-action:hover { text-decoration: none; transform: translateY(-1px); }

    .btn-cancel {
        background: #f5f8fb;
        border-color: rgba(16, 44, 87, 0.12);
        color: #2d4e77;
    }

    .btn-cancel:hover { 
        color: #2d4e77; 
        background: #eef3f9;
        box-shadow: 0 6px 12px rgba(16, 44, 87, 0.08);
    }

    .btn-save {
        background: #146c94;
        border-color: #0f4568;
        color: #fff;
        box-shadow: 0 6px 14px rgba(20, 108, 148, 0.2);
    }

    .btn-save:hover { 
        color: #fff; 
        background: #127a9d;
        box-shadow: 0 8px 18px rgba(20, 108, 148, 0.25);
    }

    .renungan-editor-wrap {
        border: 1px solid rgba(16, 44, 87, 0.14);
        border-radius: 11px;
        background: #ffffff;
        overflow: hidden;
    }

    .renungan-editor-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 10px;
        background: #f5f8fb;
        border-bottom: 1px solid rgba(16, 44, 87, 0.1);
    }

    .renungan-editor-toolbar button {
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid rgba(16, 44, 87, 0.14);
        background: #ffffff;
        color: #234267;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .renungan-editor-toolbar button:hover {
        background: #eef2f6;
    }

    .renungan-editor {
        min-height: 200px;
        padding: 12px;
        color: #344054;
        font-size: 13px;
        line-height: 1.6;
    }

    .renungan-editor:focus {
        outline: none;
    }

    @media (max-width: 640px) {
        .form-actions { flex-direction: column-reverse; align-items: stretch; }
        .btn-action { width: 100%; }
        .row-grid { grid-template-columns: 1fr; }
    }
</style>

<div style="display: grid; gap: 12px;">
    <?php if ($error !== ''): ?>
        <div class="admin-alert error" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="add-layout">
        <div class="panel panel-form">
            <div class="panel-head">
                <h2 class="panel-title"><i class="fas fa-pen-to-square"></i> Edit Renungan</h2>
                <div>
                    <a href="renungan.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data" class="form-grid">

                <!-- SECTION: INFORMASI DASAR -->
                <div class="form-section">
                    <h3 class="form-section-title">Informasi Dasar</h3>
                    
                    <div class="field">
                        <label for="judul">Judul <span class="req">*</span></label>
                        <input type="text" class="input" id="judul" name="judul"
                               value="<?php echo htmlspecialchars($renungan['judul'] ?? ''); ?>"
                               required>
                    </div>

                    <div class="row-grid">
                        <div class="field">
                            <label for="ayat">Ayat (Opsional)</label>
                            <input type="text" class="input" id="ayat" name="ayat"
                                   value="<?php echo htmlspecialchars($renungan['ayat'] ?? ''); ?>"
                                   placeholder="Contoh: Yohanes 3:16">
                        </div>

                        <div class="field">
                            <label for="tanggal">Tanggal <span class="req">*</span></label>
                            <input type="date" class="input" id="tanggal" name="tanggal"
                                   value="<?php echo htmlspecialchars($renungan['tanggal'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>
                </div>

                <!-- SECTION: GAMBAR -->
                <div class="form-section">
                    <h3 class="form-section-title">Gambar</h3>
                    
                    <div class="field">
                        <label for="gambar">Gambar (Opsional)</label>
                        <input type="file" class="input" id="gambar" name="gambar" accept=".jpg,.jpeg,.png,.webp">
                        <p class="hint">Format: JPG/JPEG/PNG/WEBP, maksimal 8MB.</p>

                        <?php if (!empty($renungan['gambar'])): ?>
                            <div class="image-preview">
                                <img src="../uploads/renungan/<?php echo htmlspecialchars($renungan['gambar']); ?>" alt="Preview">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- SECTION: ISI RENUNGAN -->
                <div class="form-section">
                    <h3 class="form-section-title">Isi Renungan</h3>
                    
                    <div class="field">
                        <label for="isi">Isi Renungan <span class="req">*</span></label>
                        <div class="renungan-editor-wrap" data-renungan-editor>
                            <div class="renungan-editor-toolbar" role="toolbar" aria-label="Format isi renungan">
                                <button type="button" data-cmd="bold" title="Bold"><strong>B</strong></button>
                                <button type="button" data-cmd="italic" title="Italic"><em>I</em></button>
                                <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                                <button type="button" data-cmd="insertUnorderedList" title="Bullet List">• List</button>
                                <button type="button" data-cmd="insertOrderedList" title="Number List">1. List</button>
                                <button type="button" data-block="blockquote" title="Quote">Quote</button>
                                <button type="button" data-block="h3" title="Heading">H3</button>
                                <button type="button" data-cmd="removeFormat" title="Clear">Clear</button>
                            </div>
                            <div id="isiEditor" class="renungan-editor" contenteditable="true"><?php echo gbi_editor_initial_html($renungan['isi'] ?? ''); ?></div>
                        </div>
                        <textarea id="isi" name="isi" class="d-none"><?php echo htmlspecialchars($renungan['isi'] ?? ''); ?></textarea>
                        <p class="hint" style="margin-top: 8px;">Gunakan bold, italic, underline, heading, quote, dan list untuk format.</p>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="renungan.php" class="btn-action btn-cancel">
                        <i class="fas fa-xmark"></i> Batal
                    </a>
                    <button type="submit" class="btn-action btn-save">
                        <i class="fas fa-floppy-disk"></i> Update Renungan
                    </button>
                </div>
            </form>
        </div>
    </div>
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
