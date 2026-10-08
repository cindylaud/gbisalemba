<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/account-utils.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = gbi_admin_normalize_text($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password harus diisi!';
    } else {
        $stmt = $conn->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');

        if (!$stmt) {
            $error = 'Persiapan query gagal.';
        } else {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if (!$user || !gbi_admin_verify_password($password, $user['password'])) {
                $error = 'Username atau password salah!';
            } else {
                // Upgrade legacy plaintext passwords or refresh hashes after a successful login.
                // This ensures that all passwords are stored securely.
                if (password_get_info((string) $user['password'])['algo'] === 0 || password_needs_rehash((string) $user['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');

                    if ($updateStmt) {
                        $userId = (int) $user['id'];
                        $updateStmt->bind_param('si', $newHash, $userId);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
                }

                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int) $user['id'];
                $_SESSION['username'] = (string) $user['username'];
                $_SESSION['login'] = true;

                header('Location: ' . gbi_admin_base_url() . '/admin/index.php');
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - GBI Salemba</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo gbi_admin_escape(gbi_admin_base_url()); ?>/assets/css/admin-theme.css">
</head>
<style>

.login-box{
    max-width:480px;
    width:100%;
    padding:40px;
    border-radius:24px;
}

.login-icon{
    width:80px;
    height:80px;
    margin:0 auto 20px;
    border-radius:50%;
    background:rgba(16,44,87,.08);

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:34px;
}

.login-title{
    text-align:center;
    margin-bottom:8px;
}

.login-brand-note{
    text-align:center;
    margin-bottom:28px;
}

.password-wrapper{
    position:relative;
}

.password-wrapper input{
    padding-right:50px;
}

.password-toggle{
    position:absolute;
    right:12px;
    top:50%;
    transform:translateY(-50%);

    border:none;
    background:none;

    cursor:pointer;
    font-size:18px;
}

.login-btn{
    height:48px;
    border-radius:12px;
    font-weight:600;
}

.forgot-password{
    display:block;
    text-align:center;
    margin-top:18px;
    font-size:14px;
    text-decoration:none;
}

.forgot-password:hover{
    text-decoration:underline;
}

</style>

<body class="admin-theme">
    <div class="login-page">
        <div class="login-box">
            <h1 class="login-title">
                Admin GBI Salemba
            </h1>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo gbi_admin_escape($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="form-group">
                    <label for="username">
                        Username
                    </label>

                    <input type="text" class="form-control" id="username" name="username"
                        value="<?php echo gbi_admin_escape($username); ?>" required autofocus autocomplete="username">
                </div>

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="password-wrapper">

                        <input type="password" class="form-control" id="password" name="password" required
                            autocomplete="current-password">

                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            👁
                        </button>

                    </div>

                </div>

                <button type="submit" class="btn btn-primary btn-block login-btn">
                    Login
                </button>

                <a href="forgot-password.php" class="forgot-password">
                    Lupa Password?
                </a>

            </form>

        </div>
    </div>
    <script>
        function togglePassword() {
            const input = document.getElementById('password');

            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }
    </script>
</body>

</html>