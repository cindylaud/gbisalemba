<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/image-helper.php';

$admin_page_title = 'Kelola Pelayanan';
$uploadDir = __DIR__ . '/../../uploads/pelayanan/';

if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

$success = $_GET['success'] ?? '';
$error = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
	$id = (int) ($_POST['id'] ?? 0);
	if ($id <= 0) {
		$error = 'ID tidak valid.';
	} else {
		$stmt = $conn->prepare('SELECT foto FROM pelayanan WHERE id = ? LIMIT 1');
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result ? $result->fetch_assoc() : null;
		$stmt->close();

		if (!$row) {
			$error = 'Data tidak ditemukan.';
		} else {
			$stmtDel = $conn->prepare('DELETE FROM pelayanan WHERE id = ?');
			if ($stmtDel) {
				$stmtDel->bind_param('i', $id);
				if ($stmtDel->execute()) {
					if (!empty($row['foto']) && is_file($uploadDir . $row['foto'])) {
						@unlink($uploadDir . $row['foto']);
					}
					$stmtDel->close();
					header('Location: index.php?success=deleted');
					exit;
				}
				$error = 'Gagal menghapus data.';
				$stmtDel->close();
			}
		}
	}
}

// Load pelayanan list
$pelayananList = [];
$query = $conn->query('SELECT id, judul, deskripsi, foto, status FROM pelayanan ORDER BY urutan ASC, id ASC');
if ($query) {
	while ($row = $query->fetch_assoc()) {
		$pelayananList[] = $row;
	}
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .pelayanan-page { display: grid; gap: 16px; }
    .admin-alert { border-radius: 12px; padding: 12px 14px; font-size: 13px; font-weight: 600; border: 1px solid transparent; display: flex; align-items: center; gap: 8px; }
    .admin-alert.success { background: #dff4e6; color: #1f6a31; border-color: #bee9cb; }
    .admin-alert.error { background: #fde8e8; color: #8d2d2d; border-color: #f8c7c7; }
    .panel-list { background: #fff; border: 1px solid rgba(16, 44, 87, 0.08); border-radius: 22px; padding: 18px; box-shadow: 0 10px 24px rgba(15, 39, 66, 0.05); }
    .panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid rgba(16, 44, 87, 0.08); }
    .panel-title { display: flex; align-items: center; gap: 8px; color: #102c57; font-size: 18px; font-weight: 700; margin: 0; }
    .panel-title i { color: #146c94; font-size: 16px; }
    .panel-head-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-left: auto; }
    .btn-add-pelayanan { display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 8px; background: #102c57; color: #fff; font-size: 12px; font-weight: 600; text-decoration: none; transition: 0.2s ease; }
    .btn-add-pelayanan:hover { background: #163b70; color: #fff; text-decoration: none; }
    .panel-subtitle { margin: 0; font-size: 13px; color: #6b7c93; font-weight: 500; }
    .pelayanan-table-wrap { overflow-x: auto; border: 1px solid rgba(16, 44, 87, 0.08); border-radius: 18px; margin-top: 12px; }
    .pelayanan-table { width: 100%; border-collapse: collapse; }
    .pelayanan-table thead th { background: #f5f8fb; color: #486581; padding: 11px 12px; font-size: 11px; font-weight: 700; border-bottom: 1px solid #e6edf3; text-transform: uppercase; white-space: nowrap; }
    .pelayanan-table tbody td { padding: 12px; font-size: 13px; color: #344054; border-bottom: 1px solid #eef2f6; vertical-align: middle; }
    .pelayanan-table tbody tr:nth-child(odd) { background: rgba(248, 251, 254, 0.78); }
    .pelayanan-table tbody tr:hover { background: rgba(20, 108, 148, 0.05); }
    .btn-action { display: inline-flex; align-items: center; gap: 5px; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: 0.2s ease; }
    .btn-edit { background: #e8f0fa; color: #146c94; border-color: rgba(20, 108, 148, 0.2); }
    .btn-edit:hover { background: #d4e5f5; color: #146c94; text-decoration: none; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(20, 108, 148, 0.15); }
    .btn-delete { background: #fde8e8; color: #8d2d2d; border-color: rgba(201, 96, 96, 0.2); }
    .btn-delete:hover { background: #fcd0d0; color: #8d2d2d; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(201, 96, 96, 0.15); }
    .status-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
    .status-badge.aktif { background: rgba(47, 158, 68, 0.15); color: #1f6a31; }
    .status-badge.nonaktif { background: rgba(220, 53, 69, 0.15); color: #8d2d2d; }
    @media (max-width: 768px) {
        .panel-head { flex-direction: column; align-items: flex-start; }
        .panel-head-actions { margin-left: 0; width: 100%; }
        .pelayanan-table { font-size: 12px; }
        .pelayanan-table th, .pelayanan-table td { padding: 8px; }
    }
</style>

<div class="pelayanan-page">
    <?php if ($error !== ''): ?>
        <div class="admin-alert error" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success === 'deleted'): ?>
        <div class="admin-alert success" role="alert">
            <i class="fas fa-check-circle"></i> Pelayanan berhasil dihapus.
        </div>
    <?php endif; ?>

    <div class="panel-list">
        <div class="panel-head">
            <h2 class="panel-title"><i class="fas fa-hands-helping"></i> Kelola Pelayanan</h2>
            <div class="panel-head-actions">
                <a href="tambah.php" class="btn-add-pelayanan">
                    <i class="fas fa-plus"></i> Tambah Pelayanan
                </a>
            </div>
        </div>
        <p class="panel-subtitle">Kelola layanan jemaat dan atur status.</p>

        <?php if (empty($pelayananList)): ?>
            <div style="text-align: center; padding: 40px 20px; color: #6b7c93;">
                <div style="font-size: 40px; margin-bottom: 10px;"><i class="fas fa-hands-helping"></i></div>
                <p>Belum ada data pelayanan.</p>
            </div>
        <?php else: ?>
            <div class="pelayanan-table-wrap">
                <table class="pelayanan-table">
                    <thead>
                        <tr>
                            <th style="min-width: 140px;">Judul</th>
                            <th>Deskripsi</th>
                            <th style="width: 90px;">Status</th>
                            <th style="width: 180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pelayananList as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['judul']); ?></strong></td>
                                <td><?php echo htmlspecialchars(substr($item['deskripsi'], 0, 80)) . (strlen($item['deskripsi']) > 80 ? '...' : ''); ?></td>
                                <td>
                                    <span class="status-badge <?php echo ($item['status'] ?? '') === 'aktif' ? 'aktif' : 'nonaktif'; ?>">
                                        <?php echo ($item['status'] ?? '') === 'aktif' ? 'Aktif' : 'Nonaktif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px; align-items: center;">
                                        <a href="edit.php?id=<?php echo (int) $item['id']; ?>" class="btn-action btn-edit" style="flex: 1; text-align: center; padding: 7px 8px; font-size: 11px;">
                                            <i class="fas fa-pen"></i> Edit
                                        </a>
                                        <form method="post" style="flex: 1;" onsubmit="return confirm('Yakin ingin menghapus pelayanan ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                                            <button type="submit" class="btn-action btn-delete" style="width: 100%; padding: 7px 8px; font-size: 11px;">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
