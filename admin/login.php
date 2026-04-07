<?php
// Enable error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        // Debug: check if database connection works
        if ($conn->connect_error) {
            $error = 'Koneksi database gagal: ' . $conn->connect_error;
        } else {
            $query = "SELECT * FROM users WHERE username = ? AND password = ?";
            $stmt = $conn->prepare($query);
            
            if (!$stmt) {
                $error = 'Persiapan query gagal: ' . $conn->error;
            } else {
                $stmt->bind_param('ss', $username, $password);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['login'] = true; // backward compatibility
                    
                    // Calculate redirect URL for subdirectory hosting
                    $admin_script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
                    $admin_base_url = strpos($admin_script_path, '/admin/') !== false
                        ? substr($admin_script_path, 0, strpos($admin_script_path, '/admin/'))
                        : rtrim(str_replace('\\', '/', dirname($admin_script_path)), '/');
                    if ($admin_base_url === '/') {
                        $admin_base_url = '';
                    }
                    $redirect_url = $admin_base_url . '/admin/index.php';
                    
                    header('Location: ' . $redirect_url);
                    exit();
                } else {
                    $error = 'Username atau password salah!';
                }
                $stmt->close();
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
    <?php
    $admin_script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $admin_base_url = strpos($admin_script_path, '/admin/') !== false
        ? substr($admin_script_path, 0, strpos($admin_script_path, '/admin/'))
        : rtrim(str_replace('\\', '/', dirname($admin_script_path)), '/');
    if ($admin_base_url === '/') {
        $admin_base_url = '';
    }
    ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($admin_base_url); ?>/assets/css/admin-theme.css">
</head>
<body class="admin-theme">
    <div class="login-page">
    <div class="login-box">
        <h1 class="login-title">Admin Login</h1>
        <p class="login-brand-note">GBI Salemba Content Management</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
    </div>
    </div>
</body>
</html>


