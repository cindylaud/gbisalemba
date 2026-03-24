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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F3F9FB;
            color: #102C57;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .header-section {
            background: linear-gradient(135deg, #1E3A5F 0%, #0F2742 100%);
            color: white;
            padding: 30px 20px;
            margin: -30px -20px 30px;
            border-radius: 0 0 8px 8px;
        }
        
        .header-section h1 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .breadcrumb {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .breadcrumb a {
            color: white;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .alert-success {
            background-color: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }
        
        .alert-error {
            background-color: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin: 30px 0 30px;
            border-bottom: 2px solid #E0E0E0;
        }
        
        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            color: #102C57;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab-btn.active {
            border-bottom-color: #3FB6A8;
            color: #3FB6A8;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #102C57;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #DDD;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: #1E3A5F;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0F2742;
        }
        
        .btn-secondary {
            background-color: #E0E0E0;
            color: #102C57;
        }
        
        .btn-secondary:hover {
            background-color: #D0D0D0;
        }
        
        .btn-danger {
            background-color: #DC3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #C82333;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .btn-download {
            background-color: #28a745;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-download:hover {
            background-color: #218838;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background-color: #1E3A5F;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        table td {
            padding: 15px;
            border-bottom: 1px solid #EEE;
        }
        
        table tr:hover {
            background-color: #F9F9F9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-aktif {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status-nonaktif {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 10px 16px;
            background-color: #3FB6A8;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .file-input-label:hover {
            background-color: #2E9A8E;
        }
        
        .file-input-wrapper input[type="file"] {
            display: none;
        }
        
        .file-info {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            align-items: flex-start;
        }
        
        .file-icon {
            width: 60px;
            height: 60px;
            background-color: #F0F0F0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #EEE;
            font-size: 24px;
            color: #666;
        }
        
        .file-details {
            flex: 1;
        }
        
        .file-name {
            font-weight: 600;
            color: #102C57;
            margin-bottom: 5px;
            word-break: break-all;
        }
        
        .file-size {
            font-size: 12px;
            color: #666;
        }
        
        .preview-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .required {
            color: red;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 13px;
            }
            
            table th, table td {
                padding: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons a,
            .action-buttons button {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="header-section">
    <div class="container">
        <h1>Kelola Formulir</h1>
        <p class="breadcrumb"><a href="index.php">← Dashboard Admin</a></p>
    </div>
</div>

<div class="container">
    
    <!-- ALERTS -->
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- TABS -->
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('list')">
            <i class="fas fa-list"></i> Daftar Formulir
        </button>
        <button class="tab-btn" onclick="switchTab('form')">
            <i class="fas fa-plus"></i> <?php echo $edit_data ? 'Edit' : 'Tambah'; ?> Formulir
        </button>
    </div>
    
    <!-- TAB: LIST -->
    <div id="list" class="tab-content active">
        <?php if (count($formulir_list) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Formulir</th>
                            <th>Deskripsi</th>
                            <th>File</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($formulir_list as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['nama_formulir']); ?></td>
                                <td><?php echo htmlspecialchars(substr($item['deskripsi'], 0, 50)); ?><?php echo strlen($item['deskripsi']) > 50 ? '...' : ''; ?></td>
                                <td>
                                    <?php if (!empty($item['file']) && file_exists(UPLOAD_DIR . $item['file'])): ?>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-file-pdf" style="color: #DC3545;"></i>
                                            <a href="download-formulir.php?id=<?php echo $item['id']; ?>" 
                                               target="_blank" 
                                               class="btn btn-download btn-small"
                                               title="Download PDF">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#999; font-size:12px;">File tidak tersedia</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- Move Up Button -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="move_up">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-small" title="Pindahkan ke atas">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                        </form>
                                        
                                        <!-- Move Down Button -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="move_down">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-small" title="Pindahkan ke bawah">
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                        </form>
                                        
                                        <!-- Edit Button -->
                                        <a href="?edit_id=<?php echo $item['id']; ?>" class="btn btn-primary btn-small" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <!-- Delete Button -->
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus formulir ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-small" title="Hapus">
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
            <div class="no-data">
                <i class="fas fa-inbox" style="font-size:48px; color:#ccc; margin-bottom:20px;"></i>
                <p>Belum ada data formulir.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- TAB: FORM -->
    <div id="form" class="tab-content">
        <div class="form-card">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="<?php echo $edit_data ? 'edit' : 'add'; ?>">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Status <span class="required">*</span></label>
                    <select name="status" required>
                        <option value="aktif" <?php echo (($edit_data['status'] ?? $_POST['status'] ?? 'aktif') === 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                        <option value="nonaktif" <?php echo (($edit_data['status'] ?? $_POST['status'] ?? '') === 'nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
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
                    <div class="file-input-wrapper">
                        <label for="file_input" class="file-input-label">
                            <i class="fas fa-upload"></i> Pilih File PDF<?php echo !$edit_data ? ' (Wajib)' : ' (Opsional)'; ?>
                        </label>
                        <input type="file" id="file_input" name="file" accept="application/pdf" onchange="previewFile(event)">
                    </div>
                    <p style="font-size:12px; color:#999; margin-top:10px;">
                        <strong>Format:</strong> PDF | <strong>Max: 10MB</strong><br>
                        <?php echo !$edit_data ? '<em>File PDF wajib diupload saat menambah formulir baru.</em>' : '<em>Jika ingin mengganti file, upload file PDF baru. Jika tidak, file lama akan tetap digunakan.</em>'; ?>
                    </p>
                </div>
                
                <!-- List File Lama (jika edit) -->
                <?php if ($edit_data && !empty($edit_data['file'])): ?>
                    <div style="background-color: #F9F9F9; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                        <p class="preview-label"><strong>File Saat Ini:</strong></p>
                        <div class="file-info">
                            <div class="file-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="file-details">
                                <div class="file-name"><?php echo htmlspecialchars($edit_data['file']); ?></div>
                                <?php 
                                $file_path = UPLOAD_DIR . $edit_data['file'];
                                if (file_exists($file_path)) {
                                    $file_size = filesize($file_path);
                                    $file_size_kb = round($file_size / 1024, 2);
                                    echo '<div class="file-size">Ukuran: ' . $file_size_kb . ' KB</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Preview File Baru -->
                <div id="preview_new" style="display:none; background-color: #E7F3F1; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <p class="preview-label"><strong>File Baru:</strong></p>
                    <div class="file-info">
                        <div class="file-icon">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <div class="file-details">
                            <div class="file-name" id="preview_filename"></div>
                            <div class="file-size" id="preview_filesize"></div>
                        </div>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?php echo $edit_data ? 'Perbarui Formulir' : 'Tambah Formulir'; ?>
                    </button>
                    <?php if ($edit_data): ?>
                        <a href="?" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
</div>

<script>
function switchTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tab).classList.add('active');
    event.target.closest('.tab-btn').classList.add('active');
}

function previewFile(event) {
    const file = event.target.files[0];
    if (file) {
        document.getElementById('preview_filename').textContent = file.name;
        document.getElementById('preview_filesize').textContent = 'Ukuran: ' + (file.size / 1024).toFixed(2) + ' KB';
        document.getElementById('preview_new').style.display = 'block';
    }
}
</script>

</body>
</html>



