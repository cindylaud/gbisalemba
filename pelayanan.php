<?php
require_once __DIR__ . '/config/database.php';
include __DIR__ . '/includes/header.php';

if (!function_exists('pelayanan_find_first_image')) {
    function pelayanan_find_first_image(array $directories, $fallback = 'assets/images/default-avatar.png') {
        $supported_extensions = ['jpg', 'jpeg', 'png', 'webp'];

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
                if (in_array($extension, $supported_extensions, true)) {
                    $candidate = rtrim($directory, '/\\') . '/' . $file;
                    if (file_exists($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        return $fallback;
    }
}

if (!function_exists('pelayanan_get_image_meta')) {
    function pelayanan_get_image_meta($path) {
        $meta = [
            'image_class' => 'service-image',
            'media_class' => 'pelayanan-card-media',
            'style' => ''
        ];

        if (!is_string($path) || $path === '' || !file_exists($path)) {
            return $meta;
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if (($extension === 'jpg' || $extension === 'jpeg') && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $orientation = isset($exif['Orientation']) ? (int) $exif['Orientation'] : 1;

            if ($orientation === 6) {
                $meta['media_class'] .= ' is-rotated';
                $meta['image_class'] .= ' rotate-90';
            } elseif ($orientation === 8) {
                $meta['media_class'] .= ' is-rotated';
                $meta['image_class'] .= ' rotate-270';
            } elseif ($orientation === 3) {
                $meta['image_class'] .= ' rotate-180';
            }
        }

        return $meta;
    }
}

// Get all active pelayanan, ordered by urutan
$query = "SELECT * FROM pelayanan WHERE status = 'aktif' ORDER BY urutan ASC";
$result = $conn->query($query);

$pelayanan_items = [];
if ($result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $pelayanan_items[] = $row;
    }
}

$pelayanan_hero_photo = pelayanan_find_first_image([
    'uploads/pelayanan',
    'uploads/slider',
    'assets/images/gembala'
]);

$pelayanan_description_map = [
    'pernikahan' => 'Awal yang baru membangun rumah tangga bersama Kristus',
    'penyerahan anak' => 'Keluarga bersatu dan berkomitmen membesarkan anak dalam kasih Kristus',
    'baptisan selam' => 'Disempurnakan menjadi seperti Kristus',
    'kedukaan' => 'Melayani dengan kasih dan penghiburan kepada keluarga yang ditinggalkan',
    'kematian' => 'Melayani dengan kasih dan penghiburan kepada keluarga yang ditinggalkan',
    'pengajaran' => 'Melalui kelas KOM (Kehidupan Orientasi Melayani) kami rindu setiap jemaat Tuhan bertumbuh dalam Kristus',
    'kom' => 'Melalui kelas KOM (Kehidupan Orientasi Melayani) kami rindu setiap jemaat Tuhan bertumbuh dalam Kristus'
];
?>

<main class="main-content pelayanan-page">

<!-- PELAYANAN HERO SECTION -->
<section class="pelayanan-hero-section">
    <div class="container-large">
        <div class="pelayanan-hero-banner">
            <img src="<?php echo htmlspecialchars($pelayanan_hero_photo); ?>" alt="Pelayanan GBI Salemba" class="pelayanan-hero-image">
            <div class="pelayanan-hero-overlay"></div>
            <div class="pelayanan-hero-content">
                <div class="pelayanan-hero-copy">
                    <h1 class="pelayanan-hero-title">Pelayanan</h1>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pelayanan-divider-section" aria-hidden="true">
    <div class="container-large">
        <div class="pelayanan-divider-wrap">
            <span class="pelayanan-divider-line"></span>
            <span class="pelayanan-divider-cross"></span>
            <span class="pelayanan-divider-line"></span>
        </div>
    </div>
</section>

<!-- PELAYANAN GRID SECTION -->
<section class="pelayanan-grid-section">
    <div class="container-large">
        <div class="pelayanan-grid-box">
    <?php
    if (count($pelayanan_items) > 0) {
        echo '<div class="pelayanan-alt-list">';
        foreach ($pelayanan_items as $index => $row) {
            $foto_path = !empty($row['foto'])
                ? 'uploads/pelayanan/' . $row['foto']
                : '';
            $image_meta = pelayanan_get_image_meta($foto_path);
            $judul_text = isset($row['judul']) ? trim((string) $row['judul']) : '';
            $judul_lower = strtolower($judul_text);
            $deskripsi_text = isset($row['deskripsi']) ? trim((string) $row['deskripsi']) : '';

            foreach ($pelayanan_description_map as $keyword => $description_override) {
                if (strpos($judul_lower, $keyword) !== false) {
                    $deskripsi_text = $description_override;
                    break;
                }
            }

            $foto_posisi_y = isset($row['foto_posisi_y']) ? (int) $row['foto_posisi_y'] : 50;
            if ($foto_posisi_y < 0) {
                $foto_posisi_y = 0;
            } elseif ($foto_posisi_y > 100) {
                $foto_posisi_y = 100;
            }
            $image_inline_style = 'object-position:center ' . $foto_posisi_y . '%;';

            ?>
            <article class="pelayanan-card pelayanan-alt-item <?php echo ($index % 2 === 0) ? 'image-right' : 'image-left'; ?>">
                <div class="pelayanan-alt-row">
                    <div class="<?php echo htmlspecialchars($image_meta['media_class']); ?> pelayanan-alt-media">
                    <?php if (!empty($foto_path) && file_exists($foto_path)): ?>
                        <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="<?php echo htmlspecialchars($judul_text); ?>" class="<?php echo htmlspecialchars($image_meta['image_class']); ?>" style="<?php echo htmlspecialchars($image_inline_style); ?>">
                    <?php else: ?>
                        <div class="service-image service-image-placeholder">
                            <i class="fas fa-image"></i>
                            <p>Foto pelayanan akan ditampilkan di sini</p>
                        </div>
                    <?php endif; ?>
                    </div>
                    <div class="pelayanan-card-content pelayanan-alt-content">
                        <h2 class="pelayanan-card-title"><?php echo htmlspecialchars($judul_text); ?></h2>
                        <p class="pelayanan-card-description"><?php echo htmlspecialchars($deskripsi_text); ?></p>
                    </div>
                </div>
            </article>
            <?php
        }
        echo '</div>';
    } else {
        ?>
            <div class="pelayanan-empty-card">
                <i class="fas fa-hands-helping" aria-hidden="true"></i>
                <p>Tidak ada data pelayanan saat ini.</p>
            </div>
        <?php
    }
    ?>
        </div>
    </div>
</section>
<!-- END PELAYANAN SECTION -->

<section class="pelayanan-contact-section">
    <div class="container-large">
        <div class="pelayanan-contact-ornament" aria-hidden="true">
            <span class="pelayanan-contact-ornament-line"></span>
            <span class="pelayanan-contact-ornament-cross"></span>
            <span class="pelayanan-contact-ornament-line"></span>
        </div>
        <div class="pelayanan-contact-card">
            <span class="pelayanan-contact-eyebrow">Butuh Informasi Lanjutan?</span>
            <div class="pelayanan-contact-actions">
                <a href="https://api.whatsapp.com/send?phone=6281918848181" target="_blank" rel="noopener noreferrer" class="pelayanan-contact-button">
                    <i class="fab fa-whatsapp"></i>
                    Hubungi 0819-1884-8181
                </a>
            </div>
        </div>
    </div>
</section>

</main>

<style>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700;9..144,800;9..144,900&family=Manrope:wght@600;700;800&display=swap');

.pelayanan-page {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    background:
    radial-gradient(circle at 10% 8%, rgba(44, 110, 170, 0.12) 0%, rgba(44, 110, 170, 0) 34%),
    radial-gradient(circle at 90% 14%, rgba(37, 133, 126, 0.08) 0%, rgba(37, 133, 126, 0) 30%),
    linear-gradient(160deg, #f8fbff 0%, #edf3fa 52%, #f5f9fc 100%);
}

.pelayanan-page::before,
.pelayanan-page::after {
    content: "";
    position: absolute;
    pointer-events: none;
    z-index: 0;
}

.pelayanan-page::before {
    width: 460px;
    height: 460px;
    top: -180px;
    right: -120px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(40, 98, 156, 0.12) 0%, rgba(40, 98, 156, 0) 70%);
    filter: blur(24px);
}

.pelayanan-page::after {
    width: 380px;
    height: 380px;
    bottom: 10%;
    left: -130px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(34, 137, 149, 0.1) 0%, rgba(34, 137, 149, 0) 72%);
    filter: blur(26px);
}

.pelayanan-page > * {
    position: relative;
    z-index: 1;
}

.pelayanan-hero-section {
    padding: 0 0 28px;
}

.pelayanan-hero-section .container-large {
    max-width: 100%;
    padding: 0;
}

.pelayanan-hero-banner {
    position: relative;
    border-radius: 0;
    overflow: hidden;
    height: clamp(300px, 50vh, 460px);
    box-shadow: 0 24px 56px rgba(7, 26, 49, 0.18);
}

.pelayanan-hero-banner::before {
    content: "";
    position: absolute;
    width: 420px;
    height: 420px;
    top: -200px;
    right: -110px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.22) 0%, rgba(255, 255, 255, 0) 70%);
    z-index: 2;
    pointer-events: none;
}

.pelayanan-hero-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 28%;
    display: block;
    transform: scale(1.02);
}

.pelayanan-hero-overlay {
    position: absolute;
    inset: 0;
    background:
      linear-gradient(180deg, rgba(2, 19, 37, 0.14) 0%, rgba(2, 19, 37, 0.18) 24%, rgba(2, 19, 37, 0.72) 100%),
      linear-gradient(90deg, rgba(7, 33, 62, 0.72) 0%, rgba(7, 33, 62, 0.28) 42%, rgba(34, 99, 122, 0.1) 100%);
}

.pelayanan-hero-content {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(26px, 4.6vw, 54px);
    z-index: 3;
}

.pelayanan-hero-copy {
    max-width: 620px;
    text-align: center;
}

.pelayanan-hero-title {
    margin: 0;
    font-family: 'Manrope', 'Segoe UI', Tahoma, sans-serif;
    font-size: clamp(40px, 6vw, 72px);
    line-height: 0.96;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    text-shadow: 0 16px 38px rgba(0, 0, 0, 0.3);
}

.pelayanan-grid-section {
    position: relative;
    padding: 4px 0 30px;
}

.pelayanan-divider-section {
    padding: 22px 0;
}

.pelayanan-divider-section .container-large {
    max-width: 1540px;
    padding: 0 22px;
}

.pelayanan-divider-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 18px;
    min-height: 44px;
}

.pelayanan-divider-line {
    width: min(340px, 36vw);
    height: 1px;
    background: linear-gradient(90deg, rgba(26, 79, 127, 0) 0%, rgba(26, 79, 127, 0.38) 50%, rgba(26, 79, 127, 0) 100%);
}

.pelayanan-divider-cross {
    position: relative;
    width: 22px;
    height: 28px;
    filter: drop-shadow(0 6px 12px rgba(17, 44, 76, 0.18));
}

.pelayanan-divider-cross::before,
.pelayanan-divider-cross::after {
    content: "";
    position: absolute;
    background: linear-gradient(180deg, #15416e 0%, #2d6f9f 100%);
    border-radius: 2px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.pelayanan-divider-cross::before {
    width: 12px;
    height: 3px;
    top: 38%;
}

.pelayanan-divider-cross::after {
    width: 3px;
    height: 24px;
}

.pelayanan-grid-section .container-large {
    max-width: 1540px;
    padding: 0 22px;
    position: relative;
    z-index: 1;
}

.pelayanan-grid-box {
    padding: 0;
}

.pelayanan-alt-list {
    display: grid;
    gap: clamp(10px, 1.2vw, 16px);
}

.pelayanan-alt-item {
    position: relative;
    opacity: 1;
    transform: none;
    animation: none;
    border: 0;
    box-shadow: none;
    background: transparent;
    border-radius: 0;
    padding: 10px 0;
    border-bottom: 0;
}

.pelayanan-alt-item::before,
.pelayanan-alt-item::after {
    content: "";
    position: absolute;
    pointer-events: none;
    z-index: 0;
}

.pelayanan-alt-item::before {
    width: 180px;
    height: 180px;
    right: 2%;
    top: 18px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(126, 156, 191, 0.16) 0%, rgba(126, 156, 191, 0) 72%);
}

.pelayanan-alt-item::after {
    width: 120px;
    height: 120px;
    left: 3%;
    bottom: 8px;
    border-radius: 44% 56% 54% 46% / 58% 42% 56% 44%;
    background: radial-gradient(circle at 38% 42%, rgba(165, 183, 120, 0.18) 0%, rgba(165, 183, 120, 0) 70%);
}

.pelayanan-alt-item:first-child {
    border-top: 0;
}

.pelayanan-alt-row {
    display: grid;
    grid-template-columns: minmax(300px, 430px) minmax(250px, 340px);
    align-items: center;
    justify-content: center;
    gap: clamp(10px, 1.3vw, 16px);
    padding: 0;
    border-radius: 0;
    background: transparent;
    border: 0;
    box-shadow: none;
    max-width: 940px;
    margin: 0 auto;
}

.pelayanan-alt-row::before,
.pelayanan-alt-row::after {
    content: "";
    position: absolute;
    pointer-events: none;
    z-index: 0;
}

.pelayanan-alt-row::before {
    width: 220px;
    height: 220px;
    top: -74px;
    left: -28px;
    border-radius: 58% 42% 52% 48% / 54% 46% 54% 46%;
    background: radial-gradient(circle at 34% 38%, rgba(102, 146, 114, 0.2) 0%, rgba(102, 146, 114, 0.06) 56%, rgba(102, 146, 114, 0) 74%);
    filter: blur(1px);
}

.pelayanan-alt-row::after {
    width: 140px;
    height: 140px;
    right: -20px;
    bottom: -18px;
    border-radius: 50%;
    background:
        radial-gradient(circle, rgba(180, 157, 106, 0.25) 0 2px, rgba(180, 157, 106, 0) 3px),
        radial-gradient(circle, rgba(180, 157, 106, 0.2) 0 1.7px, rgba(180, 157, 106, 0) 2.7px);
    background-size: 20px 20px, 16px 16px;
    background-position: 0 0, 8px 8px;
    opacity: 0.36;
}

.pelayanan-alt-item.image-left .pelayanan-alt-media {
    order: 1;
    justify-self: start;
}

.pelayanan-alt-item.image-left .pelayanan-alt-content {
    order: 2;
    justify-self: end;
}

.pelayanan-alt-item.image-right .pelayanan-alt-media {
    order: 2;
    justify-self: start;
}

.pelayanan-alt-item.image-right .pelayanan-alt-content {
    order: 1;
    justify-self: end;
}

.pelayanan-card:hover {
    transform: translateY(-4px);
}

.pelayanan-card:nth-child(1) {
    animation-delay: 0.08s;
}

.pelayanan-card:nth-child(2) {
    animation-delay: 0.16s;
}

.pelayanan-card:nth-child(3) {
    animation-delay: 0.24s;
}

.pelayanan-card:nth-child(4) {
    animation-delay: 0.32s;
}

.pelayanan-card:nth-child(5) {
    animation-delay: 0.4s;
}

.pelayanan-card:nth-child(6) {
    animation-delay: 0.48s;
}

.pelayanan-card:nth-child(n+7) {
    animation-delay: 0.56s;
}

.pelayanan-card-media {
    position: relative;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    border-radius: 14px;
    background: linear-gradient(180deg, rgba(221, 233, 245, 0.88) 0%, rgba(200, 218, 236, 0.96) 100%);
    box-shadow: none;
}

.pelayanan-alt-media {
    width: min(100%, 340px);
    box-shadow: 0 10px 22px rgba(16, 44, 87, 0.12);
}

.pelayanan-card-media::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 0;
    border: 1px solid rgba(255, 255, 255, 0.3);
    z-index: 3;
    pointer-events: none;
}

.pelayanan-card-media::after {
    display: none;
}

.pelayanan-alt-media::before,
.pelayanan-alt-media::after {
    display: none;
}

.pelayanan-card .service-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    border-radius: 0;
    transition: transform 0.75s ease, filter 0.75s ease;
}

.pelayanan-card-media.is-rotated {
    background: #0f2135;
}

.pelayanan-card .service-image.rotate-90,
.pelayanan-card .service-image.rotate-270,
.pelayanan-card .service-image.rotate-180 {
    transform-origin: center;
}

.pelayanan-card .service-image.rotate-90 {
    transform: rotate(90deg) scale(1.34);
}

.pelayanan-card .service-image.rotate-270 {
    transform: rotate(-90deg) scale(1.34);
}

.pelayanan-card .service-image.rotate-180 {
    transform: rotate(180deg) scale(1.02);
}

.pelayanan-card:hover .service-image {
    transform: scale(1.04);
    filter: saturate(1.03);
}

.pelayanan-card:hover .service-image.rotate-90 {
    transform: rotate(90deg) scale(1.38) translateY(-2px);
}

.pelayanan-card:hover .service-image.rotate-270 {
    transform: rotate(-90deg) scale(1.38) translateY(-2px);
}

.pelayanan-card:hover .service-image.rotate-180 {
    transform: rotate(180deg) scale(1.04) translateY(-2px);
}

.pelayanan-card .service-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 8px;
    color: rgba(16, 44, 87, 0.7);
}

.pelayanan-card .service-image-placeholder i {
    font-size: 24px;
}

.pelayanan-card-content {
    padding: 18px 18px 20px;
    display: grid;
    gap: 10px;
}

.pelayanan-alt-content {
    padding: 0;
    gap: 8px;
    align-content: center;
    max-width: 420px;
    position: relative;
    z-index: 1;
}

.pelayanan-card-title {
    margin: 0;
    color: #103a66;
    font-family: 'Fraunces', 'Playfair Display', Georgia, serif;
    font-size: clamp(30px, 2.7vw, 40px);
    font-weight: 800;
    line-height: 0.98;
    letter-spacing: -0.02em;
    max-width: 16ch;
}

.pelayanan-card-description {
    margin: 0;
    color: #4f617a;
    font-size: 15px;
    line-height: 1.5;
    max-width: 30ch;
}

.pelayanan-empty-card {
    display: grid;
    justify-items: center;
    gap: 12px;
    padding: 34px 24px;
    border-radius: 22px;
    border: 1px solid rgba(30, 58, 95, 0.12);
    background: rgba(255, 255, 255, 0.9);
    color: rgba(16, 44, 87, 0.8);
    font-weight: 600;
}

.pelayanan-empty-card i {
    font-size: 24px;
    color: #1e3a5f;
}

.pelayanan-contact-section {
    padding: 10px 0 36px;
    background: linear-gradient(180deg, rgba(238, 246, 252, 0) 0%, rgba(226, 240, 251, 0.82) 100%);
}

.pelayanan-contact-ornament {
    display: grid;
    grid-template-columns: minmax(70px, 1fr) auto minmax(70px, 1fr);
    align-items: center;
    column-gap: 18px;
    margin-bottom: 18px;
}

.pelayanan-contact-ornament-line {
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, rgba(21, 65, 110, 0) 0%, rgba(21, 65, 110, 0.36) 50%, rgba(21, 65, 110, 0) 100%);
}

.pelayanan-contact-ornament-cross {
    position: relative;
    width: 22px;
    height: 28px;
    filter: drop-shadow(0 4px 8px rgba(18, 58, 98, 0.16));
}

.pelayanan-contact-ornament-cross::before,
.pelayanan-contact-ornament-cross::after {
    content: "";
    position: absolute;
    background: linear-gradient(180deg, #1a4777 0%, #2f79a8 100%);
    border-radius: 2px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.pelayanan-contact-ornament-cross::before {
    width: 12px;
    height: 3px;
    top: 38%;
}

.pelayanan-contact-ornament-cross::after {
    width: 3px;
    height: 24px;
}

.pelayanan-contact-card {
    position: relative;
    max-width: 860px;
    margin: 0 auto;
    padding: clamp(28px, 4.2vw, 42px);
    text-align: center;
    border-radius: 22px;
    background: linear-gradient(132deg, rgba(19, 53, 87, 0.96) 0%, rgba(33, 87, 134, 0.94) 58%, rgba(45, 127, 151, 0.92) 100%);
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(6px);
    color: #ffffff;
    overflow: hidden;
    box-shadow: 0 16px 42px rgba(16, 44, 87, 0.18);
}

.pelayanan-contact-card::before {
    content: "";
    position: absolute;
    width: 320px;
    height: 320px;
    top: -180px;
    right: -80px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.28) 0%, rgba(255, 255, 255, 0) 72%);
}

.pelayanan-contact-eyebrow {
    position: relative;
    display: inline-block;
    margin-bottom: 18px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.9);
}

.pelayanan-contact-actions {
    position: relative;
    display: flex;
    justify-content: center;
}

.pelayanan-contact-button {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 14px 24px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.95);
    color: #133a63;
    font-weight: 800;
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 12px 24px rgba(9, 23, 40, 0.18);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.pelayanan-contact-button i {
    color: #1da851;
    font-size: 20px;
}

.pelayanan-contact-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 30px rgba(9, 23, 40, 0.24);
}

@keyframes pelayananCardReveal {
    0% {
        opacity: 0;
        transform: translateY(28px) scale(0.985);
    }

    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .pelayanan-card {
        opacity: 1;
        transform: none;
        animation: none;
    }
}

@media (max-width: 992px) {
    .pelayanan-hero-section {
        padding: 0 0 24px;
    }

    .pelayanan-hero-banner {
        height: clamp(250px, 42vh, 340px);
    }

    .pelayanan-alt-row {
        grid-template-columns: 1fr;
        gap: 12px;
        border-radius: 0;
        padding: 0;
        max-width: 100%;
    }

    .pelayanan-alt-item::before,
    .pelayanan-alt-item::after {
        opacity: 0.6;
        transform: scale(0.84);
    }

    .pelayanan-alt-row::before,
    .pelayanan-alt-row::after {
        opacity: 0.55;
        transform: scale(0.8);
    }

    .pelayanan-alt-item.image-left .pelayanan-alt-media,
    .pelayanan-alt-item.image-right .pelayanan-alt-media {
        order: 1;
        width: min(100%, 380px);
    }

    .pelayanan-alt-item.image-left .pelayanan-alt-content,
    .pelayanan-alt-item.image-right .pelayanan-alt-content {
        order: 2;
        justify-self: center;
    }

    .pelayanan-grid-section {
        padding-bottom: 20px;
    }

    .pelayanan-divider-section {
        padding: 18px 0;
    }
}

@media (max-width: 767px) {
    .pelayanan-hero-section .container-large {
        padding: 0;
    }

    .pelayanan-hero-title {
        font-size: clamp(34px, 10.2vw, 50px);
    }

    .pelayanan-grid-box {
        padding: 4px 0;
    }

    .pelayanan-alt-row {
        border-radius: 0;
        padding: 0;
        gap: 12px;
    }

    .pelayanan-alt-item::before,
    .pelayanan-alt-item::after {
        opacity: 0.42;
        transform: scale(0.66);
    }

    .pelayanan-card-media {
        aspect-ratio: 1 / 1;
        border-radius: 12px;
    }

    .pelayanan-card-title {
        font-size: clamp(28px, 9.2vw, 36px);
        line-height: 0.96;
        max-width: 100%;
    }

    .pelayanan-card-description {
        font-size: 15px;
        line-height: 1.55;
        max-width: none;
    }

    .pelayanan-alt-row::before,
    .pelayanan-alt-row::after {
        opacity: 0.42;
        transform: scale(0.64);
    }

    .pelayanan-divider-section .container-large {
        padding: 0 16px;
    }

    .pelayanan-contact-section {
        padding: 8px 0 26px;
    }

    .pelayanan-contact-card {
        border-radius: 24px;
    }

    .pelayanan-contact-button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php
include __DIR__ . '/includes/footer.php';
?>


