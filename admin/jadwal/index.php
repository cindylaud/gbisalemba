<?php
require_once '../includes/auth.php';
require_once '../../config/database.php';

// Handle toggle status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    
    $stmt = $conn->prepare("SELECT status FROM jadwal WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $new_status = ($row['status'] == 'aktif') ? 'nonaktif' : 'aktif';
        
        $stmt_update = $conn->prepare("UPDATE jadwal SET status = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_status, $id);
        $stmt_update->execute();
        $stmt_update->close();
        
        header("Location: index.php?success=Status berhasil diubah");
        exit;
    }
    
    $stmt->close();
}

// Get all jadwal ordered by urutan
$stmt = $conn->prepare("SELECT * FROM jadwal ORDER BY urutan ASC");
$stmt->execute();
$result = $stmt->get_result();
$jadwal_list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Kelola Jadwal</h1>
        <a href="tambah.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Jadwal
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
                    <h5 class="text-muted">Belum ada jadwal</h5>
                    <p class="text-muted">Silakan tambah jadwal baru</p>
                    <a href="tambah.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Jadwal
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">Judul</th>
                                <th width="12%">Kategori</th>
                                <th width="8%">Hari</th>
                                <th width="10%">Jam</th>
                                <th width="12%">Lokasi</th>
                                <th width="8%">Status</th>
                                <th width="8%">Urutan</th>
                                <th width="17%">Aksi</th>
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
                                        <strong><?php echo htmlspecialchars($jadwal['judul']); ?></strong>
                                        <?php if (!empty($jadwal['catatan'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($jadwal['catatan']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($jadwal['kategori']); ?></td>
                                    <td><?php echo htmlspecialchars($jadwal['hari']); ?></td>
                                    <td>
                                        <?php 
                                        echo date('H:i', strtotime($jadwal['jam_mulai'])) . ' - ' . 
                                             date('H:i', strtotime($jadwal['jam_selesai'])); 
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($jadwal['lokasi'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($jadwal['status'] == 'aktif'): ?>
                                            <span class="badge badge-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Non-aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo $jadwal['urutan']; ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <!-- Move Up -->
                                            <?php if ($index > 0): ?>
                                                <a href="move.php?id=<?php echo $jadwal['id']; ?>&action=up" 
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
                                                <a href="move.php?id=<?php echo $jadwal['id']; ?>&action=down" 
                                                   class="btn btn-info" 
                                                   title="Turun">
                                                    <i class="fas fa-arrow-down"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" disabled title="Sudah paling bawah">
                                                    <i class="fas fa-arrow-down"></i>
                                                </button>
                                            <?php endif; ?>

                                            <!-- Toggle Status -->
                                            <a href="?toggle=<?php echo $jadwal['id']; ?>" 
                                               class="btn btn-warning" 
                                               title="Toggle Status"
                                               onclick="return confirm('Ubah status jadwal ini?')">
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
                                               onclick="return confirm('Yakin ingin menghapus jadwal ini?')">
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

<?php include '../includes/footer.php'; ?>
