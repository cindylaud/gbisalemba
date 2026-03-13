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

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Jadwal Ibadah</h1>
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
                                   value="<?php echo htmlspecialchars($_POST['nama_ibadah'] ?? $jadwal['nama_ibadah']); ?>" 
                                   required>
                        </div>

                        <?php if ($has_kategori_column): ?>
                            <div class="form-group">
                                <label for="kategori">Kategori</label>
                                <select class="form-control" id="kategori" name="kategori">
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

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="hari">Hari <span class="text-danger">*</span></label>
                                    <select class="form-control" id="hari" name="hari" required>
                                        <option value="">-- Pilih Hari --</option>
                                        <?php
                                        $selected_hari = $_POST['hari'] ?? $jadwal['hari'];
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
                                           value="<?php echo htmlspecialchars($_POST['jam'] ?? $jadwal['jam']); ?>" 
                                           placeholder="Contoh: 08:00 WIB, 10:30 WIB"
                                           required>
                                    <small class="form-text text-muted">Contoh: 08:00 WIB atau 08:00 WIB, 10:30 WIB</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="ruangan">Ruangan</label>
                            <input type="text" class="form-control" id="ruangan" name="ruangan" 
                                   value="<?php echo htmlspecialchars($_POST['ruangan'] ?? ($jadwal['ruangan'] ?? '')); ?>">
                        </div>

                        <div class="form-group">
                            <label for="keterangan">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="4"><?php echo htmlspecialchars($_POST['keterangan'] ?? ($jadwal['keterangan'] ?? '')); ?></textarea>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <?php 
                                $is_checked = isset($_POST['is_active']) ? 
                                    (isset($_POST['is_active'])) : 
                                    ($jadwal['is_active'] == 1);
                                ?>
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" 
                                       <?php echo $is_checked ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="is_active">Aktif</label>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Jadwal</h6>
                </div>
                <div class="card-body">
                    <?php if ($has_urutan_column && isset($jadwal['urutan'])): ?>
                        <p class="mb-2"><strong>Urutan saat ini:</strong></p>
                        <p class="text-muted"><?php echo $jadwal['urutan']; ?></p>
                        <hr>
                    <?php endif; ?>
                    
                    <?php if (isset($jadwal['created_at'])): ?>
                        <p class="mb-2"><strong>Dibuat pada:</strong></p>
                        <p class="text-muted"><?php echo date('d M Y H:i', strtotime($jadwal['created_at'])); ?></p>
                        <hr>
                    <?php endif; ?>
                    
                    <p class="mb-2"><i class="fas fa-info-circle text-info"></i> <strong>Catatan:</strong></p>
                    <ul class="small">
                        <?php if ($has_urutan_column): ?>
                            <li>Urutan tidak bisa diubah di sini</li>
                            <li>Gunakan tombol Move Up/Down di halaman utama</li>
                        <?php endif; ?>
                        <li>Perubahan langsung tersimpan</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
