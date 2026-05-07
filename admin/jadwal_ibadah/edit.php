<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_table_bootstrap.php';
require_once __DIR__ . '/../../includes/image-helper.php';

define('JADWAL_UPLOAD_DIR', __DIR__ . '/../../uploads/jadwal/');
define('JADWAL_MAX_SIZE', 30 * 1024 * 1024); // 30MB
define('JADWAL_MAX_WIDTH', 1920);
define('JADWAL_QUALITY', 80);

if (!is_dir(JADWAL_UPLOAD_DIR)) {
    @mkdir(JADWAL_UPLOAD_DIR, 0755, true);
}

$table_state = ensureJadwalIbadahTable($conn);
$has_urutan_column   = (bool) ($table_state['has_urutan_column']   ?? false);
$has_kategori_column = (bool) ($table_state['has_kategori_column'] ?? false);
$has_image_columns   = (bool) ($table_state['has_image_columns']   ?? false);

$error   = '';
$success = '';
$jadwal  = null;

if (!function_exists('jadwal_parse_ini_size')) {
    function jadwal_parse_ini_size(string $value): int {
        $value = trim($value);
        if ($value === '') return 0;
        $unit   = strtolower(substr($value, -1));
        $number = (float) $value;
        switch ($unit) {
            case 'g': return (int) round($number * 1024 * 1024 * 1024);
            case 'm': return (int) round($number * 1024 * 1024);
            case 'k': return (int) round($number * 1024);
            default:  return (int) round($number);
        }
    }
}

// ── Resolve $id dari GET atau POST (fix: GET bisa hilang saat redirect form) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && is_numeric($_POST['id'])) {
    $id = (int) $_POST['id'];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];
} else {
    header("Location: index.php?error=ID tidak valid");
    exit;
}

// ── Handle form submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $post_max_size  = jadwal_parse_ini_size((string) ini_get('post_max_size'));
    $content_length = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;

    if ($post_max_size > 0 && $content_length > $post_max_size) {
        $max_mb = max(1, (int) round($post_max_size / 1024 / 1024));
        $error  = "Upload gagal: ukuran total request melebihi batas server ({$max_mb}MB). Kecilkan ukuran foto lalu coba lagi.";
    }

    $nama_ibadah = trim($_POST['nama_ibadah'] ?? '');
    $kategori    = trim($_POST['kategori']    ?? '');
    $hari        = trim($_POST['hari']        ?? '');
    // JAM MULTI INPUT - handle as array like in tambah.php
    $jam_array = $_POST['jam'] ?? [];
    $jam_array = array_filter(array_map('trim', $jam_array));
    $jam = implode(', ', $jam_array);
    $ruangan     = trim($_POST['ruangan']     ?? '');
    $keterangan  = trim($_POST['keterangan']  ?? '');
    $instagram   = trim($_POST['instagram']   ?? '');
    $is_active   = isset($_POST['is_active']) ? 1 : 0;
    $image_fit   = trim($_POST['image_fit']   ?? 'cover');
    $image_pos_y = intval($_POST['image_pos_y'] ?? 50);
    $remove_image = isset($_POST['remove_image']) ? 1 : 0;

    $image_fit = ($image_fit === 'contain') ? 'contain' : 'cover';
    if ($image_pos_y < 0)   $image_pos_y = 0;
    if ($image_pos_y > 100) $image_pos_y = 100;

    if (!empty($error)) {
        // sudah ada error dari cek post_max_size, skip validasi
    } elseif (empty($nama_ibadah) || empty($hari) || empty($jam)) {
        $error = 'Nama ibadah, hari, dan jam wajib diisi';
    } else {

        // Ambil foto lama
        $current_image = '';
        if ($has_image_columns) {
            $stmt_image = $conn->prepare("SELECT image FROM jadwal_ibadah WHERE id = ?");
            $stmt_image->bind_param("i", $id);
            $stmt_image->execute();
            $result_image = $stmt_image->get_result();
            if ($result_image && $result_image->num_rows > 0) {
                $image_row     = $result_image->fetch_assoc();
                $current_image = (string) ($image_row['image'] ?? '');
            }
            $stmt_image->close();
        }

        // Proses upload foto baru
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
                        $upload_path        = JADWAL_UPLOAD_DIR . $new_image_filename;
                        $optimize_result    = optimizeAndSaveImage(
                            $_FILES['image']['tmp_name'],
                            $upload_path,
                            JADWAL_MAX_WIDTH,
                            JADWAL_QUALITY
                        );
                        if (!$optimize_result['success']) {
                            $error              = 'Gagal memproses gambar: ' . ($optimize_result['error'] ?? 'Unknown error');
                            $new_image_filename = '';
                        }
                    }
                }
            }
        }

        // Lanjut UPDATE hanya kalau belum ada error dari upload
        if (empty($error)) {

            // Bangun query dinamis
            $fields = [
                'nama_ibadah = ?',
                'hari = ?',
                'jam = ?',
                'ruangan = ?',
                'keterangan = ?',
                'instagram = ?',
                'is_active = ?',
            ];
            $values = [$nama_ibadah, $hari, $jam, $ruangan, $keterangan, $instagram, $is_active];
            $types  = 'ssssssi';

            if ($has_kategori_column) {
                $fields[] = 'kategori = ?';
                $values[] = $kategori;
                $types   .= 's';
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
                $types   .= 's';

                $fields[] = 'image_fit = ?';
                $values[] = $image_fit;
                $types   .= 's';

                $fields[] = 'image_pos_y = ?';
                $values[] = $image_pos_y;
                $types   .= 'i';
            }

            // Tambahkan id di akhir untuk WHERE
            $values[] = $id;
            $types   .= 'i';

            $fields_str = implode(', ', $fields);
            $query      = "UPDATE jadwal_ibadah SET {$fields_str} WHERE id = ?";

            $stmt = $conn->prepare($query);
            if (!$stmt) {
                $error = 'Gagal menyiapkan query: ' . $conn->error;
            } else {
                $stmt->bind_param($types, ...$values);

                if ($stmt->execute()) {
                    // Hapus foto lama jika diganti
                    if ($has_image_columns && !empty($new_image_filename) && !empty($current_image) && $current_image !== $new_image_filename) {
                        $old_path = JADWAL_UPLOAD_DIR . $current_image;
                        if (is_file($old_path)) @unlink($old_path);
                    }
                    // Hapus foto lama jika di-remove
                    if ($has_image_columns && $remove_image && empty($new_image_filename) && !empty($current_image)) {
                        $old_path = JADWAL_UPLOAD_DIR . $current_image;
                        if (is_file($old_path)) @unlink($old_path);
                    }

                    $stmt->close();
                    header("Location: index.php?success=Jadwal ibadah berhasil diperbarui");
                    exit;

                } else {
                    // Kalau upload sudah jalan tapi UPDATE gagal, hapus foto baru yang terlanjur tersimpan
                    if ($has_image_columns && !empty($new_image_filename)) {
                        $failed_path = JADWAL_UPLOAD_DIR . $new_image_filename;
                        if (is_file($failed_path)) @unlink($failed_path);
                    }
                    $error = 'Gagal memperbarui jadwal ibadah: ' . $conn->error;
                    $stmt->close();
                }
            }
        } else {
            // Ada error dari upload foto, hapus file yang terlanjur tersimpan
            if ($has_image_columns && !empty($new_image_filename)) {
                $failed_path = JADWAL_UPLOAD_DIR . $new_image_filename;
                if (is_file($failed_path)) @unlink($failed_path);
            }
        }
    }
}

// ── Ambil data jadwal untuk ditampilkan di form ───────────────────────────────
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
if ($image_fit_value !== 'contain') $image_fit_value = 'cover';

$image_pos_y_value = intval($_POST['image_pos_y'] ?? ($jadwal['image_pos_y'] ?? 50));
if ($image_pos_y_value < 0)   $image_pos_y_value = 0;
if ($image_pos_y_value > 100) $image_pos_y_value = 100;

$current_image_url = '';
if ($has_image_columns && !empty($jadwal['image'])) {
    $current_image_path = JADWAL_UPLOAD_DIR . $jadwal['image'];
    if (is_file($current_image_path)) {
        $current_image_url = '../../uploads/jadwal/' . rawurlencode((string) $jadwal['image']);
    }
}

// Prepare jam items for form display (split from database or POST array)
$jam_value_for_form = '';
if (is_array($_POST['jam'] ?? null)) {
    // POST is array (from form)
    $jam_value_for_form = implode(', ', array_filter(array_map('trim', $_POST['jam'] ?? [])));
} else {
    // Database value (string)
    $jam_value_for_form = $_POST['jam'] ?? ($jadwal['jam'] ?? '');
}
$jam_items_for_form = [];
foreach (explode(',', (string) $jam_value_for_form) as $jam_item) {
    $jam_item = trim($jam_item);
    if ($jam_item !== '') $jam_items_for_form[] = $jam_item;
}
if (empty($jam_items_for_form)) $jam_items_for_form[] = '';

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

    .remove-wrap {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 13px;
        color: #4f5963;
        margin-top: 12px;
        padding: 10px 12px;
        background: #faf5f5;
        border: 1px solid rgba(201, 96, 96, 0.2);
        border-radius: 10px;
        cursor: pointer;
    }

    .remove-wrap input[type="checkbox"] { 
        width: 18px; 
        height: 18px; 
        flex-shrink: 0;
        margin-top: 2px;
        cursor: pointer;
    }

    .remove-wrap strong {
        color: #8d2d2d;
        display: block;
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

            <form method="POST" action="?id=<?php echo (int) $id; ?>" class="form-grid" enctype="multipart/form-data" id="jadwal-edit-form">

                <!-- FIX: hidden id supaya id tidak hilang saat POST -->
                <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

                <!-- SECTION: INFORMASI DASAR -->
                <div class="form-section">
                    <h3 class="form-section-title">Informasi Dasar</h3>
                    
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
                            <p class="hint">Tulis format hari bebas, contoh: Jumat Minggu ke-4 atau Selasa &amp; Kamis.</p>
                        </div>

                        <div class="field">
                            <label>Jam <span class="req">*</span></label>
                            <div class="time-stack" id="jam-list">
                                <?php foreach ($jam_items_for_form as $idx => $jam_item): ?>
                                    <div class="time-row">
                                        <input type="text" name="jam[]" class="input"
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
                </div>

                <!-- SECTION: DETAIL IBADAH -->
                <div class="form-section">
                    <h3 class="form-section-title">Detail Ibadah</h3>
                    
                    <div class="field">
                        <label for="ruangan">Ruangan</label>
                        <input type="text" class="input" id="ruangan" name="ruangan"
                               value="<?php echo htmlspecialchars($_POST['ruangan'] ?? ($jadwal['ruangan'] ?? '')); ?>">
                    </div>

                    <div class="field">
                        <label for="keterangan">Keterangan</label>
                        <textarea class="textarea" id="keterangan" name="keterangan"><?php echo htmlspecialchars($_POST['keterangan'] ?? ($jadwal['keterangan'] ?? '')); ?></textarea>
                    </div>

                    <div class="field">
                        <label for="instagram">Instagram <span style="font-size:12px;color:#999;font-weight:400;">(Opsional)</span></label>
                        <input type="text" class="input" id="instagram" name="instagram"
                               value="<?php echo htmlspecialchars($_POST['instagram'] ?? ($jadwal['instagram'] ?? '')); ?>"
                               placeholder="Contoh: @gbi.salemba">
                        <p class="hint">Username Instagram atau handle, bisa diisi nama atau link profil.</p>
                    </div>
                </div>

                <?php if ($has_image_columns): ?>
                    <!-- SECTION: FOTO & TAMPILAN -->
                    <div class="form-section">
                        <h3 class="form-section-title">Foto &amp; Tampilan</h3>
                        
                        <div class="field">
                            <label for="image">Foto Jadwal</label>
                            <div class="upload-wrap">
                                <input type="file" class="upload-input" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                                <p class="hint" style="margin-top:8px;">JPG, PNG, WebP. Maksimal 30 MB. Gambar akan dioptimalkan otomatis.</p>

                                <?php if ($current_image_url !== ''): ?>
                                    <div class="image-preview">
                                        <img id="current_preview"
                                             src="<?php echo htmlspecialchars($current_image_url); ?>"
                                             alt="Foto jadwal"
                                             style="object-fit:<?php echo htmlspecialchars($image_fit_value); ?>;object-position:center <?php echo $image_pos_y_value; ?>%;">
                                    </div>
                                    <label class="remove-wrap" for="remove_image">
                                        <input type="checkbox" id="remove_image" name="remove_image" value="1">
                                        <div>
                                            <strong>Hapus foto yang ada sekarang</strong>
                                            <p class="hint" style="margin: 2px 0 0 0;">Foto akan dihapus saat Anda klik "Simpan Perubahan"</p>
                                        </div>
                                    </label>
                                <?php endif; ?>

                                <div class="image-preview" id="new_preview_wrap" style="display:none;">
                                    <img id="new_preview" alt="Preview foto baru"
                                         style="object-fit:<?php echo htmlspecialchars($image_fit_value); ?>;object-position:center <?php echo $image_pos_y_value; ?>%;">
                                </div>
                            </div>
                        </div>

                        <div class="row-grid">
                            <div class="field">
                                <label for="image_fit">Fit Gambar</label>
                                <select class="select" id="image_fit" name="image_fit">
                                    <option value="cover"   <?php echo $image_fit_value === 'cover'   ? 'selected' : ''; ?>>Cover (crop untuk fill)</option>
                                    <option value="contain" <?php echo $image_fit_value === 'contain' ? 'selected' : ''; ?>>Contain (tampilkan penuh)</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="image_pos_y">Posisi Vertikal (<span id="image_pos_y_value"><?php echo $image_pos_y_value; ?></span>%)</label>
                                <input type="range" class="input" id="image_pos_y" name="image_pos_y" min="0" max="100" step="1" value="<?php echo $image_pos_y_value; ?>">
                                <p class="hint">0% = atas, 50% = tengah, 100% = bawah</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- SECTION: STATUS -->
                <div class="form-section">
                    <h3 class="form-section-title">Status</h3>
                    
                    <div class="field">
                        <?php
                        $is_checked = isset($_POST['is_active'])
                            ? true
                            : ($jadwal['is_active'] == 1);
                        ?>
                        <label class="active-wrap" for="is_active">
                            <input type="checkbox" id="is_active" name="is_active" <?php echo $is_checked ? 'checked' : ''; ?>>
                            <span>Aktifkan jadwal ini</span>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn-action btn-cancel">
                        <i class="fas fa-xmark"></i> Batal
                    </a>
                    <button type="submit" class="btn-action btn-save" id="btn-save">
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
    var form = document.getElementById('jadwal-edit-form');
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

<?php if ($has_image_columns): ?>
<script>
(function () {
    var input      = document.getElementById('image');
    var fit        = document.getElementById('image_fit');
    var pos        = document.getElementById('image_pos_y');
    var posLabel   = document.getElementById('image_pos_y_value');
    var current    = document.getElementById('current_preview');
    var nextWrap   = document.getElementById('new_preview_wrap');
    var next       = document.getElementById('new_preview');
    var maxSize    = <?php echo (int) JADWAL_MAX_SIZE; ?>;
    var maxMb      = <?php echo (int) round(JADWAL_MAX_SIZE / 1024 / 1024); ?>;

    function applyImageStyle(img) {
        if (!img) return;
        img.style.objectFit      = fit ? fit.value : 'cover';
        img.style.objectPosition = 'center ' + (pos ? pos.value : '50') + '%';
    }

    function refreshLabel() {
        if (posLabel && pos) posLabel.textContent = pos.value;
    }

    if (input && nextWrap && next) {
        input.addEventListener('change', function (e) {
            var file = e.target.files && e.target.files[0];
            if (!file) return;
            if (file.size > maxSize) {
                alert('Ukuran foto terlalu besar. Maksimal ' + maxMb + 'MB.');
                input.value = '';
                next.removeAttribute('src');
                nextWrap.style.display = 'none';
                return;
            }
            var reader = new FileReader();
            reader.onload = function (ev) {
                next.src = ev.target.result;
                nextWrap.style.display = 'inline-block';
                applyImageStyle(next);
            };
            reader.readAsDataURL(file);
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
            refreshLabel();
            applyImageStyle(current);
            applyImageStyle(next);
        });
    }

    refreshLabel();
    applyImageStyle(current);
    applyImageStyle(next);
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>