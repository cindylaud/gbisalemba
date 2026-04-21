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

// Define constants
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB
define('UPLOAD_DIR', '../uploads/pelayanan/');
define('MAX_IMAGE_WIDTH', 1600); // pixels
define('JPEG_QUALITY', 80); // 0-100

function ensurePelayananPositionColumn($conn) {
    $check = $conn->query("SHOW COLUMNS FROM pelayanan LIKE 'foto_posisi_y'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE pelayanan ADD COLUMN foto_posisi_y TINYINT UNSIGNED NOT NULL DEFAULT 50 AFTER foto");
    }
}

ensurePelayananPositionColumn($conn);

// =====================================================================
// HANDLE DELETE ACTION
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);
    
    // Get file name for deletion
    $stmt = $conn->prepare("SELECT foto FROM pelayanan WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $old_file = $row['foto'];
        
        // Delete from database
        $stmt_del = $conn->prepare("DELETE FROM pelayanan WHERE id = ?");
        $stmt_del->bind_param("i", $id);
        
        if ($stmt_del->execute()) {
            // Delete file from folder if exists
            deleteFile(UPLOAD_DIR . $old_file);
            $success = "Data pelayanan berhasil dihapus.";
        } else {
            $error = "Gagal menghapus data: " . $stmt_del->error;
        }
    }
}

// =====================================================================
// HANDLE FORM SUBMISSION (ADD & EDIT)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
    $form_action = $_POST['form_action'];
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status = trim($_POST['status'] ?? 'aktif');
    $urutan = intval($_POST['urutan'] ?? 0);
    $foto_posisi_y = intval($_POST['foto_posisi_y'] ?? 50);
    if ($foto_posisi_y < 0) {
        $foto_posisi_y = 0;
    } elseif ($foto_posisi_y > 100) {
        $foto_posisi_y = 100;
    }
    
    // Validasi input
    if (empty($judul) || empty($deskripsi)) {
        $error = "Judul dan deskripsi tidak boleh kosong.";
    } else {
        $foto_name = null;
        $upload_info = null;
        
        // ====== HANDLE FILE UPLOAD ======
        if (isset($_FILES['foto']) && $_FILES['foto']['size'] > 0) {
            $file = $_FILES['foto'];
            
            // Validate file
            $validation = validateImageUpload($file, MAX_UPLOAD_SIZE);
            
            if (!$validation['valid']) {
                $error = $validation['error'];
            } else {
                // Generate unique filename
                $foto_name = generateUniqueFilename($file['name']);
                $upload_path = UPLOAD_DIR . $foto_name;
                
                // Optimize dan simpan image
                $optimize_result = optimizeAndSaveImage(
                    $file['tmp_name'],
                    $upload_path,
                    MAX_IMAGE_WIDTH,
                    JPEG_QUALITY
                );
                
                if (!$optimize_result['success']) {
                    $error = "Gagal memproses gambar: " . $optimize_result['error'];
                    $foto_name = null;
                } else {
                    $upload_info = $optimize_result;
                }
            }
        }
        
        // ====== ADD NEW DATA ======
        if (!isset($error) && $form_action === 'add') {
            $stmt = $conn->prepare("INSERT INTO pelayanan (judul, deskripsi, foto, foto_posisi_y, status, urutan) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssisi", $judul, $deskripsi, $foto_name, $foto_posisi_y, $status, $urutan);
            
            if ($stmt->execute()) {
                $success_msg = "Data pelayanan berhasil ditambahkan.";
                if ($upload_info) {
                    $success_msg .= " (Foto: " . formatFileSize($upload_info['original_size']) . " → " . formatFileSize($upload_info['optimized_size']) . ")";
                }
                $success = $success_msg;
                // Clear form
                $_POST = [];
            } else {
                $error = "Gagal menyimpan data: " . $stmt->error;
                // Delete uploaded file if database insert fails
                if ($foto_name) {
                    deleteFile(UPLOAD_DIR . $foto_name);
                }
            }
            $stmt->close();
        }
        
        // ====== EDIT DATA ======
        elseif (!isset($error) && $form_action === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                $error = "ID tidak valid.";
            } else {
                // If new file uploaded, delete old one
                if ($foto_name) {
                    $stmt = $conn->prepare("SELECT foto FROM pelayanan WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $old_file = $row['foto'];
                        deleteFile(UPLOAD_DIR . $old_file);
                    }
                    $stmt->close();
                    
                    // Update with new file
                    $stmt = $conn->prepare("UPDATE pelayanan SET judul = ?, deskripsi = ?, foto = ?, foto_posisi_y = ?, status = ?, urutan = ? WHERE id = ?");
                    $stmt->bind_param("sssisii", $judul, $deskripsi, $foto_name, $foto_posisi_y, $status, $urutan, $id);
                } else {
                    // Update without changing file
                    $stmt = $conn->prepare("UPDATE pelayanan SET judul = ?, deskripsi = ?, foto_posisi_y = ?, status = ?, urutan = ? WHERE id = ?");
                    $stmt->bind_param("ssisii", $judul, $deskripsi, $foto_posisi_y, $status, $urutan, $id);
                }
                
                if ($stmt->execute()) {
                    $success_msg = "Data pelayanan berhasil diperbarui.";
                    if ($upload_info) {
                        $success_msg .= " (Foto: " . formatFileSize($upload_info['original_size']) . " → " . formatFileSize($upload_info['optimized_size']) . ")";
                    }
                    $success = $success_msg;
                    // Clear form
                    $_POST = [];
                } else {
                    $error = "Gagal menyimpan data: " . $stmt->error;
                    // Delete uploaded file if database update fails
                    if ($foto_name) {
                        deleteFile(UPLOAD_DIR . $foto_name);
                    }
                }
                $stmt->close();
            }
        }
    }
}

// =====================================================================
// GET DATA FOR EDIT
// =====================================================================
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM pelayanan WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// =====================================================================
// GET ALL DATA
// =====================================================================
$stmt = $conn->prepare("SELECT * FROM pelayanan ORDER BY urutan ASC");
$stmt->execute();
$result = $stmt->get_result();
$pelayanan_list = [];
while ($row = $result->fetch_assoc()) {
    $pelayanan_list[] = $row;
}
$stmt->close();

if (!isset($success) && isset($_GET['success']) && $_GET['success'] !== '') {
    $success = trim((string) $_GET['success']);
}

if (!isset($error) && isset($_GET['error']) && $_GET['error'] !== '') {
    $error = trim((string) $_GET['error']);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pelayanan - Admin GBI Salemba</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
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
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .container {
            max-width: none;
            margin: 0;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(260px, 0.45fr);
            gap: 18px;
            align-items: start;
        }

        @media (max-width: 1200px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .panel-list {
                order: 1;
                position: relative;
                z-index: 2;
            }

            .panel-upload {
                order: 2;
                position: static;
                top: auto;
                max-width: none;
                justify-self: stretch;
                margin-top: 6px;
                z-index: 1;
            }
        }

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
            width: 100%;
            max-width: 430px;
            justify-self: end;
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
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

        .panel-upload .panel-title {
            font-size: 16px;
        }

        .panel-upload .panel-title::before {
            font-size: 15px;
        }

        table {
            width: 100%;
            table-layout: auto;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(16, 44, 87, 0.08);
        }

        thead th:nth-child(1),
        tbody td:nth-child(1) {
            width: 68px;
        }

        thead th:nth-child(2),
        tbody td:nth-child(2) {
            min-width: 150px;
            word-break: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
        }

        thead th:nth-child(3),
        tbody td:nth-child(3) {
            width: 92px;
        }

        thead th:nth-child(4),
        tbody td:nth-child(4) {
            width: 94px;
        }

        thead th:nth-child(5),
        tbody td:nth-child(5) {
            width: 160px;
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

        .thumbnail {
            width: 66px;
            height: 66px;
            object-fit: cover;
            object-position: center;
            border-radius: 12px;
            display: inline-block;
            background-color: #eef4f8;
            border: 1px solid rgba(16, 44, 87, 0.1);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 11px;
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

        .btn i {
            font-size: 11px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e3a5f 0%, #146C94 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 14px rgba(20, 108, 148, 0.24);
        }

        .btn-secondary {
            background: #e8edf3;
            color: #1e3a5f;
            border: 1px solid rgba(16, 44, 87, 0.14);
        }

        .btn-secondary:hover {
            background: #dbe4ee;
        }

        .btn-danger {
            background: linear-gradient(135deg, #e45f5a 0%, #cb3f3a 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 14px rgba(203, 63, 58, 0.24);
        }

        .icon-action {
            width: 34px;
            height: 34px;
            min-width: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            color: #fff;
            text-decoration: none;
            box-shadow: 0 6px 12px rgba(16, 44, 87, 0.12);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .icon-action:hover {
            color: #fff;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 8px 14px rgba(16, 44, 87, 0.16);
        }

        .icon-action.edit {
            background: linear-gradient(135deg, #1e3a5f 0%, #146C94 100%);
            border-color: rgba(19, 54, 97, 0.4);
        }

        .icon-action.delete {
            background: linear-gradient(135deg, #e45f5a 0%, #cb3f3a 100%);
            border-color: rgba(165, 40, 34, 0.45);
        }

        .btn-order {
            width: 40px;
            min-width: 40px;
            height: 34px;
            padding: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #1e3a5f 0%, #146C94 100%);
            color: #fff;
            box-shadow: 0 6px 12px rgba(20, 108, 148, 0.22);
        }

        .btn-order:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 16px rgba(20, 108, 148, 0.26);
        }

        .btn-order.is-disabled,
        .btn-order.is-disabled:hover {
            background: #d5dde6;
            color: #8b9aac;
            box-shadow: none;
            transform: none;
            cursor: not-allowed;
            pointer-events: none;
        }

        .actions {
            display: flex;
            flex-direction: row;
            gap: 8px;
            align-items: center;
            justify-content: center;
        }

        .actions-order {
            display: flex;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 13px;
            color: #102C57;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(16, 44, 87, 0.2);
            border-radius: 12px;
            font-size: 13px;
            background: white;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .status-select-native {
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

        .status-toggle {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
            padding: 5px;
            border-radius: 12px;
            border: 1px solid rgba(16, 44, 87, 0.18);
            background: linear-gradient(180deg, #f8fbff 0%, #edf4fb 100%);
        }

        .status-option {
            border: none;
            border-radius: 9px;
            padding: 9px 10px;
            font-size: 13px;
            font-weight: 700;
            color: #3f5b78;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .status-option:hover {
            background: rgba(20, 108, 148, 0.12);
            color: #1e3a5f;
        }

        .status-option.active {
            background: linear-gradient(135deg, #1e3a5f 0%, #146C94 100%);
            color: #fff;
            box-shadow: 0 8px 14px rgba(20, 108, 148, 0.24);
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

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: rgba(63, 182, 168, 0.56);
            box-shadow: 0 0 0 0.2rem rgba(63, 182, 168, 0.14);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 130px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        .help-text {
            font-size: 12px;
            color: #6b7c93;
            margin-top: 6px;
            font-style: italic;
            line-height: 1.45;
        }

        .file-preview {
            display: flex;
            gap: 14px;
            margin-top: 14px;
            align-items: flex-start;
        }

        .preview-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            object-position: center;
            border-radius: 10px;
            border: 1px solid rgba(16, 44, 87, 0.16);
            background: #eef4f8;
        }

        .preview-label {
            font-size: 12px;
            color: #6b7c93;
            margin-bottom: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7c93;
        }

        .empty-state .icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        @media (max-width: 767px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .admin-content {
                padding: 12px 8px 20px;
            }

            .panel {
                padding: 12px;
                border-radius: 16px;
            }

            .table-scroll {
                overflow: hidden;
            }

            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
                width: 100%;
            }

            table {
                border: 0;
                background: transparent;
            }

            thead {
                display: none;
            }

            tbody tr {
                position: relative;
                display: flex;
                flex-wrap: wrap;
                align-items: flex-start;
                gap: 10px 10px;
                background: #fff;
                border: 1px solid rgba(16, 44, 87, 0.1);
                border-radius: 16px;
                padding: 13px;
                margin-bottom: 12px;
                box-shadow: 0 8px 16px rgba(15, 39, 66, 0.05);
            }

            tbody td {
                padding: 0;
                border: 0;
                min-width: 0;
                text-align: left;
            }

            tbody td::before {
                content: none;
            }

            tbody td[data-label="Foto"] {
                order: 1;
                flex: 0 0 56px;
                display: flex;
                align-items: center;
                justify-content: flex-start;
            }

            tbody td[data-label="Judul"] {
                order: 2;
                flex: 1 1 calc(100% - 72px);
                min-height: 56px;
                display: flex;
                align-items: center;
                font-size: 14px;
                font-weight: 800;
                color: #0f2d52;
                line-height: 1.3;
                word-break: break-word;
                padding-right: 44px;
            }

            tbody td[data-label="Urutan"] {
                position: absolute;
                top: 14px;
                right: 14px;
                z-index: 1;
            }

            tbody td[data-label="Urutan"] strong {
                width: 30px;
                height: 30px;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #eef2f7;
                color: #1e3a5f;
                font-size: 14px;
                font-weight: 800;
            }

            tbody td[data-label="Status"] {
                order: 3;
                flex: 1 1 100%;
                display: flex;
                align-items: center;
                justify-content: flex-start;
                gap: 8px;
                padding-top: 10px;
                border-top: 1px dashed #e5edf5;
            }

            tbody td[data-label="Status"]::after {
                content: 'Status';
                order: -1;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.3px;
                text-transform: uppercase;
                color: #7b8ea6;
            }

            tbody td[data-label="Aksi"] {
                order: 4;
                flex: 1 1 100%;
                display: block;
            }

            .thumbnail {
                width: 56px;
                height: 56px;
                border-radius: 12px;
            }

            .actions {
                display: flex;
                flex-direction: row;
                width: 100%;
                gap: 8px;
                margin-top: 0;
            }

            .actions a,
            .actions form {
                flex: 1;
                width: 100%;
            }

            .actions button,
            .actions .icon-action {
                width: 100%;
                min-width: 0;
                height: 36px;
                border-radius: 10px;
            }

            .icon-action i {
                font-size: 14px;
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
                <h1>Kelola Pelayanan</h1>
            </div>
            <div class="admin-topbar-meta">Halo, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong></div>
        </header>
        <div class="admin-content">

<div class="container">
    <div class="layout">
    <div class="panel panel-list">
        <div class="panel-title list-title">Daftar Pelayanan</div>
        <?php if (count($pelayanan_list) > 0): ?>
            <?php $total_rows = count($pelayanan_list); ?>
                <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Urutan</th>
                            <th>Judul</th>
                            <th>Foto</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pelayanan_list as $index => $item): ?>
                            <tr>
                                <td data-label="Urutan"><strong><?php echo htmlspecialchars($item['urutan']); ?></strong></td>
                                <td data-label="Judul"><?php echo htmlspecialchars($item['judul']); ?></td>
                                <td data-label="Foto">
                                    <?php if (!empty($item['foto'])): ?>
                                        <img src="../uploads/pelayanan/<?php echo htmlspecialchars($item['foto']); ?>" alt="Foto" class="thumbnail">
                                    <?php else: ?>
                                        <span style="color:#999; font-size:12px;">Tidak ada foto</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status">
                                    <span class="badge <?php echo $item['status'] === 'aktif' ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo $item['status'] === 'aktif' ? 'Aktif' : 'Nonaktif'; ?>
                                    </span>
                                </td>
                                <td data-label="Aksi">
                                    <div class="actions">
                                        <a href="?edit_id=<?php echo $item['id']; ?>" class="icon-action edit" title="Edit" aria-label="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="icon-action delete" title="Hapus" aria-label="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon"><i class="fas fa-layer-group"></i></div>
                <p>Belum ada data pelayanan.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel panel-upload" id="pelayanan-form-panel">
        <div class="panel-title upload-title"><?php echo $edit_data ? 'Edit Pelayanan' : 'Tambah Pelayanan'; ?></div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="<?php echo $edit_data ? 'edit' : 'add'; ?>">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Urutan <span style="color:red;">*</span></label>
                        <input type="number" name="urutan" value="<?php echo $edit_data['urutan'] ?? $_POST['urutan'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Status <span style="color:red;">*</span></label>
                        <select id="status_select" name="status" required class="status-select-native">
                                <option value="aktif" <?php echo (($edit_data['status'] ?? $_POST['status'] ?? 'aktif') === 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                                <option value="nonaktif" <?php echo (($edit_data['status'] ?? $_POST['status'] ?? '') === 'nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                        </select>
                        <div class="status-toggle" id="status_toggle" role="radiogroup" aria-label="Status Pelayanan">
                            <button type="button" class="status-option" data-value="aktif">Aktif</button>
                            <button type="button" class="status-option" data-value="nonaktif">Nonaktif</button>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Judul <span style="color:red;">*</span></label>
                    <input type="text" name="judul" value="<?php echo htmlspecialchars($edit_data['judul'] ?? $_POST['judul'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi <span style="color:red;">*</span></label>
                    <textarea name="deskripsi" required><?php echo htmlspecialchars($edit_data['deskripsi'] ?? $_POST['deskripsi'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Foto Pelayanan</label>
                    <div class="file-upload-wrap">
                        <input type="file" id="foto_input" class="file-input-native" name="foto" accept="image/jpeg,image/png,image/webp" onchange="previewImage(event)">
                        <button type="button" id="file_upload_button" class="file-upload-button">
                            <i class="fas fa-cloud-upload-alt"></i> Pilih Foto
                        </button>
                        <div class="file-upload-name" id="file_name_text">Belum ada file dipilih</div>
                        <div class="file-upload-meta">
                            <i class="fas fa-file-image"></i>
                            <span>Upload gambar terbaik untuk thumbnail pelayanan</span>
                        </div>
                    </div>
                    <p class="help-text">
                        <strong>Format:</strong> JPG, PNG, WebP | <strong>Max: 10MB</strong><br>
                        <em>Gambar akan otomatis di-resize dan di-compress untuk optimal loading</em>
                    </p>
                </div>

                <div class="form-group">
                    <label>Posisi Vertikal Foto (<span id="foto_posisi_value"><?php echo intval($edit_data['foto_posisi_y'] ?? $_POST['foto_posisi_y'] ?? 50); ?></span>%)</label>
                    <input type="range" id="foto_posisi_y" name="foto_posisi_y" min="0" max="100" value="<?php echo intval($edit_data['foto_posisi_y'] ?? $_POST['foto_posisi_y'] ?? 50); ?>" oninput="updateImagePositionPreview()">
                    <p class="help-text" style="margin-top:8px;">
                        Geser ke kiri untuk naik (atas), geser ke kanan untuk turun (bawah). Nilai 50% = center.
                    </p>
                </div>
                
                <!-- Preview Foto Lama (jika edit) -->
                <?php if ($edit_data && !empty($edit_data['foto'])): ?>
                    <div class="file-preview">
                        <div>
                            <p class="preview-label">Foto Saat Ini:</p>
                            <img src="../uploads/pelayanan/<?php echo htmlspecialchars($edit_data['foto']); ?>" alt="Current" class="preview-image" id="preview_existing" style="object-position:center <?php echo intval($edit_data['foto_posisi_y'] ?? 50); ?>%;">
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Preview Foto Baru -->
                <div id="preview_new" style="display:none;">
                    <div class="file-preview">
                        <div>
                            <p class="preview-label">Preview Foto Baru:</p>
                            <img id="preview_img" class="preview-image" alt="Preview" style="object-position:center <?php echo intval($edit_data['foto_posisi_y'] ?? $_POST['foto_posisi_y'] ?? 50); ?>%;">
                        </div>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?php echo $edit_data ? 'Perbarui Data' : 'Tambah Data'; ?>
                    </button>
                    <?php if ($edit_data): ?>
                        <a href="pelayanan.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    <?php endif; ?>
                </div>
            </form>
    </div>
    </div>
    
</div>

<script>
function previewImage(event) {
    const file = event.target.files[0];
    const fileNameText = document.getElementById('file_name_text');

    if (fileNameText) {
        fileNameText.textContent = file ? file.name : 'Belum ada file dipilih';
    }

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview_img').src = e.target.result;
            document.getElementById('preview_new').style.display = 'block';
            updateImagePositionPreview();
        };
        reader.readAsDataURL(file);
    }
}

function updateImagePositionPreview() {
    const slider = document.getElementById('foto_posisi_y');
    const valueEl = document.getElementById('foto_posisi_value');
    if (!slider) {
        return;
    }

    const value = slider.value;
    if (valueEl) {
        valueEl.textContent = value;
    }

    const previewNew = document.getElementById('preview_img');
    const previewExisting = document.getElementById('preview_existing');
    const positionValue = 'center ' + value + '%';

    if (previewNew) {
        previewNew.style.objectPosition = positionValue;
    }

    if (previewExisting) {
        previewExisting.style.objectPosition = positionValue;
    }
}

updateImagePositionPreview();

function initStatusToggle() {
    const select = document.getElementById('status_select');
    const toggle = document.getElementById('status_toggle');

    if (!select || !toggle) {
        return;
    }

    const options = toggle.querySelectorAll('.status-option');

    function syncStatusUI(value) {
        options.forEach(function(option) {
            option.classList.toggle('active', option.dataset.value === value);
            option.setAttribute('aria-checked', option.dataset.value === value ? 'true' : 'false');
        });
        select.value = value;
    }

    options.forEach(function(option) {
        option.addEventListener('click', function() {
            syncStatusUI(option.dataset.value);
        });
    });

    syncStatusUI(select.value || 'aktif');
}

function initFileUploadButton() {
    const input = document.getElementById('foto_input');
    const button = document.getElementById('file_upload_button');
    const wrap = document.querySelector('.file-upload-wrap');

    if (!input || !button || !wrap) {
        return;
    }

    button.addEventListener('click', function() {
        input.click();
    });

    wrap.addEventListener('dragenter', function() {
        wrap.classList.add('is-highlight');
    });

    wrap.addEventListener('dragleave', function() {
        wrap.classList.remove('is-highlight');
    });
}

initStatusToggle();
initFileUploadButton();

<?php if ($edit_data): ?>
document.getElementById('pelayanan-form-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
<?php endif; ?>
</script>

        </div>
    </main>
</div>

</body>
</html>





