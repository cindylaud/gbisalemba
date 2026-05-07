<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_table_bootstrap.php';
require_once __DIR__ . '/../../includes/image-helper.php';

define('JADWAL_UPLOAD_DIR', __DIR__ . '/../../uploads/jadwal/');
define('JADWAL_MAX_SIZE', 30 * 1024 * 1024);
define('JADWAL_MAX_WIDTH', 1920);
define('JADWAL_QUALITY', 80);

if (!is_dir(JADWAL_UPLOAD_DIR)) {
    @mkdir(JADWAL_UPLOAD_DIR, 0755, true);
}

$table_state = ensureJadwalIbadahTable($conn);
$has_kategori_column = (bool) ($table_state['has_kategori_column'] ?? false);
$has_image_columns = (bool) ($table_state['has_image_columns'] ?? false);

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nama_ibadah = trim($_POST['nama_ibadah'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $hari = trim($_POST['hari'] ?? '');
    $ruangan = trim($_POST['ruangan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // JAM MULTI INPUT
    $jam_array = $_POST['jam'] ?? [];
    $jam_array = array_filter(array_map('trim', $jam_array));
    $jam = implode(', ', $jam_array);

    $image_filename = '';

    if (empty($nama_ibadah) || empty($hari) || empty($jam)) {
        $error = "Nama ibadah, hari, dan jam wajib diisi";
    }

    // IMAGE UPLOAD
    if (empty($error) && $has_image_columns && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

        $validation = validateImageUpload($_FILES['image'], JADWAL_MAX_SIZE);

        if (!$validation['valid']) {
            $error = $validation['error'];
        } else {

            $image_filename = generateUniqueFilename($_FILES['image']['name'], 'jadwal_');
            $upload_path = JADWAL_UPLOAD_DIR . $image_filename;

            $result = optimizeAndSaveImage(
                $_FILES['image']['tmp_name'],
                $upload_path,
                JADWAL_MAX_WIDTH,
                JADWAL_QUALITY
            );

            if (!$result['success']) {
                $error = "Gagal upload gambar";
            }
        }
    }

    // INSERT DATABASE
    if (empty($error)) {

        $fields = ['nama_ibadah', 'hari', 'jam', 'ruangan', 'keterangan', 'instagram', 'is_active'];
        $values = [$nama_ibadah, $hari, $jam, $ruangan, $keterangan, $instagram, $is_active];
        $types = 'ssssssi';

        if ($has_kategori_column) {
            $fields[] = 'kategori';
            $values[] = $kategori;
            $types .= 's';
        }

        if ($has_image_columns) {
            $fields[] = 'image';
            $values[] = $image_filename;
            $types .= 's';
        }

        $sql = "INSERT INTO jadwal_ibadah (" . implode(',', $fields) . ")
                VALUES (" . implode(',', array_fill(0, count($fields), '?')) . ")";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            header("Location: index.php?success=Jadwal berhasil ditambahkan");
            exit;
        } else {
            $error = "Gagal menyimpan data";
        }

        $stmt->close();
    }
}

include __DIR__ . '/../includes/header.php';
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

    .panel-head-actions {
        display: inline-flex;
        align-items: center;
        gap: 8px;
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

    .time-stack { display: grid; gap: 8px; }

    .time-row { display: flex; align-items: center; gap: 8px; }
    .time-row .input { flex: 1; }

    .time-btn {
        width: 34px;
        height: 34px;
        border: 1px solid rgba(16, 44, 87, 0.18);
        border-radius: 10px;
        background: #f4f8fc;
        color: #234267;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .time-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 12px rgba(16, 44, 87, 0.12);
    }

    .time-btn.remove {
        color: #8d2d2d;
        border-color: rgba(201, 96, 96, 0.35);
        background: #fff3f3;
    }

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

    .upload-wrap {
        border: 1.5px dashed rgba(20, 108, 148, 0.25);
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(240, 247, 252, 0.8) 0%, rgba(255, 255, 255, 0.95) 100%);
        padding: 14px;
        transition: border-color 0.25s ease, background-color 0.25s ease;
    }

    .upload-wrap:hover {
        border-color: rgba(20, 108, 148, 0.4);
        background: linear-gradient(135deg, rgba(230, 242, 250, 1) 0%, rgba(255, 255, 255, 1) 100%);
    }

    .upload-input { display: block; width: 100%; font-size: 12px; color: #405f7b; }

    .image-preview { margin-top: 10px; display: inline-block; }

    .image-preview img {
        width: 160px;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid rgba(16, 44, 87, 0.16);
        background: #eef4f8;
    }

    .active-wrap {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #334e70;
        background: #f6fafc;
        border: 1px solid rgba(16, 44, 87, 0.1);
        border-radius: 10px;
        padding: 8px 10px;
        width: fit-content;
    }

    .active-wrap input[type="checkbox"] { width: 16px; height: 16px; }

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

    @media (max-width: 992px) { .row-grid { grid-template-columns: 1fr; } }

    @media (max-width: 640px) {
        .form-actions { flex-direction: column-reverse; align-items: stretch; }
        .btn-action { width: 100%; }
    }
</style>

<div style="display: grid; gap: 12px;">
    <?php if (!empty($error)): ?>
        <div class="admin-alert error" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="add-layout">
        <div class="panel panel-form">
            <div class="panel-head">
                <h2 class="panel-title"><i class="fas fa-plus-circle"></i> Tambah Jadwal Ibadah</h2>
                <div class="panel-head-actions">
                    <a href="index.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="form-grid" id="jadwal-add-form">

                <!-- SECTION: INFORMASI DASAR -->
                <div class="form-section">
                    <h3 class="form-section-title">Informasi Dasar</h3>
                    
                    <div class="field">
                        <label for="nama_ibadah">Nama Ibadah <span class="req">*</span></label>
                        <input type="text" class="input" id="nama_ibadah" name="nama_ibadah" required>
                    </div>

                    <?php if ($has_kategori_column): ?>
                        <div class="field">
                            <label for="kategori">Kategori</label>
                            <select class="select" id="kategori" name="kategori">
                                <option value="">-- Pilih Kategori (Opsional) --</option>
                                <option>Ibadah Umum</option>
                                <option>Ibadah Anak</option>
                                <option>Ibadah Pemuda</option>
                                <option>Ibadah Khusus</option>
                                <option>Persekutuan Doa</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="row-grid">
                        <div class="field">
                            <label for="hari">Hari <span class="req">*</span></label>
                            <input type="text" class="input" id="hari" name="hari"
                                   placeholder="Contoh: Jumat Minggu ke-4"
                                   required>
                            <p class="hint">Tulis format hari bebas, contoh: Jumat Minggu ke-4 atau Selasa &amp; Kamis.</p>
                        </div>

                        <div class="field">
                            <label>Jam <span class="req">*</span></label>
                            <div class="time-stack" id="jam-list">
                                <div class="time-row">
                                    <input type="text" name="jam[]" class="input"
                                           placeholder="Contoh: 08:00 WIB"
                                           required>
                                    <button type="button" class="time-btn add"
                                            title="Tambah jam">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="hint">Bisa isi 1 jam atau beberapa jam. Setiap jam dipisah otomatis saat disimpan.</p>
                        </div>
                    </div>
                </div>

                <!-- SECTION: DETAIL IBADAH -->
                <div class="form-section">
                    <h3 class="form-section-title">Detail Ibadah</h3>
                    
                    <div class="field">
                        <label for="ruangan">Ruangan</label>
                        <input type="text" class="input" id="ruangan" name="ruangan">
                    </div>

                    <div class="field">
                        <label for="keterangan">Keterangan</label>
                        <textarea class="textarea" id="keterangan" name="keterangan"></textarea>
                    </div>

                    <div class="field">
                        <label for="instagram">Instagram <span style="font-size:12px;color:#999;font-weight:400;">(Opsional)</span></label>
                        <input type="text" class="input" id="instagram" name="instagram"
                               placeholder="Contoh: @gbi.salemba">
                        <p class="hint">Username Instagram atau handle, bisa diisi nama atau link profil.</p>
                    </div>
                </div>

                <?php if ($has_image_columns): ?>
                    <!-- SECTION: FOTO -->
                    <div class="form-section">
                        <h3 class="form-section-title">Foto Jadwal</h3>
                        
                        <div class="field">
                            <label for="image">Foto Jadwal</label>
                            <div class="upload-wrap">
                                <input type="file" class="upload-input" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                                <p class="hint" style="margin-top:8px;">JPG, PNG, WebP. Maksimal 30 MB. Gambar akan dioptimalkan otomatis.</p>
                                <div class="image-preview" id="new_preview_wrap" style="display:none;">
                                    <img id="new_preview" alt="Preview foto baru">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- SECTION: STATUS -->
                <div class="form-section">
                    <h3 class="form-section-title">Status</h3>
                    
                    <div class="field">
                        <label class="active-wrap" for="is_active">
                            <input type="checkbox" id="is_active" name="is_active" checked>
                            <span>Aktifkan jadwal ini</span>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn-action btn-cancel">
                        <i class="fas fa-xmark"></i> Batal
                    </a>
                    <button type="submit" class="btn-action btn-save" id="btn-save">
                        <i class="fas fa-floppy-disk"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var jamList = document.getElementById('jam-list');
    var form = document.getElementById('jadwal-add-form');
    var btnSave = document.getElementById('btn-save');

    if (!jamList || !form) return;

    function refreshButtons() {
        var rows = jamList.querySelectorAll('.time-row');
        rows.forEach(function (row, index) {
            var btn = row.querySelector('.time-btn');
            if (index === rows.length - 1) {
                btn.className = 'time-btn add';
                btn.innerHTML = '<i class="fas fa-plus"></i>';
                btn.title = 'Tambah jam';
            } else {
                btn.className = 'time-btn remove';
                btn.innerHTML = '<i class="fas fa-trash"></i>';
                btn.title = 'Hapus jam';
            }
        });
    }

    function addRow(value) {
        value = value || '';
        var row = document.createElement('div');
        row.className = 'time-row';
        row.innerHTML =
            '<input type="text" name="jam[]" class="input" placeholder="Contoh: 10:30 WIB" value="' + value + '">' +
            '<button type="button" class="time-btn add"><i class="fas fa-plus"></i></button>';
        jamList.appendChild(row);
        refreshButtons();
        row.querySelector('input').focus();
    }

    jamList.addEventListener('click', function (e) {
        var btn = e.target.closest('.time-btn');
        if (!btn) return;

        if (btn.classList.contains('add')) {
            addRow();
        } else {
            var row = btn.closest('.time-row');
            if (row) row.remove();
            if (!jamList.querySelector('.time-row')) addRow();
            refreshButtons();
        }
    });

    form.addEventListener('submit', function () {
        if (btnSave) {
            btnSave.disabled = true;
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        }
    });

    // Initialize
    refreshButtons();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>