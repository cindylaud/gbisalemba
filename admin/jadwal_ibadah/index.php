<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_table_bootstrap.php';

$table_state = ensureJadwalIbadahTable($conn);
$has_urutan_column = (bool) ($table_state['has_urutan_column'] ?? false);

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

$total_count = count($jadwal_list);
$active_count = 0;
$inactive_count = 0;
$image_count = 0;

foreach ($jadwal_list as $jadwal_row) {
    if ((int) ($jadwal_row['is_active'] ?? 0) === 1) {
        $active_count++;
    } else {
        $inactive_count++;
    }

    if (!empty($jadwal_row['image'])) {
        $image_count++;
    }
}

$admin_page_title = 'Kelola Jadwal Ibadah';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .jadwal-page {
        display: grid;
        gap: 16px;
    }

    .panel-list {
        background: #fff;
        border: 1px solid rgba(16, 44, 87, 0.08);
        box-shadow: 0 10px 24px rgba(15, 39, 66, 0.05);
    }

    /* ALERT */
    .admin-alert {
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid transparent;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .admin-alert.is-hiding {
        opacity: 0;
        transform: translateY(-6px);
    }

    .admin-alert.success {
        background: #dff4e6;
        color: #1f6a31;
        border-color: #bee9cb;
    }

    .admin-alert.error {
        background: #fde8e8;
        color: #8d2d2d;
        border-color: #f8c7c7;
    }

    /* PANEL */
    .panel-list {
        border-radius: 22px;
        padding: 18px;
    }

    .panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 0;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(16, 44, 87, 0.08);
    }

    .panel-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #102c57;
        font-size: 20px;
        font-weight: 700;
        margin: 0;
    }

    .panel-title i {
        color: #146c94;
        font-size: 18px;
    }

    .panel-head-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-left: auto;
    }

    /* BUTTON TAMBAH */
    .btn-add-jadwal {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 8px;
        background: #102c57;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .btn-add-jadwal:hover {
        background: #163b70;
        color: #fff;
        text-decoration: none;
    }

    .panel-head-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        color: rgba(72, 97, 131, 0.9);
        font-size: 12px;
        font-weight: 600;
    }

    .panel-head-meta i {
        color: #146c94;
    }

    /* TABLE */
    .jadwal-table-wrap {
        margin-top: 16px;
        border: 1px solid rgba(16, 44, 87, 0.08);
        border-radius: 18px;
        overflow-x: auto;
        background: #fff;
    }

    .jadwal-table {
        width: 100%;
        border-collapse: collapse;
    }

    .jadwal-table thead th {
        background: #f5f8fb;
        color: #486581;
        padding: 11px 12px;
        font-size: 11px;
        font-weight: 700;
        border-bottom: 1px solid #e6edf3;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .jadwal-table tbody td {
        padding: 12px;
        font-size: 13px;
        color: #344054;
        border-bottom: 1px solid #eef2f6;
        vertical-align: middle;
    }

    .jadwal-table tbody tr:nth-child(odd) {
        background: rgba(248, 251, 254, 0.78);
    }

    .jadwal-table tbody tr:hover {
        background: rgba(20, 108, 148, 0.05);
    }

    .jadwal-table tbody tr:last-child td {
        border-bottom: none;
    }

    .jadwal-table td.col-no,
    .jadwal-table td.col-status,
    .jadwal-table td.col-urutan,
    .jadwal-table td.col-aksi {
        text-align: center;
    }

    .jadwal-name {
        font-weight: 600;
        color: #243b5f;
    }

    .jadwal-table td:nth-child(2) {
        font-weight: 600;
        color: #243b5f;
    }

    /* FOTO */
    .jadwal-photo-thumb {
        width: 52px;
        height: 52px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid rgba(16, 44, 87, 0.1);
        background: #eef4f8;
    }

    .jadwal-photo-empty {
        width: 52px;
        height: 52px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px dashed rgba(16, 44, 87, 0.16);
        background: #f4f8fc;
        color: #9ab1c8;
        font-size: 16px;
    }

    /* STATUS */
    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .status-badge.active {
        color: #1f6a31;
        background: rgba(47, 158, 68, 0.14);
    }

    .status-badge.inactive {
        color: #4f5963;
        background: rgba(108, 117, 125, 0.12);
    }

    /* URUTAN */
    .urutan-control {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }

    .urutan-value {
        min-width: 28px;
        height: 28px;
        border-radius: 7px;
        border: 1px solid rgba(16, 44, 87, 0.12);
        background: #f8fbfe;
        color: #243b5f;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 6px;
    }

    .btn-order-mini {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        border: 1px solid rgba(79, 89, 99, 0.22);
        background: #fff;
        color: #5b6572;
        font-size: 10px;
        transition: 0.2s ease;
    }

    .btn-order-mini:hover {
        background: #f1f5f9;
        color: #102c57;
        text-decoration: none;
    }

    .btn-order-mini.is-disabled {
        opacity: 0.45;
        pointer-events: none;
        cursor: not-allowed;
    }

    /* ACTIONS */
    .actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .btn-action {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        border: none;
        color: #fff;
        font-size: 12px;
        transition: 0.2s ease;
    }

    .btn-action:hover {
        transform: translateY(-1px);
        text-decoration: none;
        color: #fff;
    }

    .btn-toggle {
        background: #e7b13d;
    }

    .btn-edit {
        background: #1f466f;
    }

    .btn-delete {
        background: #d9534f;
    }

    /* EMPTY */
    .empty-state {
        text-align: center;
        padding: 36px 16px;
        color: #6b7c93;
    }

    .empty-state i {
        font-size: 36px;
        margin-bottom: 10px;
        color: #9ab1c8;
    }

    .empty-state h5 {
        color: #4f6580;
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .empty-state p {
        margin-bottom: 0;
        font-size: 13px;
    }

    /* TABLET */
    @media (max-width: 992px) {
        .panel-head {
            flex-direction: column;
            align-items: flex-start;
        }

        .jadwal-table tbody td {
            font-size: 12px;
        }
    }

    /* MOBILE */
    @media (max-width: 767px) {
        .panel-list {
            padding-top: 14px;
            padding-bottom: 14px;
        }

        .panel-title {
            font-size: 18px;
        }

        .jadwal-table-wrap {
            border: none;
            background: transparent;
            overflow: visible;
        }

        .jadwal-table,
        .jadwal-table thead,
        .jadwal-table tbody,
        .jadwal-table tr,
        .jadwal-table th,
        .jadwal-table td {
            display: block;
            width: 100%;
        }

        .jadwal-table thead {
            display: none;
        }

        .jadwal-table tbody tr {
            background: #fff;
            border: 1px solid rgba(16, 44, 87, 0.08);
            border-radius: 14px;
            padding: 12px;
            margin-bottom: 10px;
        }

        .jadwal-table tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 9px 0;
            border-bottom: 1px solid #eef2f6;
            text-align: left !important;
            font-size: 12px;
        }

        .jadwal-table tbody td:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .jadwal-table tbody td::before {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #7388a2;
        }

        .jadwal-table tbody td:nth-child(1)::before { content: 'No'; }
        .jadwal-table tbody td:nth-child(2)::before { content: 'Nama'; }
        .jadwal-table tbody td:nth-child(3)::before { content: 'Foto'; }
        .jadwal-table tbody td:nth-child(4)::before { content: 'Hari'; }
        .jadwal-table tbody td:nth-child(5)::before { content: 'Jam'; }
        .jadwal-table tbody td:nth-child(6)::before { content: 'Ruangan'; }
        .jadwal-table tbody td:nth-child(7)::before { content: 'Keterangan'; }
        .jadwal-table tbody td:nth-child(8)::before { content: 'Status'; }
        .jadwal-table tbody td:nth-child(9)::before { content: 'Urutan'; }
        .jadwal-table tbody td:nth-child(10)::before { content: 'Aksi'; }

        .jadwal-table tbody td:nth-child(10) {
            display: block;
        }

        .jadwal-table tbody td:nth-child(10)::before {
            display: block;
            margin-bottom: 8px;
        }

        .actions {
            justify-content: flex-start;
        }
    }
</style>

<div class="jadwal-page">
    <?php if (isset($_GET['success'])): ?>
        <div class="admin-alert success" role="alert">
            <i class="fas fa-circle-check"></i>
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="admin-alert error" role="alert">
            <i class="fas fa-triangle-exclamation"></i>
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <div class="panel-list">
        <div class="panel-head">
            <div class="panel-title"><i class="fas fa-calendar-week"></i> Daftar Jadwal Ibadah</div>
            <div class="panel-head-actions">
                <div class="panel-head-meta">
                    <i class="fas fa-layer-group"></i>
                    <span><?php echo (int) $total_count; ?> data</span>
                </div>
                <a href="tambah.php" class="btn-add-jadwal">
                    <i class="fas fa-plus"></i> Tambah Jadwal
                </a>
            </div>
        </div>

        <?php if (empty($jadwal_list)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h5>Belum ada jadwal ibadah</h5>
                <p>Belum ada data jadwal. Gunakan tombol "Tambah Jadwal" untuk membuat jadwal baru.</p>
            </div>
        <?php else: ?>
            <div class="jadwal-table-wrap">
                <table class="jadwal-table">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="18%">Nama Ibadah</th>
                            <th width="9%">Foto</th>
                            <th width="10%">Hari</th>
                            <th width="12%">Jam</th>
                            <th width="12%">Ruangan</th>
                            <th width="13%">Keterangan</th>
                            <th width="8%">Status</th>
                            <th width="12%">Urutan</th>
                            <th class="col-aksi" width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $total_rows = count($jadwal_list);
                        foreach ($jadwal_list as $index => $jadwal):
                        ?>
                            <tr>
                                <td class="col-no"><?php echo $no++; ?></td>
                                <td>
                                    <span class="jadwal-name"><?php echo htmlspecialchars($jadwal['nama_ibadah']); ?></span>
                                </td>
                                <td class="col-no">
                                    <?php if (!empty($jadwal['image']) && file_exists(__DIR__ . '/../../uploads/jadwal/' . $jadwal['image'])): ?>
                                        <img
                                            src="../../uploads/jadwal/<?php echo rawurlencode($jadwal['image']); ?>"
                                            alt="Foto <?php echo htmlspecialchars($jadwal['nama_ibadah']); ?>"
                                            class="jadwal-photo-thumb"
                                            style="object-fit: <?php echo htmlspecialchars((string) ($jadwal['image_fit'] ?? 'cover')); ?>; object-position: center <?php echo max(0, min(100, (int) ($jadwal['image_pos_y'] ?? 50))); ?>%;"
                                        >
                                    <?php else: ?>
                                        <span class="jadwal-photo-empty"><i class="fas fa-image"></i></span>
                                    <?php endif; ?>
                                </td>
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
                                <td class="col-status">
                                    <?php if ($jadwal['is_active'] == 1): ?>
                                        <span class="status-badge active">Aktif</span>
                                    <?php else: ?>
                                        <span class="status-badge inactive">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-urutan">
                                    <div class="urutan-control">
                                        <span class="urutan-value"><?php echo $jadwal['urutan'] ?? '-'; ?></span>
                                        <?php if ($index > 0): ?>
                                            <a href="move.php?id=<?php echo $jadwal['id']; ?>&dir=up"
                                               class="btn-order-mini"
                                               title="Naik">
                                                <i class="fas fa-arrow-up"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="btn-order-mini is-disabled" title="Sudah paling atas">
                                                <i class="fas fa-arrow-up"></i>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($index < $total_rows - 1): ?>
                                            <a href="move.php?id=<?php echo $jadwal['id']; ?>&dir=down"
                                               class="btn-order-mini"
                                               title="Turun">
                                                <i class="fas fa-arrow-down"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="btn-order-mini is-disabled" title="Sudah paling bawah">
                                                <i class="fas fa-arrow-down"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="col-aksi">
                                    <div class="actions">
                                        <a href="toggle.php?id=<?php echo $jadwal['id']; ?>"
                                           class="btn-action btn-toggle"
                                           title="Toggle Status"
                                           onclick="return confirm('Ubah status jadwal ibadah ini?')">
                                            <i class="fas fa-power-off"></i>
                                        </a>

                                        <a href="edit.php?id=<?php echo $jadwal['id']; ?>"
                                           class="btn-action btn-edit"
                                           title="Edit">
                                            <i class="fas fa-pen-to-square"></i>
                                        </a>

                                        <a href="delete.php?id=<?php echo $jadwal['id']; ?>"
                                           class="btn-action btn-delete"
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var alerts = document.querySelectorAll('.admin-alert');
        alerts.forEach(function (alertBox) {
            setTimeout(function () {
                alertBox.classList.add('is-hiding');
                setTimeout(function () {
                    if (alertBox && alertBox.parentNode) {
                        alertBox.parentNode.removeChild(alertBox);
                    }
                }, 350);
            }, 5000);
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>


