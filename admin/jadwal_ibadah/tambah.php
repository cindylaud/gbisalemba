<?php
require_once '../includes/auth.php';
require_once '../../config/database.php';

// Check if column 'urutan' and 'kategori' exist
$has_urutan_column = false;
$has_kategori_column = false;

$check_columns = $conn->query("SHOW COLUMNS FROM jadwal_ibadah");
while ($col = $check_columns->fetch_assoc()) {
    if ($col['Field'] == 'urutan') {
        $has_urutan_column = true;
    }
    if ($col['Field'] == 'kategori') {
        $has_kategori_column = true;
    }
}

$error = '';
$success = '';

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
        // Get next urutan if column exists
        $next_urutan = 0;
        if ($has_urutan_column) {
            $stmt_urutan = $conn->prepare("SELECT COALESCE(MAX(urutan), 0) + 1 AS next_urutan FROM jadwal_ibadah");
            $stmt_urutan->execute();
            $result_urutan = $stmt_urutan->get_result();
            $row_urutan = $result_urutan->fetch_assoc();
            $next_urutan = $row_urutan['next_urutan'];
            $stmt_urutan->close();
        }

        // Build INSERT query dynamically based on available columns
        $fields = ['nama_ibadah', 'hari', 'jam', 'ruangan', 'keterangan', 'is_active'];
        $values = [$nama_ibadah, $hari, $jam, $ruangan, $keterangan, $is_active];
        $types = 'sssssi';

        if ($has_kategori_column) {
            $fields[] = 'kategori';
            $values[] = $kategori;
            $types .= 's';
        }

        if ($has_urutan_column) {
            $fields[] = 'urutan';
            $values[] = $next_urutan;
            $types .= 'i';
        }

        $fields_str = implode(', ', $fields);
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        
        $query = "INSERT INTO jadwal_ibadah ({$fields_str}) VALUES ({$placeholders})";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: index.php?success=Jadwal ibadah berhasil ditambahkan");
            exit;
        } else {
            $error = 'Gagal menambahkan jadwal ibadah: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Jadwal Ibadah</h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="nama_ibadah">Nama Ibadah <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_ibadah" name="nama_ibadah" 
                                   value="<?php echo htmlspecialchars($_POST['nama_ibadah'] ?? ''); ?>" 
                                   required>
                        </div>

                        <?php if ($has_kategori_column): ?>
                            <div class="form-group">
                                <label for="kategori">Kategori</label>
                                <select class="form-control" id="kategori" name="kategori">
                                    <option value="">-- Pilih Kategori (Opsional) --</option>
                                    <option value="Ibadah Umum" <?php echo (($_POST['kategori'] ?? '') == 'Ibadah Umum') ? 'selected' : ''; ?>>Ibadah Umum</option>
                                    <option value="Ibadah Anak" <?php echo (($_POST['kategori'] ?? '') == 'Ibadah Anak') ? 'selected' : ''; ?>>Ibadah Anak</option>
                                    <option value="Ibadah Pemuda" <?php echo (($_POST['kategori'] ?? '') == 'Ibadah Pemuda') ? 'selected' : ''; ?>>Ibadah Pemuda</option>
                                    <option value="Ibadah Khusus" <?php echo (($_POST['kategori'] ?? '') == 'Ibadah Khusus') ? 'selected' : ''; ?>>Ibadah Khusus</option>
                                    <option value="Persekutuan Doa" <?php echo (($_POST['kategori'] ?? '') == 'Persekutuan Doa') ? 'selected' : ''; ?>>Persekutuan Doa</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="hari">Hari <span class="text-danger">*</span></label>
                                    <select class="form-control" id="hari" name="hari" required>
                                        <option value="">-- Pilih Hari --</option>
                                        <?php
                                        $selected_hari = $_POST['hari'] ?? '';
                                        $hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                                        foreach ($hari_list as $h) {
                                            $selected = ($selected_hari == $h) ? 'selected' : '';
                                            echo "<option value=\"{$h}\" {$selected}>{$h}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jam">Jam <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="jam" name="jam" 
                                           value="<?php echo htmlspecialchars($_POST['jam'] ?? ''); ?>" 
                                           placeholder="Contoh: 08:00 WIB, 10:30 WIB"
                                           required>
                                    <small class="form-text text-muted">Contoh: 08:00 WIB atau 08:00 WIB, 10:30 WIB</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="ruangan">Ruangan</label>
                            <input type="text" class="form-control" id="ruangan" name="ruangan" 
                                   value="<?php echo htmlspecialchars($_POST['ruangan'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="keterangan">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="4"><?php echo htmlspecialchars($_POST['keterangan'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" 
                                       <?php echo (isset($_POST['is_active']) || !isset($_POST['nama_ibadah'])) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="is_active">Aktif</label>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Jadwal Ibadah
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><i class="fas fa-info-circle text-info"></i> <strong>Field wajib diisi:</strong></p>
                    <ul class="small">
                        <li>Nama Ibadah</li>
                        <li>Hari</li>
                        <li>Jam</li>
                    </ul>
                    <hr>
                    <p class="mb-2"><i class="fas fa-lightbulb text-warning"></i> <strong>Tips:</strong></p>
                    <ul class="small">
                        <li>Format jam bisa satu atau lebih (pisahkan dengan koma)</li>
                        <?php if ($has_urutan_column): ?>
                            <li>Urutan akan otomatis ditempatkan di bawah</li>
                        <?php endif; ?>
                        <li>Centang "Aktif" agar muncul di website</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
