<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Get flash messages dari session
$success = '';
$error = '';
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Get list formulir untuk display
$stmt = $conn->prepare("SELECT id, nama_formulir, file, deskripsi, is_active, created_at 
                       FROM formulir 
                       ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();
$formulir_list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        
        .header-section a {
            color: white;
            text-decoration: none;
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
            min-height: 80px;
        }
        
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .btn-danger:hover {
            background-color: #C82333;
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
        
        .status-1 {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status-0 {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .required {
            color: red;
        }
        
        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            table {
                font-size: 13px;
            }
            
            table th, table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body class="admin-theme">

<div class="header-section">
    <div class="container">
        <h1>Kelola Formulir</h1>
        <p><a href="index.php">← Dashboard Admin</a></p>
    </div>
</div>

<div class="container">
    
    <!-- ALERTS -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
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
            <i class="fas fa-plus"></i> Tambah Formulir
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
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($formulir_list as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['nama_formulir']); ?></strong></td>
                                <td><?php echo htmlspecialchars(substr($item['deskripsi'], 0, 40)); ?><?php echo strlen($item['deskripsi']) > 40 ? '...' : ''; ?></td>
                                <td>
                                    <?php if (!empty($item['file'])): ?>
                                        <i class="fas fa-file-pdf" style="color: #DC3545;"></i> 
                                        <small><?php echo htmlspecialchars($item['file']); ?></small>
                                    <?php else: ?>
                                        <span style="color:#999;">Tidak ada file</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $item['is_active']; ?>">
                                        <?php echo $item['is_active'] == 1 ? 'Aktif' : 'Nonaktif'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></td>
                                <td>
                                    <a href="download-formulir.php?id=<?php echo $item['id']; ?>" 
                                       class="btn btn-primary" style="padding: 8px 12px; font-size: 12px; display: inline-block;">
                                        <i class="fas fa-download"></i>
                                    </a>
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
            <form method="POST" action="process-add-formulir.php" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label>Nama Formulir <span class="required">*</span></label>
                    <input type="text" name="nama_formulir" required placeholder="Contoh: Formulir Permohonan Doa">
                    <p class="info-text">Maksimal 100 karakter</p>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi <span class="required">*</span></label>
                    <textarea name="deskripsi" required placeholder="Jelaskan kegunaan formulir ini..."></textarea>
                    <p class="info-text">Maksimal 255 karakter</p>
                </div>
                
                <div class="form-group">
                    <label>File PDF <span class="required">*</span></label>
                    <div class="file-input-wrapper">
                        <label for="file_input" class="file-input-label">
                            <i class="fas fa-upload"></i> Pilih File PDF
                        </label>
                        <input type="file" id="file_input" name="file" accept="application/pdf" required onchange="displayFileName(this)">
                    </div>
                    <p class="info-text">Format: PDF | Maksimal: 10MB</p>
                    <p id="file_name" style="margin-top: 10px; color: #3FB6A8; font-weight: 600;"></p>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="is_active">
                        <option value="1">Aktif (Akan tampil di frontend)</option>
                        <option value="0">Nonaktif (Disembunyikan dari frontend)</option>
                    </select>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Tambah Formulir
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset Form
                    </button>
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

function displayFileName(input) {
    const fileName = input.files[0]?.name || '';
    const fileSizeKB = (input.files[0]?.size / 1024).toFixed(2) || 0;
    
    if (fileName) {
        document.getElementById('file_name').textContent = '✓ ' + fileName + ' (' + fileSizeKB + ' KB)';
    } else {
        document.getElementById('file_name').textContent = '';
    }
}
</script>

</body>
</html>



