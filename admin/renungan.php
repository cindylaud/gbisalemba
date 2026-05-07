<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

$admin_page_title = 'Kelola Renungan';
$uploadDir = __DIR__ . '/../uploads/renungan/';

if (!is_dir($uploadDir)) {
	@mkdir($uploadDir, 0755, true);
}

$success = $_GET['success'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
	$id = (int) ($_POST['id'] ?? 0);

	if ($id <= 0) {
		$error = 'ID renungan tidak valid.';
	} else {
		$stmtFind = $conn->prepare('SELECT gambar FROM renungan WHERE id = ? LIMIT 1');

		if ($stmtFind) {
			$stmtFind->bind_param('i', $id);
			$stmtFind->execute();
			$resultFind = $stmtFind->get_result();
			$row = $resultFind ? $resultFind->fetch_assoc() : null;
			$stmtFind->close();

			if (!$row) {
				$error = 'Data renungan tidak ditemukan.';
			} else {
				$stmtDelete = $conn->prepare('DELETE FROM renungan WHERE id = ?');
				if ($stmtDelete) {
					$stmtDelete->bind_param('i', $id);
					if ($stmtDelete->execute()) {
						if (!empty($row['gambar'])) {
							$oldPath = $uploadDir . $row['gambar'];
							if (is_file($oldPath)) {
								@unlink($oldPath);
							}
						}

						$stmtDelete->close();
						header('Location: renungan.php?success=deleted');
						exit;
					}

					$error = 'Gagal menghapus renungan.';
					$stmtDelete->close();
				} else {
					$error = 'Query hapus tidak bisa dijalankan.';
				}
			}
		} else {
			$error = 'Query pencarian tidak bisa dijalankan.';
		}
	}
}

$renunganList = [];
$query = $conn->query('SELECT id, judul, ayat, isi, tanggal, gambar, created_at FROM renungan ORDER BY tanggal DESC, id DESC');
if ($query) {
	while ($row = $query->fetch_assoc()) {
		$renunganList[] = $row;
	}
}

include __DIR__ . '/includes/header.php';
?>

<style>
    .renungan-page {
        display: grid;
        gap: 16px;
    }

    .admin-alert {
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid transparent;
        display: flex;
        align-items: center;
        gap: 8px;
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
        background: #fff;
        border: 1px solid rgba(16, 44, 87, 0.08);
        border-radius: 22px;
        padding: 18px;
        box-shadow: 0 10px 24px rgba(15, 39, 66, 0.05);
    }

    .panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(16, 44, 87, 0.08);
    }

    .panel-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #102c57;
        font-size: 18px;
        font-weight: 700;
        margin: 0;
    }

    .panel-title i {
        color: #146c94;
        font-size: 16px;
    }

    .panel-head-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-left: auto;
    }

    .btn-add-renungan {
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

    .btn-add-renungan:hover {
        background: #163b70;
        color: #fff;
        text-decoration: none;
    }

    .panel-subtitle {
        margin: 0;
        font-size: 13px;
        color: #6b7c93;
        font-weight: 500;
    }

    .renungan-table-wrap {
        overflow-x: auto;
        border: 1px solid rgba(16, 44, 87, 0.08);
        border-radius: 18px;
        margin-top: 12px;
    }

    .renungan-table {
        width: 100%;
        border-collapse: collapse;
    }

    .renungan-table thead th {
        background: #f5f8fb;
        color: #486581;
        padding: 11px 12px;
        font-size: 11px;
        font-weight: 700;
        border-bottom: 1px solid #e6edf3;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .renungan-table tbody td {
        padding: 12px;
        font-size: 13px;
        color: #344054;
        border-bottom: 1px solid #eef2f6;
        vertical-align: middle;
    }

    .renungan-table tbody tr:nth-child(odd) {
        background: rgba(248, 251, 254, 0.78);
    }

    .renungan-table tbody tr:hover {
        background: rgba(20, 108, 148, 0.05);
    }

    .renungan-table tbody tr.empty-row {
        text-align: center;
        color: #6b7c93;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .btn-edit {
        background: #e8f0fa;
        color: #146c94;
        border-color: rgba(20, 108, 148, 0.2);
    }

    .btn-edit:hover {
        background: #d4e5f5;
        color: #146c94;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(20, 108, 148, 0.15);
    }

    .btn-delete {
        background: #fde8e8;
        color: #8d2d2d;
        border-color: rgba(201, 96, 96, 0.2);
    }

    .btn-delete:hover {
        background: #fcd0d0;
        color: #8d2d2d;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(201, 96, 96, 0.15);
    }

    @media (max-width: 768px) {
        .panel-head {
            flex-direction: column;
            align-items: flex-start;
        }
        .panel-head-actions {
            margin-left: 0;
            width: 100%;
        }
        .renungan-table { font-size: 12px; }
        .renungan-table th, .renungan-table td { padding: 8px; }
    }

    @media (max-width: 640px) {
        .renungan-table-wrap {
            overflow-x: visible;
            border: none;
            border-radius: 0;
            margin-left: -18px;
            margin-right: -18px;
            margin-top: 16px;
            padding: 0;
        }

        .renungan-table {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 0 18px;
        }

        .renungan-table thead {
            display: none;
        }

        .renungan-table tbody {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .renungan-table tbody tr {
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: #f8f9fc !important;
            border: 1px solid rgba(16, 44, 87, 0.1);
            border-radius: 12px;
            padding: 12px;
            margin: 0;
        }

        .renungan-table tbody tr:hover {
            background: #f1f5fa !important;
        }

        .renungan-table tbody td {
            padding: 0;
            border: none;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .renungan-table tbody td::before {
            content: attr(data-label);
            font-size: 11px;
            font-weight: 700;
            color: #7a8fa7;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .renungan-table tbody td:last-child::before {
            content: '';
        }

        .renungan-table tbody td:last-child {
            gap: 8px;
            flex-direction: row;
            margin-top: 4px;
        }

        .renungan-table tbody td:last-child > div {
            display: flex;
            flex-direction: row;
            gap: 6px;
            width: 100%;
        }

        .btn-action {
            flex: 1;
            text-align: center;
            padding: 9px 8px !important;
            font-size: 12px !important;
            min-height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }
</style>

<div class="renungan-page">
    <?php if ($error !== ''): ?>
        <div class="admin-alert error" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success === 'created'): ?>
        <div class="admin-alert success" role="alert">
            <i class="fas fa-check-circle"></i> Renungan berhasil ditambahkan.
        </div>
    <?php elseif ($success === 'updated'): ?>
        <div class="admin-alert success" role="alert">
            <i class="fas fa-check-circle"></i> Renungan berhasil diperbarui.
        </div>
    <?php elseif ($success === 'deleted'): ?>
        <div class="admin-alert success" role="alert">
            <i class="fas fa-check-circle"></i> Renungan berhasil dihapus.
        </div>
    <?php endif; ?>

    <div class="panel-list">
        <div class="panel-head">
            <h2 class="panel-title"><i class="fas fa-book"></i> Kelola Renungan</h2>
            <div class="panel-head-actions">
                <a href="tambah_renungan.php" class="btn-add-renungan">
                    <i class="fas fa-plus"></i> Tambah Renungan
                </a>
            </div>
        </div>

        <?php if (empty($renunganList)): ?>
            <div style="text-align: center; padding: 40px 20px; color: #6b7c93;">
                <div style="font-size: 40px; margin-bottom: 10px;"><i class="fas fa-scroll"></i></div>
                <p>Belum ada data renungan.</p>
            </div>
        <?php else: ?>
            <div class="renungan-table-wrap">
                <table class="renungan-table">
                    <thead>
                        <tr>
                            <th style="min-width: 90px;">Tanggal</th>
                            <th style="min-width: 200px;">Judul</th>
                            <th>Isi Singkat</th>
                            <th style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($renunganList as $item): ?>
                            <tr>
                                <td data-label="Tanggal"><?php echo htmlspecialchars(date('d M Y', strtotime((string) $item['tanggal']))); ?></td>
                                <td data-label="Judul"><?php echo htmlspecialchars($item['judul']); ?></td>
                                <td data-label="Isi Singkat">
                                    <?php
                                    $plain = trim(strip_tags((string) $item['isi']));
                                    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                                        $excerpt = mb_strlen($plain) > 120 ? mb_substr($plain, 0, 120) . '...' : $plain;
                                    } else {
                                        $excerpt = strlen($plain) > 120 ? substr($plain, 0, 120) . '...' : $plain;
                                    }
                                    echo htmlspecialchars($excerpt);
                                    ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px; align-items: center;">
                                        <a href="edit_renungan.php?id=<?php echo (int) $item['id']; ?>" class="btn-action btn-edit" style="flex: 1; text-align: center; padding: 7px 8px; font-size: 11px;">
                                            <i class="fas fa-pen"></i> Edit
                                        </a>
                                        <form method="post" style="flex: 1;" onsubmit="return confirm('Yakin ingin menghapus renungan ini?');">
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

<?php include __DIR__ . '/includes/footer.php'; ?>
