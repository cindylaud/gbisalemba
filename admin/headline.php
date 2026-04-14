<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/image-helper.php';
require_once __DIR__ . '/../includes/headline-helper.php';

headline_ensure_table($conn);

$sections = [
    'jadwal' => 'Headline Jadwal Ibadah',
    'pelayanan' => 'Headline Pelayanan',
    'formulir' => 'Headline Formulir',
];

$heroTitles = [
    'jadwal' => 'JADWAL IBADAH',
    'pelayanan' => 'PELAYANAN',
    'formulir' => 'FORMULIR',
];

$defaults = [
    'jadwal' => ['pos_y' => 30, 'zoom' => 100],
    'pelayanan' => ['pos_y' => 28, 'zoom' => 102],
    'formulir' => ['pos_y' => 28, 'zoom' => 102],
];

const HEADLINE_MAX_SIZE = 50 * 1024 * 1024;
const HEADLINE_MAX_WIDTH = 2400;
const HEADLINE_WEBP_QUALITY = 82;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_headline') {
    $section = (string) ($_POST['section'] ?? '');
    $allowedSections = array_keys($sections);

    if (!in_array($section, $allowedSections, true)) {
        $error = 'Section tidak valid.';
    } else {
        $current = headline_get_setting($conn, $section, $defaults[$section]);
        $currentImage = (string) ($current['image'] ?? '');

        $posY = isset($_POST['pos_y']) ? (int) $_POST['pos_y'] : (int) ($defaults[$section]['pos_y'] ?? 50);
        $zoom = isset($_POST['zoom']) ? (int) $_POST['zoom'] : (int) ($defaults[$section]['zoom'] ?? 100);

        if ($posY < 0) {
            $posY = 0;
        } elseif ($posY > 100) {
            $posY = 100;
        }

        if ($zoom < 50) {
            $zoom = 50;
        } elseif ($zoom > 150) {
            $zoom = 150;
        }

        $newImage = $currentImage;
        $hasNewUpload = isset($_FILES['image']) && (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($hasNewUpload) {
            $validation = validateImageUpload($_FILES['image'], HEADLINE_MAX_SIZE);
            if (!$validation['valid']) {
                $error = $validation['error'];
            } else {
                $filename = 'headline_' . $section . '_' . substr(bin2hex(random_bytes(8)), 0, 8) . '.webp';
                $destPath = HEADLINE_UPLOAD_DIR . $filename;

                $optimize = optimizeAndSaveImageAsWebp(
                    $_FILES['image']['tmp_name'],
                    $destPath,
                    HEADLINE_MAX_WIDTH,
                    HEADLINE_WEBP_QUALITY
                );

                if (!$optimize['success']) {
                    $error = 'Gagal memproses gambar: ' . ($optimize['error'] ?? 'Unknown error');
                } else {
                    $newImage = $filename;
                }
            }
        }

        if ($error === '') {
            $stmt = $conn->prepare(
                'INSERT INTO ' . HEADLINE_TABLE . ' (section_key, image, pos_y, zoom) VALUES (?, ?, ?, ?) '
                . 'ON DUPLICATE KEY UPDATE image = VALUES(image), pos_y = VALUES(pos_y), zoom = VALUES(zoom)'
            );

            if ($stmt) {
                $stmt->bind_param('ssii', $section, $newImage, $posY, $zoom);
                if ($stmt->execute()) {
                    $stmt->close();

                    if ($hasNewUpload && $currentImage !== '' && $currentImage !== $newImage) {
                        headline_delete_image_file($currentImage);
                    }

                    header('Location: headline.php?section=' . urlencode($section) . '&saved=1');
                    exit;
                }

                $stmt->close();
                if ($hasNewUpload && $newImage !== '' && $newImage !== $currentImage) {
                    headline_delete_image_file($newImage);
                }
                $error = 'Gagal menyimpan data headline.';
            } else {
                if ($hasNewUpload && $newImage !== '' && $newImage !== $currentImage) {
                    headline_delete_image_file($newImage);
                }
                $error = 'Gagal memproses query headline.';
            }
        } else {
            if ($hasNewUpload && $newImage !== '' && $newImage !== $currentImage) {
                headline_delete_image_file($newImage);
            }
        }
    }
}

if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $success = 'Pengaturan headline berhasil diperbarui.';
}

$currentSection = (string) ($_GET['section'] ?? 'jadwal');
if (!isset($sections[$currentSection])) {
    $currentSection = 'jadwal';
}

$sectionSettings = [];
foreach ($sections as $key => $label) {
    $setting = headline_get_setting($conn, $key, $defaults[$key]);
    $setting['preview'] = headline_resolve_admin_image($setting['image']);
    $setting['label'] = $label;
    $sectionSettings[$key] = $setting;
}

$selected = $sectionSettings[$currentSection];
$selectedHeroTitle = $heroTitles[$currentSection] ?? strtoupper((string) $selected['label']);
$admin_page_title = 'Kelola Headline Halaman';
include __DIR__ . '/includes/header.php';
?>

<style>
.headline-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(340px, 0.9fr);
    gap: 18px;
    align-items: start;
}

.panel {
    background: linear-gradient(180deg, #ffffff 0%, #f8fbfe 100%);
    border: 1px solid rgba(16, 44, 87, 0.1);
    border-radius: 20px;
    padding: 16px;
    box-shadow: 0 10px 22px rgba(15, 39, 66, 0.08);
}

.panel-title {
    font-size: 18px;
    font-weight: 800;
    color: #102c57;
    margin: 0 0 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(16, 44, 87, 0.1);
}

.alert {
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
}

.section-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 12px;
}

.section-card {
    border: 1px solid rgba(16, 44, 87, 0.12);
    border-radius: 14px;
    padding: 10px;
    background: #fff;
}

.section-card.active {
    border-color: rgba(20, 108, 148, 0.5);
    box-shadow: 0 8px 14px rgba(20, 108, 148, 0.12);
}

.section-preview {
    width: 100%;
    aspect-ratio: 16 / 5;
    border-radius: 10px;
    overflow: hidden;
    background: #eef4f8;
    border: 1px solid rgba(16, 44, 87, 0.1);
    margin-bottom: 9px;
    position: relative;
}

.section-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.section-preview::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(2, 19, 37, 0.14) 0%, rgba(2, 19, 37, 0.2) 24%, rgba(2, 19, 37, 0.72) 100%);
    pointer-events: none;
}

.section-empty {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #8fa1b4;
    font-size: 22px;
}

.section-card h3 {
    margin: 0 0 8px;
    font-size: 13px;
    font-weight: 700;
    color: #1f3f6f;
}

.section-card .meta {
    margin: 0 0 9px;
    font-size: 11px;
    color: #627998;
}

.btn-pick {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 10px;
    border-radius: 9px;
    border: 1px solid rgba(16, 44, 87, 0.14);
    background: #e8edf3;
    color: #1e3a5f;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}

.btn-pick:hover {
    background: #dbe4ee;
    color: #1e3a5f;
    text-decoration: none;
}

.preview-sim-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
    margin-bottom: 12px;
}

.preview-sim-item {
    border: 1px solid rgba(16, 44, 87, 0.12);
    border-radius: 14px;
    background: #eef4f8;
    overflow: hidden;
}

.preview-sim-item small {
    display: block;
    padding: 6px 10px;
    background: #f5f8fc;
    border-bottom: 1px solid rgba(16, 44, 87, 0.08);
    font-size: 11px;
    font-weight: 700;
    color: #486286;
}

.preview-frame {
    position: relative;
    overflow: hidden;
    background: #dce7f3;
}

.preview-frame.desktop {
    aspect-ratio: 16 / 5;
}

.preview-frame.mobile {
    aspect-ratio: 10 / 7;
}

.preview-frame img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transform-origin: center center;
    display: block;
}

.preview-frame::after {
    content: '';
    position: absolute;
    inset: 0;
    background:
      linear-gradient(180deg, rgba(2, 19, 37, 0.14) 0%, rgba(2, 19, 37, 0.2) 24%, rgba(2, 19, 37, 0.72) 100%),
      linear-gradient(90deg, rgba(7, 33, 62, 0.72) 0%, rgba(7, 33, 62, 0.28) 42%, rgba(34, 99, 122, 0.1) 100%);
    pointer-events: none;
}

.preview-hero-title {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    margin: 0;
    z-index: 2;
    color: #fff;
    font-weight: 800;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    text-shadow: 0 12px 28px rgba(0, 0, 0, 0.38);
    text-align: center;
}

.preview-frame.desktop .preview-hero-title {
    font-size: clamp(18px, 3vw, 38px);
}

.preview-frame.mobile .preview-hero-title {
    font-size: clamp(16px, 6vw, 28px);
}

.form-group {
    margin-bottom: 12px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-size: 12px;
    font-weight: 700;
    color: #27486f;
}

.form-control {
    border-radius: 10px;
    border: 1px solid rgba(16, 44, 87, 0.18);
    font-size: 13px;
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

.input-note {
    font-size: 11px;
    color: #667d99;
    margin-top: 4px;
}

.btn-save {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 12px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #1e3a5f 0%, #146c94 100%);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.btn-save:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 14px rgba(20, 108, 148, 0.24);
}

@media (max-width: 980px) {
    .headline-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="headline-layout">
    <div class="panel">
        <h2 class="panel-title">Pilih Halaman Headline</h2>
        <?php if ($success !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="section-grid">
            <?php foreach ($sectionSettings as $key => $item): ?>
                <article class="section-card <?php echo $currentSection === $key ? 'active' : ''; ?>">
                    <div class="section-preview">
                        <?php if ($item['preview'] !== ''): ?>
                            <img src="<?php echo htmlspecialchars($item['preview']); ?>" alt="<?php echo htmlspecialchars($item['label']); ?>"
                                 style="object-position:center <?php echo (int) $item['pos_y']; ?>%; transform:scale(<?php echo number_format($item['zoom'] / 100, 2, '.', ''); ?>);">
                        <?php else: ?>
                            <div class="section-empty"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($item['label']); ?></h3>
                    <p class="meta">Posisi: <?php echo (int) $item['pos_y']; ?>% | Ukuran: <?php echo (int) $item['zoom']; ?>%</p>
                    <a class="btn-pick" href="?section=<?php echo urlencode($key); ?>">
                        <i class="fas fa-pen"></i> Kelola
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel" id="headline_form">
        <h2 class="panel-title"><?php echo htmlspecialchars($selected['label']); ?></h2>
        <div class="preview-sim-grid">
            <div class="preview-sim-item">
                <small>Simulasi Jemaat Desktop</small>
                <div class="preview-frame desktop">
                    <?php if ($selected['preview'] !== ''): ?>
                        <img id="headline_preview_img_desktop" src="<?php echo htmlspecialchars($selected['preview']); ?>" alt="Preview headline desktop"
                             style="object-position:center <?php echo (int) $selected['pos_y']; ?>%; transform:scale(<?php echo number_format($selected['zoom'] / 100, 2, '.', ''); ?>);">
                    <?php else: ?>
                        <div class="section-empty" id="headline_preview_placeholder"><i class="fas fa-image"></i></div>
                        <img id="headline_preview_img_desktop" src="" alt="Preview headline desktop" style="display:none;">
                    <?php endif; ?>
                    <h3 class="preview-hero-title"><?php echo htmlspecialchars($selectedHeroTitle); ?></h3>
                </div>
            </div>

            <div class="preview-sim-item">
                <small>Simulasi Jemaat Mobile</small>
                <div class="preview-frame mobile">
                    <?php if ($selected['preview'] !== ''): ?>
                        <img id="headline_preview_img_mobile" src="<?php echo htmlspecialchars($selected['preview']); ?>" alt="Preview headline mobile"
                             style="object-position:center <?php echo (int) $selected['pos_y']; ?>%; transform:scale(<?php echo number_format($selected['zoom'] / 100, 2, '.', ''); ?>);">
                    <?php else: ?>
                        <img id="headline_preview_img_mobile" src="" alt="Preview headline mobile" style="display:none;">
                    <?php endif; ?>
                    <h3 class="preview-hero-title"><?php echo htmlspecialchars($selectedHeroTitle); ?></h3>
                </div>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_headline">
            <input type="hidden" name="section" value="<?php echo htmlspecialchars($currentSection); ?>">

            <div class="form-group">
                <label for="headline_image">Upload Foto Headline</label>
                <div class="file-upload-wrap" id="headline_upload_wrap">
                    <input type="file" id="headline_image" name="image" class="file-input-native" accept="image/jpeg,image/png,image/webp">
                    <button type="button" class="file-upload-button" id="headline_upload_button">
                        <i class="fas fa-cloud-upload-alt"></i> Pilih Foto
                    </button>
                    <div class="file-upload-name" id="headline_file_name">Belum ada file dipilih</div>
                    <div class="file-upload-meta">
                        <i class="fas fa-file-image"></i>
                        <span>JPG, PNG, WebP. Maksimal 50 MB. Gambar otomatis dioptimalkan.</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="headline_pos_y">Posisi Vertikal (<span id="headline_pos_y_value"><?php echo (int) $selected['pos_y']; ?></span>%)</label>
                <input type="range" id="headline_pos_y" name="pos_y" class="form-control" min="0" max="100" step="1" value="<?php echo (int) $selected['pos_y']; ?>">
            </div>

            <div class="form-group">
                <label for="headline_zoom">Ukuran Foto (<span id="headline_zoom_value"><?php echo (int) $selected['zoom']; ?></span>%)</label>
                <input type="range" id="headline_zoom" name="zoom" class="form-control" min="50" max="150" step="1" value="<?php echo (int) $selected['zoom']; ?>">
                <p class="input-note">Contoh: 50% untuk lebih kecil, 100% normal, 120% lebih zoom.</p>
            </div>

            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> Simpan Pengaturan
            </button>
        </form>
    </div>
</div>

<script>
(function () {
    var fileInput = document.getElementById('headline_image');
    var uploadButton = document.getElementById('headline_upload_button');
    var uploadWrap = document.getElementById('headline_upload_wrap');
    var fileNameText = document.getElementById('headline_file_name');
    var previewDesktop = document.getElementById('headline_preview_img_desktop');
    var previewMobile = document.getElementById('headline_preview_img_mobile');
    var previewPlaceholder = document.getElementById('headline_preview_placeholder');
    var posSlider = document.getElementById('headline_pos_y');
    var zoomSlider = document.getElementById('headline_zoom');
    var posValue = document.getElementById('headline_pos_y_value');
    var zoomValue = document.getElementById('headline_zoom_value');

    function forEachPreview(callback) {
        [previewDesktop, previewMobile].forEach(function (img) {
            if (img) {
                callback(img);
            }
        });
    }

    function applyPreviewStyle() {
        if (!previewDesktop && !previewMobile) {
            return;
        }
        var pos = posSlider ? posSlider.value : '50';
        var zoom = zoomSlider ? zoomSlider.value : '100';

        forEachPreview(function (img) {
            img.style.objectPosition = 'center ' + pos + '%';
            img.style.transform = 'scale(' + (parseInt(zoom, 10) / 100).toFixed(2) + ')';
        });

        if (posValue) {
            posValue.textContent = pos;
        }
        if (zoomValue) {
            zoomValue.textContent = zoom;
        }
    }

    if (uploadButton && fileInput) {
        uploadButton.addEventListener('click', function () {
            fileInput.click();
        });
    }

    if (uploadWrap) {
        uploadWrap.addEventListener('dragenter', function () {
            uploadWrap.classList.add('is-highlight');
        });
        uploadWrap.addEventListener('dragleave', function () {
            uploadWrap.classList.remove('is-highlight');
        });
    }

    if (fileInput && (previewDesktop || previewMobile)) {
        fileInput.addEventListener('change', function (event) {
            var file = event.target.files && event.target.files[0];
            if (!file) {
                if (fileNameText) {
                    fileNameText.textContent = 'Belum ada file dipilih';
                }
                return;
            }

            if (fileNameText) {
                fileNameText.textContent = file.name;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                forEachPreview(function (img) {
                    img.src = e.target.result;
                    img.style.display = 'block';
                });
                if (previewPlaceholder) {
                    previewPlaceholder.style.display = 'none';
                }
                applyPreviewStyle();
            };
            reader.readAsDataURL(file);
        });
    }

    if (posSlider) {
        posSlider.addEventListener('input', applyPreviewStyle);
    }

    if (zoomSlider) {
        zoomSlider.addEventListener('input', applyPreviewStyle);
    }

    applyPreviewStyle();
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
