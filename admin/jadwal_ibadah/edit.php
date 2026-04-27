<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_table_bootstrap.php';
require_once __DIR__ . '/../../includes/image-helper.php';

define('JADWAL_UPLOAD_DIR', __DIR__ . '/../../uploads/jadwal/');
define('JADWAL_MAX_SIZE', 30 * 1024 * 1024); // 8MB
define('JADWAL_MAX_WIDTH', 1920);
define('JADWAL_QUALITY', 80);

if (!is_dir(JADWAL_UPLOAD_DIR)) {
    @mkdir(JADWAL_UPLOAD_DIR, 0755, true);
}

$table_state = ensureJadwalIbadahTable($conn);
$has_urutan_column = (bool) ($table_state['has_urutan_column'] ?? false);
$has_kategori_column = (bool) ($table_state['has_kategori_column'] ?? false);
$has_image_columns = (bool) ($table_state['has_image_columns'] ?? false);

$error = '';
$success = '';
$jadwal = null;

if (!function_exists('jadwal_parse_ini_size')) {
    function jadwal_parse_ini_size(string $value): int {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;
        switch ($unit) {
            case 'g':
                return (int) round($number * 1024 * 1024 * 1024);
            case 'm':
                return (int) round($number * 1024 * 1024);
            case 'k':
                return (int) round($number * 1024);
            default:
                return (int) round($number);
        }
    }
}

// Get ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=ID tidak valid");
    exit;
}

$id = (int)$_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_max_size = jadwal_parse_ini_size((string) ini_get('post_max_size'));
    $content_length = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;

    if ($post_max_size > 0 && $content_length > $post_max_size) {
        $max_mb = max(1, (int) round($post_max_size / 1024 / 1024));
        $error = "Upload gagal: ukuran total request melebihi batas server ({$max_mb}MB). Kecilkan ukuran foto lalu coba lagi.";
    }

    $nama_ibadah = trim($_POST['nama_ibadah'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $hari = trim($_POST['hari'] ?? '');
    $jam = trim($_POST['jam'] ?? '');
    $ruangan = trim($_POST['ruangan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $image_fit = trim($_POST['image_fit'] ?? 'cover');
    $image_pos_y = intval($_POST['image_pos_y'] ?? 50);
    $remove_image = isset($_POST['remove_image']) ? 1 : 0;
    $image_fit = ($image_fit === 'contain') ? 'contain' : 'cover';
    if ($image_pos_y < 0) {
        $image_pos_y = 0;
    } elseif ($image_pos_y > 100) {
        $image_pos_y = 100;
    }

    // Validation
    if (!empty($error)) {
        // Stop processing when request body exceeds PHP limit.
    } elseif (empty($nama_ibadah) || empty($hari) || empty($jam)) {
        $error = 'Nama ibadah, hari, dan jam wajib diisi';
    } else {
        $current_image = '';
        if ($has_image_columns) {
            $stmt_image = $conn->prepare("SELECT image FROM jadwal_ibadah WHERE id = ?");
            $stmt_image->bind_param("i", $id);
            $stmt_image->execute();
            $result_image = $stmt_image->get_result();
            if ($result_image && $result_image->num_rows > 0) {
                $image_row = $result_image->fetch_assoc();
                $current_image = (string) ($image_row['image'] ?? '');
            }
            $stmt_image->close();
        }

        $new_image_filename = '';
        if ($has_image_columns && isset($_FILES['image'])) {
            $upload_error = (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($upload_error !== UPLOAD_ERR_NO_FILE) {
                if ($upload_error !== UPLOAD_ERR_OK) {
                    $validation = validateImageUpload($_FILES['image'], JADWAL_MAX_SIZE);
                    $error = $validation['error'] ?? 'Gagal upload foto jadwal.';
                } else {
                    $validation = validateImageUpload($_FILES['image'], JADWAL_MAX_SIZE);
                    if (!$validation['valid']) {
                        $error = $validation['error'];
                    } else {
                        $new_image_filename = generateUniqueFilename($_FILES['image']['name'], 'jadwal_');
                        $upload_path = JADWAL_UPLOAD_DIR . $new_image_filename;
                        $optimize_result = optimizeAndSaveImage(
                            $_FILES['image']['tmp_name'],
                            $upload_path,
                            JADWAL_MAX_WIDTH,
                            JADWAL_QUALITY
                        );

                        if (!$optimize_result['success']) {
                            $error = 'Gagal memproses gambar: ' . ($optimize_result['error'] ?? 'Unknown error');
                            $new_image_filename = '';
                        }
                    }
                }
            }
        }

        // Build UPDATE query dynamically based on available columns
        $fields = ['nama_ibadah = ?', 'hari = ?', 'jam = ?', 'ruangan = ?', 'keterangan = ?', 'is_active = ?'];
        $values = [$nama_ibadah, $hari, $jam, $ruangan, $keterangan, $is_active];
        $types = 'sssssi';

        if ($has_kategori_column) {
            $fields[] = 'kategori = ?';
            $values[] = $kategori;
            $types .= 's';
        }

        $final_image = $current_image;
        if ($has_image_columns) {
            if (!empty($new_image_filename)) {
                $final_image = $new_image_filename;
            } elseif ($remove_image) {
                $final_image = '';
            }

            $fields[] = 'image = ?';
            $values[] = $final_image;
            $types .= 's';

            $fields[] = 'image_fit = ?';
            $values[] = $image_fit;
            $types .= 's';

            $fields[] = 'image_pos_y = ?';
            $values[] = $image_pos_y;
            $types .= 'i';
        }

        // Add id at the end
        $values[] = $id;
        $types .= 'i';

        $fields_str = implode(', ', $fields);
        $query = "UPDATE jadwal_ibadah SET {$fields_str} WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            if ($has_image_columns && !empty($new_image_filename) && !empty($current_image) && $current_image !== $new_image_filename) {
                $old_path = JADWAL_UPLOAD_DIR . $current_image;
                if (is_file($old_path)) {
                    @unlink($old_path);
                }
            }

            if ($has_image_columns && $remove_image && empty($new_image_filename) && !empty($current_image)) {
                $old_path = JADWAL_UPLOAD_DIR . $current_image;
                if (is_file($old_path)) {
                    @unlink($old_path);
                }
            }

            $stmt->close();
            header("Location: index.php?success=Jadwal ibadah berhasil diperbarui");
            exit;
        } else {
            if ($has_image_columns && !empty($new_image_filename)) {
                $failed_path = JADWAL_UPLOAD_DIR . $new_image_filename;
                if (is_file($failed_path)) {
                    @unlink($failed_path);
                }
            }
            $error = 'Gagal memperbarui jadwal ibadah: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

// Get jadwal data
$stmt = $conn->prepare("SELECT * FROM jadwal_ibadah WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    header("Location: index.php?error=Jadwal ibadah tidak ditemukan");
    exit;
}

$jadwal = $result->fetch_assoc();
$stmt->close();

$image_fit_value = $_POST['image_fit'] ?? ($jadwal['image_fit'] ?? 'cover');
if ($image_fit_value !== 'contain') {
    $image_fit_value = 'cover';
}

$image_pos_y_value = intval($_POST['image_pos_y'] ?? ($jadwal['image_pos_y'] ?? 50));
if ($image_pos_y_value < 0) {
    $image_pos_y_value = 0;
} elseif ($image_pos_y_value > 100) {
    $image_pos_y_value = 100;
}

$current_image_url = '';
if ($has_image_columns && !empty($jadwal['image'])) {
    $current_image_path = JADWAL_UPLOAD_DIR . $jadwal['image'];
    if (is_file($current_image_path)) {
        $current_image_url = '../../uploads/jadwal/' . rawurlencode((string) $jadwal['image']);
    }
}

$jam_value_for_form = $_POST['jam'] ?? ($jadwal['jam'] ?? '');
$jam_items_for_form = [];
foreach (explode(',', (string) $jam_value_for_form) as $jam_item) {
    $jam_item = trim($jam_item);
    if ($jam_item !== '') {
        $jam_items_for_form[] = $jam_item;
    }
}
if (empty($jam_items_for_form)) {
    $jam_items_for_form[] = '';
}

$admin_page_title = 'Edit Jadwal Ibadah';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .jadwal-edit-page {
        display: grid;
        gap: 12px;
        margin-top: -4px;
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

    .edit-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
        align-items: start;
    }

    .panel {
        background: linear-gradient(180deg, #ffffff 0%, #f8fbfe 100%);
        border: 1px solid rgba(16, 44, 87, 0.1);
        border-radius: 22px;
        box-shadow: 0 12px 26px rgba(15, 39, 66, 0.08);
    }

    .panel-form {
        padding: 16px;
    }

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

    .panel-title i {
        color: #146c94;
    }

    .form-grid {
        display: grid;
        gap: 12px;
    }

    .row-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .field {
        display: grid;
        gap: 5px;
    }

    .field label {
        margin: 0;
        font-size: 13px;
        font-weight: 700;
        color: #234267;
    }

    .req {
        color: #dc3545;
    }

    .input,
    .select,
    .textarea {
        width: 100%;
        border: 1px solid rgba(16, 44, 87, 0.18);
        border-radius: 12px;
        padding: 10px 12px;
        background: #fff;
        color: #344054;
        font-size: 13px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .textarea {
        min-height: 76px;
        resize: vertical;
    }

    .time-stack {
        display: grid;
        gap: 8px;
    }

    .time-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .time-row .input {
        flex: 1;
    }

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

    .input:focus,
    .select:focus,
    .textarea:focus {
        outline: none;
        border-color: rgba(63, 182, 168, 0.56);
        box-shadow: 0 0 0 0.2rem rgba(63, 182, 168, 0.14);
    }

    .hint {
        margin: 0;
        color: #6b7c93;
        font-size: 12px;
        line-height: 1.45;
    }

    .upload-wrap {
        border: 1px dashed rgba(20, 108, 148, 0.35);
        border-radius: 12px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.92) 0%, rgba(240, 247, 252, 0.9) 100%);
        padding: 12px;
    }

    .upload-input {
        display: block;
        width: 100%;
        font-size: 12px;
        color: #405f7b;
    }

    .image-preview {
        margin-top: 10px;
        display: inline-block;
    }

    .image-preview img {
        width: 160px;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid rgba(16, 44, 87, 0.16);
        background: #eef4f8;
    }

    .remove-wrap {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 12px;
        color: #4f5963;
        margin-top: 8px;
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

    .active-wrap input[type="checkbox"] {
        width: 16px;
        height: 16px;
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
        border-radius: 12px;
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .btn-action:hover {
        text-decoration: none;
        transform: translateY(-1px);
    }

    .btn-cancel {
        background: #f4f7fa;
        border-color: rgba(16, 44, 87, 0.15);
        color: #2d4e77;
    }

    .btn-cancel:hover {
        color: #2d4e77;
        box-shadow: 0 8px 16px rgba(16, 44, 87, 0.12);
    }

    .btn-save {
        background: linear-gradient(135deg, #1e3a5f 0%, #146c94 100%);
        border-color: rgba(12, 68, 96, 0.45);
        color: #fff;
        box-shadow: 0 10px 20px rgba(20, 108, 148, 0.24);
    }

    .btn-save:hover {
        color: #fff;
        box-shadow: 0 12px 24px rgba(20, 108, 148, 0.28);
    }

    @media (max-width: 992px) {
        .row-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .form-actions {
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .btn-action {
            width: 100%;
        }
    }
</style>

<div class="jadwal-edit-page">
    <?php if (!empty($error)): ?>
        <div class="admin-alert error" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="edit-layout">
        <div class="panel panel-form">
            <div class="panel-head">
                <h2 class="panel-title"><i class="fas fa-pen-to-square"></i> Form Edit Jadwal Ibadah</h2>
                <div class="panel-head-actions">
                    <a href="index.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <form method="POST" action="" class="form-grid" enctype="multipart/form-data" id="jadwal-edit-form">
                <div class="field">
                    <label for="nama_ibadah">Nama Ibadah <span class="req">*</span></label>
                    <input type="text" class="input" id="nama_ibadah" name="nama_ibadah"
                           value="<?php echo htmlspecialchars($_POST['nama_ibadah'] ?? $jadwal['nama_ibadah']); ?>"
                           required>
                </div>

                <?php if ($has_kategori_column): ?>
                    <div class="field">
                        <label for="kategori">Kategori</label>
                        <select class="select" id="kategori" name="kategori">
                            <option value="">-- Pilih Kategori (Opsional) --</option>
                            <?php
                            $selected_kategori = $_POST['kategori'] ?? ($jadwal['kategori'] ?? '');
                            $kategori_list = ['Ibadah Umum', 'Ibadah Anak', 'Ibadah Pemuda', 'Ibadah Khusus', 'Persekutuan Doa'];
                            foreach ($kategori_list as $kat) {
                                $selected = ($selected_kategori == $kat) ? 'selected' : '';
                                echo "<option value=\"{$kat}\" {$selected}>{$kat}</option>";
                            }
                            ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="row-grid">
                    <div class="field">
                        <label for="hari">Hari <span class="req">*</span></label>
                        <input type="text" class="input" id="hari" name="hari"
                               value="<?php echo htmlspecialchars($_POST['hari'] ?? $jadwal['hari']); ?>"
                               placeholder="Contoh: Jumat Minggu ke-4"
                               required>
                        <p class="hint">Tulis format hari bebas, contoh: Jumat Minggu ke-4 atau Selasa & Kamis.</p>
                    </div>

                    <div class="field">
                        <label>Jam <span class="req">*</span></label>
                        <input type="hidden" id="jam" name="jam" value="<?php echo htmlspecialchars($jam_value_for_form); ?>">
                        <div class="time-stack" id="jam-list">
                            <?php foreach ($jam_items_for_form as $idx => $jam_item): ?>
                                <div class="time-row">
                                    <input type="text" class="input jam-item"
                                           value="<?php echo htmlspecialchars($jam_item); ?>"
                                           placeholder="Contoh: 08:00 WIB"
                                           <?php echo $idx === 0 ? 'required' : ''; ?>>
                                    <button type="button" class="time-btn <?php echo $idx === 0 ? 'add' : 'remove'; ?>"
                                            title="<?php echo $idx === 0 ? 'Tambah jam' : 'Hapus jam'; ?>">
                                        <i class="fas <?php echo $idx === 0 ? 'fa-plus' : 'fa-trash'; ?>"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="hint">Bisa isi 1 jam atau beberapa jam. Setiap jam dipisah otomatis saat disimpan.</p>
                    </div>
                </div>

                <div class="field">
                    <label for="ruangan">Ruangan</label>
                    <input type="text" class="input" id="ruangan" name="ruangan"
                           value="<?php echo htmlspecialchars($_POST['ruangan'] ?? ($jadwal['ruangan'] ?? '')); ?>">
                </div>

                <div class="field">
                    <label for="keterangan">Keterangan</label>
                    <textarea class="textarea" id="keterangan" name="keterangan"><?php echo htmlspecialchars($_POST['keterangan'] ?? ($jadwal['keterangan'] ?? '')); ?></textarea>
                </div>

                <?php if ($has_image_columns): ?>
                    <div class="field">
                        <label for="image">Foto Jadwal</label>
                        <div class="upload-wrap">
                            <input type="file" class="upload-input" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                            <p class="hint" style="margin-top:8px;">JPG, PNG, WebP. Maksimal 30 MB. Gambar akan dioptimalkan otomatis.</p>

                            <?php if ($current_image_url !== ''): ?>
                                <div class="image-preview">
                                    <img id="current_preview" src="<?php echo htmlspecialchars($current_image_url); ?>" alt="Foto jadwal" style="object-fit: <?php echo htmlspecialchars($image_fit_value); ?>; object-position: center <?php echo $image_pos_y_value; ?>%;">
                                </div>
                                <label class="remove-wrap" for="remove_image">
                                    <input type="checkbox" id="remove_image" name="remove_image" value="1">
                                    <span>Hapus foto saat simpan</span>
                                </label>
                            <?php endif; ?>

                            <div class="image-preview" id="new_preview_wrap" style="display:none;">
                                <img id="new_preview" alt="Preview foto baru" style="object-fit: <?php echo htmlspecialchars($image_fit_value); ?>; object-position: center <?php echo $image_pos_y_value; ?>%;">
                            </div>
                        </div>
                    </div>

                    <div class="row-grid">
                        <div class="field">
                            <label for="image_fit">Fit Gambar</label>
                            <select class="select" id="image_fit" name="image_fit">
                                <option value="cover" <?php echo $image_fit_value === 'cover' ? 'selected' : ''; ?>>Cover (crop untuk fill)</option>
                                <option value="contain" <?php echo $image_fit_value === 'contain' ? 'selected' : ''; ?>>Contain (tampilkan penuh)</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="image_pos_y">Posisi Vertikal (<span id="image_pos_y_value"><?php echo $image_pos_y_value; ?></span>%)</label>
                            <input type="range" class="input" id="image_pos_y" name="image_pos_y" min="0" max="100" step="1" value="<?php echo $image_pos_y_value; ?>">
                            <p class="hint">0% = atas, 50% = tengah, 100% = bawah</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="field">
                    <?php
                    $is_checked = isset($_POST['is_active']) ?
                        (isset($_POST['is_active'])) :
                        ($jadwal['is_active'] == 1);
                    ?>
                    <label>Status</label>
                    <label class="active-wrap" for="is_active">
                        <input type="checkbox" id="is_active" name="is_active" <?php echo $is_checked ? 'checked' : ''; ?>>
                        <span>Aktifkan jadwal ini</span>
                    </label>
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn-action btn-cancel">
                        <i class="fas fa-xmark"></i> Batal
                    </a>
                    <button type="submit" class="btn-action btn-save">
                        <i class="fas fa-floppy-disk"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        var jamList = document.getElementById('jam-list');
        var jamHidden = document.getElementById('jam');
        var form = jamList ? jamList.closest('form') : null;

        if (!jamList || !jamHidden || !form) {
            return;
        }

        function updateHiddenValue() {
            var values = [];
            var inputs = jamList.querySelectorAll('.jam-item');
            inputs.forEach(function (input) {
                var val = (input.value || '').trim();
                if (val !== '') {
                    values.push(val);
                }
            });
            jamHidden.value = values.join(', ');
        }

        function addRow() {
            var row = document.createElement('div');
            row.className = 'time-row';
            row.innerHTML = '' +
                '<input type="text" class="input jam-item" placeholder="Contoh: 10:30 WIB">' +
                '<button type="button" class="time-btn remove" title="Hapus jam">' +
                '<i class="fas fa-trash"></i>' +
                '</button>';
            jamList.appendChild(row);
        }

        jamList.addEventListener('click', function (event) {
            var btn = event.target.closest('.time-btn');
            if (!btn) {
                return;
            }

            if (btn.classList.contains('add')) {
                addRow();
                return;
            }

            if (btn.classList.contains('remove')) {
                var row = btn.closest('.time-row');
                if (row) {
                    row.remove();
                    if (!jamList.querySelector('.time-row')) {
                        addRow();
                    }
                    updateHiddenValue();
                }
            }
        });

        jamList.addEventListener('input', function (event) {
            if (event.target.classList.contains('jam-item')) {
                updateHiddenValue();
            }
        });

        form.addEventListener('submit', function () {
            updateHiddenValue();
        });

        updateHiddenValue();
    })();
</script>

<?php if ($has_image_columns): ?>
<script>
    (function () {
        var form = document.getElementById('jadwal-edit-form');
        var saveButton = form ? form.querySelector('.btn-save') : null;
        var input = document.getElementById('image');
        var fit = document.getElementById('image_fit');
        var pos = document.getElementById('image_pos_y');
        var posValue = document.getElementById('image_pos_y_value');
        var current = document.getElementById('current_preview');
        var nextWrap = document.getElementById('new_preview_wrap');
        var next = document.getElementById('new_preview');
        var maxImageSize = <?php echo (int) JADWAL_MAX_SIZE; ?>;
        var maxImageMb = <?php echo (int) round(JADWAL_MAX_SIZE / 1024 / 1024); ?>;

        function applyImageStyle(img) {
            if (!img) {
                return;
            }
            var fitValue = fit ? fit.value : 'cover';
            var posValueNum = pos ? pos.value : '50';
            img.style.objectFit = fitValue;
            img.style.objectPosition = 'center ' + posValueNum + '%';
        }

        function refreshPositionLabel() {
            if (posValue && pos) {
                posValue.textContent = pos.value;
            }
        }

        if (input && nextWrap && next) {
            input.addEventListener('change', function (event) {
                var file = event.target.files && event.target.files[0];
                if (!file) {
                    return;
                }

                if (file.size > maxImageSize) {
                    alert('Ukuran foto terlalu besar. Maksimal ' + maxImageMb + 'MB.');
                    input.value = '';
                    next.removeAttribute('src');
                    nextWrap.style.display = 'none';
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (e) {
                    next.src = e.target.result;
                    nextWrap.style.display = 'inline-block';
                    applyImageStyle(next);
                };
                reader.readAsDataURL(file);
            });
        }

        if (form && saveButton) {
            form.addEventListener('submit', function () {
                saveButton.disabled = true;
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            });
        }

        if (fit) {
            fit.addEventListener('change', function () {
                applyImageStyle(current);
                applyImageStyle(next);
            });
        }

        if (pos) {
            pos.addEventListener('input', function () {
                refreshPositionLabel();
                applyImageStyle(current);
                applyImageStyle(next);
            });
        }

        refreshPositionLabel();
        applyImageStyle(current);
        applyImageStyle(next);
    })();
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>




