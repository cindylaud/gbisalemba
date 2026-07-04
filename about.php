<?php
require_once __DIR__ . '/config/database.php';
include __DIR__ . '/includes/header.php';

// BASE PATH
$about_base = 'assets/images/about/';
$gembala_base = 'assets/images/gembala/';

// HERO (FIXED)
$about_hero_image = $about_base . 'about-hero.jpg';

$about_images = [
    $about_base . 'about-1.JPG',
    $about_base . 'about-2.JPG',
    $about_base . 'about-3.JPG',
    $about_base . 'about-4.JPG'
];

// FOTO GEMBALA
$about_photo = [
    $gembala_base . 'wakilgembala1.jpeg',
    $gembala_base . 'gembala.jpeg',
    $gembala_base . 'wakilgembala2.jpeg'
];

// Fallback kalau file hilang
foreach ($about_images as &$img) {
    if (!file_exists(__DIR__ . '/' . $img)) {
        $img = $about_base . 'default.jpg';
    }
}
unset($img);

if (!file_exists(__DIR__ . '/' . $about_hero_image)) {
    $about_hero_image = $about_base . 'default.jpg';
}
?>

<main class="main-content about-gbi-wrap">
    <section class="about-gbi-shell">
        <section class="about-gbi-hero" style="--about-bg:url('<?php echo htmlspecialchars($about_hero_image); ?>');">
            <div class="about-gbi-hero-inner">
                <figure class="about-gbi-hero-media">
                    <img src="<?php echo htmlspecialchars($about_hero_image); ?>" alt="Perjalanan pelayanan GBI Salemba">
                </figure>
                <article class="about-gbi-hero-card">
                    <h1>Tentang Kami</h1>
                    <p>"Iman, ketaatan, dan kasih Kristus yang membawa gereja ini terus bertumbuh dan berkembang."</p>
                </article>
            </div>
            <div class="about-gbi-wave" aria-hidden="true"></div>
        </section>

        <section class="about-gbi-intro">
            <h2>Bukan sekadar perjalanan gereja, tetapi kesaksian hidup tentang panggilan Tuhan yang dikerjakan dalam kesetiaan.</h2>
            <p class="about-gbi-identity">Gereja Bethel Indonesia (GBI) Salemba merupakan gereja Kristen di bawah naungan GBI Jl. Jend. Gatot Subroto, <span class="about-nowrap">Jakarta Rayon 1H</span> dengan gembala sidang Pdt. DR. Ir. Niko Njotorahardjo dan gembala cabang Pdt. David Natanael, M.Th.</p>
        </section>

        <section class="about-gbi-gallery" aria-label="Dokumentasi Kegiatan">
            <div class="about-gbi-gallery-grid">
                <?php foreach ($about_images as $index => $gallery_image): ?>
                    <figure class="about-gbi-gallery-card" data-order="<?php echo (int) ($index + 1); ?>">
                        <img src="<?php echo htmlspecialchars($gallery_image); ?>" alt="Dokumentasi pelayanan GBI Salemba <?php echo (int) ($index + 1); ?>">
                    </figure>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="about-gbi-story">
            <h3>Sejarah GBI Salemba</h3>
            <article class="about-gbi-story-card">
                <p>Awal mula lahirnya GBI Salemba diawali dari seorang pengerja yang memiliki kerendahan hati dan belajar untuk taat akan panggilan Tuhan. Dalam sesi berdiskusi membicarakan pelayanan antara Pdt. David Natanael dengan seorang hamba Tuhan pada waktu itu, tiba-tiba ada suatu pertanyaan dari seorang hamba Tuhan kepada Pdt. David Natanael yaitu</p>

                <blockquote>"Kenapa tidak membuka gereja? jika mau, nanti saya bantukan mencari tempat ibadah"</blockquote>

                <p>Namun, saat itu Pdt. David Natanael masih menolak untuk membuka gereja baru dan tetap ingin mendukung pelayanan Pdt. John Silitonga di GBI Tebet.</p>

                <p>Kemudian setelah beberapa waktu, Pdt. David Natanael berkumpul dengan rekan-rekan pelayanan dan tiba-tiba tercetus mengenai pelayanan baru, yaitu membuka gereja baru. Di tengah diskusi yang ada, Pdt. David Natanael teringat akan suatu pertanyaan dari seorang hamba Tuhan yang menawarkan kepada dirinya. Sejak saat itu, muncul kerinduan dalam hati seorang Pdt. David Natanael untuk membuka gereja baru, namun masih disimpan dalam hati.</p>

                <p>Kerinduan hati tersebut dibawa dalam doa pergumulan untuk meminta konfirmasi dari Tuhan terkait membuka gereja baru. Konfirmasi yang diharapkan adalah mendapatkan orang-orang yang sehati untuk pelayanan baru, tempat ibadah, dan dana. Hingga suatu ketika, seorang hamba Tuhan yang sama menanyakan kembali terkait niatnya untuk membuka gereja baru dan memberikan informasi bahwa dapat membuka ibadah sore di Lembaga Alkitab Indonesia, Salemba.</p>

                <p>Dalam pergumulan doa yang sama untuk meminta konfirmasi dari Tuhan terkait tanda untuk membuka gereja baru. Tuhan memberikan orang-orang yang sehati untuk mendukung pelayanan baru dan tempat ibadah sudah diberikan Tuhan, tetapi ada satu keraguan yaitu dana dalam membuka gereja. Banyak pertimbangan dan pertanyaan terkait dana yang dibutuhkan dan memperoleh dana untuk membuka gereja, sehingga pada saat itu mengurungkan niatnya membuka gereja baru karena dana. Namun, kerinduan hati untuk membuka pelayanan baru masih tersimpan dalam hatinya.</p>

                <p>Beberapa waktu kemudian, Bapak gembala dan keluarga pergi ke Bali untuk acara keluarga dan menyempatkan diri untuk menghadiri ibadah di GBI Lembah Pujian. Dalam proses ibadah yang dituntun oleh Roh Kudus dan dihadiri oleh seorang hamba Tuhan dari luar negeri pada saat itu, tiba-tiba hamba Tuhan tersebut bernubuat dalam nama Tuhan Yesus dan mengatakan:</p>

                <blockquote>"anak-anak muda dengar saya bernubuat, kalian akan dipakai untuk membuka gereja baru"</blockquote>

                <p>kalimat tersebut terasa mengagetkan Pdt. David Natanael dan membuatnya terkejut setelah beberapa konfirmasi yang diterima sebelumnya dalam membuka gereja baru. Kemudian, hamba Tuhan dari luar negeri meminta untuk anak-anak muda yang mau membuka gereja baru untuk maju ke depan dan didoakan. Pdt. David Natanael dengan ketaatan dan kerendahan hati maju ke depan dan kemudian didoakan dalam ibadah tersebut.</p>

                <p>Setelah pulang dari Bali, rasa kebimbangan dan keraguan masih tidak hilang, sehingga Pdt. David Natanael meminta satu konfirmasi kembali dari Tuhan yaitu mendapatkan persetujuan dari pemimpin yaitu Pdt. John Silitonga (Gembala GBI Tebet). Setelah menghadap kepada Pdt. John Silitonga dan beliau melihat rekan-rekan pelayanan yang sehati memiliki kerinduan yang sama untuk membuka pelayanan baru, maka Pdt. John Silitonga mendoakan dan merestui untuk melangkah membuka gereja.</p>

                <p>Dalam proses persiapan membuka pelayanan baru dengan rekan-rekan pelayanan, Tuhan menolong dengan memberikan dana dari orang yang memberikan persembahan senilai Rp. 2.000.000 dan dari uang tersebut bergerak melangkah membuka ibadah perdana POS PI di Lembaga Alkitab Indonesia pukul 17:00. Ibadah perdana diadakan pada tahun 2004 dan dihadiri sekitar 30-40 orang. Sejak saat itu ibadah di GBI Salemba berjalan setiap minggunya dan rata-rata jemaat yang hadir setiap minggunya adalah 20-30 orang, sehingga beberapa rekan pelayanan mulai lelah dan mundur, tetapi Pdt. David Natanael tetap teguh dan setia percaya akan panggilan Tuhan untuk membuka pelayanan.</p>

                <p>Pada tahun berikutnya, ibadah berpindah dari gedung LAI ke gedung Sate Khas Senayan Salemba dan mulai mengadakan ibadah di pagi hari. Puji Tuhan! tahun 2006, GBI Salemba mendapatkan kesempatan untuk berpindah ke gedung Plaza Kenari Mas dan mulai menyelenggarakan ibadah sebanyak dua kali setiap minggunya dan Tuhan terus menambahkan jiwa-jiwa dalam pelayanan ini.</p>

                <p>Tahun demi tahun dilalui hingga saat ini GBI Salemba berada merupakan perjalanan panjang yang menjadi saksi nyata dari ketaatan iman seorang hamba Tuhan yang mau berkata "Ya" kepada panggilan-Nya, melahirkan sebuah gereja yang terus tumbuh dan menjadi berkat bagi banyak jiwa. Iman, ketaatan, dan kasih Kristus yang membawa gereja ini terus bertumbuh dan berkembang serta memberikan kekuatan untuk terus melayani Tuhan dalam setiap musim kehidupan.</p>

                <p>Hingga pada tahun 2015, Tuhan mempercayakan Pdt. David Natanael untuk menggembalakan jemaat Tuhan di GBI Cibubur Times Square. Keluarga baru dalam Kristus yang Tuhan percayakan menjadi bagian dari GBI Salemba merupakan bentuk nyata bahwa kesetiaan dalam Kristus tidak pernah sia-sia dan menjadikan pribadi yang terus berbuah hingga tiba kesudahanNya.</p>

                <p class="about-gbi-closing-quote">"Saya mengucap syukur kepada Tuhan atas penyertaan-Nya sepanjang perjalanan gereja ini. Doa saya, agar jemaat semakin bertumbuh sesuai dengan rencana Tuhan." -Pdt. David Natanael</p>
            </article>
        </section>

        <!-- LEADERS SECTION — zigzag layout dengan hiasan rohani -->
        <section class="about-gbi-leaders">

            <!-- Judul dengan hiasan salib -->
            <div class="ldr-title-wrap">
                <!-- Salib kiri -->
                <svg class="ldr-cross-deco ldr-cross-left" viewBox="0 0 40 40" aria-hidden="true">
                    <rect x="17" y="2" width="6" height="36" rx="3" fill="#102c57" opacity="0.18"/>
                    <rect x="2" y="14" width="36" height="6" rx="3" fill="#102c57" opacity="0.18"/>
                </svg>
                <h3 class="about-gbi-script-title">Penggembalaan</h3>
                <!-- Salib kanan -->
                <svg class="ldr-cross-deco ldr-cross-right" viewBox="0 0 40 40" aria-hidden="true">
                    <rect x="17" y="2" width="6" height="36" rx="3" fill="#102c57" opacity="0.18"/>
                    <rect x="2" y="14" width="36" height="6" rx="3" fill="#102c57" opacity="0.18"/>
                </svg>
            </div>

            <div class="ldr-zigzag-wrap">

                <!-- Garis timeline tengah -->
                <div class="ldr-timeline-line" aria-hidden="true"></div>

                <!-- ROW 1: Gembala — foto KIRI, teks KANAN -->
                <div class="ldr-row ldr-row-left ldr-row-gembala">
                    <!-- Hiasan daun zaitun pojok foto -->
                    <svg class="ldr-olive ldr-olive-tl" viewBox="0 0 50 60" aria-hidden="true">
                        <path d="M25 55 C25 55, 8 40, 10 22 C12 8, 25 5, 25 5 C25 5, 38 8, 40 22 C42 40, 25 55, 25 55Z" fill="none" stroke="#102c57" stroke-width="1.2" opacity="0.18"/>
                        <line x1="25" y1="55" x2="25" y2="5" stroke="#102c57" stroke-width="1" opacity="0.14" stroke-dasharray="3,3"/>
                        <ellipse cx="17" cy="25" rx="5" ry="8" fill="#102c57" opacity="0.10" transform="rotate(-20 17 25)"/>
                        <ellipse cx="33" cy="30" rx="5" ry="8" fill="#102c57" opacity="0.10" transform="rotate(20 33 30)"/>
                    </svg>

                    <div class="ldr-photo-col">
                        <div class="ldr-photo-frame">
                            <img src="<?php echo htmlspecialchars($about_photo[1]); ?>" alt="Gembala GBI Salemba" class="ldr-photo">
                            <!-- Ornamen sudut foto -->
                            <span class="ldr-corner ldr-corner-tl" aria-hidden="true"></span>
                            <span class="ldr-corner ldr-corner-br" aria-hidden="true"></span>
                        </div>
                    </div>

                    <div class="ldr-connector-wrap" aria-hidden="true">
                        <div class="ldr-connector-line"></div>
                        <!-- Bintang Kejora / Bintang Daud kecil di titik tengah -->
                        <svg class="ldr-star-node" viewBox="0 0 24 24">
                            <polygon points="12,2 14.9,9.3 22.5,9.3 16.3,14 18.7,21.5 12,17 5.3,21.5 7.7,14 1.5,9.3 9.1,9.3" fill="#102c57" opacity="0.30"/>
                        </svg>
                    </div>

                    <div class="ldr-info-col">
                        <div class="ldr-card">
                            <span class="ldr-badge">Gembala</span>
                            <h4 class="ldr-name">Ps. David Natanael &amp; Ps. Rita Emianata</h4>
                        </div>
                    </div>
                </div>

                <!-- Hiasan salib kecil di antara baris -->
                <div class="ldr-divider-deco" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="20" height="20">
                        <rect x="10" y="2" width="4" height="20" rx="2" fill="#102c57" opacity="0.20"/>
                        <rect x="2" y="8" width="20" height="4" rx="2" fill="#102c57" opacity="0.20"/>
                    </svg>
                </div>

                <!-- ROW 2: Wakil 1 — foto KANAN, teks KIRI -->
                <div class="ldr-row ldr-row-right ldr-row-wakil1">
                    <!-- Hiasan daun zaitun pojok kanan -->
                    <svg class="ldr-olive ldr-olive-tr" viewBox="0 0 50 60" aria-hidden="true">
                        <path d="M25 55 C25 55, 8 40, 10 22 C12 8, 25 5, 25 5 C25 5, 38 8, 40 22 C42 40, 25 55, 25 55Z" fill="none" stroke="#102c57" stroke-width="1.2" opacity="0.18"/>
                        <line x1="25" y1="55" x2="25" y2="5" stroke="#102c57" stroke-width="1" opacity="0.14" stroke-dasharray="3,3"/>
                        <ellipse cx="17" cy="25" rx="5" ry="8" fill="#102c57" opacity="0.10" transform="rotate(-20 17 25)"/>
                        <ellipse cx="33" cy="30" rx="5" ry="8" fill="#102c57" opacity="0.10" transform="rotate(20 33 30)"/>
                    </svg>

                    <div class="ldr-info-col">
                        <div class="ldr-card ldr-card-right">
                            <span class="ldr-badge">Wakil Gembala</span>
                            <h4 class="ldr-name ldr-name-split">
                                <span class="ldr-name-line">Ps. Cahyadi &amp;</span>
                                <span class="ldr-name-line">Herna JT</span>
                            </h4>
                        </div>
                    </div>

                    <div class="ldr-connector-wrap" aria-hidden="true">
                        <div class="ldr-connector-line"></div>
                        <svg class="ldr-star-node" viewBox="0 0 24 24">
                            <polygon points="12,2 14.9,9.3 22.5,9.3 16.3,14 18.7,21.5 12,17 5.3,21.5 7.7,14 1.5,9.3 9.1,9.3" fill="#102c57" opacity="0.30"/>
                        </svg>
                    </div>

                    <div class="ldr-photo-col">
                        <div class="ldr-photo-frame">
                            <img src="<?php echo htmlspecialchars($about_photo[0]); ?>" alt="Wakil Gembala GBI Salemba" class="ldr-photo">
                            <span class="ldr-corner ldr-corner-tl" aria-hidden="true"></span>
                            <span class="ldr-corner ldr-corner-br" aria-hidden="true"></span>
                        </div>
                    </div>
                </div>

                <!-- Hiasan salib kecil di antara baris -->
                <div class="ldr-divider-deco" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="20" height="20">
                        <rect x="10" y="2" width="4" height="20" rx="2" fill="#102c57" opacity="0.20"/>
                        <rect x="2" y="8" width="20" height="4" rx="2" fill="#102c57" opacity="0.20"/>
                    </svg>
                </div>

                <!-- ROW 3: Wakil 2 — foto KIRI, teks KANAN -->
                <div class="ldr-row ldr-row-left ldr-row-wakil2">
                    <svg class="ldr-olive ldr-olive-bl" viewBox="0 0 50 60" aria-hidden="true">
                        <path d="M25 55 C25 55, 8 40, 10 22 C12 8, 25 5, 25 5 C25 5, 38 8, 40 22 C42 40, 25 55, 25 55Z" fill="none" stroke="#102c57" stroke-width="1.2" opacity="0.18"/>
                        <line x1="25" y1="55" x2="25" y2="5" stroke="#102c57" stroke-width="1" opacity="0.14" stroke-dasharray="3,3"/>
                        <ellipse cx="17" cy="25" rx="5" ry="8" fill="#102c57" opacity="0.10" transform="rotate(-20 17 25)"/>
                        <ellipse cx="33" cy="30" rx="5" ry="8" fill="#102c57" opacity="0.10" transform="rotate(20 33 30)"/>
                    </svg>

                    <div class="ldr-photo-col">
                        <div class="ldr-photo-frame">
                            <img src="<?php echo htmlspecialchars($about_photo[2]); ?>" alt="Wakil Gembala GBI Salemba" class="ldr-photo">
                            <span class="ldr-corner ldr-corner-tl" aria-hidden="true"></span>
                            <span class="ldr-corner ldr-corner-br" aria-hidden="true"></span>
                        </div>
                    </div>

                    <div class="ldr-connector-wrap" aria-hidden="true">
                        <div class="ldr-connector-line"></div>
                        <svg class="ldr-star-node" viewBox="0 0 24 24">
                            <polygon points="12,2 14.9,9.3 22.5,9.3 16.3,14 18.7,21.5 12,17 5.3,21.5 7.7,14 1.5,9.3 9.1,9.3" fill="#102c57" opacity="0.30"/>
                        </svg>
                    </div>

                    <div class="ldr-info-col">
                        <div class="ldr-card">
                            <span class="ldr-badge">Wakil Gembala</span>
                            <h4 class="ldr-name">Ps. Rajendra Aling &amp; Anggi Elvira Natalia</h4>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </section>
</main>

<style>
/* =============================================
   ABOUT GBI — STYLESHEET
   ============================================= */

.about-gbi-wrap {
    --gbi-navy: #102c57;
    --gbi-green: #1a3a63;
    --gbi-green-dark: #0f2847;
    --gbi-cream: #f4f8fd;
    --gbi-card: #f8fbff;
    --gbi-intro-bg: #eef3fa;
    background: var(--gbi-intro-bg);
    position: relative;
    isolation: isolate;
    overflow: hidden;
    margin: 0 !important;
    padding: 0 !important;
    color: var(--gbi-navy);
    font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.about-gbi-wrap::before,
.about-gbi-wrap::after { display: none; }

.about-gbi-shell {
    width: 100%;
    margin: 0;
    padding: 0 !important;
    border-radius: 0;
    overflow: visible;
    background: transparent;
    box-shadow: none;
    border: none;
    position: relative;
    z-index: 1;
}

/* ── HERO ── */
.about-gbi-hero {
    position: relative;
    padding: 18px 36px 86px;
    background-image: linear-gradient(120deg, rgba(16,44,87,0.82) 0%, rgba(16,44,87,0.75) 44%, rgba(16,44,87,0.78) 100%), var(--about-bg);
    background-size: cover;
    background-position: center;
    overflow: hidden;
}

.about-gbi-hero-inner {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    align-items: stretch;
    max-width: 850px;
    margin: 0 auto;
}

.about-gbi-hero-media {
    margin: 0;
    border-radius: 20px 0 0 20px;
    overflow: hidden;
    min-height: 330px;
}

.about-gbi-hero-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.about-gbi-hero-card {
    background: var(--gbi-cream);
    border-radius: 0 20px 20px 0;
    padding: 28px 24px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    border-left: 6px solid var(--gbi-green);
}

.about-gbi-hero-card h1 {
    margin: 0 0 10px;
    font-size: clamp(30px, 4vw, 48px);
    line-height: 0.96;
    letter-spacing: -0.02em;
    color: var(--gbi-navy);
    font-weight: 800;
}

.about-gbi-hero-card h1::after {
    content: '';
    display: block;
    width: 36px;
    height: 3px;
    background: var(--gbi-navy);
    margin-top: 8px;
}

.about-gbi-hero-card p {
    margin: 12px 0 0;
    font-size: 16px;
    line-height: 1.7;
    color: #24486d;
    position: relative;
    padding: 0 24px;
}

.about-gbi-hero-card p::before,
.about-gbi-hero-card p::after {
    content: '"';
    position: absolute;
    font-size: 48px;
    color: var(--gbi-green);
    opacity: 0.6;
    line-height: 0.8;
}
.about-gbi-hero-card p::before { left: 0; top: -8px; }
.about-gbi-hero-card p::after  { right: 0; bottom: -16px; }

.about-gbi-wave {
    position: absolute;
    left: 0; right: 0; bottom: -1px;
    height: 76px;
    background: var(--gbi-intro-bg);
    border-top-left-radius: 50% 80px;
    border-top-right-radius: 50% 80px;
}

/* ── INTRO ── */
.about-gbi-intro {
    padding: 26px 52px 34px;
    text-align: center;
    background: var(--gbi-intro-bg);
}

.about-gbi-intro h2 {
    margin: 0 auto 8px;
    max-width: 920px;
    font-size: clamp(30px, 4vw, 54px);
    line-height: 1.06;
    letter-spacing: -0.025em;
    font-weight: 800;
    color: var(--gbi-navy);
}

.about-gbi-intro .about-gbi-identity {
    margin: 14px auto 0;
    max-width: 980px;
    font-size: clamp(14px, 1.3vw, 16px);
    line-height: 1.7;
    color: #1b446e;
    background: rgba(16,44,87,0.08);
    border-left: 4px solid var(--gbi-navy);
    border-right: 4px solid var(--gbi-navy);
    border-radius: 12px;
    padding: 10px 14px;
}

.about-nowrap { white-space: nowrap; }

/* ── GALLERY ── */
.about-gbi-gallery { padding: 30px 40px 26px; }

.about-gbi-gallery-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
}

.about-gbi-gallery-card {
    margin: 0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 12px 24px rgba(11,31,53,0.15);
    aspect-ratio: 5 / 4;
    border: 2px solid transparent;
    transition: transform 0.35s cubic-bezier(0.2,0.9,0.3,1), box-shadow 0.35s ease, border-color 0.35s ease;
    position: relative;
}

.about-gbi-gallery-card:hover {
    border-color: var(--gbi-green);
    box-shadow: 0 20px 48px rgba(16,44,87,0.28);
    transform: translateY(-6px) scale(1.03);
}

.about-gbi-gallery-card img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.6s cubic-bezier(0.2,0.9,0.3,1), filter 0.35s ease;
}

.about-gbi-gallery-card:hover img {
    transform: scale(1.08) translateY(-2%);
    filter: brightness(1.03);
}

@media (prefers-reduced-motion: reduce) {
    .about-gbi-gallery-card,
    .about-gbi-gallery-card img { transition: none !important; transform: none !important; }
}

/* ── STORY ── */
.about-gbi-story { padding: 44px 40px 30px; }

.about-gbi-story h3,
.about-gbi-leaders h3 {
    margin: 0 0 20px;
    text-align: center;
    font-size: clamp(30px, 4.8vw, 52px);
    line-height: 1.04;
    letter-spacing: -0.02em;
    color: var(--gbi-navy);
}

.about-gbi-story h3::after,
.about-gbi-leaders h3::after {
    content: '';
    display: block;
    width: 64px;
    height: 3px;
    background: var(--gbi-navy);
    margin: 12px auto 0;
}

.about-gbi-script-title {
    font-size: clamp(28px, 3.5vw, 40px) !important;
    font-weight: 800 !important;
    letter-spacing: -0.02em !important;
    line-height: 1.05 !important;
}

.about-gbi-story-card {
    background: linear-gradient(180deg, #fbfdff 0%, #eef4fb 100%);
    border: 1px solid rgba(16,44,87,0.13);
    border-radius: 18px;
    padding: clamp(22px, 3vw, 42px);
    box-shadow: 0 16px 32px rgba(11,31,53,0.11);
    border-left: 5px solid var(--gbi-navy);
    position: relative;
}

.about-gbi-story-card p {
    margin: 0 0 18px;
    color: #173b62;
    font-size: 17px;
    line-height: 1.9;
}

.about-gbi-story-card blockquote {
    margin: 0 0 18px;
    padding: 14px 18px;
    border-left: 5px solid var(--gbi-navy);
    background: rgba(16,44,87,0.06);
    border-radius: 0 12px 12px 0;
    color: var(--gbi-navy);
    font-size: 20px;
    line-height: 1.55;
    font-weight: 700;
}

.about-gbi-closing-quote {
    margin-bottom: 0;
    font-weight: 700;
    color: #12365f;
}

/* ── LEADERS — ZIGZAG LAYOUT ── */
.about-gbi-leaders {
    padding: 52px 40px 64px;
    position: relative;
}

.ldr-title-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-bottom: 10px;
}

.ldr-cross-deco {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
}

.ldr-verse {
    text-align: center;
    font-size: 13px;
    font-style: italic;
    color: rgba(16,44,87,0.55);
    margin: 0 0 40px;
    letter-spacing: 0.01em;
}

.ldr-zigzag-wrap {
    position: relative;
    max-width: 760px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 0;
}

/* Garis timeline vertikal di tengah */
.ldr-timeline-line {
    position: absolute;
    left: 50%;
    top: 40px;
    bottom: 40px;
    width: 1px;
    background: rgba(16,44,87,0.12);
    transform: translateX(-50%);
    pointer-events: none;
}

/* Merpati hiasan atas & bawah */
.ldr-dove-top {
    width: 60px;
    height: 40px;
    display: block;
    margin: 0 auto 8px;
    position: relative;
    z-index: 1;
}
.ldr-dove-bottom {
    width: 60px;
    height: 40px;
    display: block;
    margin: 8px auto 0;
    position: relative;
    z-index: 1;
}

/* Setiap baris zigzag */
.ldr-row {
    display: flex;
    align-items: center;
    gap: 0;
    position: relative;
    padding: 12px 0;
}

.ldr-photo-col {
    flex: 0 0 220px;
    position: relative;
    z-index: 2;
}

.ldr-photo-frame {
    position: relative;
    border-radius: 18px;
    overflow: hidden;
    aspect-ratio: 3 / 4;
    box-shadow: 0 12px 36px rgba(16,44,87,0.20);
    border: 3px solid rgba(16,44,87,0.14);
    transition: box-shadow 0.35s ease, transform 0.35s ease;
}

.ldr-row:hover .ldr-photo-frame {
    box-shadow: 0 20px 48px rgba(16,44,87,0.30);
    transform: translateY(-4px);
}

.ldr-photo {
    width: 100%; height: 100%;
    object-fit: cover;
    object-position: center 12%;
    display: block;
    transition: transform 0.5s ease;
}

.ldr-row:hover .ldr-photo { transform: scale(1.04); }

/* Ornamen sudut foto — L-shape */
.ldr-corner {
    position: absolute;
    width: 20px; height: 20px;
    border-color: rgba(16,44,87,0.35);
    border-style: solid;
    pointer-events: none;
}
.ldr-corner-tl { top: 8px; left: 8px; border-width: 2px 0 0 2px; border-radius: 3px 0 0 0; }
.ldr-corner-br { bottom: 8px; right: 8px; border-width: 0 2px 2px 0; border-radius: 0 0 3px 0; }

/* Connector horisontal */
.ldr-connector-wrap {
    flex: 0 0 60px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.ldr-connector-line {
    position: absolute;
    left: 0; right: 0;
    height: 2px;
    background: rgba(16,44,87,0.20);
}

.ldr-star-node {
    width: 18px; height: 18px;
    position: relative;
    z-index: 2;
    flex-shrink: 0;
}

/* Info card */
.ldr-info-col {
    flex: 1;
    position: relative;
    z-index: 2;
}

.ldr-card {
    background: linear-gradient(145deg, #ffffff 0%, #f0f6ff 100%);
    border: 1.5px solid rgba(16,44,87,0.15);
    border-left: 5px solid var(--gbi-navy);
    border-radius: 0 16px 16px 0;
    padding: 22px 24px;
    box-shadow: 0 6px 24px rgba(16,44,87,0.10);
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.ldr-card-right {
    border-left: 1.5px solid rgba(16,44,87,0.15);
    border-right: 5px solid var(--gbi-navy);
    border-radius: 16px 0 0 16px;
    text-align: right;
}

.ldr-row:hover .ldr-card {
    box-shadow: 0 12px 36px rgba(16,44,87,0.16);
    transform: translateX(4px);
}

.ldr-row:hover .ldr-card-right {
    transform: translateX(-4px);
}

.ldr-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--gbi-navy);
    background: rgba(16,44,87,0.09);
    border-radius: 20px;
    padding: 3px 12px;
    margin-bottom: 10px;
}

.ldr-name {
    margin: 0 0 8px;
    font-size: clamp(15px, 1.6vw, 18px);
    font-weight: 700;
    color: var(--gbi-navy);
    line-height: 1.35;
}

.ldr-name-split {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-height: 3.1em;
    justify-content: center;
}

.ldr-name-line {
    display: block;
}

.ldr-desc {
    margin: 0;
    font-size: 14px;
    line-height: 1.75;
    color: #2a4e7a;
}

/* Hiasan daun zaitun di samping */
.ldr-olive {
    position: absolute;
    width: 46px;
    height: 56px;
    pointer-events: none;
    z-index: 0;
}
.ldr-olive-tl { top: -10px; left: -14px; }
.ldr-olive-tr { top: -10px; right: -14px; transform: scaleX(-1); }
.ldr-olive-bl { bottom: -10px; left: -14px; transform: scaleY(-1); }

/* Divider salib antarbaris */
.ldr-divider-deco {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 28px;
    position: relative;
    z-index: 2;
}

/* ── DESKTOP: 3 kolom sejajar ── */
@media (min-width: 900px) {

    /* Sembunyikan elemen zigzag */
    .ldr-timeline-line,
    .ldr-divider-deco,
    .ldr-dove-top,
    .ldr-dove-bottom,
    .ldr-connector-wrap {
        display: none;
    }

    /* Grid 3 kolom */
    .ldr-zigzag-wrap {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-template-areas: "wakil1 gembala wakil2";
        gap: 24px;
        align-items: stretch;
        max-width: 1000px;
    }

    /* Urutan kolom: Wakil1(kiri) | Gembala(tengah) | Wakil2(kanan) */
    .ldr-row-wakil1 { grid-area: wakil1; }
    .ldr-row-gembala { grid-area: gembala; }
    .ldr-row-wakil2 { grid-area: wakil2; }

    /* Tiap row jadi kartu vertikal */
    .ldr-row,
    .ldr-row.ldr-row-right,
    .ldr-row.ldr-row-left {
        flex-direction: column;
        align-items: stretch;
        padding: 0;
        min-width: 0;
    }

    .ldr-row-wakil1 .ldr-photo-col {
        order: 1;
    }

    .ldr-row-wakil1 .ldr-info-col {
        order: 2;
    }

    .ldr-photo-col {
        flex: unset;
        width: 100%;
    }

    .ldr-photo-frame {
        border-radius: 18px 18px 0 0;
        aspect-ratio: 3 / 4;
        height: auto;
        box-shadow: 0 8px 28px rgba(16,44,87,0.15);
        border: 2px solid rgba(16,44,87,0.12);
        transition: box-shadow 0.35s ease, transform 0.35s ease;
    }

    .ldr-row:hover .ldr-photo-frame {
        box-shadow: 0 16px 40px rgba(16,44,87,0.24);
        transform: translateY(-4px);
    }

    .ldr-info-col {
        flex: unset;
        width: 100%;
    }

    /* Kartu info — border atas navy, sudut bawah rounded */
    .ldr-card,
    .ldr-card-right {
        border-radius: 0 0 18px 18px;
        border-top: 4px solid var(--gbi-navy);
        border-left: 1px solid rgba(16,44,87,0.12);
        border-right: 1px solid rgba(16,44,87,0.12);
        border-bottom: 1px solid rgba(16,44,87,0.12);
        text-align: center;
        padding: 20px 20px 24px;
        height: 100%;
        box-sizing: border-box;
    }

    .ldr-row:hover .ldr-card,
    .ldr-row:hover .ldr-card-right {
        transform: none;
        box-shadow: 0 8px 28px rgba(16,44,87,0.14);
    }

    /* Gembala (tengah) sedikit lebih menonjol */
    .ldr-row-gembala .ldr-photo-frame {
        border-color: rgba(16,44,87,0.25);
        box-shadow: 0 12px 36px rgba(16,44,87,0.22);
    }
    .ldr-row-gembala .ldr-card {
        border-top-width: 5px;
    }

    .ldr-name { font-size: 16px; }

    /* Daun zaitun di desktop */
    .ldr-olive { display: block; }
    .ldr-olive-tl { top: -8px; left: -10px; }
    .ldr-olive-tr { top: -8px; right: -10px; }
    .ldr-olive-bl { bottom: 60px; left: -10px; }

    .ldr-corner { width: 18px; height: 18px; }
    .ldr-corner-tl { top: 7px; left: 7px; }
    .ldr-corner-br { bottom: 7px; right: 7px; }
}

/* ── TABLET: ikuti susunan desktop agar tetap 3 kolom ── */
@media (min-width: 641px) and (max-width: 899px) {
    .ldr-timeline-line,
    .ldr-divider-deco,
    .ldr-dove-top,
    .ldr-dove-bottom,
    .ldr-olive,
    .ldr-connector-wrap {
        display: none !important;
    }

    .ldr-zigzag-wrap {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        grid-template-areas: "wakil1 gembala wakil2";
        gap: 16px;
        align-items: stretch;
        max-width: 960px;
    }

    .ldr-row,
    .ldr-row.ldr-row-right,
    .ldr-row.ldr-row-left {
        flex-direction: column;
        align-items: stretch;
        padding: 0;
        min-width: 0;
    }

    .ldr-row-wakil1 .ldr-photo-col {
        order: 1;
    }

    .ldr-row-wakil1 .ldr-info-col {
        order: 2;
    }

    .ldr-row-wakil1 { grid-area: wakil1; }
    .ldr-row-gembala { grid-area: gembala; }
    .ldr-row-wakil2 { grid-area: wakil2; }

    .ldr-photo-col,
    .ldr-info-col {
        flex: unset;
        width: 100%;
    }

    .ldr-photo-frame {
        border-radius: 18px 18px 0 0;
        aspect-ratio: 3 / 4;
    }

    .ldr-card,
    .ldr-card-right {
        border-radius: 0 0 18px 18px;
        border-top: 4px solid var(--gbi-navy);
        border-left: none;
        border-right: none;
        text-align: center;
        padding: 18px 16px 20px;
    }

    .ldr-row:hover .ldr-card,
    .ldr-row:hover .ldr-card-right {
        transform: none;
    }
}

@media (max-width: 640px) {
    .about-gbi-leaders { padding: 32px 14px 40px; }

    .ldr-timeline-line { display: none; }
    .ldr-olive { display: none; }

    /* Semua row tetap flex horizontal, tapi lebih compact */
    .ldr-row {
        align-items: stretch;
        gap: 0;
        padding: 8px 0;
    }

    .ldr-photo-col {
        flex: 0 0 42%;
        max-width: 160px;
    }

    .ldr-photo-frame {
        border-radius: 14px;
        aspect-ratio: 3 / 4;
        height: 100%;
        box-shadow: 0 6px 18px rgba(16,44,87,0.18);
    }

    .ldr-photo {
        object-position: center 10%;
    }

    .ldr-connector-wrap {
        flex: 0 0 24px;
        min-height: 60px;
    }

    .ldr-connector-line {
        left: 0; right: 0;
        height: 2px;
        top: 50%;
        transform: translateY(-50%);
    }

    .ldr-star-node {
        width: 14px; height: 14px;
    }

    .ldr-info-col {
        flex: 1;
        display: flex;
        align-items: center;
    }

    /* Row kiri: foto kiri, teks kanan — border kiri */
    .ldr-row-left .ldr-card {
        border-radius: 0 12px 12px 0;
        border-left: 4px solid var(--gbi-navy);
        border-right: 1px solid rgba(16,44,87,0.12);
        text-align: left;
        padding: 14px 12px;
        width: 100%;
    }

    /* Row kanan: teks kiri, foto kanan — border kanan */
    .ldr-row-right .ldr-card-right {
        border-radius: 12px 0 0 12px;
        border-right: 4px solid var(--gbi-navy);
        border-left: 1px solid rgba(16,44,87,0.12);
        text-align: left;
        padding: 14px 12px;
        width: 100%;
    }

    .ldr-row:hover .ldr-card,
    .ldr-row:hover .ldr-card-right {
        transform: none;
    }

    .ldr-badge {
        font-size: 9px;
        padding: 2px 8px;
        margin-bottom: 6px;
    }

    .ldr-name {
        font-size: clamp(12px, 3.4vw, 15px);
        margin-bottom: 5px;
    }

    .ldr-desc {
        font-size: 11px;
        line-height: 1.6;
    }

    /* Sudut ornamen lebih kecil di mobile */
    .ldr-corner { width: 14px; height: 14px; }
    .ldr-corner-tl { top: 5px; left: 5px; }
    .ldr-corner-br { bottom: 5px; right: 5px; }

    .ldr-verse { font-size: 11.5px; margin-bottom: 24px; }
    .ldr-cross-deco { width: 20px; height: 20px; }
    .ldr-dove-top, .ldr-dove-bottom { width: 44px; height: 30px; }
    .ldr-divider-deco { height: 20px; }
}

/* ── RESPONSIVE (non-leaders) ── */

@media (max-width: 1080px) {
    .about-gbi-hero { padding: 20px 20px 76px; }
    .about-gbi-hero-inner { grid-template-columns: 1fr; }
    .about-gbi-hero-media { border-radius: 18px 18px 0 0; min-height: 300px; }
    .about-gbi-hero-card {
        border-radius: 0 0 18px 18px;
        padding: 24px 22px;
        border-left: none;
        border-top: 5px solid var(--gbi-green);
    }
    .about-gbi-intro { padding: 24px 34px 30px; }
    .about-gbi-gallery,
    .about-gbi-story,
    .about-gbi-leaders { padding-left: 26px; padding-right: 26px; }
    .about-gbi-gallery-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 700px) {
    .about-gbi-hero { padding: 14px 16px 52px; }
    .about-gbi-hero-media { min-height: 220px; }
    .about-gbi-hero-card { padding: 18px 16px 22px; }
    .about-gbi-hero-card h1 { font-size: clamp(26px, 9vw, 34px); }
    .about-gbi-hero-card p { font-size: 14px; line-height: 1.62; padding: 0 16px; }
    .about-gbi-hero-card p::before,
    .about-gbi-hero-card p::after { font-size: 36px; }
    .about-gbi-wave { height: 48px; border-top-left-radius: 50% 50px; border-top-right-radius: 50% 50px; }
    .about-gbi-intro { padding: 18px 16px 22px; }
    .about-gbi-intro h2 { font-size: clamp(24px, 8vw, 34px); }
    .about-gbi-intro .about-gbi-identity { font-size: 13.5px; border-left-width: 3px; border-right-width: 3px; }
    .about-gbi-gallery { padding: 20px 16px 16px; }
    .about-gbi-gallery-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .about-gbi-story { padding: 30px 16px 20px; }
    .about-gbi-story-card { padding: 18px; }
    .about-gbi-story-card p { font-size: 15px; line-height: 1.78; }
    .about-gbi-story-card blockquote { font-size: 17px; padding: 12px 14px; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>