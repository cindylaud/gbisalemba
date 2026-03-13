<?php
require_once 'config/database.php';
include 'includes/header.php';

if (!function_exists('gbi_find_first_image')) {
    function gbi_find_first_image(array $directories, $fallback = 'assets/images/default-avatar.png') {
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

$ibadah_photo = gbi_find_first_image([
    'assets/images/umum',
    'uploads/slider',
    'assets/images/gembala'
]);

$gembala_photo = gbi_find_first_image([
    'assets/images/gembala',
    'uploads/slider'
], $ibadah_photo);

$cta_photo = gbi_find_first_image([
    'uploads/whatsnew',
    'uploads/slider',
    'assets/images/gembala'
], $ibadah_photo);
?>

<main class="main-content home-variant-formal">

<!-- 1. HERO / SLIDER SECTION -->
<section class="hero-slider" id="sliderSection">
  <?php
  $q = "SELECT image, urutan
        FROM slider
        WHERE is_active = 1
          AND image IS NOT NULL
          AND image <> ''
          AND image <> 'default.png'
        ORDER BY urutan ASC
        LIMIT 4";
  $res = $conn->query($q);

  $images = [];
  while ($row = $res->fetch_assoc()) {
      $images[] = $row['image'];
  }

  if (empty($images)) {
      // fallback kalau belum ada foto aktif
      ?>
      <div class="hero-slide active">
        <div class="hero-bg" style="background: linear-gradient(135deg,#102C57 0%,#DAC0A3 100%);"></div>
        <div class="hero-overlay"></div>
      </div>
      <?php
  } else {
      foreach ($images as $i => $img) { ?>
        <div class="hero-slide <?php echo $i === 0 ? 'active' : ''; ?>">
          <div class="hero-bg" style="background-image:url('/gbisalemba/uploads/slider/<?php echo htmlspecialchars($img); ?>');"></div>
          <div class="hero-overlay"></div>
        </div>
      <?php } ?>

      <?php if (count($images) > 1): ?>
        <button class="hero-btn hero-btn-prev" onclick="sliderPrev()">‹</button>
        <button class="hero-btn hero-btn-next" onclick="sliderNext()">›</button>

        <div class="hero-dots">
          <?php for ($i=0; $i<count($images); $i++): ?>
            <span class="hero-dot <?php echo $i===0?'active':''; ?>" onclick="sliderGoto(<?php echo $i; ?>)"></span>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

  <?php } ?>
</section>

<!-- 2. WHAT'S NEW SECTION -->
<section class="whats-new">
  <div class="container-large">
        <h2 class="section-title-big">WHAT'S NEW</h2>

                <div class="whats-new-slider" id="whatsNewSlider">
            <?php
            $q_wn = "SELECT image FROM whats_new
                             WHERE is_active = 1
                             ORDER BY urutan ASC
                             LIMIT 5";
            $r_wn = $conn->query($q_wn);

            $wn_images = [];
            if ($r_wn) {
                    while ($row_wn = $r_wn->fetch_assoc()) {
                            $wn_images[] = $row_wn['image'];
                    }
            }

            if (!empty($wn_images)): ?>
                <div class="whats-new-track" id="whatsNewTrack">
                    <?php foreach ($wn_images as $i => $img): ?>
                        <div class="whats-new-card">
                            <img src="/gbisalemba/uploads/whatsnew/<?php echo htmlspecialchars($img); ?>" alt="What's New <?php echo $i + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($wn_images) > 1): ?>
                    <button class="whats-new-nav prev" type="button" id="whatsNewPrev" aria-label="Foto sebelumnya">&#8249;</button>
                    <button class="whats-new-nav next" type="button" id="whatsNewNext" aria-label="Foto berikutnya">&#8250;</button>
                    <div class="whats-new-dots" id="whatsNewDots" aria-label="Navigasi foto"></div>
                <?php endif; ?>
            <?php
            else: ?>
                <div class="whats-new-empty">
                    <div class="whats-new-empty-content">
                        <span>Belum ada foto terbaru</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

  </div>
</section>

<!-- 3. IBADAH MINGGU SECTION -->
<section class="ibadah-minggu-section reveal-on-scroll" data-reveal="section" data-delay="0">
    <div class="ibadah-blob ibadah-blob-1"></div>
    <div class="ibadah-blob ibadah-blob-2"></div>
    <div class="container-large">
        <div class="ibadah-minggu-header">
            <span class="ibadah-eyebrow">Bergabunglah Bersama Kami</span>
            <h2 class="ibadah-minggu-title">Ibadah Minggu</h2>
        </div>
        <div class="ibadah-minggu-layout">
            <div class="ibadah-minggu-schedule reveal-on-scroll" data-reveal="left" data-delay="80">
                <div class="ibadah-jpcc-list">
                    <article class="ibadah-jpcc-item">
                        <span class="ibadah-jpcc-time">08:00 WIB</span>
                    </article>
                    <article class="ibadah-jpcc-item">
                        <span class="ibadah-jpcc-time">10:30 WIB</span>
                    </article>
                    <article class="ibadah-jpcc-item">
                        <span class="ibadah-jpcc-time">17:00 WIB</span>
                    </article>
                </div>
            </div>
            <div class="ibadah-minggu-image reveal-on-scroll" data-reveal="right" data-delay="160">
                <div class="ibadah-img-wrapper">
                    <img src="<?php echo htmlspecialchars($ibadah_photo); ?>"
                         alt="Ibadah Minggu GBI Salemba" class="ibadah-minggu-img">
                </div>
                <div class="ibadah-img-deco"></div>
            </div>
        </div>
    </div>
</section>

<!-- 5. PROFIL GEMBALA SECTION -->
<section class="gembala-section reveal-on-scroll" data-reveal="section" data-delay="0">
    <div class="gembala-shape-1"></div>
    <div class="gembala-shape-2"></div>
    <div class="gembala-running-text" aria-hidden="true">
        <div class="gembala-running-track">
            <span>Bapak &amp; Ibu Gembala</span>
            <span>Bapak &amp; Ibu Gembala</span>
            <span>Bapak &amp; Ibu Gembala</span>
            <span>Bapak &amp; Ibu Gembala</span>
            <span>Bapak &amp; Ibu Gembala</span>
            <span>Bapak &amp; Ibu Gembala</span>
        </div>
    </div>
    <div class="container-large">
        <?php
        $gembala_data = [
            'nama_line_1' => 'Ps. David Natanael',
            'nama_line_2' => 'Ps. Rita Emia Nata'
        ];
        ?>
        <div class="gembala-layout">
            <div class="gembala-photo reveal-on-scroll" data-reveal="left" data-delay="80">
                <div class="gembala-photo-frame">
                    <img src="<?php echo htmlspecialchars($gembala_photo); ?>"
                         alt="Bapak dan Ibu Gembala GBI Salemba"
                         class="gembala-img">
                </div>
                <div class="gembala-photo-accent"></div>
            </div>
            <div class="gembala-info reveal-on-scroll" data-reveal="right" data-delay="160">
                <h3 class="gembala-name gembala-name-only">
                    <span class="gembala-name-line"><?php echo htmlspecialchars($gembala_data['nama_line_1']); ?></span>
                    <span class="gembala-separator" aria-hidden="true">
                        <span></span><em>dan</em><span></span>
                    </span>
                    <span class="gembala-name-line"><?php echo htmlspecialchars($gembala_data['nama_line_2']); ?></span>
                </h3>
            </div>
        </div>
    </div>
</section>

<!-- 6. CTA HUBUNGI KAMI SECTION -->
<section class="cta-section reveal-on-scroll" data-reveal="section" data-delay="0">
    <div class="container-large">
        <div class="cta-layout">
            <div class="cta-content reveal-on-scroll" data-reveal="left" data-delay="80">
                <h2 class="cta-title">Ada yang Bisa Kami Bantu?</h2>
                <p class="cta-phone">WhatsApp: 0819-1884-8181</p>
                <a href="https://wa.me/6281918848181" class="btn-cta-large" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-whatsapp" aria-hidden="true"></i>
                    Chat WhatsApp Sekarang
                </a>
            </div>
            <div class="cta-photo reveal-on-scroll" data-reveal="right" data-delay="160">
                <div class="cta-photo-frame">
                    <img src="<?php echo htmlspecialchars($cta_photo); ?>" alt="Pelayanan GBI Salemba">
                </div>
            </div>
        </div>
    </div>
</section>

</main>

<script src="/gbisalemba/assets/js/slider.js"></script>
<script src="/gbisalemba/assets/js/whats-new-slider.js"></script>
<script src="/gbisalemba/assets/js/home-reveal.js"></script>

<?php include 'includes/footer.php'; ?>
