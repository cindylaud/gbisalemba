<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/image-helper.php';

define('COMING_SOON_TABLE', 'coming_soon');
define('WN_UPLOAD_DIR', __DIR__ . '/../uploads/whatsnew/');
define('WN_MAX_SIZE',   50 * 1024 * 1024); // 50MB
define('WN_ALLOWED',    ['jpg', 'jpeg', 'png', 'webp']);
define('WN_MAX_WIDTH',  1920);
define('WN_WEBP_QUALITY', 80);

function ensureComingSoonTable(mysqli $conn): void {
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
        $conn->query("RENAME TABLE whats_new TO coming_soon");
        $hasComingSoon = true;
    }

    if (!$hasComingSoon) {
        $conn->query(
            "CREATE TABLE IF NOT EXISTS coming_soon (
                id INT AUTO_INCREMENT PRIMARY KEY,
                image VARCHAR(255) NOT NULL,
                urutan INT NOT NULL DEFAULT 1,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );
    }
}

ensureComingSoonTable($conn);

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
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Coming Soon - Admin GBI Salemba</title>
    <link rel="stylesheet" href="/<?php echo htmlspecialchars(basename(dirname(__DIR__))); ?>/assets/css/admin-theme.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F3F9FB;
            color: #102C57;
            line-height: 1.6;
        }

        .container {
            max-width: none;
            margin: 0;
        }

        /* ── Header ── */
        /* ── Alerts ── */
        .alert {
            padding: 14px 18px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
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
            grid-template-columns: 1fr 340px;
            gap: 16px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
        }

        /* ── Panel ── */
        .panel {
            background: linear-gradient(165deg, #f9f2e8 0%, #efe2d1 100%);
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 4px 12px rgba(16, 44, 87, 0.1);
        }

        .panel-title {
            font-size: 17px;
            font-weight: 700;
            color: #102C57;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid #146C94;
        }

        /* ── Table ── */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        thead th {
            background: #146C94;
            color: white;
            padding: 10px 12px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tbody tr {
            border-bottom: 1px solid #f0f4f8;
            transition: background 0.2s ease;
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background-color: #f0f7fb; }
        tbody td {
            padding: 10px 12px;
            vertical-align: middle;
            font-size: 14px;
        }

        /* ── Preview thumb ── */
        .thumb {
            width: 90px;
            height: 56px;
            object-fit: cover;
            border-radius: 6px;
            display: block;
            background: #eee;
        }
        .thumb-placeholder {
            width: 90px;
            height: 56px;
            background: #e9ecef;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #aaa;
        }

        /* ── Status badge ── */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .badge-active   { background: #d4edda; color: #155724; }
        .badge-inactive { background: #e2e3e5; color: #383d41; }

        /* ── Action buttons ── */
        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .btn-toggle {
            background: #17a2b8;
            color: white;
        }
        .btn-toggle:hover { background: #138496; }

        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover { background: #c82333; }

        .btn-sort {
            background: #6c757d;
            color: white;
            padding: 5px 8px;
        }
        .btn-sort:hover { background: #545b62; }

        /* ── Inline sort form ── */
        .sort-form {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .sort-form input[type="number"] {
            width: 60px;
            padding: 5px 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 13px;
            text-align: center;
        }

        /* ── Upload form ── */
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 14px;
            color: #102C57;
        }
        .form-group input[type="file"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #146C94;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #0f5273;
        }
        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }

        .btn-submit {
            width: 100%;
            padding: 13px 20px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #146C94 0%, #0f5273 100%);
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-submit::before {
            content: '\\f093';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 8px;
        }
        .btn-submit:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 15px rgba(20, 108, 148, 0.3);
        }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }
        .empty-state .icon { font-size: 48px; margin-bottom: 12px; }
        .empty-state p { font-size: 15px; }

        /* ── Delete confirm ── */
        .actions { display: flex; gap: 6px; flex-wrap: wrap; }

        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
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
                <div class="admin-topbar-meta">Kontrol visual Coming Soon untuk halaman publik</div>
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
        <div class="panel">
            <div class="panel-title">📋 Daftar Coming Soon</div>

            <?php if (empty($items)): ?>
                <div class="empty-state">
                    <div class="icon">🖼️</div>
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
                                        <div class="thumb-placeholder">📷</div>
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
                                        <button type="submit" class="btn btn-sort">↑↓</button>
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
                                           class="btn btn-toggle"
                                           title="<?php echo $item['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                            <?php echo $item['is_active'] ? '⏸ Nonaktifkan' : '▶ Aktifkan'; ?>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $item['id']; ?>"
                                           class="btn btn-danger"
                                           onclick="return confirm('Yakin hapus gambar ini? File juga akan dihapus.')"
                                           title="Hapus">
                                            🗑 Hapus
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
        <div class="panel">
            <div class="panel-title">➕ Upload Coming Soon</div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">

                <div class="form-group">
                    <label for="image">Pilih Gambar *</label>
                    <input type="file"
                           id="image"
                           name="image"
                           accept=".jpg,.jpeg,.png,.webp"
                           required>
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

                <button type="submit" class="btn-submit">⬆ Upload Gambar</button>
            </form>
        </div>

    </div><!-- /.layout -->

</div><!-- /.container -->
        </div>
    </main>
</div>
</body>
</html>





