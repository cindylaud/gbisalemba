<?php
require_once __DIR__ . '/config/database.php';
include __DIR__ . '/includes/header.php';

if (!function_exists('about_find_first_image')) {
    function about_find_first_image(array $directories, $fallback = 'uploads/slider/slider_1__1771686869_4ba458.jpg') {
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

if (!function_exists('about_collect_images')) {
    function about_collect_images(array $directories, $limit = 4) {
        $images = [];
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

                $path = rtrim($directory, '/\\') . '/' . $file;
                if (!file_exists($path)) {
                    continue;
                }

                $images[] = $path;
                if (count($images) >= $limit) {
                    return $images;
                }
            }
        }

        return $images;
    }
}

$about_hero_image = about_find_first_image([
    'uploads/whatsnew',
    'uploads/slider',
    'assets/images/gembala'
]);

$about_gallery_images = about_collect_images([
    'uploads/whatsnew',
    'uploads/slider',
    'uploads/pelayanan'
], 4);

while (count($about_gallery_images) < 4) {
    $about_gallery_images[] = $about_hero_image;
}

$about_photo_1 = about_find_first_image([
    'uploads/gembala',
    'assets/images/gembala',
    'uploads/slider'
]);

$about_photo_2 = about_find_first_image([
    'uploads/gembala',
    'assets/images/gembala',
    'uploads/slider'
], $about_photo_1);

$about_photo_3 = about_find_first_image([
    'uploads/gembala',
    'assets/images/gembala',
    'uploads/slider'
], $about_photo_1);
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
        </section>

        <section class="about-gbi-gallery" aria-label="Dokumentasi Kegiatan">
            <div class="about-gbi-gallery-grid">
                <?php foreach ($about_gallery_images as $index => $gallery_image): ?>
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

        <section class="about-gbi-leaders">
            <h3>Para Gembala</h3>
            <div class="about-gbi-leaders-grid">
                <article class="about-gbi-leader-card">
                    <div class="about-gbi-leader-photo-wrap">
                        <img src="<?php echo htmlspecialchars($about_photo_1); ?>" alt="Wakil Gembala GBI Salemba" class="about-gbi-leader-photo">
                    </div>
                    <div class="about-gbi-leader-meta">
                        <h4 class="about-gbi-leader-position">Wakil Gembala</h4>
                        <h5 class="about-gbi-leader-name">Ps. David Natanael<br>Ps. Rita Emia Nata</h5>
                    </div>
                </article>

                <article class="about-gbi-leader-card about-gbi-leader-main">
                    <div class="about-gbi-leader-photo-wrap">
                        <img src="<?php echo htmlspecialchars($about_photo_2); ?>" alt="Gembala GBI Salemba" class="about-gbi-leader-photo">
                    </div>
                    <div class="about-gbi-leader-meta">
                        <h4 class="about-gbi-leader-position">Gembala</h4>
                        <h5 class="about-gbi-leader-name">Ps. David Natanael<br>Ps. Rita Emia Nata</h5>
                    </div>
                </article>

                <article class="about-gbi-leader-card">
                    <div class="about-gbi-leader-photo-wrap">
                        <img src="<?php echo htmlspecialchars($about_photo_3); ?>" alt="Wakil Gembala GBI Salemba" class="about-gbi-leader-photo">
                    </div>
                    <div class="about-gbi-leader-meta">
                        <h4 class="about-gbi-leader-position">Wakil Gembala</h4>
                        <h5 class="about-gbi-leader-name">Ps. David Natanael<br>Ps. Rita Emia Nata</h5>
                    </div>
                </article>
            </div>
        </section>
    </section>
</main>

<style>
.about-gbi-wrap {
    --gbi-navy: #102c57;
    --gbi-green: #1a3a63;
    --gbi-green-dark: #0f2847;
    --gbi-cream: #f4f8fd;
    --gbi-card: #f8fbff;
    background:
        radial-gradient(circle at 10% 8%, rgba(44, 110, 170, 0.12) 0%, rgba(44, 110, 170, 0) 34%),
        radial-gradient(circle at 90% 14%, rgba(37, 133, 126, 0.08) 0%, rgba(37, 133, 126, 0) 30%),
        linear-gradient(160deg, #f8fbff 0%, #edf3fa 52%, #f5f9fc 100%);
    position: relative;
    isolation: isolate;
    overflow: hidden;
    margin: 0 !important;
    padding: 0 !important;
    color: var(--gbi-navy);
    font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.about-gbi-wrap::before,
.about-gbi-wrap::after {
    content: '';
    position: absolute;
    pointer-events: none;
    z-index: 0;
    border-radius: 50%;
}

.about-gbi-wrap::before {
    width: 460px;
    height: 460px;
    top: -180px;
    right: -120px;
    background: radial-gradient(circle, rgba(40, 98, 156, 0.12) 0%, rgba(40, 98, 156, 0) 70%);
    filter: blur(24px);
}

.about-gbi-wrap::after {
    width: 380px;
    height: 380px;
    bottom: 10%;
    left: -130px;
    background: radial-gradient(circle, rgba(34, 137, 149, 0.1) 0%, rgba(34, 137, 149, 0) 72%);
    filter: blur(26px);
}

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

.about-gbi-hero {
    position: relative;
    padding: 18px 36px 86px;
    background-image: linear-gradient(120deg, rgba(16, 44, 87, 0.82) 0%, rgba(16, 44, 87, 0.75) 44%, rgba(16, 44, 87, 0.78) 100%), var(--about-bg);
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

.about-gbi-hero-card::after {
    content: '';
    position: absolute;
    bottom: -1px;
    right: 0;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, rgba(16, 44, 87, 0.15) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.about-gbi-hero-card h1 {
    margin: 0 0 10px;
    font-size: clamp(30px, 4vw, 48px);
    line-height: 0.96;
    letter-spacing: -0.02em;
    color: var(--gbi-navy);
    font-family: 'Playfair Display', serif;
    position: relative;
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
    font-family: 'Playfair Display', serif;
    color: var(--gbi-green);
    opacity: 0.6;
    line-height: 0.8;
}

.about-gbi-hero-card p::before {
    left: 0;
    top: -8px;
}

.about-gbi-hero-card p::after {
    right: 0;
    bottom: -16px;
}

.about-gbi-wave {
    position: absolute;
    left: 0;
    right: 0;
    bottom: -1px;
    height: 76px;
    background: var(--gbi-card);
    border-top-left-radius: 50% 80px;
    border-top-right-radius: 50% 80px;
}

.about-gbi-intro {
    padding: 26px 52px 34px;
    text-align: center;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.55) 0%, rgba(241, 247, 255, 0.7) 100%);
}

.about-gbi-intro h2 {
    margin: 0 auto 8px;
    max-width: 920px;
    font-size: clamp(30px, 4vw, 54px);
    line-height: 1.06;
    letter-spacing: -0.025em;
    font-family: 'Playfair Display', serif;
    color: var(--gbi-navy);
    position: relative;
    display: inline-block;
}

.about-gbi-gallery {
    padding: 30px 40px 26px;
    position: relative;
}

.about-gbi-gallery::before {
    content: '';
    position: absolute;
    top: 0;
    left: 60px;
    width: 14px;
    height: 14px;
    background: var(--gbi-navy);
    border-radius: 50%;
    opacity: 0.4;
}

.about-gbi-gallery-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
}

.about-gbi-gallery-card {
    margin: 0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 12px 24px rgba(11, 31, 53, 0.15);
    aspect-ratio: 5 / 4;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.about-gbi-gallery-card:hover {
    border-color: var(--gbi-green);
    box-shadow: 0 12px 32px rgba(16, 44, 87, 0.25);
}

.about-gbi-gallery-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.about-gbi-story {
    padding: 44px 40px 30px;
}

.about-gbi-story h3,
.about-gbi-leaders h3 {
    margin: 0 0 16px;
    text-align: center;
    font-size: clamp(30px, 4.8vw, 52px);
    line-height: 1.04;
    letter-spacing: -0.02em;
    font-family: 'Playfair Display', serif;
    color: var(--gbi-navy);
    position: relative;
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

.about-gbi-story-card {
    background: linear-gradient(180deg, #fbfdff 0%, #eef4fb 100%);
    border: 1px solid rgba(16, 44, 87, 0.13);
    border-radius: 18px;
    padding: clamp(22px, 3vw, 42px);
    box-shadow: 0 16px 32px rgba(11, 31, 53, 0.11);
    border-left: 5px solid var(--gbi-navy);
    position: relative;
}

.about-gbi-story-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 80px;
    height: 80px;
    background: radial-gradient(circle, rgba(16, 44, 87, 0.08) 0%, transparent 70%);
    border-radius: 50%;
    transform: translate(20px, -20px);
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
    background: rgba(16, 44, 87, 0.06);
    border-radius: 0 12px 12px 0;
    color: var(--gbi-navy);
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    line-height: 1.55;
    font-weight: 500;
}

.about-gbi-closing-quote {
    margin-bottom: 0;
    font-weight: 700;
    color: #12365f;
}

.about-gbi-leaders {
    padding: 52px 40px 54px;
}

.about-gbi-leaders-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    align-items: start;
}

.about-gbi-leader-card {
    border-radius: 20px;
    border: 2px solid rgba(16, 44, 87, 0.2);
    background: linear-gradient(180deg, #ffffff 0%, #f3f8ff 100%);
    box-shadow: 0 8px 20px rgba(11, 31, 53, 0.08);
    overflow: hidden;
    text-align: center;
    transition: all 0.3s ease;
}

.about-gbi-leader-card:hover {
    border-color: var(--gbi-green);
    box-shadow: 0 12px 32px rgba(16, 44, 87, 0.2);
    transform: translateY(-4px);
}

.about-gbi-leader-main {
    transform: none;
}

.about-gbi-leader-photo-wrap {
    width: 100%;
    aspect-ratio: 1 / 1.25;
    overflow: hidden;
    background: #d4dce8;
    border-radius: 16px 16px 0 0;
}

.about-gbi-leader-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 25%;
    display: block;
}

.about-gbi-leader-meta {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(236, 244, 254, 0.9) 100%);
    padding: 18px 14px 16px;
}

.about-gbi-leader-position {
    margin: 0;
    text-align: center;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.1em;
    color: var(--gbi-green);
    text-transform: uppercase;
    display: inline-block;
    background: rgba(16, 44, 87, 0.08);
    padding: 4px 12px;
    border-radius: 20px;
    width: fit-content;
    margin-left: auto;
    margin-right: auto;
}

.about-gbi-leader-name {
    margin: 8px 0 0;
    text-align: center;
    font-size: clamp(16px, 2vw, 20px);
    line-height: 1.3;
    font-family: 'Playfair Display', serif;
    color: var(--gbi-navy);
    font-weight: 700;
}

@media (max-width: 1080px) {
    .about-gbi-hero {
        padding: 20px 20px 76px;
    }

    .about-gbi-hero-inner {
        grid-template-columns: 1fr;
    }

    .about-gbi-hero-media {
        border-radius: 18px 18px 0 0;
        min-height: 300px;
    }

    .about-gbi-hero-card {
        border-radius: 0 0 18px 18px;
    }

    .about-gbi-gallery-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .about-gbi-leaders-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .about-gbi-wrap {
        padding: 0 0 60px;
    }

    .about-gbi-shell {
        width: min(1200px, calc(100% - 16px));
        border-radius: 18px;
    }

    .about-gbi-hero,
    .about-gbi-intro,
    .about-gbi-gallery,
    .about-gbi-story,
    .about-gbi-leaders {
        padding-left: 14px;
        padding-right: 14px;
    }

    .about-gbi-intro h2,
    .about-gbi-story h3,
    .about-gbi-leaders h3 {
        line-height: 1.08;
    }

    .about-gbi-story-card p {
        font-size: 15px;
    }

    .about-gbi-story-card blockquote {
        font-size: 19px;
    }

    .about-gbi-gallery-grid,
    .about-gbi-leaders-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>


