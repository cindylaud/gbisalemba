<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

// =====================================================================
// KONFIGURASI UPLOAD
// =====================================================================
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_DIR', '../uploads/formulir/');
define('ALLOWED_MIME', 'application/pdf');

// Pastikan folder upload ada
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// =====================================================================
// AMBIL DATA FORMULIR UNTUK LIST
// =====================================================================
$stmt = $conn->prepare("SELECT id, nama_formulir, file, deskripsi, status, urutan, created_at 
                       FROM formulir 
                       ORDER BY urutan ASC");
$stmt->execute();
$result = $stmt->get_result();
$formulir_list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// =====================================================================
// HANDLE EDIT DATA (Load data jika ada parameter edit_id)
// =====================================================================
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT id, nama_formulir, file, deskripsi, status, urutan 
                           FROM formulir 
                           WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// =====================================================================
// HANDLE MOVE UP
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'move_up') {

    $id = intval($_POST['id']);

    $stmt = $conn->prepare("SELECT id, urutan FROM formulir WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $current = $result->fetch_assoc();
        $current_urutan = $current['urutan'];

        $stmt_prev = $conn->prepare("
            SELECT id, urutan 
            FROM formulir 
            WHERE urutan < ? 
            ORDER BY urutan DESC 
            LIMIT 1
        ");
        $stmt_prev->bind_param("i", $current_urutan);
        $stmt_prev->execute();
        $result_prev = $stmt_prev->get_result();

        if ($result_prev->num_rows > 0) {

            $prev = $result_prev->fetch_assoc();

            $conn->begin_transaction();

            try {

                $stmt1 = $conn->prepare("UPDATE formulir SET urutan = ? WHERE id = ?");
                $stmt1->bind_param("ii", $prev['urutan'], $current['id']);
                $stmt1->execute();

                $stmt2 = $conn->prepare("UPDATE formulir SET urutan = ? WHERE id = ?");
                $stmt2->bind_param("ii", $current_urutan, $prev['id']);
                $stmt2->execute();

                $conn->commit();
                $success = "Formulir berhasil dipindahkan ke atas.";

                $stmt1->close();
                $stmt2->close();

            } catch (Exception $e) {
                $conn->rollback();
                $error = "Gagal memindahkan formulir.";
            }

        } else {
            $error = "Formulir sudah di posisi paling atas.";
        }

        $stmt_prev->close();
    }

    $stmt->close();
}


// =====================================================================
// HANDLE MOVE DOWN
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'move_down') {

    $id = intval($_POST['id']);

    $stmt = $conn->prepare("SELECT id, urutan FROM formulir WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $current = $result->fetch_assoc();
        $current_urutan = $current['urutan'];

        $stmt_next = $conn->prepare("
            SELECT id, urutan 
            FROM formulir 
            WHERE urutan > ? 
            ORDER BY urutan ASC 
            LIMIT 1
        ");
        $stmt_next->bind_param("i", $current_urutan);
        $stmt_next->execute();
        $result_next = $stmt_next->get_result();

        if ($result_next->num_rows > 0) {

            $next = $result_next->fetch_assoc();

            $conn->begin_transaction();

            try {

                $stmt1 = $conn->prepare("UPDATE formulir SET urutan = ? WHERE id = ?");
                $stmt1->bind_param("ii", $next['urutan'], $current['id']);
                $stmt1->execute();

                $stmt2 = $conn->prepare("UPDATE formulir SET urutan = ? WHERE id = ?");
                $stmt2->bind_param("ii", $current_urutan, $next['id']);
                $stmt2->execute();

                $conn->commit();
                $success = "Formulir berhasil dipindahkan ke bawah.";

                $stmt1->close();
                $stmt2->close();

            } catch (Exception $e) {
                $conn->rollback();
                $error = "Gagal memindahkan formulir.";
            }

        } else {
            $error = "Formulir sudah di posisi paling bawah.";
        }

        $stmt_next->close();
    }

    $stmt->close();
}



// =====================================================================
// HANDLE DELETE ACTION
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);
    
    // Get file name for deletion
    $stmt = $conn->prepare("SELECT file FROM formulir WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $old_file = $row['file'];
        
        // Delete from database
        $stmt_del = $conn->prepare("DELETE FROM formulir WHERE id = ?");
        $stmt_del->bind_param("i", $id);
        
        if ($stmt_del->execute()) {
            // Delete file from folder if exists
            $file_path = UPLOAD_DIR . $old_file;
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
            $success = "Formulir berhasil dihapus.";
        } else {
            $error = "Gagal menghapus formulir: " . $stmt_del->error;
        }
        $stmt_del->close();
    }
    $stmt->close();
}

// =====================================================================
// HANDLE FORM SUBMISSION (ADD & EDIT)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
    $form_action = $_POST['form_action'];
    $nama_formulir = trim($_POST['nama_formulir'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status = trim($_POST['status'] ?? 'aktif');
    
    // Validasi input
    if (empty($nama_formulir) || empty($deskripsi)) {
        $error = "Nama formulir dan deskripsi tidak boleh kosong.";
    } else {
        $file_name = null;
        $old_file = null;
        
        // ====== GET OLD FILE NAME (jika edit) ======
        if ($form_action === 'edit') {
            $id_edit = intval($_POST['id'] ?? 0);
            $stmt = $conn->prepare("SELECT file FROM formulir WHERE id = ?");
            $stmt->bind_param("i", $id_edit);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $old_file = $row['file'];
            }
            $stmt->close();
        }
        
        // ====== HANDLE FILE UPLOAD ======
        if (isset($_FILES['file']) && $_FILES['file']['size'] > 0) {
            $file = $_FILES['file'];
            
            // Validasi nama file
            if (empty($file['name'])) {
                $error = "Nama file tidak valid.";
            }
            // Validasi ukuran
            elseif ($file['size'] > MAX_UPLOAD_SIZE) {
                $error = "Ukuran file terlalu besar. Maksimal 10MB.";
            }
            // Validasi MIME type menggunakan finfo
            else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if ($mime !== ALLOWED_MIME) {
                    $error = "File harus berformat PDF. Tipe file terdeteksi: " . htmlspecialchars($mime);
                } else {
                    // Generate filename dari nama asli
                    $original_name = pathinfo($file['name'], PATHINFO_FILENAME);
                    
                    // Bersihkan karakter (lowercase, hapus spesial, ganti space dengan underscore)
                    $clean_name = strtolower($original_name);
                    $clean_name = preg_replace('/[^a-z0-9\s]/', '', $clean_name);
                    $clean_name = preg_replace('/\s+/', '_', $clean_name);
                    
                    // Pastikan nama tidak kosong
                    if (empty($clean_name)) {
                        $clean_name = 'formulir';
                    }
                    
                    // Tambahkan extension
                    $file_name = $clean_name . ".pdf";
                    
                    // Cek duplikat dan tambahkan increment jika perlu
                    $counter = 1;
                    while (file_exists(UPLOAD_DIR . $file_name)) {
                        $file_name = $clean_name . "_" . $counter . ".pdf";
                        $counter++;
                    }
                    
                    // Upload file
                    $upload_path = UPLOAD_DIR . $file_name;
                    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $error = "Gagal mengupload file. Silakan periksa permission folder.";
                        $file_name = null;
                    }
                }
            }
        } else {
            // Jika tidak ada file upload baru
            if ($form_action === 'add') {
                $error = "File PDF harus diupload.";
            } else {
                // Untuk edit, gunakan file lama jika tidak upload baru
                $file_name = $old_file;
            }
        }
        
        // ====== ADD NEW DATA ======
        if (!isset($error) && $form_action === 'add') {
            // Ambil urutan terakhir
            $result_max = $conn->query("SELECT COALESCE(MAX(urutan),0) + 1 AS next_urutan FROM formulir");
            $row_max = $result_max->fetch_assoc();
            $next_urutan = $row_max['next_urutan'];
            
            // Insert dengan urutan baru
            $stmt = $conn->prepare("INSERT INTO formulir 
                                   (nama_formulir, deskripsi, file, status, urutan) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $nama_formulir, $deskripsi, $file_name, $status, $next_urutan);
            
            if ($stmt->execute()) {
                $success = "Formulir berhasil ditambahkan.";
                $_POST = array();
            } else {
                $error = "Gagal menambah formulir: " . $stmt->error;
                // Hapus file jika insert gagal
                if ($file_name && file_exists(UPLOAD_DIR . $file_name)) {
                    @unlink(UPLOAD_DIR . $file_name);
                }
            }
            $stmt->close();
        }
        // ====== EDIT DATA ======
        elseif (!isset($error) && $form_action === 'edit') {
            $id_edit = intval($_POST['id'] ?? 0);
            $stmt = $conn->prepare("UPDATE formulir 
                                   SET nama_formulir = ?, deskripsi = ?, file = ?, status = ? 
                                   WHERE id = ?");
            $stmt->bind_param("ssssi", $nama_formulir, $deskripsi, $file_name, $status, $id_edit);
            
            if ($stmt->execute()) {
                $success = "Formulir berhasil diperbarui.";
                
                // Hapus file lama jika ada file baru
                if ($file_name !== $old_file && $old_file && file_exists(UPLOAD_DIR . $old_file)) {
                    @unlink(UPLOAD_DIR . $old_file);
                }
                
                // Reload edit data
                $stmt_reload = $conn->prepare("SELECT id, nama_formulir, file, deskripsi, status 
                                             FROM formulir 
                                             WHERE id = ?");
                $stmt_reload->bind_param("i", $id_edit);
                $stmt_reload->execute();
                $result_reload = $stmt_reload->get_result();
                if ($result_reload->num_rows > 0) {
                    $edit_data = $result_reload->fetch_assoc();
                }
                $stmt_reload->close();
            } else {
                $error = "Gagal memperbarui formulir: " . $stmt->error;
                // Hapus file baru jika update gagal
                if ($file_name !== $old_file && $file_name && file_exists(UPLOAD_DIR . $file_name)) {
                    @unlink(UPLOAD_DIR . $file_name);
                }
            }
            $stmt->close();
        }
    }
    
    // Reload list data
    $stmt = $conn->prepare("SELECT id, nama_formulir, file, deskripsi, status, urutan, created_at 
                           FROM formulir 
                           ORDER BY urutan ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $formulir_list = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Formulir - Admin GBI Salemba</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php $admin_theme_path = dirname(__DIR__) . '/assets/css/admin-theme.css'; ?>
    <link rel="stylesheet" href="/<?php echo htmlspecialchars(basename(dirname(__DIR__))); ?>/assets/css/admin-theme.css?v=<?php echo urlencode((string) (is_file($admin_theme_path) ? filemtime($admin_theme_path) : time())); ?>">
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

        .back-link {
            display: inline-block;
            margin-bottom: 14px;
            padding: 10px 20px;
            background-color: #146C94;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .back-link:hover { background-color: #0f5273; }

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

        .layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 16px;
            align-items: start;
        }

        .panel {
            background: linear-gradient(165deg, #f9f2e8 0%, #efe2d1 100%);
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 4px 12px rgba(16, 44, 87, 0.1);
        }

        .panel-upload {
            position: sticky;
            top: 104px;
        }

        .panel-title {
            font-size: 17px;
            font-weight: 700;
            color: #102C57;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid #146C94;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-title::before {
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 16px;
        }

        .list-title::before {
            content: '\f15c';
            color: #8b74c7;
        }

        .upload-title::before {
            content: '\2b';
            color: #7d5ad8;
        }

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

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #e2e3e5; color: #383d41; }

        .actions { display: flex; gap: 6px; flex-wrap: wrap; }

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

        .btn-sort {
            background: #6c757d;
            color: white;
            padding: 5px 8px;
        }

        .btn-sort:hover { background: #545b62; }

        .btn-download {
            background: #28a745;
            color: white;
        }

        .btn-download:hover { background: #218838; }

        .btn-edit {
            background: #1e3a5f;
            color: white;
        }

        .btn-edit:hover { background: #0f2742; }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover { background: #c82333; }

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

        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 14px;
            color: #102C57;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #146C94;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: border-color 0.3s ease;
        }

        .form-group textarea { min-height: 110px; resize: vertical; }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0f5273;
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

        .btn-submit:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 15px rgba(20, 108, 148, 0.3);
        }

        .file-box {
            background: rgba(255, 255, 255, 0.66);
            padding: 12px;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px dashed #b5b5b5;
        }

        .file-line {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 6px;
            font-size: 13px;
            color: #243b5f;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }

        .empty-state .icon { font-size: 48px; margin-bottom: 12px; }
        .empty-state p { font-size: 15px; }

        .required { color: #d93025; }

        @media (max-width: 980px) {
            .layout { grid-template-columns: 1fr; }
            .panel-upload {
                position: static;
                top: auto;
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
                <h1>Kelola Formulir</h1>
                <div class="admin-topbar-meta">Atur file PDF formulir untuk kebutuhan jemaat</div>
            </div>
            <div class="admin-topbar-meta">Halo, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong></div>
        </header>
        <div class="admin-content">

<div class="container">
    <!-- ALERTS -->
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            ✓ <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            ✗ <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="layout">
    <div class="panel panel-list">
        <div class="panel-title list-title">Daftar Formulir</div>
        <?php if (count($formulir_list) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Urutan</th>
                            <th>Nama Formulir</th>
                            <th>File</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($formulir_list as $index => $item): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <form method="POST" class="sort-form">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <input type="number" value="<?php echo intval($item['urutan']); ?>" min="1" readonly>
                                        <button type="submit" name="action" value="move_up" class="btn btn-sort" title="Naik">↑</button>
                                        <button type="submit" name="action" value="move_down" class="btn btn-sort" title="Turun">↓</button>
                                    </form>
                                </td>
                                <td><?php echo htmlspecialchars($item['nama_formulir']); ?></td>
                                <td>
                                    <?php if (!empty($item['file']) && file_exists(UPLOAD_DIR . $item['file'])): ?>
                                        <div class="actions">
                                                          <a href="../download-formulir.php?id=<?php echo $item['id']; ?>" 
                                               target="_blank" 
                                               class="btn btn-download"
                                               title="Download PDF">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Tidak tersedia</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($item['status'] ?? '') === 'aktif'): ?>
                                        <span class="badge badge-active">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="?edit_id=<?php echo $item['id']; ?>" class="btn btn-edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus formulir ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">📄</div>
                <p>Belum ada data formulir. Tambahkan formulir baru di panel kanan.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel panel-upload">
        <div class="panel-title upload-title"><?php echo $edit_data ? 'Edit Formulir' : 'Tambah Formulir'; ?></div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="<?php echo $edit_data ? 'edit' : 'add'; ?>">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Status <span class="required">*</span></label>
                    <select id="status_select" name="status" required class="status-select-native">
                        <option value="aktif" <?php echo (($edit_data['status'] ?? $_POST['status'] ?? 'aktif') === 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                        <option value="nonaktif" <?php echo (($edit_data['status'] ?? $_POST['status'] ?? '') === 'nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                    <div class="status-toggle" id="status_toggle" role="radiogroup" aria-label="Status Formulir">
                        <button type="button" class="status-option" data-value="aktif">Aktif</button>
                        <button type="button" class="status-option" data-value="nonaktif">Nonaktif</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Nama Formulir <span class="required">*</span></label>
                    <input type="text" name="nama_formulir" value="<?php echo htmlspecialchars($edit_data['nama_formulir'] ?? $_POST['nama_formulir'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi <span class="required">*</span></label>
                    <textarea name="deskripsi" required><?php echo htmlspecialchars($edit_data['deskripsi'] ?? $_POST['deskripsi'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>File PDF <?php echo !$edit_data ? '<span class="required">*</span>' : ''; ?></label>
                    <div class="file-upload-wrap">
                        <input type="file" id="file_input" class="file-input-native" name="file" accept="application/pdf" onchange="previewFile(event)">
                        <button type="button" id="file_upload_button" class="file-upload-button">
                            <i class="fas fa-file-arrow-up"></i> Pilih File PDF
                        </button>
                        <div class="file-upload-name" id="file_name_text">Belum ada file dipilih</div>
                        <div class="file-upload-meta">
                            <i class="fas fa-file-pdf"></i>
                            <span>Upload PDF formulir yang siap dibagikan ke jemaat</span>
                        </div>
                    </div>
                    <p class="info-text">
                        <strong>Format:</strong> PDF | <strong>Max: 10MB</strong><br>
                        <?php echo !$edit_data ? '<em>File PDF wajib diupload saat menambah formulir baru.</em>' : '<em>Jika ingin mengganti file, upload file PDF baru. Jika tidak, file lama akan tetap digunakan.</em>'; ?>
                    </p>
                </div>
                
                <!-- List File Lama (jika edit) -->
                <?php if ($edit_data && !empty($edit_data['file'])): ?>
                    <div class="file-box">
                        <div class="file-line"><i class="fas fa-file-pdf"></i> <strong>File Saat Ini:</strong></div>
                        <div class="file-line"><?php echo htmlspecialchars($edit_data['file']); ?></div>
                                <?php 
                                $file_path = UPLOAD_DIR . $edit_data['file'];
                                if (file_exists($file_path)) {
                                    $file_size = filesize($file_path);
                                    $file_size_kb = round($file_size / 1024, 2);
                                    echo '<div class="file-line">Ukuran: ' . $file_size_kb . ' KB</div>';
                                }
                                ?>
                    </div>
                <?php endif; ?>
                
                <!-- Preview File Baru -->
                <div id="preview_new" class="file-box" style="display:none;">
                    <div class="file-line"><i class="fas fa-file-pdf"></i> <strong>File Baru:</strong></div>
                    <div class="file-line" id="preview_filename"></div>
                    <div class="file-line" id="preview_filesize"></div>
                </div>
                
                    <button type="submit" class="btn-submit">
                        <?php echo $edit_data ? 'Perbarui Formulir' : 'Tambah Formulir'; ?>
                    </button>
                    <?php if ($edit_data): ?>
                        <a href="formulir.php" class="back-link" style="display:block; text-align:center; margin-top:12px; margin-bottom:0;">Batal Edit</a>
                    <?php endif; ?>
            </form>
    </div>
</div>
</div>

<script>
function previewFile(event) {
    const file = event.target.files[0];
    const fileNameText = document.getElementById('file_name_text');

    if (fileNameText) {
        fileNameText.textContent = file ? file.name : 'Belum ada file dipilih';
    }

    if (file) {
        document.getElementById('preview_filename').textContent = file.name;
        document.getElementById('preview_filesize').textContent = 'Ukuran: ' + (file.size / 1024).toFixed(2) + ' KB';
        document.getElementById('preview_new').style.display = 'block';
    }
}

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
    const input = document.getElementById('file_input');
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
</script>

        </div>
    </main>
</div>

</body>
</html>



