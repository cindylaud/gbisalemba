<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/image-helper.php';

// Define constants
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_DIR', '../uploads/pelayanan/');
define('MAX_IMAGE_WIDTH', 1600); // pixels
define('JPEG_QUALITY', 80); // 0-100

function ensurePelayananPositionColumn(mysqli $conn) {
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

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pelayanan - Admin GBI Salemba</title>
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
        
        .thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            object-position: center;
            border-radius: 4px;
            background-color: #EEE;
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
        
        .file-preview {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            align-items: flex-start;
        }
        
        .preview-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            object-position: center;
            border-radius: 6px;
            border: 2px solid #EEE;
        }
        
        .preview-info {
            flex: 1;
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
        }
    </style>
</head>
<body>

<div class="header-section">
    <div class="container">
        <h1>Kelola Pelayanan</h1>
        <p class="breadcrumb"><a href="index.php" style="color:#fff; text-decoration:none;">← Dashboard Admin</a></p>
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
            <i class="fas fa-list"></i> Daftar Pelayanan
        </button>
        <button class="tab-btn" onclick="switchTab('form')">
            <i class="fas fa-plus"></i> <?php echo $edit_data ? 'Edit' : 'Tambah'; ?> Pelayanan
        </button>
    </div>
    
    <!-- TAB: LIST -->
    <div id="list" class="tab-content active">
        <?php if (count($pelayanan_list) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Urutan</th>
                            <th>Judul</th>
                            <th>Foto</th>
                            <th>Posisi Foto</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pelayanan_list as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['urutan']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['judul']); ?></td>
                                <td>
                                    <?php if (!empty($item['foto'])): ?>
                                        <img src="../uploads/pelayanan/<?php echo htmlspecialchars($item['foto']); ?>" alt="Foto" class="thumbnail">
                                    <?php else: ?>
                                        <span style="color:#999; font-size:12px;">Tidak ada foto</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo isset($item['foto_posisi_y']) ? intval($item['foto_posisi_y']) : 50; ?>%</td>
                                <td>
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit_id=<?php echo $item['id']; ?>" class="btn btn-primary btn-small">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-small">
                                                <i class="fas fa-trash"></i> Hapus
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
                <p>Belum ada data pelayanan.</p>
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
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Urutan <span style="color:red;">*</span></label>
                        <input type="number" name="urutan" value="<?php echo $edit_data['urutan'] ?? $_POST['urutan'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Status <span style="color:red;">*</span></label>
                        <select name="status" required>
                            <option value="aktif" <?php echo (($edit_data['status'] ?? $_POST['status'] ?? 'aktif') === 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                            <option value="nonaktif" <?php echo (($edit_data['status'] ?? $_POST['status'] ?? '') === 'nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                        </select>
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
                    <div class="file-input-wrapper">
                        <label for="foto_input" class="file-input-label">
                            <i class="fas fa-upload"></i> Pilih Foto (Optional)
                        </label>
                        <input type="file" id="foto_input" name="foto" accept="image/jpeg,image/png,image/webp" onchange="previewImage(event)">
                    </div>
                    <p style="font-size:12px; color:#999; margin-top:10px;">
                        <strong>Format:</strong> JPG, PNG, WebP | <strong>Max: 10MB</strong><br>
                        <em>Gambar akan otomatis di-resize dan di-compress untuk optimal loading</em>
                    </p>
                </div>

                <div class="form-group">
                    <label>Posisi Vertikal Foto (<span id="foto_posisi_value"><?php echo intval($edit_data['foto_posisi_y'] ?? $_POST['foto_posisi_y'] ?? 50); ?></span>%)</label>
                    <input type="range" id="foto_posisi_y" name="foto_posisi_y" min="0" max="100" value="<?php echo intval($edit_data['foto_posisi_y'] ?? $_POST['foto_posisi_y'] ?? 50); ?>" oninput="updateImagePositionPreview()">
                    <p style="font-size:12px; color:#999; margin-top:8px;">
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

function previewImage(event) {
    const file = event.target.files[0];
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
</script>

</body>
</html>





