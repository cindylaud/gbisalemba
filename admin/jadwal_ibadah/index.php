<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Check if column 'urutan' exists in jadwal_ibadah table
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

// Determine ORDER BY clause
if ($has_urutan_column) {
    $order_by = "ORDER BY urutan ASC";
} else {
    $order_by = "ORDER BY id DESC";
}

// Get all jadwal_ibadah
$query = "SELECT * FROM jadwal_ibadah {$order_by}";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$jadwal_list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Kelola Jadwal Ibadah</h1>
        <a href="tambah.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Jadwal Ibadah
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <?php if (empty($jadwal_list)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum ada jadwal ibadah</h5>
                    <p class="text-muted">Silakan tambah jadwal ibadah baru</p>
                    <a href="tambah.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Jadwal Ibadah
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">Nama Ibadah</th>
                                <?php if ($has_kategori_column): ?>
                                    <th width="12%">Kategori</th>
                                <?php endif; ?>
                                <th width="10%">Hari</th>
                                <th width="12%">Jam</th>
                                <th width="12%">Ruangan</th>
                                <th width="15%">Keterangan</th>
                                <th width="8%">Status</th>
                                <?php if ($has_urutan_column): ?>
                                    <th width="6%">Urutan</th>
                                <?php endif; ?>
                                <th width="<?php echo $has_urutan_column ? '20%' : '16%'; ?>">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $total_rows = count($jadwal_list);
                            foreach ($jadwal_list as $index => $jadwal): 
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($jadwal['nama_ibadah']); ?></strong>
                                    </td>
                                    <?php if ($has_kategori_column): ?>
                                        <td><?php echo htmlspecialchars($jadwal['kategori'] ?? '-'); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($jadwal['hari']); ?></td>
                                    <td><?php echo htmlspecialchars($jadwal['jam']); ?></td>
                                    <td><?php echo htmlspecialchars($jadwal['ruangan'] ?? '-'); ?></td>
                                    <td>
                                        <?php 
                                        $keterangan = $jadwal['keterangan'] ?? '';
                                        if (strlen($keterangan) > 50) {
                                            echo htmlspecialchars(substr($keterangan, 0, 50)) . '...';
                                        } else {
                                            echo htmlspecialchars($keterangan);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($jadwal['is_active'] == 1): ?>
                                            <span class="badge badge-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Non-aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($has_urutan_column): ?>
                                        <td class="text-center"><?php echo $jadwal['urutan'] ?? '-'; ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if ($has_urutan_column): ?>
                                                <!-- Move Up -->
                                                <?php if ($index > 0): ?>
                                                    <a href="move.php?id=<?php echo $jadwal['id']; ?>&dir=up" 
                                                       class="btn btn-info" 
                                                       title="Naik">
                                                        <i class="fas fa-arrow-up"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary" disabled title="Sudah paling atas">
                                                        <i class="fas fa-arrow-up"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <!-- Move Down -->
                                                <?php if ($index < $total_rows - 1): ?>
                                                    <a href="move.php?id=<?php echo $jadwal['id']; ?>&dir=down" 
                                                       class="btn btn-info" 
                                                       title="Turun">
                                                        <i class="fas fa-arrow-down"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary" disabled title="Sudah paling bawah">
                                                        <i class="fas fa-arrow-down"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <!-- Toggle Status -->
                                            <a href="toggle.php?id=<?php echo $jadwal['id']; ?>" 
                                               class="btn btn-warning" 
                                               title="Toggle Status"
                                               onclick="return confirm('Ubah status jadwal ibadah ini?')">
                                                <i class="fas fa-power-off"></i>
                                            </a>

                                            <!-- Edit -->
                                            <a href="edit.php?id=<?php echo $jadwal['id']; ?>" 
                                               class="btn btn-primary" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <!-- Delete -->
                                            <a href="delete.php?id=<?php echo $jadwal['id']; ?>" 
                                               class="btn btn-danger" 
                                               title="Hapus"
                                               onclick="return confirm('Yakin ingin menghapus jadwal ibadah ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>


