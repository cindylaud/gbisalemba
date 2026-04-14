<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/headline-helper.php';

if (!function_exists('jadwal_collect_images')) {
    function jadwal_collect_images(array $directories) {
        $supported_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        $images = [];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $files = scandir($directory);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($extension, $supported_extensions, true)) {
                    continue;
                }

                $candidate = rtrim($directory, '/\\') . '/' . $file;
                if (file_exists($candidate)) {
                    $images[] = $candidate;
                }
            }
        }

        return $images;
    }
}

if (!function_exists('jadwal_normalize_name')) {
    function jadwal_normalize_name($name) {
        $name = strtolower(trim((string) $name));
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name);
        return trim($name);
    }
}

if (!function_exists('jadwal_resolve_key')) {
    function jadwal_resolve_key($name) {
        $normalized = jadwal_normalize_name($name);

        if (strpos($normalized, 'raya') !== false) {
            return 'ibadah_raya';
        }

        if (strpos($normalized, 'starskids') !== false || strpos($normalized, 'stars kids') !== false) {
            return 'ibadah_starskids';
        }

        if (strpos($normalized, 'starsjc') !== false || strpos($normalized, 'stars jc') !== false) {
            return 'ibadah_starsjc';
        }

        if (strpos($normalized, 'stars community') !== false) {
            return 'ibadah_stars_community';
        }

        if (strpos($normalized, 'rumah doa') !== false) {
            return 'rumah_doa';
        }

        return null;
    }
}

if (!function_exists('jadwal_row_is_active')) {
    function jadwal_row_is_active(array $row): bool {
        if (array_key_exists('is_active', $row)) {
            $value = $row['is_active'];
            if (is_numeric($value)) {
                return (int) $value === 1;
            }

            $text = strtolower(trim((string) $value));
            if ($text === 'aktif' || $text === 'active' || $text === 'yes' || $text === 'true') {
                return true;
            }
            if ($text === 'nonaktif' || $text === 'inactive' || $text === 'no' || $text === 'false') {
                return false;
            }
        }

        if (array_key_exists('status', $row)) {
            return strtolower(trim((string) $row['status'])) === 'aktif';
        }

        return true;
    }
}

// Get all active jadwal ibadah
$jadwal_list = [];
$error_message = '';

$stmt = $conn->prepare("SELECT * FROM jadwal_ibadah ORDER BY COALESCE(urutan, id) ASC, id ASC");

if (!$stmt) {
    // Fallback for legacy schema that may not yet have urutan.
    $stmt = $conn->prepare("SELECT * FROM jadwal_ibadah ORDER BY id ASC");
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        if (jadwal_row_is_active($row)) {
            $jadwal_list[] = $row;
        }
    }
    $stmt->close();
} else {
    $error_message = 'Gagal mengambil data jadwal. Silakan coba lagi nanti.';
}

$jadwal_gallery_images = jadwal_collect_images([
    'uploads/jadwal',
    'uploads/slider',
    'uploads/pelayanan',
    'uploads/whatsnew'
]);

if (count($jadwal_gallery_images) === 0) {
    $jadwal_gallery_images[] = 'assets/images/default-avatar.png';
}

$jadwal_hero_photo = $jadwal_gallery_images[count($jadwal_gallery_images) - 1];

headline_ensure_table($conn);
$jadwal_headline_setting = headline_get_setting($conn, 'jadwal', ['pos_y' => 30, 'zoom' => 100]);
$jadwal_headline_custom = headline_resolve_public_image($jadwal_headline_setting['image']);
if ($jadwal_headline_custom !== '') {
    $jadwal_hero_photo = $jadwal_headline_custom;
}
$jadwal_hero_pos_y = (int) ($jadwal_headline_setting['pos_y'] ?? 30);
$jadwal_hero_zoom = (int) ($jadwal_headline_setting['zoom'] ?? 100);
$jadwal_hero_scale = number_format($jadwal_hero_zoom / 100, 2, '.', '');
?>

<div class="jadwal-page">
    <section class="jadwal-hero">
        <img src="<?php echo htmlspecialchars($jadwal_hero_photo); ?>" alt="Jadwal Ibadah GBI Salemba" class="jadwal-hero-image" style="object-position:center <?php echo $jadwal_hero_pos_y; ?>%; transform:scale(<?php echo htmlspecialchars($jadwal_hero_scale); ?>);">
        <div class="jadwal-hero-overlay"></div>
        <div class="jadwal-hero-container">
            <h1 class="jadwal-hero-title">Jadwal Ibadah</h1>
        </div>
    </section>

    <section class="jadwal-divider-section" aria-hidden="true">
        <div class="jadwal-container">
            <div class="jadwal-divider-wrap">
                <span class="jadwal-divider-line"></span>
                <span class="jadwal-divider-cross"></span>
                <span class="jadwal-divider-line"></span>
            </div>
        </div>
    </section>

    <section class="jadwal-content">
        <div class="jadwal-container">
            <?php if (!empty($error_message)): ?>
                <div class="jadwal-error-box">
                    <div class="jadwal-error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p class="jadwal-error-text"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php elseif (empty($jadwal_list)): ?>
                <div class="jadwal-empty-state">
                    <div class="jadwal-empty-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="jadwal-empty-title">Belum Ada Jadwal</h3>
                    <p class="jadwal-empty-text">Jadwal ibadah akan segera dipublikasikan. Silakan cek kembali nanti.</p>
                </div>
            <?php else: ?>
                <div class="jadwal-selector-shell">
                    <div class="jadwal-selector-sidebar" role="tablist" aria-label="Pilih ibadah">
                        <?php foreach ($jadwal_list as $index => $jadwal): ?>
                            <button
                                type="button"
                                class="jadwal-tab-button<?php echo $index === 0 ? ' is-active' : ''; ?>"
                                role="tab"
                                aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                                data-target="jadwal-panel-<?php echo (int) $jadwal['id']; ?>"
                            >
                                <strong class="jadwal-tab-name"><?php echo htmlspecialchars($jadwal['nama_ibadah']); ?></strong>
                                <i class="fas fa-chevron-right jadwal-tab-arrow"></i>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="jadwal-selector-content">
                        <?php foreach ($jadwal_list as $index => $jadwal): ?>
                            <?php
                                $panel_image = $panel_image_fit = '';
                                if (!empty($jadwal['image']) && file_exists('uploads/jadwal/' . $jadwal['image'])) {
                                    $panel_image = 'uploads/jadwal/' . $jadwal['image'];
                                    $panel_image_fit = (string) ($jadwal['image_fit'] ?? 'cover');
                                } else {
                                    $panel_image = $jadwal_gallery_images[$index % count($jadwal_gallery_images)];
                                    $panel_image_fit = 'cover';
                                }
                                $panel_image_pos_y = isset($jadwal['image_pos_y']) ? (int) $jadwal['image_pos_y'] : 50;
                                if ($panel_image_pos_y < 0) {
                                    $panel_image_pos_y = 0;
                                } elseif ($panel_image_pos_y > 100) {
                                    $panel_image_pos_y = 100;
                                }
                            ?>
                            <article
                                id="jadwal-panel-<?php echo (int) $jadwal['id']; ?>"
                                class="jadwal-detail-panel<?php echo $index === 0 ? ' is-active' : ''; ?>"
                                role="tabpanel"
                            >
                                <div class="jadwal-detail-layout">
                                    <div class="jadwal-detail-main">
                                        <header class="jadwal-detail-header">
                                            <h2 class="jadwal-detail-title"><?php echo htmlspecialchars($jadwal['nama_ibadah']); ?></h2>
                                        </header>

                                        <div class="jadwal-detail-grid">
                                            <div class="jadwal-detail-item">
                                                <span class="jadwal-info-icon-wrap"><i class="fas fa-calendar-day jadwal-icon"></i></span>
                                                <div>
                                                    <span class="jadwal-row-label">Hari Ibadah</span>
                                                    <span class="jadwal-info-text jadwal-info-text-day"><?php echo htmlspecialchars($jadwal['hari']); ?></span>
                                                </div>
                                            </div>

                                            <div class="jadwal-detail-item">
                                                <span class="jadwal-info-icon-wrap"><i class="fas fa-clock jadwal-icon"></i></span>
                                                <div>
                                                    <span class="jadwal-row-label">Jam Ibadah</span>
                                                    <span class="jadwal-info-text"><?php echo htmlspecialchars($jadwal['jam']); ?></span>
                                                </div>
                                            </div>

                                            <div class="jadwal-detail-item">
                                                <span class="jadwal-info-icon-wrap"><i class="fas fa-map-marker-alt jadwal-icon"></i></span>
                                                <div>
                                                    <span class="jadwal-row-label">Ruangan</span>
                                                    <span class="jadwal-info-text"><?php echo !empty($jadwal['ruangan']) ? htmlspecialchars($jadwal['ruangan']) : 'Akan diinformasikan'; ?></span>
                                                </div>
                                            </div>

                                            <div class="jadwal-detail-item">
                                                <span class="jadwal-info-icon-wrap"><i class="fas fa-info-circle jadwal-icon"></i></span>
                                                <div>
                                                    <span class="jadwal-row-label">Keterangan</span>
                                                    <span class="jadwal-keterangan-text"><?php echo !empty($jadwal['keterangan']) ? htmlspecialchars($jadwal['keterangan']) : 'Datang lebih awal untuk mempersiapkan hati dalam ibadah.'; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <aside class="jadwal-detail-thumb">
                                        <div class="jadwal-detail-thumb-frame">
                                            <img src="<?php echo htmlspecialchars($panel_image); ?>" alt="<?php echo htmlspecialchars($jadwal['nama_ibadah']); ?>" style="object-fit: <?php echo htmlspecialchars($panel_image_fit); ?>; object-position: center <?php echo $panel_image_pos_y; ?>%;">
                                        </div>
                                    </aside>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="jadwal-info-box">
                    <div class="jadwal-info-box-left">
                        <div class="jadwal-info-box-icon">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div>
                            <h4 class="jadwal-info-box-title">Informasi Penting</h4>
                            <p class="jadwal-info-box-text">Cek pengumuman terbaru di media sosial kami</p>
                        </div>
                    </div>
                    <div class="jadwal-info-links">
                        <a href="https://www.facebook.com/people/Gbi-salemba/61583162131557/" target="_blank" rel="noopener noreferrer" class="jadwal-social-btn jadwal-social-fb">
                            <i class="fab fa-facebook jadwal-social-icon"></i>
                            <span>Gbi.Salemba</span>
                        </a>
                        <a href="https://www.instagram.com/gbi.salemba" target="_blank" rel="noopener noreferrer" class="jadwal-social-btn jadwal-social-ig">
                            <i class="fab fa-instagram jadwal-social-icon"></i>
                            <span>@gbi.salemba</span>
                        </a>
                        <a href="https://www.tiktok.com/@gbisalemba" target="_blank" rel="noopener noreferrer" class="jadwal-social-btn jadwal-social-tt">
                            <i class="fab fa-tiktok jadwal-social-icon"></i>
                            <span>GBI Salemba</span>
                        </a>
                        <a href="https://www.youtube.com/@gbisalemba" target="_blank" rel="noopener noreferrer" class="jadwal-social-btn jadwal-social-yt">
                            <i class="fab fa-youtube jadwal-social-icon"></i>
                            <span>GBI Salemba</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ===== Color Palette ===== */
:root {
    --color-bg-main: #F3F9FB;
    --color-bg-accent: #EADBC8;
    --color-text-main: #102C57;
    --color-primary: #146C94;
    --color-white: #FFFFFF;
    --color-error: #dc3545;
}

.jadwal-page {
    min-height: 100vh;
    position: relative;
    isolation: isolate;
    overflow: hidden;
    font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background:
    radial-gradient(circle at 9% 8%, rgba(42, 106, 168, 0.12) 0%, rgba(42, 106, 168, 0) 34%),
    radial-gradient(circle at 91% 15%, rgba(31, 129, 121, 0.08) 0%, rgba(31, 129, 121, 0) 30%),
    linear-gradient(160deg, #f8fbff 0%, #edf3fa 52%, #f5f9fc 100%);
}

.jadwal-page::before,
.jadwal-page::after {
    content: "";
    position: absolute;
    pointer-events: none;
    z-index: 0;
}

.jadwal-page::before {
    width: 460px;
    height: 460px;
    top: -180px;
    right: -120px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(40, 98, 156, 0.12) 0%, rgba(40, 98, 156, 0) 70%);
    filter: blur(24px);
}

.jadwal-page::after {
    width: 380px;
    height: 380px;
    left: -130px;
    bottom: 10%;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(34, 137, 149, 0.1) 0%, rgba(34, 137, 149, 0) 72%);
    filter: blur(26px);
}

.jadwal-page > * {
    position: relative;
    z-index: 1;
}

.jadwal-hero {
    position: relative;
    overflow: hidden;
    min-height: clamp(290px, 45vh, 440px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.jadwal-hero-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 30%;
}

.jadwal-hero-overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(180deg, rgba(2, 19, 37, 0.14) 0%, rgba(2, 19, 37, 0.2) 24%, rgba(2, 19, 37, 0.74) 100%),
        linear-gradient(90deg, rgba(7, 33, 62, 0.72) 0%, rgba(7, 33, 62, 0.28) 42%, rgba(34, 99, 122, 0.1) 100%);
}

.jadwal-hero-container {
    position: relative;
    z-index: 1;
    max-width: 860px;
    margin: 0 auto;
    text-align: center;
    padding: 0 20px;
}

.jadwal-hero-eyebrow {
    display: inline-block;
    margin-bottom: 14px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.76);
}

.jadwal-hero-title {
    font-size: clamp(40px, 6.2vw, 74px);
    line-height: 0.98;
    font-weight: 800;
    color: #ffffff;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    text-shadow: 0 16px 38px rgba(0,0,0,0.3);
}

.jadwal-divider-section {
    padding: 28px 20px 16px;
}

.jadwal-divider-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 18px;
}

.jadwal-divider-line {
    width: min(340px, 36vw);
    height: 1px;
    background: linear-gradient(90deg, rgba(21, 65, 110, 0) 0%, rgba(21, 65, 110, 0.36) 50%, rgba(21, 65, 110, 0) 100%);
}

.jadwal-divider-cross {
    position: relative;
    width: 22px;
    height: 28px;
    filter: drop-shadow(0 6px 12px rgba(17, 44, 76, 0.18));
}

.jadwal-divider-cross::before,
.jadwal-divider-cross::after {
    content: '';
    position: absolute;
    background: linear-gradient(180deg, #15416e 0%, #2d6f9f 100%);
    border-radius: 2px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.jadwal-divider-cross::before {
    width: 12px;
    height: 3px;
    top: 38%;
}

.jadwal-divider-cross::after {
    width: 3px;
    height: 24px;
}

.jadwal-content {
    padding: 20px 20px 52px;
}

.jadwal-container {
    max-width: 1080px;
    margin: 0 auto;
}

.jadwal-highlight-panel {
    display: none;
}

.jadwal-panel-label {
    display: inline-block;
    margin-bottom: 12px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2.6px;
    text-transform: uppercase;
    color: var(--color-primary);
}

.jadwal-highlight-panel h2 {
    margin: 0 0 12px;
    color: var(--color-text-main);
    font-size: clamp(28px, 4vw, 40px);
    line-height: 1.12;
}

.jadwal-highlight-panel p {
    margin: 0;
    color: rgba(16, 44, 87, 0.72);
    font-size: 15px;
    line-height: 1.8;
    max-width: 60ch;
}

.jadwal-panel-badges {
    display: none;
}

.jadwal-selector-shell {
    position: relative;
    margin-bottom: 24px;
    max-width: 1040px;
    margin-left: auto;
    margin-right: auto;
    display: grid;
    grid-template-columns: 210px 1fr;
    gap: 0;
    padding: 0;
    border-radius: 24px;
    background:
        linear-gradient(rgba(255,255,255,0.02), rgba(255,255,255,0.02)),
        linear-gradient(90deg, rgba(18,58,98,0.05) 1px, transparent 1px),
        linear-gradient(rgba(18,58,98,0.05) 1px, transparent 1px),
        linear-gradient(145deg, rgba(248, 251, 255, 0.98) 0%, rgba(235, 243, 251, 0.94) 100%);
    background-size: auto, 28px 28px, 28px 28px, auto;
    border: 1px solid rgba(16, 44, 87, 0.1);
    box-shadow: 0 26px 58px rgba(16, 44, 87, 0.14);
    overflow: hidden;
}

.jadwal-selector-shell::before,
.jadwal-selector-shell::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}

.jadwal-selector-shell::before {
    width: 240px;
    height: 240px;
    right: -110px;
    top: -110px;
    background: radial-gradient(circle, rgba(63, 182, 168, 0.2) 0%, rgba(63, 182, 168, 0) 70%);
}

.jadwal-selector-shell::after {
    width: 260px;
    height: 260px;
    left: -130px;
    bottom: -150px;
    background: radial-gradient(circle, rgba(20, 108, 148, 0.14) 0%, rgba(20, 108, 148, 0) 72%);
}

.jadwal-selector-sidebar {
    position: relative;
    z-index: 1;
    display: grid;
    grid-auto-rows: 1fr;
    gap: 4px;
    padding: 16px 10px;
    border-right: 1px solid rgba(16, 44, 87, 0.1);
    background: rgba(246, 250, 255, 0.5);
    border-radius: 24px 0 0 24px;
    height: 100%;
    overflow-y: auto;
}

.jadwal-selector-content {
    position: relative;
    z-index: 1;
    padding: 16px;
    min-width: 0;
}

.jadwal-tab-button {
    position: relative;
    text-align: left;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    border: 1px solid transparent;
    background: transparent;
    color: rgba(16, 44, 87, 0.60);
    border-radius: 10px;
    padding: 10px 12px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    width: 100%;
    min-height: 0;
    height: 100%;
    transition: background 0.2s ease, color 0.2s ease;
}

.jadwal-tab-button:hover {
    background: rgba(20, 108, 148, 0.07);
    color: var(--color-text-main);
}

.jadwal-tab-name {
    display: block;
    font-size: 11px;
    line-height: 1.25;
    font-weight: 800;
    color: inherit;
    flex: 1;
}

.jadwal-tab-arrow {
    font-size: 10px;
    color: rgba(16, 44, 87, 0.25);
    flex-shrink: 0;
    transition: color 0.2s ease, transform 0.2s ease;
}

.jadwal-tab-button.is-active {
    background: rgba(20, 108, 148, 0.1);
    color: #102C57;
    border-color: rgba(20, 108, 148, 0.22);
}

.jadwal-tab-button.is-active .jadwal-tab-name {
    color: var(--color-primary);
}

.jadwal-tab-button.is-active .jadwal-tab-arrow {
    color: var(--color-primary);
    transform: translateX(2px);
}

.jadwal-selector-panels {
    position: relative;
    z-index: 1;
}

.jadwal-detail-panel {
    display: none;
    padding: 0;
    opacity: 0;
    transform: translateY(14px);
}

.jadwal-detail-panel.is-active {
    display: block;
    animation: jadwalPanelReveal 0.45s ease forwards;
}

.jadwal-detail-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 236px;
    gap: 14px;
    align-items: start;
}

.jadwal-detail-main {
    display: grid;
    gap: 12px;
    min-width: 0;
}

.jadwal-detail-header {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 0;
    padding: 10px 12px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid rgba(16, 44, 87, 0.08);
}

.jadwal-detail-title {
    margin: 0;
    font-size: clamp(18px, 2.1vw, 25px);
    line-height: 1.14;
    color: var(--color-text-main);
    text-align: center;
}

.jadwal-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.jadwal-detail-item {
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    text-align: left;
    gap: 9px;
    min-height: 84px;
    padding: 10px;
    border-radius: 12px;
    background: rgba(255,255,255,0.9);
    border: 1px solid rgba(16, 44, 87, 0.08);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);
}

.jadwal-detail-item-wide {
    grid-column: 1 / -1;
}

.jadwal-detail-thumb {
    display: flex;
    align-items: flex-start;
}

.jadwal-detail-thumb-frame {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 16px 32px rgba(16, 44, 87, 0.14);
}

.jadwal-detail-thumb-frame::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(4, 21, 40, 0.02) 0%, rgba(4, 21, 40, 0.18) 58%, rgba(4, 21, 40, 0.56) 100%);
}

.jadwal-detail-thumb-frame img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 0.6s ease;
}

.jadwal-detail-panel.is-active .jadwal-detail-thumb-frame img {
    transform: scale(1.02);
}

.jadwal-card {
    display: none;
}

.jadwal-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 22px 42px rgba(16, 44, 87, 0.12);
}

.jadwal-card-topbar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: linear-gradient(90deg, #146C94 0%, #3FB6A8 100%);
}

.jadwal-card-header {
    display: flex;
    gap: 16px;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 22px;
}

.jadwal-card-label {
    display: inline-block;
    margin-bottom: 8px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: rgba(16, 44, 87, 0.48);
}

.jadwal-card-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--color-text-main);
    margin: 0;
    line-height: 1.2;
}

.jadwal-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    border-radius: 999px;
    background: rgba(20, 108, 148, 0.12);
    color: #146C94;
    font-size: 0.82rem;
    font-weight: 700;
    white-space: nowrap;
}

.jadwal-time-pill {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    margin-bottom: 22px;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(20,108,148,0.1) 0%, rgba(63,182,168,0.12) 100%);
    color: var(--color-text-main);
    font-size: 1rem;
    font-weight: 700;
}

.jadwal-card-body {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.jadwal-info-row,
.jadwal-keterangan {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 14px;
    align-items: flex-start;
    padding: 16px 0 0;
    border-top: 1px solid rgba(16, 44, 87, 0.08);
}

.jadwal-info-icon-wrap {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #e9edf2;
    flex-shrink: 0;
}

.jadwal-detail-item > div {
    display: grid;
    gap: 2px;
    justify-items: start;
    align-items: start;
}

.jadwal-icon {
    color: var(--color-primary);
    font-size: 0.92rem;
}

.jadwal-row-label {
    display: block;
    margin-bottom: 1px;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.6px;
    text-transform: uppercase;
    color: rgba(16, 44, 87, 0.48);
    text-align: left;
}

.jadwal-info-text,
.jadwal-keterangan-text {
    display: block;
    color: var(--color-text-main);
    font-size: 0.72rem;
    line-height: 1.35;
    text-align: left;
}

.jadwal-info-text-day {
    color: #146C94;
    font-weight: 700;
}

.jadwal-keterangan-muted .jadwal-keterangan-text {
    color: rgba(16, 44, 87, 0.7);
}

.jadwal-info-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    padding: 22px 28px;
    border-radius: 20px;
    background: linear-gradient(135deg, #102C57 0%, #153355 60%, #1a4272 100%);
    border: 1px solid rgba(255, 255, 255, 0.06);
    box-shadow: 0 16px 36px rgba(16, 44, 87, 0.22);
}

.jadwal-info-box-left {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-shrink: 0;
}

.jadwal-info-box-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.12);
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.jadwal-info-box-icon i {
    color: rgba(255, 255, 255, 0.92);
    font-size: 1.25rem;
}

.jadwal-info-box-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #fff;
    margin: 0 0 3px;
}

.jadwal-info-box-text {
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.87rem;
    line-height: 1.5;
    margin: 0;
}

.jadwal-info-links {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.jadwal-social-btn {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    padding: 10px 16px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.09);
    border: 1px solid rgba(255, 255, 255, 0.14);
    color: rgba(255, 255, 255, 0.88);
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s ease, transform 0.2s ease;
}

.jadwal-social-btn:hover {
    background: rgba(255, 255, 255, 0.19);
    transform: translateY(-2px);
    color: #fff;
}

.jadwal-social-icon {
    font-size: 16px;
}

.jadwal-social-fb .jadwal-social-icon { color: #74a7f0; }
.jadwal-social-ig .jadwal-social-icon { color: #f584a8; }
.jadwal-social-tt .jadwal-social-icon { color: rgba(255,255,255,0.9); }
.jadwal-social-yt .jadwal-social-icon { color: #f98080; }

.jadwal-empty-state {
    border-radius: 24px;
    padding: 60px 40px;
    box-shadow: 0 22px 40px rgba(16, 44, 87, 0.08);
}

.jadwal-empty-icon {
    width: 80px;
    height: 80px;
    background-color: var(--color-bg-accent);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
}

.jadwal-empty-icon i {
    font-size: 2.5rem;
    color: var(--color-text-main);
    opacity: 0.5;
}

.jadwal-empty-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text-main);
    margin: 0 0 12px;
}

.jadwal-empty-text {
    color: rgba(16, 44, 87, 0.6);
    font-size: 1rem;
    line-height: 1.6;
    margin: 0;
}

.jadwal-error-box {
    border-radius: 20px;
    padding: 24px;
}

.jadwal-error-icon {
    width: 42px;
    height: 42px;
    background-color: var(--color-error);
    border-radius: 50%;
}

.jadwal-error-icon i {
    color: var(--color-white);
    font-size: 1.2rem;
}

.jadwal-error-text {
    color: var(--color-error);
    font-size: 0.95rem;
    font-weight: 500;
    margin: 0;
}

@keyframes jadwalPanelReveal {
    from {
        opacity: 0;
        transform: translateY(14px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 900px) {
    .jadwal-selector-shell {
        grid-template-columns: 1fr;
        border-radius: 22px;
    }

    .jadwal-selector-sidebar {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        border-right: none;
        border-bottom: 1px solid rgba(16, 44, 87, 0.1);
        border-radius: 22px 22px 0 0;
        padding: 12px;
        gap: 6px;
        height: auto;
    }

    .jadwal-tab-button {
        width: auto;
        height: auto;
        min-height: 42px;
        flex: 0 0 auto;
    }

    .jadwal-detail-layout {
        grid-template-columns: 1fr;
    }

    .jadwal-detail-thumb {
        order: -1;
    }

    .jadwal-detail-grid {
        grid-template-columns: 1fr;
    }

    .jadwal-detail-item-wide {
        grid-column: auto;
    }
}

@media (max-width: 768px) {
    .jadwal-content {
        padding: 18px 16px 46px;
    }

    .jadwal-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .jadwal-tab-button {
        width: 100%;
    }

    .jadwal-detail-header {
        padding: 12px;
    }

    .jadwal-card,
    .jadwal-info-box {
        padding: 18px;
        border-radius: 22px;
    }

    .jadwal-info-box {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .jadwal-hero-title {
        font-size: 2.2rem;
    }

    .jadwal-highlight-panel h2 {
        font-size: 1.9rem;
    }

    .jadwal-panel-badges span,
    .jadwal-info-links a {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.jadwal-tab-button');
    const panels = document.querySelectorAll('.jadwal-detail-panel');

    tabButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const targetId = button.getAttribute('data-target');

            tabButtons.forEach(function (btn) {
                btn.classList.remove('is-active');
                btn.setAttribute('aria-selected', 'false');
            });

            panels.forEach(function (panel) {
                panel.classList.remove('is-active');
            });

            button.classList.add('is-active');
            button.setAttribute('aria-selected', 'true');

            const activePanel = document.getElementById(targetId);
            if (activePanel) {
                activePanel.classList.add('is-active');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


