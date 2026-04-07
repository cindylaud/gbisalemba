<?php
// Enable error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/image-helper.php';

define('COMING_SOON_TABLE', 'coming_soon');
define('WN_UPLOAD_DIR', __DIR__ . '/../uploads/whatsnew/');
define('WN_MAX_SIZE',   50 * 1024 * 1024); // 50MB
define('WN_ALLOWED',    ['jpg', 'jpeg', 'png', 'webp']);
define('WN_MAX_WIDTH',  1920);
define('WN_WEBP_QUALITY', 80);

function ensureComingSoonTable($conn) {
    $hasComingSoon = false;
    $hasWhatsNew = false;

    $checkComingSoon = $conn->query("SHOW TABLES LIKE 'coming_soon'");
    if ($checkComingSoon && $checkComingSoon->num_rows > 0) {
        $hasComingSoon = true;
    }

    $checkWhatsNew = $conn->query("SHOW TABLES LIKE 'whats_new'");
    if ($checkWhatsNew && $checkWhatsNew->num_rows > 0) {
        $hasWhatsNew = true;
    }

    if (!$hasComingSoon && $hasWhatsNew) {
        $rename_result = $conn->query("RENAME TABLE whats_new TO coming_soon");
        if ($rename_result === false) {
            return "Error renaming table: " . $conn->error;
        }
        $hasComingSoon = true;
    }

    if (!$hasComingSoon) {
        $create_result = $conn->query(
            "CREATE TABLE IF NOT EXISTS coming_soon (
                id INT AUTO_INCREMENT PRIMARY KEY,
                image VARCHAR(255) NOT NULL,
                urutan INT NOT NULL DEFAULT 1,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );
        if ($create_result === false) {
            return "Error creating table: " . $conn->error;
        }
    }
    
    return null; // Success
}

$table_error = ensureComingSoonTable($conn);
if ($table_error !== null) {
    die('Error: ' . htmlspecialchars($table_error));
}

$message = '';
$error   = '';

// ─── Ambil session flash ────────────────────────────────────────────────────
if (!empty($_SESSION['message'])) { $message = $_SESSION['message']; unset($_SESSION['message']); }
if (!empty($_SESSION['error']))   { $error   = $_SESSION['error'];   unset($_SESSION['error']);   }

// ============================================================
// HANDLE: HAPUS GAMBAR
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = intval($_GET['id']);

    if ($del_id <= 0) {
        $_SESSION['error'] = 'ID tidak valid';
        header('Location: whatsnew.php');
        exit;
    }

    // Ambil nama file
    $stmt = $conn->prepare("SELECT image FROM " . COMING_SOON_TABLE . " WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $_SESSION['error'] = 'Data tidak ditemukan';
        header('Location: whatsnew.php');
        exit;
    }

    // Hapus file fisik
    $file_path = WN_UPLOAD_DIR . $row['image'];
    if (file_exists($file_path)) {
        @unlink($file_path);
    }

    // Hapus dari database
    $stmt = $conn->prepare("DELETE FROM " . COMING_SOON_TABLE . " WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Gambar berhasil dihapus';
    } else {
        $_SESSION['error'] = 'Gagal menghapus: ' . $conn->error;
    }
    $stmt->close();

    header('Location: whatsnew.php');
    exit;
}

// ============================================================
// HANDLE: TOGGLE AKTIF / NONAKTIF
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $tog_id = intval($_GET['id']);

    if ($tog_id <= 0) {
        $_SESSION['error'] = 'ID tidak valid';
        header('Location: whatsnew.php');
        exit;
    }

    $stmt = $conn->prepare("UPDATE " . COMING_SOON_TABLE . " SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
    $stmt->bind_param("i", $tog_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Status berhasil diubah';
    } else {
        $_SESSION['error'] = 'Gagal mengubah status: ' . $conn->error;
    }
    $stmt->close();

    header('Location: whatsnew.php');
    exit;
}

// ============================================================
// HANDLE: UPDATE URUTAN
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sort') {
    $sort_id     = intval($_POST['sort_id']   ?? 0);
    $sort_urutan = intval($_POST['sort_urutan'] ?? 0);

    if ($sort_id <= 0 || $sort_urutan <= 0) {
        $_SESSION['error'] = 'Data urutan tidak valid';
    } else {
        $stmt = $conn->prepare("UPDATE " . COMING_SOON_TABLE . " SET urutan = ? WHERE id = ?");
        $stmt->bind_param("ii", $sort_urutan, $sort_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Urutan berhasil diubah';
        } else {
            $_SESSION['error'] = 'Gagal mengubah urutan: ' . $conn->error;
        }
        $stmt->close();
    }

    header('Location: whatsnew.php');
    exit;
}

// ============================================================
// HANDLE: UPLOAD GAMBAR BARU
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $urutan = intval($_POST['urutan'] ?? 1);

    if ($urutan < 1) { $urutan = 1; }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Silakan pilih file gambar yang valid';
    } else {
        $file = $_FILES['image'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $validation = validateImageUpload($file, WN_MAX_SIZE);

        // Validasi ekstensi
        if (!in_array($ext, WN_ALLOWED)) {
            $error = 'Format file tidak diizinkan. Gunakan: JPG, JPEG, PNG, WEBP';
        }
        elseif (!$validation['valid']) {
            $error = $validation['error'];
        }
        else {
            // Buat folder jika belum ada
            if (!is_dir(WN_UPLOAD_DIR)) {
                mkdir(WN_UPLOAD_DIR, 0755, true);
            }

            // Simpan hasil akhir sebagai WEBP terkompresi
            $filename  = 'img_' . substr(bin2hex(random_bytes(8)), 0, 6) . '.webp';
            $dest_path = WN_UPLOAD_DIR . $filename;
            $processed = optimizeAndSaveImageAsWebp(
                $file['tmp_name'],
                $dest_path,
                WN_MAX_WIDTH,
                WN_WEBP_QUALITY
            );

            if (!$processed['success']) {
                $error = $processed['error'];
            } else {
                // Insert ke database
                $stmt = $conn->prepare("INSERT INTO " . COMING_SOON_TABLE . " (image, urutan, is_active) VALUES (?, ?, 1)");
                $stmt->bind_param("si", $filename, $urutan);
                if ($stmt->execute()) {
                    $message = 'Gambar berhasil diupload dan dikonversi ke WEBP';
                } else {
                    // Rollback file jika insert gagal
                    @unlink($dest_path);
                    $error = 'Gagal menyimpan ke database: ' . $conn->error;
                }
                $stmt->close();
            }
        }
    }
}

// ============================================================
// AMBIL DATA UNTUK DITAMPILKAN
// ============================================================
$items = [];
$res = $conn->query("SELECT * FROM " . COMING_SOON_TABLE . " ORDER BY urutan ASC");
if ($res === false) {
    $error = 'Gagal mengambil data: ' . $conn->error;
} else {
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Coming Soon - Admin GBI Salemba</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php $admin_theme_path = dirname(__DIR__) . '/assets/css/admin-theme.css'; ?>
    <?php
    $admin_script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $admin_base_url = strpos($admin_script_path, '/admin/') !== false
        ? substr($admin_script_path, 0, strpos($admin_script_path, '/admin/'))
        : rtrim(str_replace('\\', '/', dirname($admin_script_path)), '/');
    if ($admin_base_url === '/') {
        $admin_base_url = '';
    }
    ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($admin_base_url); ?>/assets/css/admin-theme.css?v=<?php echo urlencode((string) (is_file($admin_theme_path) ? filemtime($admin_theme_path) : time())); ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F3F9FB;
            color: #102C57;
            line-height: 1.6;
        }

        .container {
            max-width: none;
            margin: 0;
        }

        .admin-topbar {
            border-radius: 14px;
        }

        /* ── Alerts ── */
        .alert {
            padding: 13px 16px;
            margin-bottom: 16px;
            border-radius: 12px;
            font-weight: 600;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* ── Two-column layout ── */
        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1.18fr) minmax(320px, 0.82fr);
            gap: 18px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
        }

        /* ── Panel ── */
        .panel {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfe 100%);
            border: 1px solid rgba(16, 44, 87, 0.1);
            border-radius: 22px;
            padding: 20px;
            box-shadow: 0 12px 26px rgba(15, 39, 66, 0.08);
        }

        .panel-upload {
            position: sticky;
            top: 104px;
        }

        .panel-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 19px;
            font-weight: 800;
            color: #102C57;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(16, 44, 87, 0.1);
        }

        .panel-title::before {
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #146C94;
            font-size: 17px;
        }

        .list-title::before {
            content: '\f03a';
        }

        .upload-title::before {
            content: '\2b';
        }

        /* ── Table ── */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(16, 44, 87, 0.08);
        }
        thead th {
            background: linear-gradient(180deg, #eff7fb 0%, #e4f0f5 100%);
            color: #1e3a5f;
            padding: 11px 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.45px;
            border-bottom: 1px solid #d9e9ef;
        }

        thead th:nth-child(1) { width: 56px; text-align: center; }
        thead th:nth-child(2) { width: 138px; }
        thead th:nth-child(3) { width: 170px; text-align: center; }
        thead th:nth-child(4) { width: 110px; text-align: center; }
        thead th:nth-child(5) { width: 230px; text-align: center; }

        tbody tr {
            border-bottom: 1px solid #edf3f8;
            transition: background 0.2s ease;
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background-color: rgba(63, 182, 168, 0.06); }
        tbody td {
            padding: 12px;
            vertical-align: middle;
            font-size: 13px;
        }

        tbody td:nth-child(1),
        tbody td:nth-child(3),
        tbody td:nth-child(4),
        tbody td:nth-child(5) {
            text-align: center;
        }

        /* ── Preview thumb ── */
        .thumb {
            width: 96px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            display: block;
            background: #eef4f8;
            border: 1px solid rgba(16, 44, 87, 0.1);
        }
        .thumb-placeholder {
            width: 96px;
            height: 60px;
            background: #eef4f8;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #8fa1b4;
            border: 1px dashed rgba(16, 44, 87, 0.18);
        }

        /* ── Status badge ── */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .badge-active   { background: rgba(47, 158, 68, 0.14); color: #1f6a31; }
        .badge-inactive { background: rgba(108, 117, 125, 0.14); color: #4f5963; }

        /* ── Action buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn i {
            font-size: 11px;
        }

        .btn-toggle {
            background: #146C94;
            color: white;
        }
        .btn-toggle:hover { background: #0f5273; }

        .btn-danger {
            background: #d9534f;
            color: white;
        }
        .btn-danger:hover { background: #c13f3b; }

        .btn-sort {
            background: #6c757d;
            color: white;
            padding: 6px 7px;
            min-width: 30px;
            height: 30px;
            border-radius: 8px;
        }
        .btn-sort:hover { background: #545b62; }

        .sort-arrows {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            line-height: 0.9;
            gap: 0;
            font-size: 10px;
        }

        /* ── Inline sort form ── */
        body.admin-theme .sort-form {
            display: inline-flex !important;
            align-items: center;
            gap: 4px;
            justify-content: center;
            flex-wrap: nowrap !important;
            white-space: nowrap;
            margin: 0;
        }
        body.admin-theme .sort-form input[type="number"] {
            width: 58px;
            min-width: 58px;
            padding: 6px 8px;
            border: 1px solid rgba(16, 44, 87, 0.2);
            border-radius: 10px;
            font-size: 13px;
            text-align: center;
            margin: 0;
        }
        body.admin-theme .sort-form .btn-sort {
            margin: 0;
            flex: 0 0 auto;
        }

        /* ── Upload form ── */
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 13px;
            color: #102C57;
        }
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(16, 44, 87, 0.2);
            border-radius: 12px;
            font-size: 13px;
            background: white;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .file-upload-wrap {
            border: 1px dashed rgba(20, 108, 148, 0.35);
            border-radius: 12px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.92) 0%, rgba(240, 247, 252, 0.9) 100%);
            padding: 12px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .file-upload-wrap.is-highlight {
            border-color: rgba(20, 108, 148, 0.62);
            box-shadow: 0 0 0 0.2rem rgba(63, 182, 168, 0.14);
        }

        .file-input-native {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .file-upload-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            background: linear-gradient(135deg, #1e3a5f 0%, #146C94 100%);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .file-upload-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 14px rgba(20, 108, 148, 0.22);
        }

        .file-upload-name {
            margin-top: 10px;
            padding: 9px 10px;
            border-radius: 9px;
            border: 1px solid rgba(16, 44, 87, 0.15);
            background: rgba(255, 255, 255, 0.86);
            font-size: 12px;
            color: #405f7b;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-upload-meta {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #59708a;
            font-weight: 600;
        }

        .file-upload-meta i {
            color: #146C94;
        }
        .form-group input:focus {
            outline: none;
            border-color: rgba(63, 182, 168, 0.56);
            box-shadow: 0 0 0 0.2rem rgba(63, 182, 168, 0.14);
        }
        .info-text {
            font-size: 12px;
            color: #6b7c93;
            margin-top: 5px;
            font-style: italic;
            line-height: 1.45;
        }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: fit-content;
            min-width: 170px;
            padding: 8px 12px;
            border: none;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            background: linear-gradient(135deg, #1e3a5f 0%, #146C94 100%);
            color: white;
            letter-spacing: 0.15px;
        }

        .btn-submit i {
            font-size: 11px;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(20, 108, 148, 0.24);
        }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7c93;
        }
        .empty-state .icon { font-size: 40px; margin-bottom: 10px; }
        .empty-state p { font-size: 14px; }

        /* ── Delete confirm ── */
        .actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-wrap: nowrap;
            justify-content: center;
            align-items: center;
        }

        .actions .btn {
            width: 150px;
            min-width: 150px;
            padding: 8px 12px;
            border-radius: 11px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.1px;
            border: 1px solid transparent;
            box-shadow: 0 6px 12px rgba(16, 44, 87, 0.12);
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
        }

        .actions .btn i {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            background: rgba(255, 255, 255, 0.24);
        }

        .actions .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(16, 44, 87, 0.17);
            filter: saturate(1.02);
        }

        body.admin-theme td .actions {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            width: 100%;
            margin: 0 auto;
        }

        body.admin-theme td .actions .btn {
            width: 150px !important;
            min-width: 150px !important;
            margin: 0 !important;
            justify-content: center;
        }

        .actions .btn-toggle {
            color: #fff;
            border-color: rgba(9, 72, 112, 0.34);
        }

        .actions .btn-toggle.will-disable {
            background: linear-gradient(135deg, #176b94 0%, #0f4f76 100%);
        }

        .actions .btn-toggle.will-enable {
            background: linear-gradient(135deg, #1b8f5a 0%, #147947 100%);
            border-color: rgba(19, 103, 60, 0.45);
        }

        .actions .btn-danger {
            background: linear-gradient(135deg, #e45f5a 0%, #cb3f3a 100%);
            border-color: rgba(165, 40, 34, 0.45);
        }

        .actions .btn-danger i {
            background: rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .panel-upload {
                position: static;
                top: auto;
            }

            .actions {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body class="admin-theme">
<div class="admin-shell">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1>Kelola Coming Soon</h1>
            </div>
            <div class="admin-topbar-meta">Halo, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong></div>
        </header>
        <div class="admin-content">
<div class="container">

    <?php if ($message): ?>
        <div class="alert alert-success">✓ <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">✗ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="layout">

        <!-- ── KIRI: DAFTAR GAMBAR ───────────────────────────────────── -->
        <div class="panel panel-list">
            <div class="panel-title list-title">Daftar Coming Soon</div>

            <?php if (empty($items)): ?>
                <div class="empty-state">
                    <div class="icon"><i class="fas fa-image"></i></div>
                    <p>Belum ada gambar. Upload gambar pertama di panel kanan.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Preview</th>
                            <th>Urutan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $no => $item): ?>
                            <?php
                                $file_exists = file_exists(WN_UPLOAD_DIR . $item['image']);
                                $img_url     = '../uploads/whatsnew/' . htmlspecialchars($item['image']);
                            ?>
                            <tr>
                                <!-- No -->
                                <td><?php echo $no + 1; ?></td>

                                <!-- Preview -->
                                <td>
                                    <?php if ($file_exists): ?>
                                        <img src="<?php echo $img_url; ?>"
                                             alt="Preview"
                                             class="thumb">
                                    <?php else: ?>
                                        <div class="thumb-placeholder"><i class="fas fa-camera"></i></div>
                                    <?php endif; ?>
                                </td>

                                <!-- Urutan (inline form) -->
                                <td>
                                    <form method="POST" class="sort-form">
                                        <input type="hidden" name="action"   value="sort">
                                        <input type="hidden" name="sort_id"  value="<?php echo $item['id']; ?>">
                                        <input type="number" name="sort_urutan"
                                               value="<?php echo intval($item['urutan']); ?>"
                                               min="1" max="999">
                                        <button type="submit" class="btn btn-sort" title="Simpan urutan">
                                            <span class="sort-arrows"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span>
                                        </button>
                                    </form>
                                </td>

                                <!-- Status -->
                                <td>
                                    <?php if ($item['is_active'] == 1): ?>
                                        <span class="badge badge-active">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Nonaktif</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Aksi -->
                                <td>
                                    <div class="actions">
                                        <a href="?action=toggle&id=<?php echo $item['id']; ?>"
                                                         class="btn btn-toggle <?php echo $item['is_active'] ? 'will-disable' : 'will-enable'; ?>"
                                           title="<?php echo $item['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                            <?php if ($item['is_active']): ?>
                                                <i class="fas fa-eye-slash"></i> Nonaktifkan
                                            <?php else: ?>
                                                <i class="fas fa-eye"></i> Aktifkan
                                            <?php endif; ?>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $item['id']; ?>"
                                           class="btn btn-danger"
                                           onclick="return confirm('Yakin hapus gambar ini? File juga akan dihapus.')"
                                           title="Hapus">
                                            <i class="fas fa-trash-can"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- ── KANAN: FORM UPLOAD ────────────────────────────────────── -->
        <div class="panel panel-upload">
            <div class="panel-title upload-title">Upload Coming Soon</div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">

                <div class="form-group">
                    <label for="image">Pilih Gambar *</label>
                    <div class="file-upload-wrap" id="coming_file_wrap">
                        <input type="file"
                               id="image"
                               class="file-input-native"
                               name="image"
                               accept=".jpg,.jpeg,.png,.webp"
                               required>
                        <button type="button" id="coming_file_button" class="file-upload-button">
                            <i class="fas fa-cloud-upload-alt"></i> Pilih Gambar
                        </button>
                        <div class="file-upload-name" id="coming_file_name">Belum ada file dipilih</div>
                        <div class="file-upload-meta">
                            <i class="fas fa-file-image"></i>
                            <span>Upload gambar terbaik untuk Coming Soon</span>
                        </div>
                    </div>
                    <p class="info-text">Format: JPG, JPEG, PNG, WEBP &bull; Maks. 50MB &bull; Otomatis diubah ke WEBP (maks. 1920px, quality 80)</p>
                </div>

                <div class="form-group">
                    <label for="urutan">Urutan</label>
                    <input type="number"
                           id="urutan"
                           name="urutan"
                           value="<?php echo count($items) + 1; ?>"
                           min="1" max="999">
                    <p class="info-text">Angka lebih kecil tampil lebih awal</p>
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Simpan Data</button>
            </form>
        </div>

    </div><!-- /.layout -->

</div><!-- /.container -->
        </div>
    </main>
</div>
<script>
(function () {
    const input = document.getElementById('image');
    const button = document.getElementById('coming_file_button');
    const fileName = document.getElementById('coming_file_name');
    const wrap = document.getElementById('coming_file_wrap');

    if (!input || !button || !fileName || !wrap) {
        return;
    }

    button.addEventListener('click', function () {
        input.click();
    });

    input.addEventListener('change', function () {
        const file = input.files && input.files[0] ? input.files[0] : null;
        fileName.textContent = file ? file.name : 'Belum ada file dipilih';
    });

    button.addEventListener('dragenter', function () {
        wrap.classList.add('is-highlight');
    });

    button.addEventListener('dragleave', function () {
        wrap.classList.remove('is-highlight');
    });
})();
</script>
</body>
</html>





