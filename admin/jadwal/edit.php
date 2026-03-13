<?php
require_once '../includes/auth.php';
require_once '../../config/database.php';

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
    $judul = trim($_POST['judul'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $hari = trim($_POST['hari'] ?? '');
    $jam_mulai = trim($_POST['jam_mulai'] ?? '');
    $jam_selesai = trim($_POST['jam_selesai'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');
    $status = $_POST['status'] ?? 'aktif';

    // Validation
    if (empty($judul) || empty($kategori) || empty($hari) || empty($jam_mulai) || empty($jam_selesai)) {
        $error = 'Semua field wajib diisi kecuali lokasi dan catatan';
    } else {
        // Update jadwal
        $stmt = $conn->prepare("UPDATE jadwal SET judul = ?, kategori = ?, hari = ?, jam_mulai = ?, jam_selesai = ?, lokasi = ?, catatan = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssssssi", $judul, $kategori, $hari, $jam_mulai, $jam_selesai, $lokasi, $catatan, $status, $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: index.php?success=Jadwal berhasil diperbarui");
            exit;
        } else {
            $error = 'Gagal memperbarui jadwal: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

// Get jadwal data
$stmt = $conn->prepare("SELECT * FROM jadwal WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    header("Location: index.php?error=Jadwal tidak ditemukan");
    exit;
}

$jadwal = $result->fetch_assoc();
$stmt->close();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Jadwal</h1>
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
                            <label for="judul">Judul <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="judul" name="judul" 
                                   value="<?php echo htmlspecialchars($_POST['judul'] ?? $jadwal['judul']); ?>" 
                                   required maxlength="150">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="kategori">Kategori <span class="text-danger">*</span></label>
                                    <select class="form-control" id="kategori" name="kategori" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <?php
                                        $selected_kategori = $_POST['kategori'] ?? $jadwal['kategori'];
                                        $kategori_list = ['Ibadah Umum', 'Doa dan Puasa', 'Pemahaman Alkitab', 'Kelompok Usia', 'Persekutuan Khusus', 'Kegiatan Lainnya'];
                                        foreach ($kategori_list as $kat) {
                                            $selected = ($selected_kategori == $kat) ? 'selected' : '';
                                            echo "<option value=\"{$kat}\" {$selected}>{$kat}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

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
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jam_mulai">Jam Mulai <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="jam_mulai" name="jam_mulai" 
                                           value="<?php echo htmlspecialchars($_POST['jam_mulai'] ?? $jadwal['jam_mulai']); ?>" 
                                           required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jam_selesai">Jam Selesai <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="jam_selesai" name="jam_selesai" 
                                           value="<?php echo htmlspecialchars($_POST['jam_selesai'] ?? $jadwal['jam_selesai']); ?>" 
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="lokasi">Lokasi</label>
                            <input type="text" class="form-control" id="lokasi" name="lokasi" 
                                   value="<?php echo htmlspecialchars($_POST['lokasi'] ?? $jadwal['lokasi']); ?>" 
                                   maxlength="150">
                        </div>

                        <div class="form-group">
                            <label for="catatan">Catatan</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3"><?php echo htmlspecialchars($_POST['catatan'] ?? $jadwal['catatan']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-control" id="status" name="status" required>
                                <?php
                                $selected_status = $_POST['status'] ?? $jadwal['status'];
                                ?>
                                <option value="aktif" <?php echo ($selected_status == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                                <option value="nonaktif" <?php echo ($selected_status == 'nonaktif') ? 'selected' : ''; ?>>Non-aktif</option>
                            </select>
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
                    <p class="mb-2"><strong>Urutan saat ini:</strong></p>
                    <p class="text-muted"><?php echo $jadwal['urutan']; ?></p>
                    
                    <hr>
                    
                    <p class="mb-2"><strong>Dibuat pada:</strong></p>
                    <p class="text-muted"><?php echo date('d M Y H:i', strtotime($jadwal['created_at'])); ?></p>
                    
                    <hr>
                    
                    <p class="mb-2"><i class="fas fa-info-circle text-info"></i> <strong>Catatan:</strong></p>
                    <ul class="small">
                        <li>Urutan tidak bisa diubah di sini</li>
                        <li>Gunakan tombol Move Up/Down di halaman utama</li>
                        <li>Perubahan langsung tersimpan</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
