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
                foreach ($formulir_items as $row) {
                    // Check if file exists
                    $file_exists = !empty($row['file']) && file_exists('uploads/formulir/' . $row['file']);
                    ?>
                    <div class="formulir-card">
                        <div class="formulir-card-media">
                            <img src="<?php echo htmlspecialchars($formulir_hero_photo); ?>" alt="<?php echo htmlspecialchars($row['nama_formulir']); ?>" class="formulir-card-photo">
                        </div>
                        <div class="formulir-card-content">
                            <h3 class="formulir-title"><?php echo htmlspecialchars($row['nama_formulir']); ?></h3>
                            <?php if ($file_exists): ?>
                                <a href="download-formulir.php?id=<?php echo $row['id']; ?>" 
                                   class="btn-download-formulir">
                                    <i class="fas fa-download"></i> Download
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
                <div class="formulir-step-flow-line third-line"></div>

                <div class="formulir-step-item">
                    <span class="formulir-step-icon"><i class="fas fa-file-arrow-down"></i></span>
                    <span class="formulir-step-label">Step 1</span>
                    <strong>Download Formulir</strong>
                </div>

                <div class="formulir-step-item">
                    <span class="formulir-step-icon"><i class="fas fa-pen"></i></span>
                    <span class="formulir-step-label">Step 2</span>
                    <strong>Isi Formulir</strong>
                </div>

                <div class="formulir-step-item">
                    <span class="formulir-step-icon"><i class="fas fa-paper-plane"></i></span>
                    <span class="formulir-step-label">Step 3</span>
                    <strong>Serahkan Formulir</strong>
                </div>

                <div class="formulir-step-item">
                    <span class="formulir-step-icon"><i class="fas fa-hourglass-half"></i></span>
                    <span class="formulir-step-label">Step 4</span>
                    <strong>Tunggu Konfirmasi</strong>
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
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 26px;
    margin-bottom: 0;
}

.formulir-card {
    background: rgba(255,255,255,0.92);
    border: 1px solid rgba(30, 58, 95, 0.08);
    border-radius: 34px;
    min-height: 380px;
    text-align: center;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: space-between;
    box-shadow: 0 16px 34px rgba(16, 44, 87, 0.08);
    overflow: hidden;
}

.formulir-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 22px 42px rgba(30, 58, 95, 0.13);
    border-color: rgba(63, 182, 168, 0.3);
}

.formulir-card-media {
    width: 100%;
    height: clamp(170px, 17vw, 220px);
    overflow: hidden;
    background: #d9e5f2;
}

.formulir-card-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
    transform: scale(1.02);
    transition: transform 0.45s ease;
}

.formulir-card:hover .formulir-card-photo {
    transform: scale(1.08);
}

.formulir-card-content {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18px;
    padding: 22px 20px 24px;
}

.formulir-title {
    font-size: clamp(19px, 1.8vw, 28px);
    font-weight: 700;
    color: #102C57;
    margin: 0;
    line-height: 1.35;
    max-width: 18ch;
}

.btn-download-formulir {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, #1E3A5F 0%, #0F2742 100%);
    color: white;
    min-width: 170px;
    justify-content: center;
    padding: 10px 20px;
    border-radius: 999px;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
}

.btn-download-formulir:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(30, 58, 95, 0.25);
}

.btn-download-formulir i {
    font-size: 16px;
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
    padding: 0 0 28px;
}

.formulir-page + .footer {
    margin-top: 20px;
}

.formulir-process-shell {
    max-width: 1180px;
    margin: 0 auto;
    padding: clamp(28px, 4vw, 42px);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(30, 58, 95, 0.08);
    box-shadow: 0 18px 38px rgba(16, 44, 87, 0.08);
    text-align: center;
}

.formulir-process-eyebrow {
    display: none;
}

.formulir-process-title {
    font-size: clamp(32px, 4vw, 44px);
    line-height: 1.12;
    color: #102C57;
    margin: 0 0 30px;
}

.formulir-step-flow {
    position: relative;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    align-items: start;
    padding: 12px 10px 0;
}

.formulir-step-flow-line {
    position: absolute;
    top: 25px;
    height: 2px;
    background: linear-gradient(90deg, rgba(21, 65, 110, 0.24) 0%, rgba(21, 65, 110, 0.38) 100%);
    z-index: 0;
}

.formulir-step-flow-line {
    left: calc(12.5% + 26px);
    width: calc(25% - 52px);
}

.formulir-step-flow-line.second-line {
    left: calc(37.5% + 26px);
}

.formulir-step-flow-line.third-line {
    left: calc(62.5% + 26px);
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
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    margin-bottom: 14px;
    border: 2px solid rgba(21, 65, 110, 0.18);
    background: #ffffff;
    color: #1b4d7b;
    box-shadow: 0 4px 12px rgba(21, 65, 110, 0.12);
}

.formulir-step-label {
    display: block;
    margin-bottom: 6px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #96a2b1;
}

.formulir-step-item strong {
    display: block;
    font-size: 18px;
    line-height: 1.25;
    color: #102C57;
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
        border-radius: 28px;
    }

    .formulir-card-media {
        height: 160px;
    }

    .formulir-card-content {
        padding: 20px 16px 20px;
    }

    .formulir-process-section {
        padding: 0 0 20px;
    }

    .formulir-process-shell {
        padding: 24px 18px;
        border-radius: 20px;
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
        font-size: clamp(20px, 7vw, 26px);
    }
}
</style>

<?php
include 'includes/footer.php';
?>
