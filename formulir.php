<?php
require_once 'config/database.php';
include 'includes/header.php';

if (!function_exists('formulir_find_first_image')) {
    function formulir_find_first_image(array $directories, $fallback = 'assets/images/default-avatar.png') {
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
                if (!in_array($extension, $supported_extensions, true)) {
                    continue;
                }

                $candidate = rtrim($directory, '/\\') . '/' . $file;
                if (file_exists($candidate)) {
                    return $candidate;
                }
            }
        }

        return $fallback;
    }
}

if (!function_exists('formulir_collect_images')) {
    function formulir_collect_images(array $directories, $fallback = 'assets/images/default-avatar.png', $max_images = 24) {
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
                if (!file_exists($candidate)) {
                    continue;
                }

                $images[] = $candidate;
                if (count($images) >= $max_images) {
                    break 2;
                }
            }
        }

        if (empty($images)) {
            $images[] = $fallback;
        }

        return $images;
    }
}

// Get all active formulir, ordered by id DESC
$query = "SELECT id, nama_formulir, deskripsi, file, status FROM formulir 
          WHERE status = 'aktif' 
          ORDER BY id DESC";
$result = $conn->query($query);

$formulir_items = [];
if ($result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $formulir_items[] = $row;
    }
}

$formulir_hero_photo = formulir_find_first_image([
    'uploads/pelayanan',
    'uploads/slider',
    'assets/images/gembala'
]);

$formulir_card_images = formulir_collect_images([
    'uploads/pelayanan',
    'uploads/slider',
    'assets/images/gembala'
], $formulir_hero_photo);

?>

<main class="main-content formulir-page">

<!-- FORMULIR HEADER SECTION -->
<section class="formulir-header-section">
    <div class="container-large">
        <div class="formulir-hero-banner">
            <img src="<?php echo htmlspecialchars($formulir_hero_photo); ?>" alt="Formulir GBI Salemba" class="formulir-hero-image">
            <div class="formulir-hero-overlay"></div>
            <div class="formulir-hero-content">
                <div class="formulir-hero-copy">
                    <h1 class="formulir-header-title">Formulir</h1>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="formulir-divider-section" aria-hidden="true">
    <div class="container-large">
        <div class="formulir-divider-wrap">
            <span class="formulir-divider-line"></span>
            <span class="formulir-divider-cross"></span>
            <span class="formulir-divider-line"></span>
        </div>
    </div>
</section>

<!-- FORMULIR GRID SECTION -->
<section class="formulir-section">
    <div class="container-large">
        <?php
        if (count($formulir_items) > 0) {
            ?>
            <div class="formulir-grid">
                <?php
                foreach ($formulir_items as $index => $row) {
                    // Check if file exists
                    $file_exists = !empty($row['file']) && file_exists('uploads/formulir/' . $row['file']);
                    $card_image = $formulir_card_images[$index % count($formulir_card_images)];
                    $card_description = trim((string)($row['deskripsi'] ?? ''));
                    ?>
                    <div class="formulir-card">
                        <div class="formulir-card-media">
                            <img src="<?php echo htmlspecialchars($card_image); ?>" alt="<?php echo htmlspecialchars($row['nama_formulir']); ?>" class="formulir-card-photo">
                        </div>
                        <div class="formulir-card-content">
                            <h3 class="formulir-title"><?php echo htmlspecialchars($row['nama_formulir']); ?></h3>
                            <?php if ($card_description !== ''): ?>
                                <p class="formulir-description"><?php echo htmlspecialchars($card_description); ?></p>
                            <?php endif; ?>
                            <?php if ($file_exists): ?>
                                <a href="download-formulir.php?id=<?php echo $row['id']; ?>" 
                                   class="btn-download-formulir">
                                    <i class="fas fa-download"></i> Download PDF
                                </a>
                            <?php else: ?>
                                <div class="file-not-available">
                                    <i class="fas fa-exclamation-circle"></i> File tidak tersedia
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php
        } else {
            ?>
            <div style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-inbox" style="font-size: 64px; color: #CCC; margin-bottom: 20px; display: block;"></i>
                <p style="font-size: 18px; color: #999;">Tidak ada formulir yang tersedia saat ini.</p>
            </div>
            <?php
        }
        ?>
    </div>
</section>

<section class="formulir-process-section">
    <div class="container-large">
        <div class="formulir-process-shell">
            <h2 class="formulir-process-title">Alur Penyerahan Formulir</h2>
            <div class="formulir-step-flow">
                <div class="formulir-step-flow-line"></div>
                <div class="formulir-step-flow-line second-line"></div>

                <div class="formulir-step-item">
                    <span class="formulir-step-icon"><i class="fas fa-file-arrow-down"></i></span>
                    <span class="formulir-step-label">Langkah 1</span>
                    <strong>Download Formulir</strong>
                </div>

                <div class="formulir-step-item">
                    <span class="formulir-step-icon"><i class="fas fa-pen-to-square"></i></span>
                    <span class="formulir-step-label">Langkah 2</span>
                    <strong>Mengisi Formulir &amp; Mempersiapkan kelengkapan berkas</strong>
                </div>

                <div class="formulir-step-item">
                    <span class="formulir-step-icon"><i class="fas fa-paper-plane"></i></span>
                    <span class="formulir-step-label">Langkah 3</span>
                    <strong>Mengirimkan ke Sekretariat</strong>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- END FORMULIR SECTION -->

</main>

<style>
@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&display=swap');

/* ============================================
   FORMULIR CARD STYLING
   ============================================ */
.formulir-page {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    font-family: 'Manrope', 'Segoe UI', Tahoma, sans-serif;
    background:
    radial-gradient(circle at 9% 9%, rgba(42, 106, 168, 0.12) 0%, rgba(42, 106, 168, 0) 34%),
    radial-gradient(circle at 92% 15%, rgba(30, 129, 121, 0.08) 0%, rgba(30, 129, 121, 0) 30%),
    linear-gradient(160deg, #f8fbff 0%, #edf3fa 52%, #f5f9fc 100%);
}

.formulir-page::before,
.formulir-page::after {
    content: "";
    position: absolute;
    pointer-events: none;
    z-index: 0;
}

.formulir-page::before {
    width: 460px;
    height: 460px;
    top: -180px;
    right: -120px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(40, 98, 156, 0.12) 0%, rgba(40, 98, 156, 0) 70%);
    filter: blur(24px);
}

.formulir-page::after {
    width: 380px;
    height: 380px;
    left: -130px;
    bottom: 10%;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(34, 137, 149, 0.1) 0%, rgba(34, 137, 149, 0) 72%);
    filter: blur(26px);
}

.formulir-page > * {
    position: relative;
    z-index: 1;
}

.formulir-header-section {
    padding: 0 0 28px;
}

.formulir-header-section .container-large {
    max-width: 100%;
    padding: 0;
}

.formulir-hero-banner {
    position: relative;
    border-radius: 0;
    overflow: hidden;
    height: clamp(300px, 50vh, 460px);
    box-shadow: 0 24px 56px rgba(7, 26, 49, 0.18);
}

.formulir-hero-banner::before {
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

.formulir-hero-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 28%;
    display: block;
    transform: scale(1.02);
}

.formulir-hero-overlay {
    position: absolute;
    inset: 0;
    background:
      linear-gradient(180deg, rgba(2, 19, 37, 0.14) 0%, rgba(2, 19, 37, 0.18) 24%, rgba(2, 19, 37, 0.72) 100%),
      linear-gradient(90deg, rgba(7, 33, 62, 0.72) 0%, rgba(7, 33, 62, 0.28) 42%, rgba(34, 99, 122, 0.1) 100%);
}

.formulir-hero-content {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(26px, 4.6vw, 54px);
    z-index: 3;
}

.formulir-hero-copy {
    max-width: 620px;
    text-align: center;
}

.formulir-header-title {
    margin: 0;
    font-family: 'Manrope', 'Segoe UI', Tahoma, sans-serif;
    font-size: clamp(40px, 6vw, 72px);
    line-height: 0.96;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    text-shadow: 0 16px 38px rgba(0, 0, 0, 0.3);
}

.formulir-divider-section {
    padding: 22px 0;
}

.formulir-divider-section .container-large {
    max-width: 1540px;
    padding: 0 22px;
}

.formulir-divider-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 18px;
    min-height: 44px;
}

.formulir-divider-line {
    width: min(340px, 36vw);
    height: 1px;
    background: linear-gradient(90deg, rgba(21, 65, 110, 0) 0%, rgba(21, 65, 110, 0.36) 50%, rgba(21, 65, 110, 0) 100%);
}

.formulir-divider-cross {
    position: relative;
    width: 22px;
    height: 28px;
    filter: drop-shadow(0 6px 12px rgba(17, 44, 76, 0.18));
}

.formulir-divider-cross::before,
.formulir-divider-cross::after {
    content: '';
    position: absolute;
    background: linear-gradient(180deg, #15416e 0%, #2d6f9f 100%);
    border-radius: 2px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.formulir-divider-cross::before {
    width: 12px;
    height: 3px;
    top: 38%;
}

.formulir-divider-cross::after {
    width: 3px;
    height: 24px;
}

.formulir-section {
    padding: 0 0 58px !important;
}

.formulir-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(238px, 1fr));
    gap: 22px;
    margin-bottom: 0;
}

.formulir-card {
    background: linear-gradient(180deg, rgba(255,255,255,0.9) 0%, rgba(249, 251, 255, 0.88) 100%);
    border: 1px solid rgba(30, 58, 95, 0.08);
    border-radius: 20px;
    min-height: 366px;
    text-align: left;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: space-between;
    box-shadow: 0 10px 24px rgba(16, 44, 87, 0.06);
    overflow: hidden;
}

.formulir-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 36px rgba(30, 58, 95, 0.11);
    border-color: rgba(63, 182, 168, 0.18);
}

.formulir-card-media {
    width: calc(100% - 18px);
    margin: 9px 9px 0;
    height: clamp(166px, 16vw, 205px);
    overflow: hidden;
    background: #d9e5f2;
    border-radius: 12px;
}

.formulir-card-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
    transform: scale(1.01);
    transition: transform 0.45s ease;
}

.formulir-card:hover .formulir-card-photo {
    transform: scale(1.05);
}

.formulir-card-content {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 18px 18px;
}

.formulir-title {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: clamp(22px, 1.65vw, 28px);
    font-weight: 600;
    color: #102C57;
    margin: 0;
    line-height: 1.2;
    max-width: 19ch;
}

.formulir-description {
    margin: 0;
    color: #5f6d84;
    font-size: 14px;
    line-height: 1.5;
    max-width: 34ch;
    min-height: 64px;
}

.btn-download-formulir {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #eaedf5;
    color: #1f3f6f;
    min-width: 132px;
    justify-content: center;
    padding: 8px 14px;
    border-radius: 8px;
    border: 1px solid rgba(55, 84, 126, 0.12);
    font-weight: 600;
    font-size: 12px;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    margin-top: auto;
}

.btn-download-formulir:hover {
    transform: translateY(-1px);
    background: #dfe6f4;
    box-shadow: 0 5px 10px rgba(30, 58, 95, 0.12);
}

.btn-download-formulir i {
    font-size: 11px;
}

.file-not-available {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #999;
    font-size: 14px;
}

.file-not-available i {
    color: #DC3545;
}

.formulir-process-section {
    padding: 0 0 18px;
}

.formulir-page + .footer {
    margin-top: 20px;
}

.formulir-process-shell {
    max-width: 1180px;
    margin: 0 auto;
    padding: clamp(22px, 3.2vw, 32px);
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(30, 58, 95, 0.08);
    box-shadow: 0 18px 38px rgba(16, 44, 87, 0.08);
    text-align: center;
}

.formulir-process-eyebrow {
    display: none;
}

.formulir-process-title {
    font-size: clamp(26px, 3.2vw, 34px);
    line-height: 1.12;
    color: #102C57;
    margin: 0 0 20px;
}

.formulir-step-flow {
    position: relative;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    align-items: start;
    padding: 8px 8px 0;
}

.formulir-step-flow-line {
    position: absolute;
    top: 21px;
    height: 2px;
    background: linear-gradient(90deg, rgba(21, 65, 110, 0.24) 0%, rgba(21, 65, 110, 0.38) 100%);
    z-index: 0;
}

.formulir-step-flow-line {
    left: calc(16.666% + 26px);
    width: calc(33.333% - 52px);
}

.formulir-step-flow-line.second-line {
    left: calc(50% + 26px);
}

.formulir-step-item {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.formulir-step-icon {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    margin-bottom: 10px;
    border: 2px solid rgba(21, 65, 110, 0.18);
    background: #ffffff;
    color: #1b4d7b;
    box-shadow: 0 4px 12px rgba(21, 65, 110, 0.12);
}

.formulir-step-label {
    display: block;
    margin-bottom: 4px;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #96a2b1;
}

.formulir-step-item strong {
    display: block;
    font-size: 15px;
    line-height: 1.3;
    color: #102C57;
    max-width: 30ch;
}

/* ============================================
   RESPONSIVE MEDIA QUERIES
   ============================================ */

/* Tablet: 768px - 1199px */
@media (min-width: 768px) and (max-width: 1199px) {
    .formulir-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
    }
}

/* Mobile: < 768px */
@media (max-width: 767px) {
    .formulir-header-section {
        padding: 0;
    }

    .formulir-hero-banner {
        min-height: 250px;
    }

    .formulir-header-title {
        font-size: clamp(34px, 10vw, 48px);
    }

    .formulir-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .formulir-card {
        min-height: 340px;
        border-radius: 18px;
    }

    .formulir-card-media {
        height: 170px;
    }

    .formulir-card-content {
        padding: 14px 14px 16px;
    }

    .formulir-process-section {
        padding: 0 0 20px;
    }

    .formulir-process-shell {
        padding: 20px 14px;
        border-radius: 16px;
    }

    .formulir-step-flow {
        grid-template-columns: 1fr;
        gap: 18px;
        padding-top: 0;
    }

    .formulir-step-flow-line {
        display: none;
    }

    .formulir-step-item {
        padding: 16px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.74);
        border: 1px solid rgba(30, 58, 95, 0.08);
    }

    .formulir-title {
        font-size: clamp(24px, 8vw, 30px);
    }

    .formulir-description {
        font-size: 13px;
        min-height: 0;
    }

    .btn-download-formulir {
        min-width: 128px;
    }
}
</style>

<?php
include 'includes/footer.php';
?>
