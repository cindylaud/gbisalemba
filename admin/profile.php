<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/account-utils.php';

$admin_page_title = 'Profile Admin';
$adminId = (int) ($_SESSION['admin_id'] ?? 0);
$success = gbi_admin_normalize_text($_GET['success'] ?? '');
$error = gbi_admin_normalize_text($_GET['error'] ?? '');

if ($adminId <= 0) {
    header('Location: login.php');
    exit;
}

function profile_redirect($params = [])
{
    $target = 'profile.php';
    if (!empty($params)) {
        $target .= '?' . http_build_query($params);
    }
    header('Location: ' . $target);
    exit;
}

function load_profile_user($conn, $adminId)
{
    $stmt = $conn->prepare('SELECT id, username, email, created_at, updated_at, password FROM users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $user;
}

$user = load_profile_user($conn, $adminId);
if (!$user) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Each action validates the current password and keeps the update flow small.
    // This ensures that only authorized changes are made to the account.
    $action = gbi_admin_normalize_text($_POST['action'] ?? '');
    $currentPassword = gbi_admin_normalize_text($_POST['current_password'] ?? '');

    if ($action === 'update_username') {
        $newUsername = gbi_admin_normalize_text($_POST['username'] ?? '');

        if ($newUsername === '') {
            profile_redirect(['error' => 'Username baru tidak boleh kosong.']);
        }

        if (strlen($newUsername) > 50) {
            profile_redirect(['error' => 'Username maksimal 50 karakter.']);
        }

        if (!gbi_admin_verify_password($currentPassword, $user['password'])) {
            profile_redirect(['error' => 'Password saat ini salah.']);
        }

        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
        if (!$stmt) {
            profile_redirect(['error' => 'Gagal memeriksa username.']);
        }

        $stmt->bind_param('si', $newUsername, $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $duplicate = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($duplicate) {
            profile_redirect(['error' => 'Username sudah digunakan.']);
        }

        $updateStmt = $conn->prepare('UPDATE users SET username = ? WHERE id = ? LIMIT 1');
        if (!$updateStmt) {
            profile_redirect(['error' => 'Gagal memperbarui username.']);
        }

        $updateStmt->bind_param('si', $newUsername, $adminId);
        if ($updateStmt->execute()) {
            $updateStmt->close();
            $_SESSION['username'] = $newUsername;
            profile_redirect(['success' => 'Username berhasil diperbarui.']);
        }

        $updateStmt->close();
        profile_redirect(['error' => 'Gagal memperbarui username.']);
    }

    if ($action === 'update_email') {

        $newEmail = strtolower(trim($_POST['email'] ?? ''));

        if ($newEmail === '') {
            profile_redirect(['error' => 'Email tidak boleh kosong.']);
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            profile_redirect(['error' => 'Format email tidak valid.']);
        }

        if (!gbi_admin_verify_password($currentPassword, $user['password'])) {
            profile_redirect(['error' => 'Password saat ini salah.']);
        }

        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');

        if (!$stmt) {
            profile_redirect(['error' => 'Gagal memeriksa email.']);
        }

        $stmt->bind_param('si', $newEmail, $adminId);
        $stmt->execute();

        $result = $stmt->get_result();
        $duplicate = $result ? $result->fetch_assoc() : null;

        $stmt->close();

        if ($duplicate) {
            profile_redirect(['error' => 'Email sudah digunakan.']);
        }

        $updateStmt = $conn->prepare('UPDATE users SET email = ? WHERE id = ? LIMIT 1');

        if (!$updateStmt) {
            profile_redirect(['error' => 'Gagal memperbarui email.']);
        }

        $updateStmt->bind_param('si', $newEmail, $adminId);

        if ($updateStmt->execute()) {
            $updateStmt->close();
            profile_redirect(['success' => 'Email berhasil diperbarui.']);
        }

        $updateStmt->close();

        profile_redirect(['error' => 'Gagal memperbarui email.']);
    }

    if ($action === 'update_password') {
        $oldPassword = gbi_admin_normalize_text($_POST['old_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (!gbi_admin_verify_password($oldPassword, $user['password'])) {
            profile_redirect(['error' => 'Password lama tidak sesuai.']);
        }

        if (strlen($newPassword) < 8) {
            profile_redirect(['error' => 'Password baru minimal 8 karakter.']);
        }

        if ($newPassword !== $confirmPassword) {
            profile_redirect(['error' => 'Konfirmasi password baru tidak sama.']);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ? LIMIT 1');
        if (!$updateStmt) {
            profile_redirect(['error' => 'Gagal memperbarui password.']);
        }

        $updateStmt->bind_param('si', $newHash, $adminId);
        if ($updateStmt->execute()) {
            $updateStmt->close();
            profile_redirect(['success' => 'Password berhasil diperbarui.']);
        }

        $updateStmt->close();
        profile_redirect(['error' => 'Gagal memperbarui password.']);
    }

    profile_redirect(['error' => 'Aksi tidak dikenali.']);
}

function format_admin_datetime($value)
{
    if (empty($value)) {
        return '-';
    }

    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d-m-Y H:i', $timestamp) : '-';
}

include __DIR__ . '/includes/header.php';
?>

<style>
    .profile-grid {
        display: grid;
        grid-template-columns: 320px minmax(0, 1fr);
        gap: 24px;
    }

    .profile-card,
    .profile-panel {
        background: #fff;
        border: 1px solid rgba(16, 44, 87, 0.08);
        border-radius: 20px;
        box-shadow: 0 10px 24px rgba(15, 39, 66, 0.05);
    }

    .profile-card {
        padding: 24px;
    }

    .profile-panel {
        padding: 24px;
    }

    .profile-header {
        text-align: center;
        margin-bottom: 24px;
    }

    .profile-avatar {
        width: 90px;
        height: 90px;
        margin: 0 auto 14px;
        border-radius: 50%;
        background: #102c57;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 34px;
        font-weight: 700;
    }

    .profile-name {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #102c57;
    }

    .profile-role {
        margin-top: 4px;
        color: #6b7c93;
        font-size: 13px;
    }

    .profile-title {
        margin: 0 0 18px;
        font-size: 18px;
        font-weight: 700;
        color: #102c57;
    }

    .profile-meta {
        display: grid;
        gap: 12px;
    }

    .profile-meta-item {
        padding: 14px;
        border-radius: 14px;
        background: #f7f9fc;
        border: 1px solid #e8eef6;
        transition: all .2s ease;
    }

    .profile-meta-item:hover {
        transform: translateY(-2px);
    }

    .profile-meta-item .label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #6b7c93;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 6px;
    }

    .profile-meta-item .value {
        font-size: 15px;
        font-weight: 600;
        color: #1f2937;
    }

    .profile-form {
        display: grid;
        gap: 18px;
    }

    .form-block {
        padding: 22px;
        border-radius: 18px;
        background: #fff;
        border: 1px solid #e6edf5;
    }

    .form-block h3 {
        margin-bottom: 6px;
        font-size: 16px;
        font-weight: 700;
        color: #102c57;
    }

    .form-description {
        color: #6b7c93;
        font-size: 13px;
        margin-bottom: 16px;
    }

    .alert-soft {
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 18px;
    }

    .password-group {
        position: relative;
    }

    .password-group .form-control {
        padding-right: 50px;
    }

    .password-toggle {
        position: absolute;
        top: 50%;
        right: 14px;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        cursor: pointer;
        color: #6b7c93;
        font-size: 16px;
    }

    .btn-primary {
        min-width: 180px;
        border-radius: 10px;
        font-weight: 600;
    }

    @media (max-width: 992px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="profile-grid">

    <div class="profile-card">

        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
            </div>

            <h2 class="profile-name">
                <?php echo gbi_admin_escape($user['username']); ?>
            </h2>

            <p class="profile-role">
                Administrator
            </p>
        </div>

        <div class="profile-meta">

            <div class="profile-meta-item">
                <span class="label">Username</span>
                <span class="value">
                    <?php echo gbi_admin_escape($user['username']); ?>
                </span>
            </div>

            <div class="profile-meta-item">
                <span class="label">Email</span>

                <span class="value">
                    <?php echo gbi_admin_escape($user['email'] ?? '-'); ?>
                </span>
            </div>

            <div class="profile-meta-item">
                <span class="label">Tanggal Dibuat</span>
                <span class="value">
                    <?php echo gbi_admin_escape(format_admin_datetime($user['created_at'] ?? '')); ?>
                </span>
            </div>

            <div class="profile-meta-item">
                <span class="label">Terakhir Diperbarui</span>
                <span class="value">
                    <?php echo gbi_admin_escape(format_admin_datetime($user['updated_at'] ?? '')); ?>
                </span>
            </div>

        </div>

    </div>

    <div class="profile-panel">

        <h2 class="profile-title">Kelola Akun</h2>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success alert-soft" role="alert">
                <?php echo gbi_admin_escape(str_replace('_', ' ', $success)); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger alert-soft" role="alert">
                <?php echo gbi_admin_escape($error); ?>
            </div>
        <?php endif; ?>

        <div class="profile-form">

            <!-- GANTI USERNAME -->
            <div class="form-block">

                <h3>Ganti Username</h3>

                <p class="form-description">
                    Ubah username yang digunakan untuk login ke sistem admin.
                </p>

                <form method="post">

                    <input type="hidden" name="action" value="update_username">

                    <div class="form-group">
                        <label for="username">Username Baru</label>

                        <input type="text" class="form-control" id="username" name="username"
                            value="<?php echo gbi_admin_escape($user['username'] ?? ''); ?>" required maxlength="50">
                    </div>

                    <div class="form-group">

                        <label for="current_password_username">
                            Password Saat Ini
                        </label>

                        <div class="password-group">

                            <input type="password" class="form-control" id="current_password_username"
                                name="current_password" required autocomplete="current-password">

                            <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                👁
                            </button>

                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary">
                        Simpan Username
                    </button>

                </form>

            </div>

            <div class="form-block">

                <h3>Ganti Email</h3>

                <p class="form-description">
                    Email digunakan untuk proses pemulihan akun ketika lupa password.
                </p>

                <form method="post">

                    <input type="hidden" name="action" value="update_email">

                    <div class="form-group">
                        <label for="email">Email Baru</label>

                        <input type="email" class="form-control" id="email" name="email"
                            value="<?php echo gbi_admin_escape($user['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">

                        <label for="current_password_email">
                            Password Saat Ini
                        </label>

                        <div class="password-group">

                            <input type="password" class="form-control" id="current_password_email"
                                name="current_password" required>

                            <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                👁
                            </button>

                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary">
                        Simpan Email
                    </button>

                </form>

            </div>

            <!-- GANTI PASSWORD -->
            <div class="form-block">

                <h3>Ganti Password</h3>

                <p class="form-description">
                    Gunakan password yang kuat dan mudah Anda ingat.
                </p>

                <form method="post">

                    <input type="hidden" name="action" value="update_password">

                    <div class="form-group">

                        <label for="old_password">
                            Password Lama
                        </label>

                        <div class="password-group">

                            <input type="password" class="form-control" id="old_password" name="old_password" required
                                autocomplete="current-password">

                            <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                👁
                            </button>

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="new_password">
                            Password Baru
                        </label>

                        <div class="password-group">

                            <input type="password" class="form-control" id="new_password" name="new_password" required
                                minlength="8" autocomplete="new-password">

                            <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                👁
                            </button>

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="confirm_password">
                            Konfirmasi Password Baru
                        </label>

                        <div class="password-group">

                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required minlength="8" autocomplete="new-password">

                            <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                👁
                            </button>

                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary">
                        Simpan Password
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<script>
    function togglePassword(button) {
        const input = button.parentElement.querySelector('input');

        if (input.type === 'password') {
            input.type = 'text';
            button.innerHTML = '🙈';
        } else {
            input.type = 'password';
            button.innerHTML = '👁';
        }
    }
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>