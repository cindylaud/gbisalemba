<?php
require_once __DIR__ . '/config/database.php';
include __DIR__ . '/includes/header.php';

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

if (!function_exists('gbi_text_excerpt')) {
    function gbi_text_excerpt($text, $limit = 140)
    {
        $plain = trim(strip_tags((string) $text));
        if ($plain === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($plain) > $limit ? mb_substr($plain, 0, $limit) . '...' : $plain;
        }

        return strlen($plain) > $limit ? substr($plain, 0, $limit) . '...' : $plain;
    }
}

if (!function_exists('gbi_table_has_column')) {
    function gbi_table_has_column($conn, $table, $column)
    {
        $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
        $columnSafe = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);

        if ($tableSafe === '' || $columnSafe === '') {
            return false;
        }

        $sql = "SHOW COLUMNS FROM `" . $tableSafe . "` LIKE '" . $conn->real_escape_string($columnSafe) . "'";
        $check = $conn->query($sql);

        return $check && $check->num_rows > 0;
    }
}

if (!function_exists('gbi_extract_times_from_jam')) {
    function gbi_extract_times_from_jam($jamText)
    {
        $times = [];
        $parts = preg_split('/[,;\n\r]+/', (string) $jamText);
        if (!is_array($parts)) {
            return $times;
        }

        foreach ($parts as $part) {
            $time = trim((string) $part);
            if ($time === '') {
                continue;
            }

            if (!in_array($time, $times, true)) {
                $times[] = $time;
            }
        }

        return $times;
    }
}

$ibadah_minggu_times = ['08:00 WIB', '10:30 WIB', '17:00 WIB'];
$jadwal_stmt = $conn->prepare("SELECT nama_ibadah, jam, is_active FROM jadwal_ibadah ORDER BY COALESCE(urutan, id) ASC, id ASC");
if (!$jadwal_stmt) {
    $jadwal_stmt = $conn->prepare("SELECT nama_ibadah, jam, is_active FROM jadwal_ibadah ORDER BY id ASC");
}

if ($jadwal_stmt) {
    $jadwal_stmt->execute();
    $jadwal_result = $jadwal_stmt->get_result();
    $dynamic_times = [];

    while ($jadwal_row = $jadwal_result->fetch_assoc()) {
        $isActive = (int) ($jadwal_row['is_active'] ?? 0) === 1;
        if (!$isActive) {
            continue;
        }

        $namaIbadah = strtolower(trim((string) ($jadwal_row['nama_ibadah'] ?? '')));
        if ($namaIbadah !== '' && strpos($namaIbadah, 'raya') === false && strpos($namaIbadah, 'minggu') === false) {
            continue;
        }

        $rowTimes = gbi_extract_times_from_jam($jadwal_row['jam'] ?? '');
        foreach ($rowTimes as $rowTime) {
            if (!in_array($rowTime, $dynamic_times, true)) {
                $dynamic_times[] = $rowTime;
            }
        }
    }

    if (!empty($dynamic_times)) {
        $ibadah_minggu_times = $dynamic_times;
    }

    $jadwal_stmt->close();
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
    'uploads/cta',
    'assets/images/gembala'
], $ibadah_photo);
?>
<main class="main-content home-variant-formal">

<!-- 1. HERO / SLIDER SECTION -->
<section class="hero-slider" id="sliderSection">
    <canvas id="fireworksCanvas"></canvas>
    <?php
    $slider_has_zoom = gbi_table_has_column($conn, 'slider', 'image_zoom');
    $slider_zoom_select = $slider_has_zoom ? 'COALESCE(image_zoom, 100) AS image_zoom' : '100 AS image_zoom';
    $slider_has_pos_x = gbi_table_has_column($conn, 'slider', 'image_pos_x');
    $slider_has_pos_y = gbi_table_has_column($conn, 'slider', 'image_pos_y');
    $slider_pos_x_select = $slider_has_pos_x ? 'COALESCE(image_pos_x, 50) AS image_pos_x' : '50 AS image_pos_x';
    $slider_pos_y_select = $slider_has_pos_y ? 'COALESCE(image_pos_y, 50) AS image_pos_y' : '50 AS image_pos_y';

    $q = "SELECT image, urutan, {$slider_zoom_select}, {$slider_pos_x_select}, {$slider_pos_y_select}
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
            $zoom = (int) ($row['image_zoom'] ?? 100);
            if ($zoom < 50) {
                $zoom = 50;
            } elseif ($zoom > 150) {
                $zoom = 150;
            }

            $images[] = [
                'image' => (string) $row['image'],
                'zoom' => $zoom,
                'pos_x' => max(0, min(100, (int) ($row['image_pos_x'] ?? 50))),
                'pos_y' => max(0, min(100, (int) ($row['image_pos_y'] ?? 50))),
            ];
    }

    if (empty($images)) {
            ?>
            <div class="hero-slide active">
                <div class="hero-bg" style="background: linear-gradient(135deg,#102C57 0%,#DAC0A3 100%);"></div>
                <div class="hero-overlay"></div>
            </div>
            <?php
    } else {
            foreach ($images as $i => $slide) {
                $bgScale = number_format(((int) $slide['zoom']) / 100, 2, '.', '');
                ?>
                <div class="hero-slide <?php echo $i === 0 ? 'active' : ''; ?>">
                    <div class="hero-bg" style="background-image:url('uploads/slider/<?php echo htmlspecialchars($slide['image']); ?>'); background-position: <?php echo (int) $slide['pos_x']; ?>% <?php echo (int) $slide['pos_y']; ?>% !important; --hero-bg-scale:<?php echo htmlspecialchars($bgScale); ?>;"></div>
                    <div class="hero-overlay"></div>
                </div>
            <?php } ?>

            <?php if (count($images) > 1): ?>
                <button class="hero-btn hero-btn-prev" type="button" onclick="sliderPrev()" aria-label="Slide sebelumnya">&#8249;</button>
                <button class="hero-btn hero-btn-next" type="button" onclick="sliderNext()" aria-label="Slide berikutnya">&#8250;</button>
                <div class="hero-dots">
                    <?php for ($i = 0; $i < count($images); $i++): ?>
                        <span class="hero-dot <?php echo $i === 0 ? 'active' : ''; ?>" onclick="sliderGoto(<?php echo $i; ?>)"></span>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
    <?php } ?>

    <div class="hero-countdown-card" id="heroCountdown" aria-live="polite" data-server-now="<?= (int) round(microtime(true) * 1000) ?>" data-live-window="120">
        <p class="hero-countdown-kicker">Selamat Datang</p>
        <h1 class="hero-countdown-title">GBI SALEMBA</h1>

        <div class="hero-countdown-box">
            <p class="hero-countdown-label" id="heroCountdownLabel">Ibadah Berikutnya Dimulai Dalam</p>
            <div class="hero-countdown-values" id="heroCountdownValues">
                <div class="hero-countdown-item"><span id="heroCountdownDays">00</span><small>Hari</small></div>
                <div class="hero-countdown-item"><span id="heroCountdownHours">00</span><small>Jam</small></div>
                <div class="hero-countdown-item"><span id="heroCountdownMinutes">00</span><small>Menit</small></div>
                <div class="hero-countdown-item"><span id="heroCountdownSeconds">00</span><small>Detik</small></div>
            </div>
        </div>

        <p class="hero-countdown-caption" id="heroServiceLabel">Ibadah Raya 08:00 WIB</p>
        <div class="hero-live-actions" id="heroLiveActions">
            <a id="heroLivePrimary" href="https://www.youtube.com/@gbisalemba" target="_blank" rel="noopener noreferrer" class="hero-live-btn hero-live-btn-primary">Ke YouTube</a>
            <a id="heroLiveSecondary" href="https://maps.app.goo.gl/6duXhZBcrC26enUPA" target="_blank" rel="noopener noreferrer" class="hero-live-btn hero-live-btn-secondary">Lokasi Gereja</a>
        </div>
    </div>
</section>

<!-- 2. COMING SOON SECTION -->
<section class="whats-new">
    <div class="container-large">
        <h2 class="section-title-big">COMING SOON</h2>

        <div class="whats-new-slider" id="whatsNewSlider" tabindex="0" aria-label="Slider Coming Soon">
            <div class="whats-new-decor" aria-hidden="true"></div>

            <?php
            $coming_has_zoom = gbi_table_has_column($conn, 'coming_soon', 'image_zoom');
            $coming_has_title = gbi_table_has_column($conn, 'coming_soon', 'event_title');
            $coming_has_datetime = gbi_table_has_column($conn, 'coming_soon', 'event_datetime');
            $coming_has_location = gbi_table_has_column($conn, 'coming_soon', 'event_location');
            $coming_has_registration = gbi_table_has_column($conn, 'coming_soon', 'event_registration');
            $coming_has_description = gbi_table_has_column($conn, 'coming_soon', 'event_description');
            $coming_zoom_select = $coming_has_zoom ? 'COALESCE(image_zoom, 100) AS image_zoom' : '100 AS image_zoom';
            $coming_title_select = $coming_has_title ? "COALESCE(event_title, '') AS event_title" : "'' AS event_title";
            $coming_datetime_select = $coming_has_datetime ? "COALESCE(event_datetime, '') AS event_datetime" : "'' AS event_datetime";
            $coming_location_select = $coming_has_location ? "COALESCE(event_location, '') AS event_location" : "'' AS event_location";
            $coming_registration_select = $coming_has_registration ? "COALESCE(event_registration, '') AS event_registration" : "'' AS event_registration";
            $coming_description_select = $coming_has_description ? "COALESCE(event_description, '') AS event_description" : "'' AS event_description";
            $q_wn = "SELECT image, {$coming_zoom_select}, {$coming_title_select}, {$coming_datetime_select}, {$coming_location_select}, {$coming_registration_select}, {$coming_description_select} FROM coming_soon WHERE is_active = 1 ORDER BY urutan ASC LIMIT 5";
            $r_wn = $conn->query($q_wn);

            $wn_images = [];
            if ($r_wn) {
                    while ($row_wn = $r_wn->fetch_assoc()) {
                            $zoom = (int) ($row_wn['image_zoom'] ?? 100);
                            if ($zoom < 50) {
                                $zoom = 50;
                            } elseif ($zoom > 150) {
                                $zoom = 150;
                            }

                            $wn_images[] = [
                                'image' => (string) $row_wn['image'],
                                'title' => (string) ($row_wn['event_title'] ?? ''),
                                'datetime' => (string) ($row_wn['event_datetime'] ?? ''),
                                'location' => (string) ($row_wn['event_location'] ?? ''),
                                'registration' => (string) ($row_wn['event_registration'] ?? ''),
                                'description' => (string) ($row_wn['event_description'] ?? ''),
                                'zoom' => $zoom,
                            ];
                    }
            }

            if (!empty($wn_images)): ?>
                <div class="whats-new-track" id="whatsNewTrack">
                    <?php foreach ($wn_images as $i => $item): ?>
                        <?php $imgScale = number_format(((int) $item['zoom']) / 100, 2, '.', ''); ?>
                        <div class="whats-new-card" role="button" tabindex="0"
                            data-event-image="uploads/whatsnew/<?php echo htmlspecialchars($item['image'], ENT_QUOTES); ?>"
                            data-event-title="<?php echo htmlspecialchars($item['title'] !== '' ? $item['title'] : 'Coming Soon Event', ENT_QUOTES); ?>"
                            data-event-datetime="<?php echo htmlspecialchars($item['datetime'], ENT_QUOTES); ?>"
                            data-event-location="<?php echo htmlspecialchars($item['location'], ENT_QUOTES); ?>"
                            data-event-registration="<?php echo htmlspecialchars($item['registration'], ENT_QUOTES); ?>"
                            data-event-description="<?php echo htmlspecialchars($item['description'], ENT_QUOTES); ?>">
                            <img src="uploads/whatsnew/<?php echo htmlspecialchars($item['image']); ?>" alt="Coming Soon <?php echo $i + 1; ?>" style="--coming-zoom-scale:<?php echo htmlspecialchars($imgScale); ?>;">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($wn_images) > 1): ?>
                    <button class="whats-new-nav prev" type="button" id="whatsNewPrev" aria-label="Foto sebelumnya">&#8249;</button>
                    <button class="whats-new-nav next" type="button" id="whatsNewNext" aria-label="Foto berikutnya">&#8250;</button>
                    <div class="whats-new-dots" id="whatsNewDots" aria-label="Navigasi foto"></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="whats-new-empty">
                    <div class="whats-new-empty-content"><span>Belum ada foto terbaru</span></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="whats-new-modal" id="comingSoonModal" aria-hidden="true">
    <div class="whats-new-modal-backdrop" data-modal-close></div>
    <div class="whats-new-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="comingSoonModalTitle">
        <button type="button" class="whats-new-modal-close" data-modal-close aria-label="Tutup detail coming soon">&times;</button>
        <div class="whats-new-modal-media">
            <img id="comingSoonModalImage" src="" alt="Detail Coming Soon">
        </div>
        <div class="whats-new-modal-body">
            <p class="whats-new-modal-kicker">Coming Soon Event</p>
            <h3 id="comingSoonModalTitle">Coming Soon Event</h3>
            <div class="whats-new-modal-grid">
                <section class="whats-new-modal-card">
                    <span class="whats-new-modal-label">Hari/Tanggal</span>
                    <p id="comingSoonModalDatetime">Belum diisi</p>
                </section>
                <section class="whats-new-modal-card">
                    <span class="whats-new-modal-label">Lokasi</span>
                    <p id="comingSoonModalLocation">Belum diisi</p>
                </section>
                <section class="whats-new-modal-card">
                    <span class="whats-new-modal-label">Registrasi</span>
                    <p id="comingSoonModalRegistration">Belum diisi</p>
                </section>
                <section class="whats-new-modal-card whats-new-modal-card-wide">
                    <span class="whats-new-modal-label">Deskripsi</span>
                    <p id="comingSoonModalDescription">Belum diisi</p>
                </section>
            </div>
        </div>
    </div>
</div>

<!-- 3. IBADAH MINGGU SECTION -->
<section class="ibadah-minggu-section reveal-on-scroll" data-reveal="section" data-delay="0">
    <div class="ibadah-blob ibadah-blob-1"></div>
    <div class="ibadah-blob ibadah-blob-2"></div>
    <div class="ibadah-section-divider" aria-hidden="true">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="container-large">
        <div class="ibadah-editorial-card">
            <div class="ibadah-showcase">
                <figure class="ibadah-showcase-photo reveal-on-scroll" data-reveal="right" data-delay="70">
                    <div class="ibadah-showcase-media">
                        <iframe
                            class="ibadah-showcase-video"
                            src="https://www.youtube.com/embed/D2JMjs73K_g?rel=0"
                            loading="lazy"
                            title="Video Ibadah Minggu GBI Salemba"
                            allow="autoplay; encrypted-media; picture-in-picture"
                            allowfullscreen>
                        </iframe>
                        <div class="ibadah-showcase-privacy-mask" aria-hidden="true"></div>
                        <div class="ibadah-showcase-aesthetic-controls" aria-hidden="true">
                            <span class="dot"></span>
                            <span class="dot"></span>
                        </div>
                    </div>
                </figure>
                <article class="ibadah-showcase-panel reveal-on-scroll" data-reveal="left" data-delay="140">
                    <h2 class="ibadah-showcase-title">Ibadah Minggu</h2>
                    <div class="ibadah-showcase-times">
                        <?php foreach ($ibadah_minggu_times as $ibadah_time): ?>
                            <span><?php echo htmlspecialchars($ibadah_time); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <p class="ibadah-showcase-note">Disertai ibadah Starskids dan disiarkan secara online.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<!-- 4. PROFIL GEMBALA SECTION -->
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
                    <img src="<?php echo htmlspecialchars($gembala_photo); ?>" alt="Bapak dan Ibu Gembala GBI Salemba" class="gembala-img">
                </div>
                <div class="gembala-photo-accent"></div>
            </div>
            <div class="gembala-info reveal-on-scroll" data-reveal="right" data-delay="160">
                <h3 class="gembala-name gembala-name-only">
                    <span class="gembala-name-line"><?php echo htmlspecialchars($gembala_data['nama_line_1']); ?></span>
                    <span class="gembala-separator" aria-hidden="true"><span></span><em>dan</em><span></span></span>
                    <span class="gembala-name-line"><?php echo htmlspecialchars($gembala_data['nama_line_2']); ?></span>
                </h3>
            </div>
        </div>
    </div>

</section>

<!-- 5. CTA HUBUNGI KAMI SECTION -->
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
                    <img src="assets/images/umum/hubungi-kami.JPG" alt="Pelayanan GBI Salemba">
                </div>
            </div>
        </div>
    </div>
</section>

</main>

<script src="assets/js/slider.js"></script>
<script src="assets/js/whats-new-slider.js"></script>
<script src="assets/js/home-reveal.js"></script>
<!-- Canvas-confetti must load before countdown and fireworks -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<!-- Fireworks module: provides window.launchFireworks() -->
<script src="assets/js/fireworks.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/assets/js/fireworks.js')); ?>"></script>
<!-- Countdown with live service detection and fireworks integration -->
<script src="assets/js/hero-countdown.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/assets/js/hero-countdown.js')); ?>"></script>

<!-- Debug: Verify launchFireworks is available globally -->
<script>
// Wait a tick to ensure all scripts loaded
setTimeout(function() {
  if (typeof window.launchFireworks === 'function') {
    console.log('✓ launchFireworks is available globally');
    console.log('✓ Call it with: launchFireworks()');
  } else {
    console.error('✗ launchFireworks is NOT available');
    console.log('Available on window:', Object.keys(window).filter(k => k.includes('fire') || k.includes('Fire')));
  }
}, 100);
</script>

<script>

(function() {
    const videoIframe = document.querySelector('.ibadah-showcase-video');
    if (!videoIframe || videoIframe.getAttribute('src')) return;

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const src = entry.target.getAttribute('data-src');
                if (src && !entry.target.getAttribute('src')) {
                    entry.target.setAttribute('src', src);
                    observer.unobserve(entry.target);
                }
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '50px'
    });

    observer.observe(videoIframe);
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>


