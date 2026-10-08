<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (
        $username === '' ||
        $email === '' ||
        $newPassword === '' ||
        $confirmPassword === ''
    ) {
        $error = 'Semua field wajib diisi.';
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    }

    elseif (strlen($newPassword) < 8) {
        $error = 'Password minimal 8 karakter.';
    }

    elseif ($newPassword !== $confirmPassword) {
        $error = 'Konfirmasi password tidak sama.';
    }

    else {

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            AND email = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            'ss',
            $username,
            $email
        );

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            $hash = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $update = $conn->prepare("
                UPDATE users
                SET password = ?
                WHERE id = ?
            ");

            $update->bind_param(
                'si',
                $hash,
                $user['id']
            );

            if ($update->execute()) {
                $message = 'Password berhasil diperbarui. Silakan login.';
            } else {
                $error = 'Gagal memperbarui password.';
            }

            $update->close();

        } else {
            $error = 'Username atau email tidak ditemukan.';
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Lupa Password - GBI Salemba</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

<style>

body{
    background:#f7f9fc;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:30px;
}

.reset-wrapper{
    width:100%;
    max-width:550px;
}

.reset-box{
    background:#fff;
    border:1px solid rgba(16,44,87,.08);
    border-radius:20px;
    box-shadow:0 10px 24px rgba(15,39,66,.05);
    padding:32px;
}

.reset-header{
    text-align:center;
    margin-bottom:25px;
}

.reset-avatar{
    width:90px;
    height:90px;
    margin:0 auto 15px;
    border-radius:50%;
    background:#102c57;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:34px;
}

.reset-title{
    margin:0;
    color:#102c57;
    font-size:24px;
    font-weight:700;
}

.reset-description{
    margin-top:8px;
    color:#6b7c93;
    font-size:14px;
}

.form-group label{
    color:#102c57;
    font-weight:600;
}

.form-control{
    border-radius:12px;
    min-height:46px;
}

.form-control:focus{
    border-color:#102c57;
    box-shadow:0 0 0 .2rem rgba(16,44,87,.15);
}

.password-group{
    position:relative;
}

.password-group .form-control{
    padding-right:50px;
}

.password-toggle{
    position:absolute;
    top:50%;
    right:15px;
    transform:translateY(-50%);
    border:none;
    background:none;
    cursor:pointer;
    color:#6b7c93;
    font-size:16px;
}

.btn-primary{
    background:#102c57;
    border-color:#102c57;
    border-radius:12px;
    min-height:46px;
    font-weight:600;
}

.btn-primary:hover{
    background:#0d2344;
    border-color:#0d2344;
}

.btn-outline-secondary{
    border-radius:12px;
    min-height:46px;
}

.alert{
    border-radius:12px;
}

.form-divider{
    height:1px;
    background:#e8eef6;
    margin:20px 0;
}

</style>

</head>
<body>

<div class="reset-wrapper">

    <div class="reset-box">

        <div class="reset-header">
            <h2 class="reset-title">
                Lupa Password
            </h2>

            <p class="reset-description">
                Masukkan username dan email yang terdaftar untuk membuat password baru.
            </p>

        </div>

        <?php if($message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post">

            <div class="form-group">
                <label>Username</label>
                <input
                    type="text"
                    name="username"
                    class="form-control"
                    required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    required>
            </div>

            <div class="form-divider"></div>

            <div class="form-group">

                <label>Password Baru</label>

                <div class="password-group">

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        class="form-control"
                        required>

                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword('new_password', this)">
                        👁
                    </button>

                </div>

            </div>

            <div class="form-group">

                <label>Konfirmasi Password Baru</label>

                <div class="password-group">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        required>

                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword('confirm_password', this)">
                        👁
                    </button>

                </div>

            </div>

            <button
                type="submit"
                class="btn btn-primary btn-block">
                Reset Password
            </button>

            <a
                href="login.php"
                class="btn btn-outline-secondary btn-block">
                Kembali ke Login
            </a>

        </form>

    </div>

</div>

<script>

function togglePassword(id, button){

    const input = document.getElementById(id);

    if(input.type === 'password'){
        input.type = 'text';
        button.innerHTML = '🙈';
    }else{
        input.type = 'password';
        button.innerHTML = '👁';
    }
}

</script>

</body>
</html>