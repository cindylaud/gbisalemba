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

<div class="card p-4">
	<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
		<div>
			<h2 class="h4 mb-1">Kelola Renungan</h2>
			<p class="text-muted mb-0">Tambah, edit, dan hapus renungan jemaat.</p>
		</div>
		<a href="tambah_renungan.php" class="btn btn-primary mt-2 mt-sm-0">
			<i class="fas fa-plus mr-1"></i> Tambah Renungan
		</a>
	</div>

	<?php if ($error !== ''): ?>
		<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
	<?php endif; ?>

	<?php if ($success === 'created'): ?>
		<div class="alert alert-success">Renungan berhasil ditambahkan.</div>
	<?php elseif ($success === 'updated'): ?>
		<div class="alert alert-success">Renungan berhasil diperbarui.</div>
	<?php elseif ($success === 'deleted'): ?>
		<div class="alert alert-success">Renungan berhasil dihapus.</div>
	<?php endif; ?>

	<div class="table-responsive">
		<table class="table table-striped table-hover">
			<thead class="thead-light">
				<tr>
					<th style="min-width: 80px;">Tanggal</th>
					<th style="min-width: 220px;">Judul</th>
					<th style="min-width: 120px;">Ayat</th>
					<th>Isi Singkat</th>
					<th style="width: 180px;">Aksi</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($renunganList)): ?>
					<tr>
						<td colspan="5" class="text-center text-muted py-4">Belum ada data renungan.</td>
					</tr>
				<?php else: ?>
					<?php foreach ($renunganList as $item): ?>
						<tr>
							<td><?php echo htmlspecialchars(date('d M Y', strtotime((string) $item['tanggal']))); ?></td>
							<td><?php echo htmlspecialchars($item['judul']); ?></td>
							<td><?php echo htmlspecialchars($item['ayat']); ?></td>
							<td>
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
								<a href="edit_renungan.php?id=<?php echo (int) $item['id']; ?>" class="btn btn-sm btn-outline-primary mr-1">
									<i class="fas fa-edit"></i> Edit
								</a>
								<form method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus renungan ini?');">
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
									<button type="submit" class="btn btn-sm btn-outline-danger">
										<i class="fas fa-trash"></i> Hapus
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
