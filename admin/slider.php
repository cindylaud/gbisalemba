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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Slider - Admin GBI Salemba</title>
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
            line-height: 1.6;
        }
        
        .container {
            max-width: none;
            margin: 0;
        }
        
        .slider-page-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: #1e3a5f;
            color: #fff;
            text-decoration: none;
            border-radius: 999px;
            font-weight: 600;
            box-shadow: 0 8px 16px rgba(15, 39, 66, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .back-link:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 16px 26px rgba(15, 39, 66, 0.18);
        }

        .slider-page-note {
            margin: 0;
            color: #6b7c93;
            font-size: 12px;
            font-weight: 500;
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
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
        }
        
        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .slider-card {
            background: #ffffff;
            border: 1px solid rgba(16, 44, 87, 0.08);
            border-radius: 18px;
            padding: 14px;
            box-shadow: 0 8px 18px rgba(15, 39, 66, 0.06);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            overflow: hidden;
        }
        
        .slider-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 39, 66, 0.1);
        }
        
        .card-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 16px;
            font-weight: 700;
            color: #102C57;
            margin-bottom: 12px;
            text-align: left;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(16, 44, 87, 0.08);
        }
        
        .preview-box {
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #f4f7fb;
            border: 1px solid rgba(16, 44, 87, 0.08);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            overflow: hidden;
            position: relative;
        }
        
        .preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
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
            margin-bottom: 9px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #102C57;
            font-size: 12px;
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(20, 108, 148, 0.2);
            border-radius: 10px;
            font-size: 12px;
            background: white;
            cursor: pointer;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        .form-group input[type="file"]:hover {
            border-color: rgba(20, 108, 148, 0.5);
        }

        .form-group input[type="file"]:focus {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(63, 182, 168, 0.14);
        }
        
        .info-text {
            font-size: 11px;
            color: #6b7c93;
            margin-top: 5px;
            font-style: italic;
            line-height: 1.5;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            padding: 11px 12px;
            background: #f8fbfd;
            border: 1px solid rgba(16, 44, 87, 0.08);
            border-radius: 12px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #146C94;
        }
        
        .checkbox-group label {
            font-weight: 600;
            color: #102C57;
            cursor: pointer;
            user-select: none;
            margin: 0;
            font-size: 12px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 12px 18px;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #146C94;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.35px;
        }

        .btn-submit::before {
            content: '\\f0c7';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 8px;
        }
        
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(20, 108, 148, 0.18);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 700;
            margin-top: 0;
            letter-spacing: 0.35px;
            text-transform: uppercase;
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
            .slider-page-toolbar {
                align-items: stretch;
            }

            .back-link {
                width: 100%;
                justify-content: center;
            }

            .cards-grid {
                grid-template-columns: 1fr;
            }

            .card-title {
                font-size: 15px;
            }

            .preview-box {
                aspect-ratio: 16 / 10;
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
                    <div class="admin-topbar-meta">Atur 4 hero visual utama di beranda gereja</div>
                </div>
                <div class="admin-topbar-meta">Halo, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong></div>
            </header>
            <div class="admin-content">
    <div class="container">
        <div class="slider-page-toolbar">
            <a href="index.php" class="back-link">← Kembali ke Dashboard</a>
            <p class="slider-page-note">Tersedia 4 slot foto utama. Upload, aktifkan, lalu simpan perubahan per kartu.</p>
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
                <div class="slider-card">
                    <div class="card-title">
                        <span>Foto <?php echo $i; ?></span>
                        <span class="status-badge <?php echo $is_active ? 'status-active' : 'status-inactive'; ?>"><?php echo $is_active ? 'AKTIF' : 'NONAKTIF'; ?></span>
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
            </div>
        </main>
    </div>
</body>
</html>





