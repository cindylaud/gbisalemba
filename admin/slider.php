<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/image-helper.php';

// Define constants untuk slider
define('SLIDER_MAX_UPLOAD_SIZE', 30 * 1024 * 1024); // 30MB
define('SLIDER_UPLOAD_DIR', __DIR__ . '/../uploads/slider/');
define('SLIDER_MAX_WIDTH', 2000); // pixels
define('SLIDER_JPEG_QUALITY', 80); // 0-100

$message = '';
$error = '';

// Auto-seed: Pastikan record urutan 1-4 selalu ada (image 'default.png' karena NOT NULL)
for ($i = 1; $i <= 4; $i++) {
    $check = $conn->prepare("SELECT id FROM slider WHERE urutan = ?");
    $check->bind_param("i", $i);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows == 0) {
        $default_img = 'default.png';
        $insert = $conn->prepare("INSERT INTO slider (urutan, title, subtitle, image, is_active, created_at) VALUES (?, NULL, NULL, ?, 0, NOW())");
        $insert->bind_param("is", $i, $default_img);
        $insert->execute();
    }
    $check->close();
}

// Handle POST: Upload & Simpan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['urutan'])) {
    $urutan = intval($_POST['urutan']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
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
                    $stmt = $conn->prepare("UPDATE slider SET image = ?, is_active = ?, updated_at = NOW() WHERE urutan = ?");
                    $stmt->bind_param("sii", $new_filename, $is_active, $urutan);
                } else {
                    // Hanya update is_active (tidak ubah image)
                    $stmt = $conn->prepare("UPDATE slider SET is_active = ?, updated_at = NOW() WHERE urutan = ?");
                    $stmt->bind_param("ii", $is_active, $urutan);
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
                $stmt = $conn->prepare("INSERT INTO slider (urutan, title, subtitle, image, is_active, created_at) VALUES (?, NULL, NULL, ?, ?, NOW())");
                $stmt->bind_param("isi", $urutan, $img, $is_active);
                
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
$query = "SELECT id, urutan, image, is_active FROM slider WHERE urutan IN (1,2,3,4) ORDER BY urutan ASC";
$result = $conn->query($query);
$cards = [];
while ($row = $result->fetch_assoc()) {
    $cards[$row['urutan']] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Slider - Admin GBI Salemba</title>
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
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        h1 {
            color: #102C57;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #146C94;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        
        .back-link:hover {
            background-color: #0f5273;
        }
        
        .alert {
            padding: 15px 20px;
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
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: #EADBC8;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(16, 44, 87, 0.1);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(16, 44, 87, 0.15);
        }
        
        .card-title {
            font-size: 22px;
            font-weight: bold;
            color: #102C57;
            margin-bottom: 20px;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #146C94;
        }
        
        .preview-box {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            border: 2px dashed #b0b0b0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            overflow: hidden;
            position: relative;
        }
        
        .preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .preview-box .placeholder {
            text-align: center;
            color: #999;
        }
        
        .preview-box .placeholder .icon {
            font-size: 60px;
            margin-bottom: 10px;
            opacity: 0.5;
        }
        
        .preview-box .placeholder .text {
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #102C57;
            font-size: 14px;
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #146C94;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }
        
        .form-group input[type="file"]:hover {
            border-color: #0f5273;
        }
        
        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
            padding: 10px;
            background: white;
            border-radius: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 22px;
            height: 22px;
            margin-right: 12px;
            cursor: pointer;
            accent-color: #146C94;
        }
        
        .checkbox-group label {
            font-weight: 600;
            color: #102C57;
            cursor: pointer;
            user-select: none;
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
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
        
        .btn-submit:active {
            transform: scale(0.98);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .status-active {
            background-color: #28a745;
            color: white;
        }
        
        .status-inactive {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index.php" class="back-link">← Kembali ke Dashboard</a>
            <h1>Kelola Slider (4 Foto)</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">✓ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">✗ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="cards-grid">
            <?php for ($i = 1; $i <= 4; $i++): 
                $card_data = isset($cards[$i]) ? $cards[$i] : null;
                
                // Cek gambar: harus ada nilai (bukan default.png) dan file fisik exists
                $has_image = false;
                if ($card_data && !empty($card_data['image']) && $card_data['image'] !== 'default.png') {
                    $file_path = __DIR__ . '/../uploads/slider/' . $card_data['image'];
                    $has_image = file_exists($file_path);
                }
                
                $image_path = $has_image ? '../uploads/slider/' . $card_data['image'] : '';
                $is_active = $card_data && $card_data['is_active'] == 1;
            ?>
                <div class="card">
                    <div class="card-title">
                        Foto <?php echo $i; ?>
                        <?php if ($is_active): ?>
                            <span class="status-badge status-active">AKTIF</span>
                        <?php else: ?>
                            <span class="status-badge status-inactive">NONAKTIF</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="preview-box">
                        <?php if ($has_image): ?>
                            <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                 alt="Slider <?php echo $i; ?>"
                                 title="Slider <?php echo $i; ?>">
                        <?php else: ?>
                            <div class="placeholder">
                                <div class="icon">📷</div>
                                <div class="text">Belum ada gambar</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="urutan" value="<?php echo $i; ?>">
                        
                        <div class="form-group">
                            <label for="image_<?php echo $i; ?>">Upload Gambar Baru</label>
                            <input type="file" 
                                   id="image_<?php echo $i; ?>" 
                                   name="image" 
                                   accept=".jpg,.jpeg,.png,.webp">
                            <p class="info-text">JPG, JPEG, PNG, WEBP • Maksimal 30MB • Auto resize & optimize</p>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" 
                                   id="active_<?php echo $i; ?>" 
                                   name="is_active" 
                                   value="1" 
                                   <?php echo $is_active ? 'checked' : ''; ?>>
                            <label for="active_<?php echo $i; ?>">Tampilkan di Frontend</label>
                        </div>
                        
                        <button type="submit" class="btn-submit">💾 Simpan Perubahan</button>
                    </form>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>





