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

$admin_page_title = 'Kelola Jadwal Ibadah';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .jadwal-page {
        display: grid;
        gap: 16px;
    }

    .admin-alert {
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid transparent;
        opacity: 1;
        transform: translateY(0);
        transition: opacity 0.35s ease, transform 0.35s ease;
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

    .panel-list {
        background: linear-gradient(180deg, #ffffff 0%, #f8fbfe 100%);
        border: 1px solid rgba(16, 44, 87, 0.1);
        border-radius: 22px;
        padding: 18px;
        box-shadow: 0 12px 26px rgba(15, 39, 66, 0.08);
    }

    .panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
        padding-bottom: 14px;
        border-bottom: 1px solid rgba(16, 44, 87, 0.1);
    }

    .panel-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #102c57;
        font-size: 22px;
        font-weight: 800;
        margin: 0;
    }

    .panel-title i {
        color: #146c94;
        font-size: 24px;
    }

    .jadwal-table-wrap {
        border: 1px solid rgba(16, 44, 87, 0.08);
        border-radius: 14px;
        overflow: auto;
        background: #fff;
    }

    .jadwal-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }

    .jadwal-table thead th {
        background: linear-gradient(180deg, #eff7fb 0%, #e4f0f5 100%);
        color: #1e3a5f;
        padding: 12px 14px;
        border-bottom: 1px solid #d9e9ef;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        white-space: nowrap;
    }

    .jadwal-table tbody td {
        padding: 12px 14px;
        font-size: 14px;
        color: #344054;
        border-bottom: 1px solid #edf3f8;
        vertical-align: middle;
    }

    .jadwal-table tbody tr:hover {
        background: rgba(63, 182, 168, 0.06);
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

    .jadwal-table th.col-aksi,
    .jadwal-table td.col-aksi {
        min-width: 152px;
    }

    .jadwal-name {
        font-weight: 700;
        color: #243b5f;
    }

    .jadwal-photo-thumb {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        object-fit: cover;
        object-position: center;
        border: 1px solid rgba(16, 44, 87, 0.14);
        background: #eef4f8;
    }

    .jadwal-photo-empty {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px dashed rgba(16, 44, 87, 0.18);
        background: #f4f8fc;
        color: #9ab1c8;
        font-size: 18px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 5px 11px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }

    .status-badge.active {
        color: #1f6a31;
        background: rgba(47, 158, 68, 0.16);
    }

    .status-badge.inactive {
        color: #4f5963;
        background: rgba(108, 117, 125, 0.15);
    }

    .urutan-control {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        white-space: nowrap;
    }

    .urutan-value {
        min-width: 30px;
        height: 28px;
        border-radius: 8px;
        border: 1px solid rgba(16, 44, 87, 0.16);
        background: #f8fbfe;
        color: #243b5f;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 7px;
    }

    .btn-order-mini {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        border: 1px solid rgba(79, 89, 99, 0.35);
        background: linear-gradient(135deg, #8592a0 0%, #707d8a 100%);
        color: #fff;
        font-size: 10px;
        box-shadow: 0 5px 10px rgba(16, 44, 87, 0.12);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .btn-order-mini:hover {
        text-decoration: none;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 8px 14px rgba(16, 44, 87, 0.16);
    }

    .btn-order-mini.is-disabled {
        background: #c4ccd5;
        border-color: #c4ccd5;
        box-shadow: none;
        pointer-events: none;
        cursor: not-allowed;
    }

    .actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        flex-wrap: nowrap;
        white-space: nowrap;
    }

    .btn-action {
        width: 34px;
        height: 30px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        border: 1px solid transparent;
        color: #fff;
        font-size: 12px;
        box-shadow: 0 6px 12px rgba(16, 44, 87, 0.12);
        transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
    }

    .btn-action:hover {
        text-decoration: none;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 8px 14px rgba(16, 44, 87, 0.16);
        filter: saturate(1.03);
    }

    .btn-order {
        background: linear-gradient(135deg, #8592a0 0%, #707d8a 100%);
        border-color: rgba(79, 89, 99, 0.35);
    }

    .btn-order.is-disabled,
    .btn-order:disabled {
        background: #c4ccd5;
        border-color: #c4ccd5;
        box-shadow: none;
        pointer-events: none;
        cursor: not-allowed;
    }

    .btn-toggle {
        background: linear-gradient(135deg, #f0b434 0%, #d89a1a 100%);
        border-color: rgba(158, 102, 4, 0.45);
        color: #17324d;
        font-weight: 700;
    }

    .btn-toggle:hover {
        color: #17324d;
    }

    .btn-edit {
        background: linear-gradient(135deg, #274d7f 0%, #1d3f6b 100%);
        border-color: rgba(19, 54, 97, 0.5);
    }

    .btn-delete {
        background: linear-gradient(135deg, #e45f5a 0%, #cb3f3a 100%);
        border-color: rgba(165, 40, 34, 0.45);
    }

    .empty-state {
        text-align: center;
        padding: 44px 20px;
        color: #6b7c93;
    }

    .empty-state i {
        font-size: 44px;
        margin-bottom: 12px;
        color: #9ab1c8;
    }

    .empty-state h5 {
        color: #4f6580;
        font-weight: 700;
    }

    .empty-state p {
        margin-bottom: 14px;
    }

    @media (max-width: 992px) {
        .panel-head {
            flex-direction: column;
            align-items: flex-start;
        }

        .panel-title {
            font-size: 20px;
        }

        .jadwal-table tbody td {
            font-size: 13px;
        }
    }

    @media (max-width: 767px) {
        .panel-list {
            padding: 14px;
            border-radius: 16px;
        }

        .jadwal-table-wrap {
            border: 0;
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
            border: 1px solid rgba(16, 44, 87, 0.1);
            border-radius: 14px;
            padding: 10px;
            margin-bottom: 10px;
            box-shadow: 0 8px 16px rgba(15, 39, 66, 0.05);
        }

        .jadwal-table tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #edf3f8;
            text-align: left !important;
            font-size: 13px;
        }

        .jadwal-table tbody td:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .jadwal-table tbody td::before {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #7388a2;
        }

        .jadwal-table tbody td:nth-child(1)::before { content: 'No'; }
        .jadwal-table tbody td:nth-child(2)::before { content: 'Nama Ibadah'; }
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

        .jadwal-table tbody td:nth-child(10) .actions {
            display: flex;
            gap: 8px;
            width: 100%;
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
        </div>
        <?php if (empty($jadwal_list)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h5>Belum ada jadwal ibadah</h5>
                <p>Belum ada data jadwal. Silakan hubungi developer jika perlu menambah jadwal baru.</p>
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


