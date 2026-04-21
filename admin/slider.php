<?php
// Enable error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Cek database connection
if ($conn->connect_error) {
    die('Error: Koneksi database gagal - ' . htmlspecialchars($conn->connect_error));
}

// Load image helper with error handling
if (!file_exists(__DIR__ . '/../includes/image-helper.php')) {
    die('Error: File image-helper.php tidak ditemukan');
}
require_once __DIR__ . '/../includes/image-helper.php';

// Define constants untuk slider
define('SLIDER_MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB
define('SLIDER_UPLOAD_DIR', __DIR__ . '/../uploads/slider/');
define('SLIDER_MAX_WIDTH', 2000); // pixels
define('SLIDER_JPEG_QUALITY', 80); // 0-100

function ensureSliderZoomColumn($conn) {
    $check = $conn->query("SHOW COLUMNS FROM slider LIKE 'image_zoom'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE slider ADD COLUMN image_zoom TINYINT UNSIGNED NOT NULL DEFAULT 100 AFTER image");
    }
}

ensureSliderZoomColumn($conn);

$message = '';
$error = '';

// Auto-seed: Pastikan record urutan 1-4 selalu ada (image 'default.png' karena NOT NULL)
for ($i = 1; $i <= 4; $i++) {
    $check = $conn->prepare("SELECT id FROM slider WHERE urutan = ?");
    if (!$check) {
        $error = 'Database error (check): ' . $conn->error;
        break;
    }
    $check->bind_param("i", $i);
    if (!$check->execute()) {
        $error = 'Database error (execute check): ' . $check->error;
        $check->close();
        break;
    }
    $result = $check->get_result();
    
    if ($result->num_rows == 0) {
        $default_img = 'default.png';
        $insert = $conn->prepare("INSERT INTO slider (urutan, title, subtitle, image, is_active, created_at) VALUES (?, NULL, NULL, ?, 0, NOW())");
        if (!$insert) {
            $error = 'Database error (insert): ' . $conn->error;
            $check->close();
            break;
        }
        $insert->bind_param("is", $i, $default_img);
        if (!$insert->execute()) {
            $error = 'Database error (insert execute): ' . $insert->error;
            $insert->close();
            $check->close();
            break;
        }
        $insert->close();
    }
    $check->close();
}

// Handle POST: Upload & Simpan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['urutan'])) {
    $urutan = intval($_POST['urutan']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $image_zoom = intval($_POST['image_zoom'] ?? 100);
    if ($image_zoom < 50) {
        $image_zoom = 50;
    } elseif ($image_zoom > 150) {
        $image_zoom = 150;
    }
    
    // Validasi urutan 1-4
    if ($urutan < 1 || $urutan > 4) {
        $error = 'Urutan harus antara 1-4';
    } else {
        // Get existing record
        $check_stmt = $conn->prepare("SELECT id, image FROM slider WHERE urutan = ?");
        $check_stmt->bind_param("i", $urutan);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $existing = $check_result->fetch_assoc();
        $check_stmt->close();
        
        $update_image = false;
        $new_filename = '';
        $upload_info = null;
        
        // Handle file upload (optional) dengan auto optimize
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            
            // Validasi file menggunakan helper
            $validation = validateImageUpload($file, SLIDER_MAX_UPLOAD_SIZE);
            
            if (!$validation['valid']) {
                $error = $validation['error'];
            } else {
                // Generate unique filename
                $new_filename = generateUniqueFilename($file['name'], 'slider_' . $urutan . '_');
                $upload_path = SLIDER_UPLOAD_DIR . $new_filename;
                
                // Create folder if not exists
                if (!is_dir(SLIDER_UPLOAD_DIR)) {
                    mkdir(SLIDER_UPLOAD_DIR, 0755, true);
                }
                
                // Optimize dan simpan image
                $optimize_result = optimizeAndSaveImage(
                    $file['tmp_name'],
                    $upload_path,
                    SLIDER_MAX_WIDTH,
                    SLIDER_JPEG_QUALITY
                );
                
                if (!$optimize_result['success']) {
                    $error = "Gagal memproses gambar: " . $optimize_result['error'];
                    $new_filename = '';
                } else {
                    $update_image = true;
                    $upload_info = $optimize_result;
                    
                    // Delete old file (jika bukan default.png)
                    if ($existing && !empty($existing['image']) && $existing['image'] !== 'default.png') {
                        $old_file = SLIDER_UPLOAD_DIR . $existing['image'];
                        if (file_exists($old_file)) {
                            @unlink($old_file);
                        }
                    }
                }
            }
        }
        
        // Save to database
        if (empty($error)) {
            if ($existing) {
                // Update existing record
                if ($update_image) {
                    // Update dengan gambar baru
                    $stmt = $conn->prepare("UPDATE slider SET image = ?, image_zoom = ?, is_active = ?, updated_at = NOW() WHERE urutan = ?");
                    $stmt->bind_param("siii", $new_filename, $image_zoom, $is_active, $urutan);
                } else {
                    // Update zoom + is_active (tanpa ubah image)
                    $stmt = $conn->prepare("UPDATE slider SET image_zoom = ?, is_active = ?, updated_at = NOW() WHERE urutan = ?");
                    $stmt->bind_param("iii", $image_zoom, $is_active, $urutan);
                }
                
                if ($stmt->execute()) {
                    $message = 'Foto ' . $urutan . ' berhasil disimpan';
                    if ($upload_info) {
                        $message .= ' (' . formatFileSize($upload_info['original_size']) . ' → ' . formatFileSize($upload_info['optimized_size']) . ')';
                    }
                } else {
                    $error = 'Gagal menyimpan ke database';
                }
                $stmt->close();
            } else {
                // Insert new (seharusnya tidak pernah sampai sini karena auto-seed)
                $img = $update_image ? $new_filename : 'default.png';
                $stmt = $conn->prepare("INSERT INTO slider (urutan, title, subtitle, image, image_zoom, is_active, created_at) VALUES (?, NULL, NULL, ?, ?, ?, NOW())");
                $stmt->bind_param("isii", $urutan, $img, $image_zoom, $is_active);
                
                if ($stmt->execute()) {
                    $message = 'Foto ' . $urutan . ' berhasil disimpan';
                    if ($upload_info) {
                        $message .= ' (' . formatFileSize($upload_info['original_size']) . ' → ' . formatFileSize($upload_info['optimized_size']) . ')';
                    }
                } else {
                    $error = 'Gagal menyimpan ke database';
                }
                $stmt->close();
            }
        }
    }
}

// Get data untuk 4 card (urutan 1-4)
$query = "SELECT id, urutan, image, COALESCE(image_zoom, 100) AS image_zoom, is_active FROM slider WHERE urutan IN (1,2,3,4) ORDER BY urutan ASC";
$result = $conn->query($query);
if ($result === false) {
    $error = 'Query error: ' . $conn->error;
    die('Error: ' . htmlspecialchars($error));
}
$cards = [];
while ($row = $result->fetch_assoc()) {
    $cards[$row['urutan']] = $row;
}

$active_count = 0;
$with_image_count = 0;
for ($i = 1; $i <= 4; $i++) {
    if (!empty($cards[$i]['is_active'])) {
        $active_count++;
    }
    if (!empty($cards[$i]['image']) && $cards[$i]['image'] !== 'default.png') {
        $with_image_count++;
    }
}

$selected_slot = isset($_GET['slot']) ? intval($_GET['slot']) : (isset($_POST['urutan']) ? intval($_POST['urutan']) : 1);
if ($selected_slot < 1 || $selected_slot > 4) {
    $selected_slot = 1;
}

$selected_card = $cards[$selected_slot] ?? null;
$selected_has_image = false;
$selected_image_path = '';
$selected_is_active = false;
$selected_zoom = 100;

if ($selected_card && !empty($selected_card['image']) && $selected_card['image'] !== 'default.png') {
    $selected_file_path = __DIR__ . '/../uploads/slider/' . $selected_card['image'];
    if (file_exists($selected_file_path)) {
        $selected_has_image = true;
        $selected_image_path = '../uploads/slider/' . $selected_card['image'];
    }
}

if ($selected_card && !empty($selected_card['is_active'])) {
    $selected_is_active = true;
}

if ($selected_card) {
    $selected_zoom = (int) ($selected_card['image_zoom'] ?? 100);
    if ($selected_zoom < 50) {
        $selected_zoom = 50;
    } elseif ($selected_zoom > 150) {
        $selected_zoom = 150;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Slider - Admin GBI Salemba</title>
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F3F9FB;
            color: #102C57;
            line-height: 1.6;
        }
        
        .container {
            max-width: none;
            margin: 0;
            width: 100%;
            min-width: 0;
        }
        
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

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1.18fr) minmax(320px, 0.82fr);
            gap: 18px;
            align-items: start;
            min-width: 0;
        }

        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }

        .panel {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfe 100%);
            border: 1px solid rgba(16, 44, 87, 0.1);
            border-radius: 22px;
            padding: 20px;
            box-shadow: 0 12px 26px rgba(15, 39, 66, 0.08);
            min-width: 0;
        }

        .table-scroll {
            width: 100%;
            min-width: 0;
            overflow-x: auto;
        }

        .panel-upload {
            position: sticky;
            top: 104px;
            background: #ffffff;
            border: 1px solid rgba(16, 44, 87, 0.08);
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 4px 14px rgba(15, 39, 66, 0.05);
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
            content: '\f030';
        }

        .panel-upload .panel-title {
            gap: 8px;
            margin-bottom: 10px;
            padding-bottom: 10px;
            font-size: 18px;
            border-bottom: 1px solid rgba(16, 44, 87, 0.08);
        }

        .panel-upload .panel-title::before {
            font-size: 16px;
            color: #1f7aa3;
        }

        .panel-upload .slot-note {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 11px;
            color: #6f8099;
            font-weight: 500;
        }

        .slot-note {
            margin-top: -4px;
            margin-bottom: 14px;
            font-size: 12px;
            color: #6b7c93;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
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

        thead th:nth-child(1),
        tbody td:nth-child(1),
        thead th:nth-child(3),
        tbody td:nth-child(3),
        thead th:nth-child(4),
        tbody td:nth-child(4),
        thead th:nth-child(5),
        tbody td:nth-child(5) {
            text-align: center;
        }

        tbody tr {
            border-bottom: 1px solid #edf3f8;
            transition: background 0.2s ease;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:hover {
            background-color: rgba(63, 182, 168, 0.06);
        }

        tbody tr.is-current {
            background-color: rgba(20, 108, 148, 0.08);
        }

        tbody td {
            padding: 12px;
            vertical-align: middle;
            font-size: 13px;
        }

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

        .badge-active {
            background: rgba(47, 158, 68, 0.14);
            color: #1f6a31;
        }

        .badge-inactive {
            background: rgba(108, 117, 125, 0.14);
            color: #4f5963;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e3a5f 0%, #146C94 100%);
            color: #fff;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 14px rgba(20, 108, 148, 0.24);
            color: #fff;
        }

        .btn-manage {
            width: 112px;
        }

        .preview-box {
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #eef4f8;
            border: 1px solid rgba(16, 44, 87, 0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            overflow: hidden;
            position: relative;
        }

        .panel-upload .preview-box {
            margin-bottom: 10px;
            border-radius: 12px;
            border-color: rgba(16, 44, 87, 0.08);
            background: #f5f8fb;
        }

        .preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform-origin: center center;
            display: block;
        }

        .range-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 6px;
        }

        .range-row .range-value {
            min-width: 44px;
            text-align: right;
            font-weight: 800;
            color: #1e3a5f;
            font-size: 12px;
        }

        input[type="range"] {
            width: 100%;
            accent-color: #146C94;
        }

        .preview-box .placeholder {
            text-align: center;
            color: #6b7c93;
        }

        .preview-box .placeholder .icon {
            font-size: 34px;
            margin-bottom: 6px;
            opacity: 0.5;
        }

        .preview-box .placeholder .text {
            font-size: 12px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .panel-upload .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            color: #102C57;
            font-size: 13px;
        }

        .panel-upload .form-group label {
            font-size: 12px;
            font-weight: 700;
            color: #2c4567;
        }

        .form-group input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(16, 44, 87, 0.2);
            border-radius: 12px;
            font-size: 13px;
            background: white;
            cursor: pointer;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .panel-upload .form-group input[type="file"] {
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 12px;
            border-color: rgba(16, 44, 87, 0.16);
        }

        .form-group input[type="file"]:hover {
            border-color: rgba(20, 108, 148, 0.5);
        }

        .form-group input[type="file"]:focus {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(63, 182, 168, 0.14);
        }

        .info-text {
            font-size: 12px;
            color: #6b7c93;
            margin-top: 5px;
            font-style: italic;
            line-height: 1.45;
        }

        .panel-upload .info-text {
            font-size: 11px;
            line-height: 1.4;
            margin-top: 4px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            padding: 11px 12px;
            background: #f8fbfd;
            border: 1px solid rgba(16, 44, 87, 0.08);
            border-radius: 12px;
        }

        .panel-upload .checkbox-group {
            margin-bottom: 12px;
            padding: 9px 10px;
            border-radius: 10px;
            background: #fafcfe;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #146C94;
        }

        .checkbox-group label {
            font-weight: 700;
            color: #102C57;
            cursor: pointer;
            user-select: none;
            margin: 0;
            font-size: 13px;
        }

        .panel-upload .checkbox-group label {
            font-size: 12px;
            font-weight: 600;
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

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(20, 108, 148, 0.24);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 11px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .status-row {
            margin-bottom: 10px;
        }

        .panel-upload .status-badge {
            padding: 4px 10px;
            font-size: 10px;
            letter-spacing: 0.3px;
        }
        
        .status-active {
            background-color: rgba(40, 167, 69, 0.12);
            color: #1f6a31;
        }
        
        .status-inactive {
            background-color: rgba(108, 117, 125, 0.12);
            color: #4f5963;
        }

        @media (max-width: 768px) {
            .admin-content {
                padding-left: 10px;
                padding-right: 10px;
            }

            .layout {
                gap: 12px;
            }

            .panel {
                padding: 14px;
                border-radius: 16px;
            }

            table th,
            table td {
                padding: 10px;
            }

            .preview-box {
                aspect-ratio: 16 / 10;
            }

            .panel-upload {
                position: static;
                top: auto;
            }

            .btn-manage {
                width: 100%;
                min-width: 0;
            }

            .table-scroll {
                overflow-x: auto;
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
                    <h1>Kelola Slider</h1>
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
                <div class="panel-title list-title">Daftar Slider</div>
                <p class="slot-note">Pilih slot yang ingin dikelola, lalu ubah di panel kanan.</p>

                <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Slot</th>
                            <th>Preview</th>
                            <th>Status</th>
                            <th>Pakai Foto</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <?php
                                $card_data = $cards[$i] ?? null;
                                $has_image = false;
                                $image_path = '';
                                if ($card_data && !empty($card_data['image']) && $card_data['image'] !== 'default.png') {
                                    $file_path = __DIR__ . '/../uploads/slider/' . $card_data['image'];
                                    if (file_exists($file_path)) {
                                        $has_image = true;
                                        $image_path = '../uploads/slider/' . $card_data['image'];
                                    }
                                }
                                $is_active = $card_data && $card_data['is_active'] == 1;
                            ?>
                            <tr class="<?php echo $selected_slot === $i ? 'is-current' : ''; ?>">
                                <td><strong>Foto <?php echo $i; ?></strong></td>
                                <td>
                                    <?php if ($has_image): ?>
                                        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Slider <?php echo $i; ?>" class="thumb">
                                    <?php else: ?>
                                        <div class="thumb-placeholder"><i class="fas fa-camera"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $is_active ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo $is_active ? 'Aktif' : 'Nonaktif'; ?>
                                    </span>
                                </td>
                                <td><?php echo $has_image ? 'Ya' : 'Tidak'; ?></td>
                                <td>
                                    <a href="?slot=<?php echo $i; ?>" class="btn btn-primary btn-manage">Kelola</a>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <div class="panel panel-upload">
                <div class="panel-title upload-title">Kelola Foto <?php echo $selected_slot; ?></div>
                <p class="slot-note">Upload gambar baru atau ubah status tayang untuk slot terpilih.</p>

                <div class="preview-box">
                    <?php if ($selected_has_image): ?>
                        <img id="slider_preview_img" src="<?php echo htmlspecialchars($selected_image_path); ?>" alt="Slider <?php echo $selected_slot; ?>" title="Slider <?php echo $selected_slot; ?>" style="transform:scale(<?php echo htmlspecialchars(number_format($selected_zoom / 100, 2, '.', '')); ?>);">
                    <?php else: ?>
                        <div class="placeholder">
                            <div class="icon"><i class="fas fa-camera"></i></div>
                            <div class="text">Belum ada gambar</div>
                        </div>
                        <img id="slider_preview_img" src="" alt="Preview Slider <?php echo $selected_slot; ?>" style="display:none;">
                    <?php endif; ?>
                </div>

                <div class="status-row">
                    <span class="status-badge <?php echo $selected_is_active ? 'status-active' : 'status-inactive'; ?>"><?php echo $selected_is_active ? 'AKTIF' : 'NONAKTIF'; ?></span>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="urutan" value="<?php echo $selected_slot; ?>">

                    <div class="form-group">
                        <label for="image_selected">Upload Gambar Baru</label>
                        <input type="file" id="image_selected" name="image" accept=".jpg,.jpeg,.png,.webp">
                        <p class="info-text">JPG, JPEG, PNG, WEBP • Maksimal 30MB • Auto resize & optimize</p>
                    </div>

                    <div class="form-group">
                        <div class="range-row">
                            <label for="image_zoom">Ukuran Foto</label>
                            <span class="range-value" id="image_zoom_value"><?php echo (int) $selected_zoom; ?>%</span>
                        </div>
                        <input type="range" id="image_zoom" name="image_zoom" min="50" max="150" value="<?php echo (int) $selected_zoom; ?>">
                        <p class="info-text">Atur skala foto untuk tampilan slider di website jemaat.</p>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="active_selected" name="is_active" value="1" <?php echo $selected_is_active ? 'checked' : ''; ?>>
                        <label for="active_selected">Tampilkan di Frontend</label>
                    </div>

                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
            </div>
        </main>
    </div>

<script>
(function () {
    const input = document.getElementById('image_selected');
    const previewImg = document.getElementById('slider_preview_img');
    const zoomInput = document.getElementById('image_zoom');
    const zoomValue = document.getElementById('image_zoom_value');

    if (!zoomInput || !zoomValue || !previewImg) {
        return;
    }

    let objectUrl = null;

    function applyZoom() {
        const zoom = parseInt(zoomInput.value || '100', 10);
        const safeZoom = Number.isFinite(zoom) ? Math.max(50, Math.min(150, zoom)) : 100;
        zoomValue.textContent = safeZoom + '%';
        previewImg.style.transform = 'scale(' + (safeZoom / 100).toFixed(2) + ')';
    }

    if (input) {
        input.addEventListener('change', function () {
            const file = input.files && input.files[0] ? input.files[0] : null;
            if (!file) {
                return;
            }

            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }

            objectUrl = URL.createObjectURL(file);
            previewImg.src = objectUrl;
            previewImg.style.display = '';

            const placeholder = previewImg.parentElement.querySelector('.placeholder');
            if (placeholder) {
                placeholder.style.display = 'none';
            }

            applyZoom();
        });
    }

    zoomInput.addEventListener('input', applyZoom);
    applyZoom();
})();
</script>
</body>
</html>





