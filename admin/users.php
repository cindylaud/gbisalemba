<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/account-utils.php';

$admin_page_title = 'Daftar Admin';
$currentAdminId = (int) ($_SESSION['admin_id'] ?? 0);
$success = gbi_admin_normalize_text($_GET['success'] ?? '');
$error = gbi_admin_normalize_text($_GET['error'] ?? '');
$editId = (int) ($_GET['edit'] ?? 0);
$resetId = (int) ($_GET['reset'] ?? 0);
$editUser = null;
$resetUser = null;
$pendingEditValues = null;

function users_redirect($params = []) {
	$target = 'users.php';
	if (!empty($params)) {
		$target .= '?' . http_build_query($params);
	}
	header('Location: ' . $target);
	exit;
}

function load_admin_user_by_id($conn, $userId) {
	$stmt = $conn->prepare('SELECT id, username, created_at, updated_at FROM users WHERE id = ? LIMIT 1');
	if (!$stmt) {
		return null;
	}

	$stmt->bind_param('i', $userId);
	$stmt->execute();
	$result = $stmt->get_result();
	$user = $result ? $result->fetch_assoc() : null;
	$stmt->close();

	return $user;
}

function format_admin_datetime($value) {
	if (empty($value)) {
		return '-';
	}

	$timestamp = strtotime((string) $value);
	return $timestamp ? date('d-m-Y H:i', $timestamp) : '-';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Keep edit, reset, and delete handling in one request path for simplicity.
	// This reduces the complexity of managing user actions.
	$action = gbi_admin_normalize_text($_POST['action'] ?? '');
	$userId = (int) ($_POST['id'] ?? 0);

	if ($action === 'update_user') {
		$newUsername = gbi_admin_normalize_text($_POST['username'] ?? '');
		$pendingEditValues = [
			'id' => $userId,
			'username' => $newUsername,
		];

		if ($userId <= 0) {
			users_redirect(['error' => 'ID admin tidak valid.']);
		}

		if ($newUsername === '') {
			$error = 'Username tidak boleh kosong.';
			$editId = $userId;
		} elseif (strlen($newUsername) > 50) {
			$error = 'Username maksimal 50 karakter.';
			$editId = $userId;
		} else {
			$stmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
			if (!$stmt) {
				$error = 'Gagal memeriksa username.';
				$editId = $userId;
			} else {
				$stmt->bind_param('si', $newUsername, $userId);
				$stmt->execute();
				$result = $stmt->get_result();
				$duplicateUsername = $result ? $result->fetch_assoc() : null;
				$stmt->close();

				if ($duplicateUsername) {
					$error = 'Username sudah digunakan.';
					$editId = $userId;
				} else {
					$updateStmt = $conn->prepare('UPDATE users SET username = ? WHERE id = ? LIMIT 1');
					if (!$updateStmt) {
						$error = 'Gagal memperbarui admin.';
						$editId = $userId;
					} else {
						$updateStmt->bind_param('si', $newUsername, $userId);
						if ($updateStmt->execute()) {
							$updateStmt->close();
							if ($userId === $currentAdminId) {
								$_SESSION['username'] = $newUsername;
							}
							users_redirect(['success' => 'updated']);
						}
						$updateStmt->close();
						$error = 'Gagal memperbarui admin.';
						$editId = $userId;
					}
				}
			}
		}
	}

	if ($action === 'reset_password') {
		$newPassword = (string) ($_POST['new_password'] ?? '');
		$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

		if ($userId <= 0) {
			users_redirect(['error' => 'ID admin tidak valid.']);
		}

		if (strlen($newPassword) < 8) {
			$error = 'Password baru minimal 8 karakter.';
			$resetId = $userId;
		} elseif ($newPassword !== $confirmPassword) {
			$error = 'Konfirmasi password baru tidak sama.';
			$resetId = $userId;
		} else {
			$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
			$updateStmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ? LIMIT 1');
			if (!$updateStmt) {
				$error = 'Gagal mereset password.';
				$resetId = $userId;
			} else {
				$updateStmt->bind_param('si', $newHash, $userId);
				if ($updateStmt->execute()) {
					$updateStmt->close();
					users_redirect(['success' => 'password-reset']);
				}
				$updateStmt->close();
				$error = 'Gagal mereset password.';
				$resetId = $userId;
			}
		}
	}

	if ($action === 'delete_user') {
		if ($userId <= 0) {
			users_redirect(['error' => 'ID admin tidak valid.']);
		}

		if ($userId === $currentAdminId) {
			users_redirect(['error' => 'Tidak bisa menghapus akun yang sedang login.']);
		}

		$deleteStmt = $conn->prepare('DELETE FROM users WHERE id = ? LIMIT 1');
		if (!$deleteStmt) {
			users_redirect(['error' => 'Gagal menghapus admin.']);
		}

		$deleteStmt->bind_param('i', $userId);
		if ($deleteStmt->execute()) {
			$deleteStmt->close();
			users_redirect(['success' => 'deleted']);
		}

		$deleteStmt->close();
		users_redirect(['error' => 'Gagal menghapus admin.']);
	}
}

if ($editId > 0) {
	$editUser = load_admin_user_by_id($conn, $editId);
	if (!$editUser) {
		$error = 'Admin yang dipilih tidak ditemukan.';
		$editId = 0;
	} elseif (is_array($pendingEditValues) && (int) $pendingEditValues['id'] === (int) $editUser['id']) {
		$editUser['username'] = $pendingEditValues['username'];
	}
}

if ($resetId > 0) {
	$resetUser = load_admin_user_by_id($conn, $resetId);
	if (!$resetUser) {
		$error = 'Admin yang dipilih tidak ditemukan.';
		$resetId = 0;
	}
}

$users = [];
$listStmt = $conn->prepare('SELECT id, username, created_at, updated_at FROM users ORDER BY id ASC');
if ($listStmt) {
	$listStmt->execute();
	$result = $listStmt->get_result();
	while ($row = $result ? $result->fetch_assoc() : null) {
		$users[] = $row;
	}
	$listStmt->close();
}

include __DIR__ . '/includes/header.php';
?>

<style>
	.admin-account-page {
		display: grid;
		gap: 16px;
	}
	.admin-panel {
		background: #fff;
		border: 1px solid rgba(16, 44, 87, 0.08);
		border-radius: 20px;
		padding: 18px;
		box-shadow: 0 10px 24px rgba(15, 39, 66, 0.05);
	}
	.admin-panel-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		margin-bottom: 14px;
		padding-bottom: 12px;
		border-bottom: 1px solid rgba(16, 44, 87, 0.08);
	}
	.admin-panel-title {
		margin: 0;
		font-size: 18px;
		font-weight: 700;
		color: #102c57;
	}
	.admin-panel-subtitle {
		margin: 0;
		font-size: 13px;
		color: #6b7c93;
	}
	.admin-alert {
		border-radius: 12px;
		padding: 12px 14px;
		font-size: 13px;
		font-weight: 600;
	}
	.admin-table-wrap {
		overflow-x: auto;
		border: 1px solid rgba(16, 44, 87, 0.08);
		border-radius: 18px;
	}
	.admin-table {
		width: 100%;
		border-collapse: collapse;
	}
	.admin-table thead th {
		background: #f5f8fb;
		color: #486581;
		padding: 11px 12px;
		font-size: 11px;
		font-weight: 700;
		border-bottom: 1px solid #e6edf3;
		text-transform: uppercase;
		white-space: nowrap;
	}
	.admin-table tbody td {
		padding: 12px;
		font-size: 13px;
		color: #344054;
		border-bottom: 1px solid #eef2f6;
		vertical-align: middle;
	}
	.admin-table tbody tr:nth-child(odd) {
		background: rgba(248, 251, 254, 0.78);
	}
	.admin-table tbody tr:hover {
		background: rgba(20, 108, 148, 0.05);
	}
	.action-group {
		display: flex;
		gap: 6px;
		flex-wrap: wrap;
	}
	.btn-action {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		padding: 6px 10px;
		border-radius: 6px;
		font-size: 12px;
		font-weight: 600;
		text-decoration: none;
		border: 1px solid transparent;
		cursor: pointer;
	}
	.btn-edit {
		background: #e8f0fa;
		color: #146c94;
		border-color: rgba(20, 108, 148, 0.2);
	}
	.btn-reset {
		background: #fff4db;
		color: #a16100;
		border-color: rgba(161, 97, 0, 0.15);
	}
	.btn-delete {
		background: #fde8e8;
		color: #8d2d2d;
		border-color: rgba(201, 96, 96, 0.2);
	}
	.btn-primary-soft {
		background: #102c57;
		color: #fff;
	}
	.btn-primary-soft:hover {
		color: #fff;
		text-decoration: none;
	}
	.admin-form-grid {
		display: grid;
		gap: 16px;
		grid-template-columns: repeat(2, minmax(0, 1fr));
	}
	.admin-form-box {
		padding: 16px;
		border-radius: 16px;
		background: #fdfefe;
		border: 1px solid #e6edf5;
	}
	.admin-form-box h3 {
		margin: 0 0 10px;
		font-size: 15px;
		font-weight: 700;
		color: #102c57;
	}
	@media (max-width: 992px) {
		.admin-form-grid {
			grid-template-columns: 1fr;
		}
	}
</style>

<div class="admin-account-page">
	<div class="admin-panel">
		<div class="admin-panel-head">
			<div>
				<h2 class="admin-panel-title">Daftar Admin</h2>
				<p class="admin-panel-subtitle">Kelola akun admin dengan aman menggunakan password hash.</p>
			</div>
			<a href="register.php" class="btn-action btn-primary-soft">
				<i class="fas fa-user-plus"></i> Tambah Admin
			</a>
		</div>

		<?php if ($success !== ''): ?>
			<?php
			$successMessage = $success;
			if ($success === 'updated') {
				$successMessage = 'Data admin berhasil diperbarui.';
			} elseif ($success === 'password-reset') {
				$successMessage = 'Password admin berhasil direset.';
			} elseif ($success === 'deleted') {
				$successMessage = 'Admin berhasil dihapus.';
			}
			?>
			<div class="alert alert-success admin-alert" role="alert"><?php echo gbi_admin_escape($successMessage); ?></div>
		<?php endif; ?>

		<?php if ($error !== ''): ?>
			<div class="alert alert-danger admin-alert" role="alert"><?php echo gbi_admin_escape($error); ?></div>
		<?php endif; ?>

		<?php if (empty($users)): ?>
			<div class="text-center py-5 text-muted">
				<i class="fas fa-users fa-3x mb-3"></i>
				<h5>Belum ada data admin</h5>
				<p class="mb-0">Tambahkan akun admin pertama dari menu Tambah Admin.</p>
			</div>
		<?php else: ?>
			<div class="admin-table-wrap">
				<table class="admin-table">
					<thead>
						<tr>
							<th>Username</th>
							<th>Tanggal Dibuat</th>
							<th>Tanggal Update</th>
							<th style="width: 280px;">Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($users as $row): ?>
							<tr>
								<td><strong><?php echo gbi_admin_escape($row['username'] ?? '-'); ?></strong><?php echo ((int) $row['id'] === $currentAdminId) ? ' <span class="badge badge-info">Saya</span>' : ''; ?></td>
								<td><?php echo gbi_admin_escape(format_admin_datetime($row['created_at'] ?? '')); ?></td>
								<td><?php echo gbi_admin_escape(format_admin_datetime($row['updated_at'] ?? '')); ?></td>
								<td>
									<div class="action-group">
										<a href="users.php?edit=<?php echo (int) $row['id']; ?>" class="btn-action btn-edit">
											<i class="fas fa-pen"></i> Edit
										</a>
										<a href="users.php?reset=<?php echo (int) $row['id']; ?>" class="btn-action btn-reset">
											<i class="fas fa-key"></i> Reset Password
										</a>
										<?php if ((int) $row['id'] !== $currentAdminId): ?>
											<form method="post" onsubmit="return confirm('Yakin ingin menghapus admin ini?');" style="margin: 0;">
												<input type="hidden" name="action" value="delete_user">
												<input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
												<button type="submit" class="btn-action btn-delete">
													<i class="fas fa-trash"></i> Hapus
												</button>
											</form>
										<?php else: ?>
											<button type="button" class="btn-action btn-delete" disabled title="Akun yang sedang login tidak bisa dihapus">
												<i class="fas fa-trash"></i> Hapus
											</button>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<?php if ($editId > 0 && $editUser): ?>
		<div class="admin-panel">
			<div class="admin-panel-head">
				<div>
					<h2 class="admin-panel-title">Edit Admin</h2>
					<p class="admin-panel-subtitle">Ubah username admin terpilih.</p>
				</div>
			</div>
			<div class="admin-form-box">
				<form method="post">
					<input type="hidden" name="action" value="update_user">
					<input type="hidden" name="id" value="<?php echo (int) $editUser['id']; ?>">
					<div class="form-group">
						<label for="edit_username">Username</label>
						<input type="text" class="form-control" id="edit_username" name="username" value="<?php echo gbi_admin_escape($editUser['username'] ?? ''); ?>" required maxlength="50">
					</div>
					<div class="mt-3 d-flex gap-2 flex-wrap">
						<button type="submit" class="btn btn-primary">Simpan Perubahan</button>
						<a href="users.php" class="btn btn-outline-secondary">Batal</a>
					</div>
				</form>
			</div>
		</div>
	<?php endif; ?>

	<?php if ($resetId > 0 && $resetUser): ?>
		<div class="admin-panel">
			<div class="admin-panel-head">
				<div>
					<h2 class="admin-panel-title">Reset Password</h2>
					<p class="admin-panel-subtitle">Reset password untuk akun <?php echo gbi_admin_escape($resetUser['username'] ?? '-'); ?>.</p>
				</div>
			</div>
			<div class="admin-form-box">
				<form method="post">
					<input type="hidden" name="action" value="reset_password">
					<input type="hidden" name="id" value="<?php echo (int) $resetUser['id']; ?>">
					<div class="admin-form-grid">
						<div class="form-group">
							<label for="new_password">Password Baru</label>
							<input type="password" class="form-control" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
						</div>
						<div class="form-group">
							<label for="confirm_password">Konfirmasi Password Baru</label>
							<input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
						</div>
					</div>
					<div class="mt-3 d-flex gap-2 flex-wrap">
						<button type="submit" class="btn btn-warning">Reset Password</button>
						<a href="users.php" class="btn btn-outline-secondary">Batal</a>
					</div>
				</form>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>