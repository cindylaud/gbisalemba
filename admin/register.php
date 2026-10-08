<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/account-utils.php';

$admin_page_title = 'Tambah Admin';
$currentAdminId = (int) ($_SESSION['admin_id'] ?? 0);
$success = gbi_admin_normalize_text($_GET['success'] ?? '');
$error = '';
$form = [
	'username' => '',
];

if ($currentAdminId <= 0) {
	header('Location: login.php');
	exit;
}

function register_redirect($params = []) {
	$target = 'register.php';
	if (!empty($params)) {
		$target .= '?' . http_build_query($params);
	}
	header('Location: ' . $target);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Enforce unique identity fields before saving the new admin password hash.
	// This prevents duplicate accounts and ensures data integrity.
	$form['username'] = gbi_admin_normalize_text($_POST['username'] ?? '');
	$password = (string) ($_POST['password'] ?? '');
	$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

	if ($form['username'] === '' || $password === '' || $confirmPassword === '') {
		$error = 'Semua field wajib diisi.';
	} elseif (strlen($form['username']) > 50) {
		$error = 'Username maksimal 50 karakter.';
	} elseif (strlen($password) < 8) {
		$error = 'Password minimal 8 karakter.';
	} elseif ($password !== $confirmPassword) {
		$error = 'Konfirmasi password tidak sama.';
	} else {
		$stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
		if (!$stmt) {
			$error = 'Gagal memeriksa username.';
		} else {
			$stmt->bind_param('s', $form['username']);
			$stmt->execute();
			$result = $stmt->get_result();
			$duplicateUsername = $result ? $result->fetch_assoc() : null;
			$stmt->close();

			if ($duplicateUsername) {
				$error = 'Username sudah digunakan.';
			} else {
				$hash = password_hash($password, PASSWORD_DEFAULT);
				$insertStmt = $conn->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
				if (!$insertStmt) {
					$error = 'Gagal membuat admin baru.';
				} else {
					$insertStmt->bind_param('ss', $form['username'], $hash);
					if ($insertStmt->execute()) {
						$insertStmt->close();
						register_redirect(['success' => 'created']);
					}
					$insertStmt->close();
					$error = 'Gagal membuat admin baru.';
				}
			}
		}
	}
}

include __DIR__ . '/includes/header.php';
?>

<style>
	.register-panel {
		max-width: 780px;
		background: #fff;
		border: 1px solid rgba(16, 44, 87, 0.08);
		border-radius: 20px;
		padding: 18px;
		box-shadow: 0 10px 24px rgba(15, 39, 66, 0.05);
	}
	.register-title {
		margin: 0 0 14px;
		font-size: 18px;
		font-weight: 700;
		color: #102c57;
	}
	.register-alert {
		border-radius: 12px;
		padding: 12px 14px;
		font-size: 13px;
		font-weight: 600;
	}
	.register-note {
		margin-bottom: 16px;
		font-size: 13px;
		color: #6b7c93;
	}
</style>

<div class="register-panel">
	<h2 class="register-title">Tambah Admin</h2>
	<p class="register-note">Buat akun admin baru dengan password yang disimpan memakai hash.</p>

	<?php if ($success !== ''): ?>
		<?php $successMessage = $success === 'created' ? 'Admin baru berhasil dibuat.' : $success; ?>
		<div class="alert alert-success register-alert" role="alert"><?php echo gbi_admin_escape($successMessage); ?></div>
	<?php endif; ?>

	<?php if ($error !== ''): ?>
		<div class="alert alert-danger register-alert" role="alert"><?php echo gbi_admin_escape($error); ?></div>
	<?php endif; ?>

	<form method="post">
		<div class="form-group">
			<label for="username">Username</label>
			<input type="text" class="form-control" id="username" name="username" value="<?php echo gbi_admin_escape($form['username']); ?>" required maxlength="50">
		</div>
		<div class="form-group">
			<label for="password">Password</label>
			<input type="password" class="form-control" id="password" name="password" required minlength="8" autocomplete="new-password">
		</div>
		<div class="form-group">
			<label for="confirm_password">Konfirmasi Password</label>
			<input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
		</div>
		<div class="d-flex flex-wrap gap-2">
			<button type="submit" class="btn btn-primary">Simpan Admin</button>
			<a href="users.php" class="btn btn-outline-secondary">Kembali ke Daftar Admin</a>
		</div>
	</form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>