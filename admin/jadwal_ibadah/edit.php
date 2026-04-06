<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_table_bootstrap.php';

$table_state = ensureJadwalIbadahTable($conn);
$has_urutan_column = (bool) ($table_state['has_urutan_column'] ?? false);
$has_kategori_column = (bool) ($table_state['has_kategori_column'] ?? false);

$error = '';
$success = '';
$jadwal = null;

// Get ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=ID tidak valid");
    exit;
}

$id = (int)$_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_ibadah = trim($_POST['nama_ibadah'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $hari = trim($_POST['hari'] ?? '');
    $jam = trim($_POST['jam'] ?? '');
    $ruangan = trim($_POST['ruangan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($nama_ibadah) || empty($hari) || empty($jam)) {
        $error = 'Nama ibadah, hari, dan jam wajib diisi';
    } else {
        // Build UPDATE query dynamically based on available columns
        $fields = ['nama_ibadah = ?', 'hari = ?', 'jam = ?', 'ruangan = ?', 'keterangan = ?', 'is_active = ?'];
        $values = [$nama_ibadah, $hari, $jam, $ruangan, $keterangan, $is_active];
        $types = 'sssssi';

        if ($has_kategori_column) {
            $fields[] = 'kategori = ?';
            $values[] = $kategori;
            $types .= 's';
        }

        // Add id at the end
        $values[] = $id;
        $types .= 'i';

        $fields_str = implode(', ', $fields);
        $query = "UPDATE jadwal_ibadah SET {$fields_str} WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: index.php?success=Jadwal ibadah berhasil diperbarui");
            exit;
        } else {
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
            <form method="POST" action="" class="form-grid">
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

<?php include __DIR__ . '/../includes/footer.php'; ?>




