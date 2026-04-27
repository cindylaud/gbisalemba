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

        $fields = ['nama_ibadah', 'hari', 'jam', 'ruangan', 'keterangan', 'is_active'];
        $values = [$nama_ibadah, $hari, $jam, $ruangan, $keterangan, $is_active];
        $types = 'sssssi';

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
    .panel {
        background: #fff;
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }

    .panel-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 15px;
    }

    .form-grid {
        display: grid;
        gap: 14px;
    }

    .field label {
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 5px;
        display: block;
    }

    .input, .select, .textarea {
        width: 100%;
        padding: 10px;
        border-radius: 10px;
        border: 1px solid #ddd;
    }

    .textarea {
        min-height: 80px;
    }

    .time-row {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .time-row .input {
        flex: 1;
    }

    .btn-small {
        width: 36px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }

    .btn-add { background: #e6f4ea; }
    .btn-remove { background: #fdecea; }

    .btn-action {
        padding: 10px 16px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-save {
        background: #1e3a5f;
        color: #fff;
    }

    .btn-cancel {
        background: #eee;
        color: #333;
        text-decoration: none;
        display: inline-block;
    }

    .error {
        background: #ffe5e5;
        padding: 10px;
        border-radius: 10px;
        margin-bottom: 10px;
    }
</style>

<div class="panel">
    <div class="panel-title">Tambah Jadwal Ibadah</div>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form-grid">

        <div class="field">
            <label>Nama Ibadah *</label>
            <input type="text" name="nama_ibadah" class="input" required>
        </div>

        <?php if ($has_kategori_column): ?>
        <div class="field">
            <label>Kategori</label>
            <select name="kategori" class="select">
                <option value="">-- Pilih --</option>
                <option>Ibadah Umum</option>
                <option>Ibadah Anak</option>
                <option>Ibadah Pemuda</option>
                <option>Ibadah Khusus</option>
            </select>
        </div>
        <?php endif; ?>

        <div class="field">
            <label>Hari *</label>
            <input type="text" name="hari" class="input" placeholder="Contoh: Minggu" required>
        </div>

        <div class="field">
            <label>Jam *</label>

            <div id="jam-wrapper">
                <div class="time-row">
                    <input type="text" name="jam[]" class="input" placeholder="08:00 WIB" required>
                    <button type="button" class="btn-small btn-add" onclick="addJam()">+</button>
                </div>
            </div>
        </div>

        <div class="field">
            <label>Ruangan</label>
            <input type="text" name="ruangan" class="input">
        </div>

        <div class="field">
            <label>Keterangan</label>
            <textarea name="keterangan" class="textarea"></textarea>
        </div>

        <?php if ($has_image_columns): ?>
        <div class="field">
            <label>Foto</label>
            <input type="file" name="image" class="input">
        </div>
        <?php endif; ?>

        <div class="field">
            <label>
                <input type="checkbox" name="is_active" checked>
                Aktif
            </label>
        </div>

        <div>
            <button type="submit" class="btn-action btn-save">Simpan</button>
            <a href="index.php" class="btn-action btn-cancel">Batal</a>
        </div>

    </form>
</div>

<script>
function addJam() {
    const wrapper = document.getElementById('jam-wrapper');

    const row = document.createElement('div');
    row.className = 'time-row';
    row.innerHTML = `
        <input type="text" name="jam[]" class="input" placeholder="10:00 WIB">
        <button type="button" class="btn-small btn-remove" onclick="this.parentElement.remove()">x</button>
    `;

    wrapper.appendChild(row);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>