<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/image-helper.php';

define('COMING_SOON_TABLE', 'coming_soon');
define('WN_UPLOAD_DIR', __DIR__ . '/../uploads/whatsnew/');
define('WN_MAX_SIZE', 30 * 1024 * 1024);
define('WN_ALLOWED', ['jpg', 'jpeg', 'png', 'webp']);
define('WN_MAX_WIDTH', 1920);
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
        $renameResult = $conn->query("RENAME TABLE whats_new TO coming_soon");
        if ($renameResult === false) {
            return 'Error renaming table: ' . $conn->error;
        }
        $hasComingSoon = true;
    }

    if (!$hasComingSoon) {
        $createResult = $conn->query(
            "CREATE TABLE IF NOT EXISTS coming_soon (
                id INT AUTO_INCREMENT PRIMARY KEY,
                image VARCHAR(255) NOT NULL,
                event_title VARCHAR(255) NOT NULL DEFAULT '',
                event_datetime VARCHAR(255) NOT NULL DEFAULT '',
                event_location VARCHAR(255) NOT NULL DEFAULT '',
                event_registration VARCHAR(255) NOT NULL DEFAULT '',
                event_description TEXT NULL,
                urutan INT NOT NULL DEFAULT 1,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );
        if ($createResult === false) {
            return 'Error creating table: ' . $conn->error;
        }
    }

    return null;
}

function getNextUrutan($conn) {
    $next = 1;
    $res = $conn->query('SELECT COALESCE(MAX(urutan), 0) + 1 AS next_urutan FROM ' . COMING_SOON_TABLE);
    if ($res) {
        $row = $res->fetch_assoc();
        $next = (int) ($row['next_urutan'] ?? 1);
    }

    return max(1, $next);
}

$tableError = ensureComingSoonTable($conn);
if ($tableError !== null) {
    die('Error: ' . htmlspecialchars($tableError));
}

function ensureComingSoonZoomColumn($conn) {
    $check = $conn->query("SHOW COLUMNS FROM " . COMING_SOON_TABLE . " LIKE 'image_zoom'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE " . COMING_SOON_TABLE . " ADD COLUMN image_zoom TINYINT UNSIGNED NOT NULL DEFAULT 100 AFTER image");
    }
}

ensureComingSoonZoomColumn($conn);

function ensureComingSoonDetailColumns($conn) {
    $columns = [
        'event_title' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER image",
        'event_datetime' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER event_title",
        'event_location' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER event_datetime",
        'event_registration' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER event_location",
        'event_description' => "TEXT NULL AFTER event_registration",
    ];

    foreach ($columns as $column => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM " . COMING_SOON_TABLE . " LIKE '" . $conn->real_escape_string($column) . "'");
        if ($check && $check->num_rows === 0) {
            $conn->query("ALTER TABLE " . COMING_SOON_TABLE . " ADD COLUMN " . $column . " " . $definition);
        }
    }
}

ensureComingSoonDetailColumns($conn);

$wnServerLimit = getServerUploadLimit();
$wnEffectiveMaxSize = WN_MAX_SIZE;
if ($wnServerLimit > 0 && $wnServerLimit < $wnEffectiveMaxSize) {
    $wnEffectiveMaxSize = $wnServerLimit;
}
$wnEffectiveMaxMb = max(1, (int) floor($wnEffectiveMaxSize / 1024 / 1024));

$error = '';
if (!empty($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    if ($wnServerLimit > 0 && $contentLength > $wnServerLimit) {
        $error = 'Upload gagal: ukuran request melebihi batas server (' . $wnEffectiveMaxMb . 'MB). Kecilkan ukuran gambar lalu coba lagi.';
    }
}

// Delete
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $id = (int) $_GET['id'];
    if ($id <= 0) {
        $_SESSION['error'] = 'ID tidak valid';
        header('Location: whatsnew.php');
        exit;
    }

    $stmt = $conn->prepare('SELECT image FROM ' . COMING_SOON_TABLE . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $_SESSION['error'] = 'Data tidak ditemukan';
        header('Location: whatsnew.php');
        exit;
    }

    $stmt = $conn->prepare('DELETE FROM ' . COMING_SOON_TABLE . ' WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $oldPath = WN_UPLOAD_DIR . $row['image'];
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    } else {
        $_SESSION['error'] = 'Gagal menghapus: ' . $conn->error;
    }
    $stmt->close();

    header('Location: whatsnew.php');
    exit;
}

// Upload new
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $imageZoom = (int) ($_POST['image_zoom'] ?? 100);
    $eventTitle = trim((string) ($_POST['event_title'] ?? ''));
    $eventDatetime = trim((string) ($_POST['event_datetime'] ?? ''));
    $eventLocation = trim((string) ($_POST['event_location'] ?? ''));
    $eventRegistration = trim((string) ($_POST['event_registration'] ?? ''));
    $eventDescription = trim((string) ($_POST['event_description'] ?? ''));
    if ($imageZoom < 50) {
        $imageZoom = 50;
    } elseif ($imageZoom > 150) {
        $imageZoom = 150;
    }
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Silakan pilih file gambar yang valid';
    } else {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $validation = validateImageUpload($file, $wnEffectiveMaxSize);

        if (!in_array($ext, WN_ALLOWED, true)) {
            $error = 'Format file tidak diizinkan. Gunakan: JPG, JPEG, PNG, WEBP';
        } elseif (!$validation['valid']) {
            $error = $validation['error'];
        } else {
            if (!is_dir(WN_UPLOAD_DIR)) {
                mkdir(WN_UPLOAD_DIR, 0755, true);
            }

            $filename = 'img_' . substr(bin2hex(random_bytes(8)), 0, 6) . '.webp';
            $destPath = WN_UPLOAD_DIR . $filename;
            $processed = optimizeAndSaveImageAsWebp($file['tmp_name'], $destPath, WN_MAX_WIDTH, WN_WEBP_QUALITY);

            if (!$processed['success']) {
                $error = $processed['error'];
            } else {
                $urutan = getNextUrutan($conn);
                $stmt = $conn->prepare('INSERT INTO ' . COMING_SOON_TABLE . ' (image, event_title, event_datetime, event_location, event_registration, event_description, image_zoom, urutan, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('ssssssiii', $filename, $eventTitle, $eventDatetime, $eventLocation, $eventRegistration, $eventDescription, $imageZoom, $urutan, $isActive);
                if ($stmt->execute()) {
                    header('Location: whatsnew.php');
                    exit;
                }

                @unlink($destPath);
                $error = 'Gagal menyimpan ke database: ' . $conn->error;
                $stmt->close();
            }
        }
    }
}

// Update existing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_image') {
    $editId = (int) ($_POST['edit_id'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $imageZoom = (int) ($_POST['image_zoom'] ?? 100);
    $eventTitle = trim((string) ($_POST['event_title'] ?? ''));
    $eventDatetime = trim((string) ($_POST['event_datetime'] ?? ''));
    $eventLocation = trim((string) ($_POST['event_location'] ?? ''));
    $eventRegistration = trim((string) ($_POST['event_registration'] ?? ''));
    $eventDescription = trim((string) ($_POST['event_description'] ?? ''));
    if ($imageZoom < 50) {
        $imageZoom = 50;
    } elseif ($imageZoom > 150) {
        $imageZoom = 150;
    }

    if ($editId <= 0) {
        $error = 'ID edit tidak valid';
    } else {
        $stmt = $conn->prepare('SELECT image FROM ' . COMING_SOON_TABLE . ' WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $editId);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$current) {
            $error = 'Data yang akan diedit tidak ditemukan';
        } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $stmt = $conn->prepare('UPDATE ' . COMING_SOON_TABLE . ' SET event_title = ?, event_datetime = ?, event_location = ?, event_registration = ?, event_description = ?, image_zoom = ?, is_active = ? WHERE id = ?');
            $stmt->bind_param('sssssiii', $eventTitle, $eventDatetime, $eventLocation, $eventRegistration, $eventDescription, $imageZoom, $isActive, $editId);
            if ($stmt->execute()) {
                header('Location: whatsnew.php');
                exit;
            }
            $error = 'Gagal menyimpan perubahan: ' . $conn->error;
            $stmt->close();
        } else {
            $file = $_FILES['image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $validation = validateImageUpload($file, $wnEffectiveMaxSize);

            if (!in_array($ext, WN_ALLOWED, true)) {
                $error = 'Format file tidak diizinkan. Gunakan: JPG, JPEG, PNG, WEBP';
            } elseif (!$validation['valid']) {
                $error = $validation['error'];
            } else {
                if (!is_dir(WN_UPLOAD_DIR)) {
                    mkdir(WN_UPLOAD_DIR, 0755, true);
                }

                $filename = 'img_' . substr(bin2hex(random_bytes(8)), 0, 6) . '.webp';
                $destPath = WN_UPLOAD_DIR . $filename;
                $processed = optimizeAndSaveImageAsWebp($file['tmp_name'], $destPath, WN_MAX_WIDTH, WN_WEBP_QUALITY);

                if (!$processed['success']) {
                    $error = $processed['error'];
                } else {
                    $stmt = $conn->prepare('UPDATE ' . COMING_SOON_TABLE . ' SET image = ?, event_title = ?, event_datetime = ?, event_location = ?, event_registration = ?, event_description = ?, image_zoom = ?, is_active = ? WHERE id = ?');
                    $stmt->bind_param('ssssssiii', $filename, $eventTitle, $eventDatetime, $eventLocation, $eventRegistration, $eventDescription, $imageZoom, $isActive, $editId);

                    if ($stmt->execute()) {
                        $oldPath = WN_UPLOAD_DIR . $current['image'];
                        if (!empty($current['image']) && is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                        header('Location: whatsnew.php');
                        exit;
                    }

                    @unlink($destPath);
                    $error = 'Gagal menyimpan perubahan: ' . $conn->error;
                    $stmt->close();
                }
            }
        }
    }
}

$items = [];
$res = $conn->query('SELECT * FROM ' . COMING_SOON_TABLE . ' ORDER BY id ASC');
if ($res === false) {
    $error = 'Gagal mengambil data: ' . $conn->error;
} else {
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
}

$editingItem = null;
$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $stmt = $conn->prepare('SELECT * FROM ' . COMING_SOON_TABLE . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editingItem = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    if (!$editingItem) {
        $error = 'Data edit tidak ditemukan';
    }
}

$createMode = isset($_GET['create']) && $_GET['create'] === '1';
$selectedItem = null;
if (!$createMode) {
    if ($editingItem) {
        $selectedItem = $editingItem;
    } elseif (!empty($items)) {
        $selectedItem = $items[0];
    }
}

$selectedId = $selectedItem ? (int) $selectedItem['id'] : 0;
$panelPreviewUrl = '';
if ($selectedItem && !empty($selectedItem['image']) && is_file(WN_UPLOAD_DIR . $selectedItem['image'])) {
    $panelPreviewUrl = '../uploads/whatsnew/' . rawurlencode((string) $selectedItem['image']);
}
$panelStatusActive = $selectedItem ? ((int) $selectedItem['is_active'] === 1) : true;
$panelZoom = $selectedItem ? (int) ($selectedItem['image_zoom'] ?? 100) : 100;
$panelEventTitle = $selectedItem ? (string) ($selectedItem['event_title'] ?? '') : '';
$panelEventDatetime = $selectedItem ? (string) ($selectedItem['event_datetime'] ?? '') : '';
$panelEventLocation = $selectedItem ? (string) ($selectedItem['event_location'] ?? '') : '';
$panelEventRegistration = $selectedItem ? (string) ($selectedItem['event_registration'] ?? '') : '';
$panelEventDescription = $selectedItem ? (string) ($selectedItem['event_description'] ?? '') : '';
if ($panelZoom < 50) {
    $panelZoom = 50;
} elseif ($panelZoom > 150) {
    $panelZoom = 150;
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
        body { font-family: 'Inter','Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#F3F9FB; color:#102C57; line-height:1.6; overflow-x:hidden; }
        .form-group textarea,
        .form-group input[type='text'] {
            width:100%;
            padding:10px 12px;
            border:1px solid rgba(16,44,87,.2);
            border-radius:12px;
            font-size:13px;
            background:#fff;
            resize:vertical;
            min-height:42px;
        }
        .form-group textarea:focus,
        .form-group input[type='text']:focus { outline:none; border-color:rgba(63,182,168,.56); box-shadow:0 0 0 .2rem rgba(63,182,168,.14); }
        .container { max-width:none; margin:0; width:100%; min-width:0; }

        .alert { padding:13px 16px; margin-bottom:16px; border-radius:12px; font-weight:600; }
        .alert-danger { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

        .layout { display:grid; grid-template-columns:minmax(0,1.18fr) minmax(320px,0.82fr); gap:18px; align-items:start; min-width:0; }
        .panel { background:#fff; border:1px solid rgba(16,44,87,.08); border-radius:22px; padding:20px; box-shadow:0 10px 24px rgba(15,39,66,.05); min-width:0; }
        
        .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 0;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(16, 44, 87, 0.08);
        }

        .panel-title { display:flex; align-items:center; gap:10px; font-size:18px; font-weight:700; color:#102C57; margin:0; padding:0; }
        .panel-title::before { font-family:'Font Awesome 6 Free'; font-weight:900; color:#146C94; font-size:16px; }
        .list-title::before { content:'\f03a'; }
        .upload-title::before { content:'\f030'; }

        .panel-head-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-left: auto;
        }

        .btn-add-coming {
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

        .btn-add-coming:hover {
            background: #163b70;
            color: #fff;
            text-decoration: none;
        }

        .table-scroll { width:100%; min-width:0; overflow-x:auto; }
        .panel-upload { position:sticky; top:104px; z-index:5; background:#fff; border:1px solid rgba(16,44,87,.08); border-radius:16px; padding:16px; box-shadow:0 4px 14px rgba(15,39,66,.05); }

        .panel-upload .panel-title { gap:8px; margin-bottom:10px; padding-bottom:10px; font-size:18px; border-bottom:1px solid rgba(16,44,87,.08); }
        .panel-upload .panel-title { gap:8px; margin-bottom:10px; padding-bottom:10px; font-size:18px; border-bottom:1px solid rgba(16,44,87,.08); }

        .slot-note { margin-top:0; margin-bottom:14px; font-size:12px; color:#6b7c93; font-weight:600; }
        .panel-upload .slot-note { margin-top:0; margin-bottom:12px; font-size:11px; color:#6f8099; font-weight:500; }

        table { width:100%; border-collapse:separate; border-spacing:0; background:#fff; border-radius:14px; overflow:hidden; border:1px solid rgba(16,44,87,.08); }
        thead th { background:linear-gradient(180deg,#eff7fb 0%,#e4f0f5 100%); color:#1e3a5f; padding:11px 12px; text-align:left; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.45px; border-bottom:1px solid #d9e9ef; }
        thead th:nth-child(1), tbody td:nth-child(1), thead th:nth-child(3), tbody td:nth-child(3), thead th:nth-child(4), tbody td:nth-child(4), thead th:nth-child(5), tbody td:nth-child(5) { text-align:center; }
        tbody tr { border-bottom:1px solid #edf3f8; transition:background .2s ease; }
        tbody tr:last-child { border-bottom:none; }
        tbody tr:hover { background-color:rgba(63,182,168,.06); }
        tbody tr.is-current { background-color:rgba(20,108,148,.08); }
        tbody td { padding:12px; vertical-align:middle; font-size:13px; }

        .thumb { width:96px; height:60px; object-fit:cover; border-radius:10px; display:block; background:#eef4f8; border:1px solid rgba(16,44,87,.1); }
        .thumb-placeholder { width:96px; height:60px; background:#eef4f8; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; color:#8fa1b4; border:1px dashed rgba(16,44,87,.18); }

        .badge { display:inline-flex; align-items:center; justify-content:center; padding:5px 10px; border-radius:999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
        .badge-active { background:rgba(47,158,68,.14); color:#1f6a31; }
        .badge-inactive { background:rgba(108,117,125,.14); color:#4f5963; }

        .btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:8px 12px; border-radius:10px; font-size:12px; font-weight:700; text-decoration:none; cursor:pointer; border:none; transition:all .2s ease; white-space:nowrap; }
        .btn-primary { background:linear-gradient(135deg,#1e3a5f 0%,#146C94 100%); color:#fff; }
        .btn-primary:hover { transform:translateY(-1px); box-shadow:0 8px 14px rgba(20,108,148,.24); color:#fff; }
        .btn-danger { background:linear-gradient(135deg,#e45f5a 0%,#cb3f3a 100%); color:#fff; }
        .btn-danger:hover { color:#fff; filter:saturate(1.04); }
        .btn-manage { width:96px; }

        .preview-box { width:100%; aspect-ratio:16/9; background:#eef4f8; border:1px solid rgba(16,44,87,.1); border-radius:16px; display:flex; align-items:center; justify-content:center; margin-bottom:14px; overflow:hidden; position:relative; }
        .panel-upload .preview-box { margin-bottom:10px; border-radius:12px; border-color:rgba(16,44,87,.08); background:#f5f8fb; }
        .preview-box img { width:100%; height:100%; object-fit:cover; display:block; }
        .preview-box img { transform-origin:center center; }
        .preview-box .placeholder { text-align:center; color:#6b7c93; }
        .preview-box .placeholder .icon { font-size:34px; margin-bottom:6px; opacity:.5; }
        .preview-box .placeholder .text { font-size:12px; font-weight:600; }

        .range-row { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:6px; }
        .range-row .range-value { min-width:44px; text-align:right; font-weight:800; color:#1e3a5f; font-size:12px; }
        input[type='range'] { width:100%; accent-color:#146C94; }

        .status-row { margin-bottom:10px; }
        .status-badge { display:inline-flex; align-items:center; justify-content:center; padding:5px 11px; border-radius:999px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; }
        .status-active { background-color:rgba(40,167,69,.12); color:#1f6a31; }
        .status-inactive { background-color:rgba(108,117,125,.12); color:#4f5963; }

        .form-group { margin-bottom:16px; }
        .panel-upload .form-group { margin-bottom:12px; }
        .form-group label { display:block; margin-bottom:6px; font-weight:700; color:#102C57; font-size:13px; }
        .panel-upload .form-group label { font-size:12px; color:#2c4567; }
        .form-group input[type='file'], .form-group input[type='number'] { width:100%; padding:10px 12px; border:1px solid rgba(16,44,87,.2); border-radius:12px; font-size:13px; background:#fff; transition:border-color .2s ease, box-shadow .2s ease; }
        .panel-upload .form-group input[type='file'] { padding:8px 10px; border-radius:10px; font-size:12px; border-color:rgba(16,44,87,.16); }
        .form-group input:focus { outline:none; border-color:rgba(63,182,168,.56); box-shadow:0 0 0 .2rem rgba(63,182,168,.14); }

        .info-text { font-size:12px; color:#6b7c93; margin-top:5px; font-style:italic; line-height:1.45; }
        .panel-upload .info-text { font-size:11px; line-height:1.4; margin-top:4px; }

        .checkbox-group { display:flex; align-items:center; gap:10px; margin-bottom:14px; padding:11px 12px; background:#f8fbfd; border:1px solid rgba(16,44,87,.08); border-radius:12px; }
        .panel-upload .checkbox-group { margin-bottom:12px; padding:9px 10px; border-radius:10px; background:#fafcfe; }
        .checkbox-group input[type='checkbox'] { width:20px; height:20px; cursor:pointer; accent-color:#146C94; }
        .checkbox-group label { font-weight:700; color:#102C57; cursor:pointer; user-select:none; margin:0; font-size:13px; }

        .form-actions { position:relative; z-index:7; display:flex; flex-wrap:wrap; gap:8px; width:100%; margin-top:10px; }

        .form-actions .btn,
        .form-actions button.btn {
            position: relative;
            z-index: 8;
            pointer-events: auto;
            min-height:36px;
            padding:8px 12px;
            border-radius:10px;
            box-shadow:none;
        }

        .panel-list {
            position: relative;
            z-index: 1;
        }

        .form-actions .btn i,
        .form-actions button.btn i {
            font-size:12px;
        }

        .form-actions .btn-primary {
            background:linear-gradient(135deg,#1f466f 0%,#175f87 100%);
            color:#fff;
        }

        .form-actions .btn-danger {
            background:linear-gradient(135deg,#ea625b 0%,#d74a44 100%);
            color:#fff;
        }

        .form-actions .btn-add {
            background:#e8edf3;
            color:#1e3a5f;
            border:1px solid rgba(16,44,87,.14);
        }

        .form-actions .btn-add:hover {
            background:#dbe4ee;
            color:#1e3a5f;
            border-color:rgba(16,44,87,.14);
        }

        .form-actions.is-edit .btn-primary,
        .form-actions.is-edit .btn-danger,
        .form-actions.is-edit .btn-add {
            flex: 0 0 auto;
        }

        .empty-state { text-align:center; padding:40px 20px; color:#6b7c93; }
        .empty-state .icon { font-size:40px; margin-bottom:10px; }

        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .panel-upload { position: static; top: auto; }
        }

        @media (max-width: 768px) {
            .admin-content { padding-left: 10px; padding-right: 10px; }
            .layout { gap: 12px; }
            .panel { padding: 14px; border-radius: 16px; }
            table th, table td { padding: 10px; }
            .preview-box { aspect-ratio: 16 / 10; }
            .btn-manage { width: 100%; min-width: 0; }
            .form-actions { gap: 8px; }
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
                <?php if ($error): ?>
                    <div class="alert alert-danger">✗ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="layout">
                    <div class="panel panel-list">
                        <div class="panel-head">
                            <h2 class="panel-title list-title"></i> Daftar Coming Soon</h2>
                            <div class="panel-head-actions">
                                <a href="?create=1#coming_form" class="btn-add-coming">
                                    <i class="fas fa-plus"></i> Tambah Baru
                                </a>
                            </div>
                        </div>
                        <p class="slot-note">Pilih gambar yang ingin dikelola, lalu ubah di panel kanan.</p>

                        <?php if (empty($items)): ?>
                            <div class="empty-state">
                                <div class="icon"><i class="fas fa-image"></i></div>
                                <p>Belum ada gambar. Upload gambar pertama di panel kanan.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-scroll">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Preview</th>
                                            <th>Status</th>
                                            <th>Pakai Foto</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $no => $item): ?>
                                            <?php
                                                $fileExists = !empty($item['image']) && is_file(WN_UPLOAD_DIR . $item['image']);
                                                $imgUrl = $fileExists ? '../uploads/whatsnew/' . rawurlencode((string) $item['image']) : '';
                                                $isActive = (int) $item['is_active'] === 1;
                                            ?>
                                            <tr class="<?php echo $selectedId === (int) $item['id'] ? 'is-current' : ''; ?>">
                                                <td><strong><?php echo $no + 1; ?></strong></td>
                                                <td>
                                                    <?php if ($fileExists): ?>
                                                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Coming Soon <?php echo $no + 1; ?>" class="thumb">
                                                    <?php else: ?>
                                                        <div class="thumb-placeholder"><i class="fas fa-camera"></i></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $isActive ? 'badge-active' : 'badge-inactive'; ?>">
                                                        <?php echo $isActive ? 'Aktif' : 'Nonaktif'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $fileExists ? 'Ya' : 'Tidak'; ?></td>
                                                <td>
                                                    <a href="?edit=<?php echo (int) $item['id']; ?>#coming_form" class="btn btn-primary btn-manage">Kelola</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="panel panel-upload" id="coming_form">
                        <div class="panel-title upload-title"><?php echo $selectedItem ? 'Kelola Foto ' . (int) $selectedItem['id'] : 'Upload Coming Soon'; ?></div>
                        <p class="slot-note"><?php echo $selectedItem ? 'Upload gambar baru atau ubah status tayang untuk item terpilih.' : 'Upload gambar pertama untuk daftar Coming Soon.'; ?></p>

                        <div class="preview-box">
                            <?php if ($panelPreviewUrl !== ''): ?>
                                <img id="coming_preview_img" src="<?php echo htmlspecialchars($panelPreviewUrl); ?>" alt="Preview gambar coming soon" style="transform:scale(<?php echo htmlspecialchars(number_format($panelZoom / 100, 2, '.', '')); ?>);">
                            <?php else: ?>
                                <div class="placeholder" id="coming_preview_placeholder">
                                    <div class="icon"><i class="fas fa-camera"></i></div>
                                    <div class="text">Belum ada gambar</div>
                                </div>
                                <img id="coming_preview_img" src="" alt="Preview gambar coming soon" style="display:none; transform:scale(<?php echo htmlspecialchars(number_format($panelZoom / 100, 2, '.', '')); ?>);">
                            <?php endif; ?>
                        </div>

                        <div class="status-row">
                            <span class="status-badge <?php echo $panelStatusActive ? 'status-active' : 'status-inactive'; ?>" id="coming_status_badge">
                                <?php echo $panelStatusActive ? 'AKTIF' : 'NONAKTIF'; ?>
                            </span>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $selectedItem ? 'update_image' : 'upload'; ?>">
                            <?php if ($selectedItem): ?>
                                <input type="hidden" name="edit_id" value="<?php echo (int) $selectedItem['id']; ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="image">Upload Gambar Baru</label>
                                <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" <?php echo $selectedItem ? '' : 'required'; ?>>
                                <p class="info-text">Format: JPG, JPEG, PNG, WEBP • Maksimal <?php echo (int) $wnEffectiveMaxMb; ?>MB • Auto resize & optimize</p>
                            </div>

                            <div class="form-group">
                                <div class="range-row">
                                    <label for="image_zoom">Ukuran Foto</label>
                                    <span class="range-value" id="image_zoom_value"><?php echo (int) $panelZoom; ?>%</span>
                                </div>
                                <input type="range" id="image_zoom" name="image_zoom" min="50" max="150" value="<?php echo (int) $panelZoom; ?>">
                                <p class="info-text">Atur skala foto untuk tampilan Coming Soon di website jemaat.</p>
                            </div>

                            <div class="form-group">
                                <label for="event_title">Judul Event</label>
                                <input type="text" id="event_title" name="event_title" value="<?php echo htmlspecialchars($panelEventTitle); ?>" placeholder="Contoh: College Bible Study (Upperroom)">
                            </div>

                            <div class="form-group">
                                <label for="event_datetime">Hari/Tanggal</label>
                                <input type="text" id="event_datetime" name="event_datetime" value="<?php echo htmlspecialchars($panelEventDatetime); ?>" placeholder="Contoh: 24 Mei 2026 14.30 - 16.00 WIB">
                            </div>

                                                <div class="form-group">
                                                    <label for="event_location">Lokasi</label>
                                                    <textarea id="event_location" name="event_location" rows="2" placeholder="Contoh: Upperroom / Annex 1"><?php echo htmlspecialchars($panelEventLocation); ?></textarea>
                                                </div>

                                                <div class="form-group">
                                                    <label for="event_registration">Registrasi</label>
                                                    <textarea id="event_registration" name="event_registration" rows="2" placeholder="Contoh: Dibuka 12 Mei 2026 via MyJPCC App"><?php echo htmlspecialchars($panelEventRegistration); ?></textarea>
                                                </div>

                            <div class="form-group">
                                <label for="event_description">Deskripsi</label>
                                <textarea id="event_description" name="event_description" rows="6" placeholder="Tulis deskripsi singkat event coming soon di sini."><?php echo htmlspecialchars($panelEventDescription); ?></textarea>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" id="coming_is_active" name="is_active" value="1" <?php echo $panelStatusActive ? 'checked' : ''; ?>>
                                <label for="coming_is_active">Tampilkan di Frontend</label>
                            </div>

                            <div class="form-actions <?php echo $selectedItem ? 'is-edit' : 'is-create'; ?>">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $selectedItem ? 'Simpan Perubahan' : 'Simpan Data'; ?></button>
                                <?php if ($selectedItem): ?>
                                    <a href="?action=delete&id=<?php echo (int) $selectedItem['id']; ?>" class="btn btn-danger" onclick="return confirm('Yakin hapus gambar ini? File juga akan dihapus.');">
                                        <i class="fas fa-trash-can"></i> Hapus Item
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
(function () {
    const input = document.getElementById('image');
    const previewImg = document.getElementById('coming_preview_img');
    const previewPlaceholder = document.getElementById('coming_preview_placeholder');
    const activeCheck = document.getElementById('coming_is_active');
    const statusBadge = document.getElementById('coming_status_badge');
    const zoomInput = document.getElementById('image_zoom');
    const zoomValue = document.getElementById('image_zoom_value');

    if (!input || !previewImg) {
        return;
    }

    let objectUrl = null;

    function showPlaceholder() {
        if (previewPlaceholder) {
            previewPlaceholder.style.display = '';
        }
        previewImg.style.display = 'none';
        previewImg.removeAttribute('src');
    }

    function showImage(src) {
        if (previewPlaceholder) {
            previewPlaceholder.style.display = 'none';
        }
        previewImg.style.display = '';
        previewImg.src = src;
        applyZoom();
    }

    function applyZoom() {
        if (!zoomInput || !zoomValue) {
            return;
        }

        const zoom = parseInt(zoomInput.value || '100', 10);
        const safeZoom = Number.isFinite(zoom) ? Math.max(50, Math.min(150, zoom)) : 100;
        zoomValue.textContent = safeZoom + '%';
        previewImg.style.transform = 'scale(' + (safeZoom / 100).toFixed(2) + ')';
    }

    input.addEventListener('change', function () {
        const file = input.files && input.files[0] ? input.files[0] : null;
        if (!file) {
            return;
        }

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }

        objectUrl = URL.createObjectURL(file);
        showImage(objectUrl);
    });

    if (activeCheck && statusBadge) {
        const syncBadge = function () {
            if (activeCheck.checked) {
                statusBadge.textContent = 'AKTIF';
                statusBadge.classList.remove('status-inactive');
                statusBadge.classList.add('status-active');
            } else {
                statusBadge.textContent = 'NONAKTIF';
                statusBadge.classList.remove('status-active');
                statusBadge.classList.add('status-inactive');
            }
        };
        activeCheck.addEventListener('change', syncBadge);
        syncBadge();
    }

    if (!previewImg.getAttribute('src')) {
        showPlaceholder();
    }

    if (zoomInput) {
        zoomInput.addEventListener('input', applyZoom);
    }

    applyZoom();
})();
</script>
</body>
</html>
