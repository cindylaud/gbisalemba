<?php 
include 'config/db.php';
$conf = $conn->query("SELECT * FROM config LIMIT 1")->fetch_assoc();

$config = $conn->query("SELECT * FROM config LIMIT 1");
$config = $config ? $config->fetch_assoc() : null;
$bgMode = $config['bg_mode'] ?? 'default';

$bgItems = [];
$singleBg = null;

if($bgMode === "slider"){
    $q = $conn->query("SELECT file FROM background ORDER BY urutan ASC, id DESC");
    while($d = $q->fetch_assoc()) $bgItems[] = ["file" => "uploads/".$d['file'], "type" => "image"];
    $qv = $conn->query("SELECT file FROM background_video ORDER BY urutan ASC, id DESC LIMIT 1");
    if($v = $qv->fetch_assoc()) $bgItems[] = ["file" => "uploads/".$v['file'], "type" => "video"];
}

if(in_array($bgMode, ["image", "default"])){
    $q = $conn->query("SELECT file FROM background ORDER BY urutan ASC, id DESC LIMIT 1");
    if($d = $q->fetch_assoc()) $singleBg = ["file" => "uploads/".$d['file'], "type" => "image"];
    else $singleBg = ["file" => "uploads/bg.jpg", "type" => "image"];
}

if($bgMode === "video"){
    $q = $conn->query("SELECT file FROM background_video ORDER BY urutan ASC, id DESC LIMIT 1");
    if($d = $q->fetch_assoc()) $singleBg = ["file" => "uploads/".$d['file'], "type" => "video"];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>GBI Salemba</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>

    <style>
        :root {
            --glass: rgba(15, 15, 15, 0.55);
            --border: rgba(255, 255, 255, 0.12);
            --accent: #93c5fd;
            --bg-dark: #050505;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html { scroll-behavior: smooth; }
        
        body {
             font-family: 'Plus Jakarta Sans', sans-serif;
            background: #050000;
            color: #fff;
            margin: 0;
            overflow-x: hidden;
        }

        .bg-container { position: fixed; inset: 0; z-index: -1; overflow: hidden; background: #000; }
        .bg {
            position: absolute; inset: -5%; width: 110%; height: 110%;
            background-position: center; background-size: cover;
            transition: opacity 2.2s cubic-bezier(0.4, 0, 0.2, 1); will-change: transform;
            filter: brightness(0.35) contrast(1.1) blur(2px);
        }
        .bg video { width: 100%; height: 100%; object-fit: cover; }
        .overlay {
            position: fixed; inset: 0; z-index: -1;
            background: radial-gradient(circle at center, transparent 0%, rgba(0,0,0,0.8) 100%);
            pointer-events: none;
        }

        .container { width: 100%; max-width: 1000px; margin: 0 auto; padding: 0 25px; }

        .hero {
            height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 0 20px;
    
    padding-top: 80px; 
    
    box-sizing: border-box;
        }
        .hero h1 {
    font-family: 'Plus Jakarta Sans', sans-serif !important; 
    font-weight: 800 !important; /* Ketebalan maksimal biar gagah */
            font-size: clamp(2.8rem, 10vw, 5.5rem); margin: 0;
            background: linear-gradient(180deg, #fff 40%, rgba(255,255,255,0.2));
            -webkit-background-clip: text; color: transparent; font-weight: 600;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.5));
        }

        .section {
    padding: clamp(30px, 5vw, 60px);
    margin-bottom: 60px;
    backdrop-filter: blur(40px);
    -webkit-backdrop-filter: blur(40px);
    border: 1px solid var(--border); 
    border-radius: 40px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.4);
    overflow: hidden;
    position: relative;
    transition: background 0.5s ease;
}

.glass-default {
    background: var(--glass);
}

.glass-red {
    background: rgba(66, 0, 0, 0.65); 
}

        h2 { font-size: 1.8rem; font-weight: 400; margin-bottom: 40px; text-align: center; opacity: 0.9; }

        .jadwal-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 20px;
        padding: 20px 0;
    }

    .jadwal-card-glass {
        position: relative;
        height: 420px;
        border-radius: 12px;
        overflow: hidden;
        cursor: pointer;
        background: #000;
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .jadwal-card-glass:hover {
        transform: scale(1.05);
        z-index: 10;
        box-shadow: 0 15px 30px rgba(0,0,0,0.8);
    }

    .bg-card-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        inset: 0;
        transition: transform 0.5s ease;
    }

    .jadwal-card-glass::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, 
            rgba(0,0,0,0.9) 0%, 
            rgba(0,0,0,0.4) 40%, 
            transparent 80%);
        z-index: 2;
    }

    .glass-info-panel {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 25px 20px;
        z-index: 3;
        transition: transform 0.4s ease;
    }

    .tag-cat {
        font-size: 11px;
        font-weight: 800;
        color: #e50914;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        display: block;
        margin-bottom: 5px;
    }

    .main-title {
        font-size: 1.6rem;
        font-weight: 800;
        color: #fff;
        margin: 0 0 8px 0;
        line-height: 1.1;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    }

    .time-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.4s ease;
    }

    .jadwal-card-glass:hover .time-wrap {
        opacity: 1;
        transform: translateY(0);
    }

    .time-text {
        font-size: 12px;
        font-weight: 500;
        color: #b3b3b3;
        margin: 0;
    }

    @media (max-width: 768px) {
        .jadwal-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .jadwal-card-glass { height: 280px; }
        .main-title { font-size: 1.1rem; }
        .time-wrap { opacity: 1; transform: none; }
    }

    @media (max-width: 768px) {
        .jadwal-card-glass { height: 320px; }
        .jadwal-grid { grid-template-columns: 1fr; padding: 0 10px; }
    }

        .gallerySwiper { 
            padding: 10px 5px 60px !important; 
            overflow: visible !important; 
        }
        .bento-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(2, 200px); gap: 15px;
        }
        .bento-item {
            position: relative; overflow: hidden; border-radius: 20px;
            border: 1px solid var(--border); background: rgba(255,255,255,0.05);
            cursor: zoom-in; transition: transform 0.4s ease;
        }
        .bento-item img {
            width: 100%; height: 100%; object-fit: cover;
            transition: 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .bento-item:hover { transform: translateY(-5px); }
        .bento-item:hover img { transform: scale(1.08); filter: brightness(1.1); }
        
        .bento-item:nth-child(1) { grid-column: span 2; grid-row: span 2; }
        .bento-item:nth-child(2) { grid-column: span 2; grid-row: span 1; }
        .bento-item:nth-child(3) { grid-column: span 1; grid-row: span 1; }
        .bento-item:nth-child(4) { grid-column: span 1; grid-row: span 1; }

        .popup-overlay {
            position: fixed; inset: 0; background: rgba(0, 0, 0, 0);
            backdrop-filter: blur(0px); z-index: 9999;
            display: flex; align-items: center; justify-content: center;
            pointer-events: none; opacity: 0;
            transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            cursor: zoom-out;
        }
        .popup-overlay.active {
            background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(15px);
            pointer-events: auto; opacity: 1;
        }
        .popup-image {
            max-width: 90%; max-height: 90%; border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
            transform: scale(0.7) translateY(50px); opacity: 0;
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.1);
        }
        .popup-overlay.active .popup-image { transform: scale(1) translateY(0); opacity: 1; }

        .videoSwiper {
            padding: 20px 5px 60px !important; 
            margin-top: -20px !important;
            overflow: visible !important; 
        }
        .video-wrapper-outer { transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); padding: 5px; }
        .video-container {
            position: relative; width: 100%; padding-bottom: 56.25%; height: 0;
            border-radius: 25px; overflow: hidden; border: 1px solid var(--border);
            background: #000; transition: all 0.5s ease;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }
        .video-wrapper-outer:hover .video-container {
            transform: translateY(-10px) scale(1.02);
            border-color: var(--accent); box-shadow: 0 20px 45px rgba(147, 197, 253, 0.3);
        }
        .video-container iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }

        /* PAGINATION DOTS */
        .swiper-pagination { bottom: 10px !important; }
        .swiper-pagination-bullet { background: #fff !important; opacity: 0.2; transition: 0.3s; }
        .swiper-pagination-bullet-active { background: #b22222 !important; opacity: 1; width: 25px !important; border-radius: 5px !important; }

        .reveal { opacity: 0; transform: translateY(40px); transition: 1.2s cubic-bezier(0.15, 0.85, 0.35, 1); }
        .reveal.active { opacity: 1; transform: translateY(0); }

        @media (max-width: 768px) {
            .section { padding: 40px 15px; }
            .bento-grid { grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(3, 140px); }
            .jadwal-card { flex-direction: column; text-align: center; }
            .videoSwiper, .gallerySwiper { overflow: hidden !important; }
        }

        .section-title {
        font-weight: 900 !important;
        letter-spacing: 2px;
        font-size: clamp(1.4rem, 4vw, 2.2rem);
        margin-bottom: 50px;
    }

    .video-pro-card {
        padding: 20px;
        transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
    }

    .video-main-engine {
        position: relative;
        z-index: 1;
    }

    .video-glow-effect {
        position: absolute;
        inset: -10px;
        background: radial-gradient(circle, rgba(229, 9, 20, 0.3) 0%, transparent 70%);
        filter: blur(20px);
        opacity: 0;
        transition: 0.5s;
        z-index: -1;
    }

    .swiper-slide-active .video-glow-effect,
    .video-pro-card:hover .video-glow-effect {
        opacity: 1;
    }

    .video-container {
        position: relative;
        width: 100%;
        padding-bottom: 56.25%;
        border-radius: 20px;
        overflow: hidden;
        background: #000;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 20px 40px rgba(0,0,0,0.6);
        transition: all 0.5s ease;
    }

    .video-pro-card:hover .video-container {
        transform: translateY(-10px) scale(1.03);
        border-color: rgba(229, 9, 20, 0.5);
    }

    .video-info-box {
        margin-top: 25px;
        text-align: center;
    }

    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 10px;
        font-weight: 800;
        color: #e50914;
        letter-spacing: 2px;
        margin-bottom: 10px;
    }

    .live-indicator .dot {
        width: 6px;
        height: 6px;
        background: #e50914;
        border-radius: 50%;
        box-shadow: 0 0 10px #e50914;
    }

    .v-title {
        font-size: 15px;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: 0.5px;
        margin: 0;
        opacity: 0.6;
        transition: 0.4s;
    }

    .video-pro-card:hover .v-title {
        opacity: 1;
        text-shadow: 0 0 15px rgba(255,255,255,0.3);
    }

    .swiper-pagination-bullet {
        background: rgba(255,255,255,0.2) !important;
        width: 8px !important;
        height: 8px !important;
        transition: 0.4s !important;
    }
    .swiper-pagination-bullet-active {
        background: #e50914 !important;
        width: 30px !important;
        border-radius: 10px !important;
    }

@keyframes deepSlideUp {
    from { 
        opacity: 0; 
        transform: translateY(120px) scale(0.95);
        filter: blur(10px);
    }
    to { 
        opacity: 1; 
        transform: translateY(0) scale(1);
        filter: blur(0);
    }
}

@keyframes fadeInHero {
    from { opacity: 0; letter-spacing: 15px; }
    to { opacity: 1; letter-spacing: 5px; }
}

.hero h1 {
    animation: deepSlideUp 1.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}

.hero span {
    animation: fadeInHero 1.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}

.hero .container > div:nth-child(3) { 
    animation: deepSlideUp 2s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}

.hero-description {
    opacity: 0;
    animation: deepSlideUp 1.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.5; }
    100% { transform: scale(1); opacity: 1; }
}

    </style>
</head>
<body>

    <?php 
    if(file_exists('navbar.php')) include 'navbar.php'; 
    ?>

    <?php if($conf['bg_audio']): ?>
    <audio id="bgMusic" loop>
        <source src="uploads/<?= $conf['bg_audio'] ?>" type="audio/mpeg">
    </audio>
    
    <div id="musicToggle" onclick="toggleMusic()" style="
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 9998;
        transition: 0.3s;
        box-shadow: 0 10px 20px rgba(0,0,0,0.3);
    ">
        <div id="musicIcon" style="color: white; display: flex;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H2v6h4l5 4V5zM23 9l-6 6M17 9l6 6"/></svg>
        </div>
    </div>
<?php endif; ?>

<div class="bg-container">
    <div id="bg1" class="bg" style="opacity: 1;"></div>
    <div id="bg2" class="bg" style="opacity: 0;"></div>
    <div class="overlay"></div>
</div>

<div class="popup-overlay" id="imagePopup">
    <img src="" alt="Popup" class="popup-image" id="popupImg">
</div>

<div class="container">
    <div class="hero" id="hero" style="background: transparent !important;">
    <div class="container" style="text-align: center; z-index: 1;">
        <span style="letter-spacing: 5px; font-size: 12px; font-weight: 600; color: #b22222; text-transform: uppercase; margin-bottom: 20px; display: block;">Welcome to our Church</span>
        
        <h1 style="font-size: clamp(3rem, 8vw, 5rem); margin-bottom: 30px; line-height: 1;">GBI SALEMBA</h1>

         <div class="hero-countdown-mobile" style="display: inline-flex;">
    <p class="hero-countdown-mobile-label">
        Next Service Starts In
    </p>
    
    <div id="timer" class="hero-countdown-mobile-timer">
        <span id="d" class="hero-countdown-digit">00</span>
        <span id="h" class="hero-countdown-digit">00</span>
        <span id="m" class="hero-countdown-digit">00</span>
        <span id="s" class="hero-countdown-digit">00</span>
    </div>

    <div class="hero-countdown-mobile-units">
        <span>Days</span>
        <span>Hrs</span>
        <span>Min</span>
        <span>Sec</span>
    </div>
</div>

        <p class="hero-description" style="margin-top: 40px; opacity: 0.5; font-size: 14px; max-width: 500px; margin-left: auto; margin-right: auto; line-height: 1.6;">
    Bergabunglah bersama kami di Plaza Kenari Mas Lantai 7A untuk mengalami hadirat Tuhan yang mengubah hidup.
</p>
    </div>
</div>

    <div class="section reveal glass-red worship-container">
    <h2 class="section-title">
        WORSHIP <span class="highlight">SCHEDULE</span>
    </h2>

    <div class="jadwal-grid">
        <?php
        $q = $conn->query("SELECT * FROM jadwal ORDER BY urutan ASC, id DESC");
        while($d = $q->fetch_assoc()): 
            $img = !empty($d['gambar']) ? 'uploads/'.$d['gambar'] : 'uploads/default.jpg';
        ?>
            <div class="jadwal-card-glass">
                <img src="<?= $img ?>" class="bg-card-img" alt="<?= htmlspecialchars($d['judul']) ?>">
                
                <div class="glass-info-panel">
                    <span class="tag-cat"><?= htmlspecialchars($d['kategori']) ?></span>
                    <h3 class="main-title"><?= htmlspecialchars($d['judul']) ?></h3>
                    <div class="time-wrap">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="opacity:0.6;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <p class="time-text"><?= htmlspecialchars($d['hari']) ?> • <?= htmlspecialchars($d['jam']) ?></p>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

    <div class="section reveal">
        <h2 class="section-title">
        MOMENTS <span class="highlight">
    </h2>
        <div class="swiper gallerySwiper">
            <div class="swiper-wrapper">
                <?php
                $q = $conn->query("SELECT * FROM gallery ORDER BY urutan ASC, id DESC");
                $all_images = [];
                while($d = $q->fetch_assoc()) { $all_images[] = $d['file']; }
                
                if(count($all_images) > 0) {
                    $chunks = array_chunk($all_images, 4); 
                    foreach($chunks as $chunk): ?>
                        <div class="swiper-slide">
                            <div class="bento-grid">
                                <?php foreach($chunk as $file): ?>
                                    <div class="bento-item" onclick="openPopup('uploads/<?= $file ?>')">
                                        <img src="uploads/<?= $file ?>" loading="lazy">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; 
                } ?>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
<div class="section reveal glass-red">
    <h2 class="section-title">
        LIVE <span class="highlight">STREAMING</span>
    </h2>
    
    <div class="swiper videoSwiper">
        <div class="swiper-wrapper">
            <?php
            $q = $conn->query("SELECT * FROM video ORDER BY urutan ASC, id DESC");
            while($d = $q->fetch_assoc()):
                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $d['link'], $m);
                $id = $m[1] ?? '';
                if($id):
            ?>
                <div class="swiper-slide">
                    <div class="video-pro-card">
                        <div class="video-main-engine">
                            <div class="video-glow-effect"></div> <div class="video-container">
                                <iframe src='https://www.youtube.com/embed/<?= $id ?>?modestbranding=1&rel=0&hd=1' allowfullscreen></iframe>
                            </div>
                        </div>

                        <div class="video-info-box">
                            <div class="live-indicator">
                                <span class="dot"></span> ARCHIVE
                            </div>
                            <h4 class="v-title"><?= htmlspecialchars($d['title'] ?? 'Church Service') ?></h4>
                        </div>
                    </div>
                </div>
            <?php endif; endwhile; ?>
        </div>
        <div class="swiper-pagination"></div>
    </div>
</div>

    <div class="section reveal glass-default" style="padding: 80px 40px; max-width: 1200px; margin: 0 auto;">
    
    <div class="visit-grid">
        
        <div class="visit-col">
            <div class="pro-header">
                <span class="pro-tag">Visit Us</span>
            </div>
            <h2 class="pro-title" style="text-align: left; margin-left: 0; width: 100%;">
    Find Our <span class="highlight">Home</span>
</h2>
            
            <div class="map-frame-outer">
                <div class="map-glow"></div>
                <div class="map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.5502495093674!2d106.84621937499007!3d-6.190882293796743!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f443f572bfcb%3A0xbe095dbdf568c42f!2sGBI%20Salemba!5e0!3m2!1sen!2sid!4v1774939435615!5m2!1sen!2sid" 
                        allowfullscreen="" loading="lazy">
                    </iframe>
                </div>
            </div>
            
            <a href="https://maps.google.com" target="_blank" class="maps-btn">
                <span>Navigate via Google Maps</span>
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/></svg>
            </a>
        </div>

        <div class="visit-col">
            <div class="pro-header">
                <span class="pro-tag">Testimonials</span>
            </div>
            <h2 class="pro-title" style="text-align: left; margin-left: 0; width: 100%;">
    Reviews <span class="highlight"></span>
</h2>
            
            <div class="review-scroll-container">
                <?php for($i=1; $i<=10; $i++): ?>
                <div class="review-card">
                    <div class="stars">★★★★★</div>
                    <p class="review-text">"Ibadah di sini memberikan ketenangan dan pertumbuhan iman yang nyata. Komunitasnya sangat hangat."</p>
                    <div class="reviewer-info">
                        <div class="avatar-placeholder"><?= substr("Jemaat", 0, 1) ?></div>
                        <span class="name">Jemaat GBI Salemba</span>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<style>
.pro-header { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
.pro-line { width: 30px; height: 1.5px; background: var(--primary); border-radius: 2px; }
.pro-tag { color: var(--primary); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 3px; }
.pro-title { font-size: clamp(1.6rem, 4vw, 2.2rem); font-weight: 900; color: #fff; margin: 0; }

.visit-grid { 
    display: grid; 
    grid-template-columns: 1.4fr 1fr; 
    gap: 50px; 
    align-items: start; 
}

.map-frame-outer { position: relative; margin: 25px 0; }
.map-glow {
    position: absolute; inset: -5px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 70%);
    filter: blur(15px); z-index: -1;
}
.map-container {
    height: 380px; border-radius: 25px; overflow: hidden;
    border: 1px solid rgba(255,255,255,0.1); background: #000;
}
.map-container iframe {
    width: 100%; height: 100%; border: 0;
    filter: invert(90%) hue-rotate(180deg) brightness(0.9) contrast(1.2);
}

.maps-btn {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 16px; background: rgba(255,255,255,0.03); color: #fff;
    border: 1px solid rgba(255,255,255,0.1); border-radius: 18px;
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    text-decoration: none; transition: 0.4s;
}
.maps-btn:hover { background: var(--primary); transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.3); }

.review-scroll-container {
    display: flex; flex-direction: column; gap: 15px;
    max-height: 480px; overflow-y: auto; padding-right: 15px;
    margin-top: 25px;
}

.review-scroll-container::-webkit-scrollbar { width: 4px; }
.review-scroll-container::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
.review-scroll-container::-webkit-scrollbar-thumb:hover { background: var(--primary); }

.review-card {
    padding: 25px; background: rgba(255,255,255,0.02);
    border-radius: 24px; border: 1px solid rgba(255,255,255,0.05);
    transition: 0.4s;
}
.review-card:hover { 
    background: rgba(255,255,255,0.05); 
    border-color: var(--primary); 
    transform: scale(0.98); 
}
.stars { color: #fbbf24; font-size: 12px; margin-bottom: 12px; }
.review-text { font-size: 13px; color: rgba(255,255,255,0.7); font-style: italic; line-height: 1.6; margin: 0; }
.reviewer-info { display: flex; align-items: center; gap: 10px; margin-top: 15px; }
.avatar-placeholder { 
    width: 30px; height: 30px; background: var(--primary); 
    border-radius: 50%; display: flex; align-items: center; 
    justify-content: center; font-size: 10px; font-weight: 800; 
}
.reviewer-info .name { font-size: 11px; font-weight: 700; color: #fff; }

.social-footer { 
    margin-top: 80px; padding-top: 40px; 
    border-top: 1px solid rgba(255,255,255,0.05); text-align: center; 
}
.social-tag { font-size: 10px; text-transform: uppercase; letter-spacing: 4px; opacity: 0.4; margin-bottom: 30px; }
.social-icons-wrap { display: flex; justify-content: center; gap: 20px; }
.social-btn {
    width: 55px; height: 55px; display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
    border-radius: 50%; color: #fff; transition: 0.4s;
}
.social-btn:hover { transform: translateY(-8px) rotate(8deg); color: #fff; }
.social-btn.ig:hover { background: linear-gradient(45deg, #f09433, #bc1888); }
.social-btn.yt:hover { background: #ff0000; }
.social-btn.fb:hover { background: #1877f2; }

.social-btn.tt:hover {
        background: #000000;
        border-color: #fe2c55;
        box-shadow: -3px 0 10px rgba(37, 244, 238, 0.6), 3px 0 10px rgba(254, 44, 85, 0.6);
        color: #fff;
    }

@media (max-width: 992px) {
    .visit-grid { grid-template-columns: 1fr; gap: 60px; }
    .map-container { height: 300px; }
    .review-scroll-container { max-height: 400px; }
}
    .review-scroll::-webkit-scrollbar { width: 4px; }
    .review-scroll::-webkit-scrollbar-track { background: transparent; }
    .review-scroll::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }
    .review-scroll::-webkit-scrollbar-thumb:hover { background: var(--accent); }

    @media (max-width: 992px) {
        .section > div:first-child { grid-template-columns: 1fr !important; gap: 30px; }
        .section { padding: 20px !important; }
    }

    .section-title {
        text-align: center;
        margin-bottom: 0px;
        
        font-family: 'Poppins', sans-serif; 
        font-weight: 700 !important;      
        font-size: clamp(1.4rem, 4vw, 2.2rem); 
        letter-spacing: 8px;              
        line-height: 1;
        text-transform: uppercase;          
        color: #ffffff;
        text-shadow: 0 10px 20px rgba(0,0,0,0.5);
        
        position: relative;
        z-index: 10;
    }

    .section-title::after {
        content: '';
        display: block;
        width: 80px;
        height: 6px;
        background: var(--primary);
        margin: 20px auto 0;
        border-radius: 10px;
        box-shadow: 0 0 15px var(--primary);
    }

    .hero-countdown-mobile {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        width: min(100%, 760px) !important;
        max-width: 100% !important;
        padding: clamp(14px, 2.4vw, 24px) clamp(12px, 2.5vw, 24px) !important;
        box-sizing: border-box;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.14) !important;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.03)) !important;
        box-shadow: 0 16px 30px rgba(0, 0, 0, 0.28);
    }

    .hero-countdown-mobile-label {
        font-size: clamp(9px, 0.82vw, 12px) !important;
        letter-spacing: 0.22em !important;
        margin-bottom: clamp(8px, 1.3vw, 14px) !important;
    }

    .hero-countdown-mobile-timer {
        font-family: 'Inter', sans-serif;
        color: #fff;
        line-height: 1;
        width: 100%;
        max-width: 100%;
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: clamp(6px, 1.4vw, 14px);
        align-items: stretch;
    }

    .hero-countdown-digit {
        min-width: 0 !important;
        width: 100%;
        display: flex !important;
        align-items: center;
        justify-content: center;
        padding: clamp(10px, 1.65vw, 15px) 4px;
        border-radius: clamp(11px, 1.2vw, 16px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        font-size: clamp(1.35rem, 3.2vw, 2.7rem) !important;
        font-weight: 800;
        line-height: 1;
        text-shadow: 0 4px 10px rgba(0, 0, 0, 0.35);
        box-sizing: border-box;
    }

    .hero-countdown-mobile-units {
        width: 100%;
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: clamp(6px, 1.4vw, 14px);
        margin-top: clamp(7px, 0.9vw, 10px) !important;
        opacity: 0.62 !important;
    }

    .hero-countdown-mobile-units span {
        text-align: center;
        font-size: clamp(8px, 0.75vw, 11px) !important;
        letter-spacing: 0.14em !important;
    }

    @media (max-width: 768px) {
        .hero {
            min-height: 100svh;
            height: auto;
            padding-top: 70px;
            padding-bottom: 24px;
        }

        .hero > .container {
            width: 100%;
            padding: 0 8px;
        }

        .hero > .container > span {
            font-size: 10px !important;
            letter-spacing: 2.3px !important;
            margin-bottom: 10px !important;
        }

        .hero > .container > h1 {
            font-size: clamp(1.9rem, 10.8vw, 2.9rem) !important;
            margin-bottom: 10px !important;
            text-wrap: balance;
        }

        .hero-countdown-mobile {
            width: 100% !important;
            padding: 10px 8px !important;
            border-radius: 20px !important;
        }

        .hero-countdown-digit {
            font-size: clamp(0.95rem, 6.4vw, 1.35rem) !important;
            padding: 8px 2px;
        }

        .hero-countdown-mobile-units span {
            font-size: clamp(6px, 2vw, 8px) !important;
            letter-spacing: 0.12em !important;
        }

        .hero-description {
            margin-top: 16px !important;
            max-width: 100% !important;
            font-size: 12px !important;
            line-height: 1.4 !important;
        }
    }

    @media (max-width: 420px) {
        .hero {
            padding-top: 62px;
            padding-bottom: 16px;
        }

        .hero > .container {
            padding: 0 5px;
        }

        .hero > .container > h1 {
            font-size: clamp(1.72rem, 11vw, 2.35rem) !important;
        }

        .hero-countdown-mobile {
            padding: 9px 6px !important;
            border-radius: 16px !important;
        }

        .hero-countdown-mobile-timer,
        .hero-countdown-mobile-units {
            gap: 4px;
        }

        .hero-countdown-digit {
            font-size: clamp(0.82rem, 6.1vw, 1.12rem) !important;
            padding: 7px 1px;
            border-radius: 10px;
        }

        .hero-countdown-mobile-units span {
            font-size: 7px !important;
        }

        .hero-description {
            margin-top: 12px !important;
            font-size: 11px !important;
        }
    }

</style>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    const bgMode = "<?= $bgMode ?>", items = <?= json_encode($bgItems) ?>, single = <?= json_encode($singleBg) ?>;
    const bg1 = document.getElementById("bg1"), bg2 = document.getElementById("bg2"), hero = document.getElementById('hero');
    let currentIdx = 0, targetX = 0, targetY = 0, curX = 0, curY = 0, scrollY = 0;

    function renderBg(el, data) {
        if(!data) return;
        if(data.type === "video") el.innerHTML = `<video autoplay muted loop playsinline><source src="${data.file}"></video>`;
        else { el.innerHTML = ""; el.style.backgroundImage = `url(${data.file})`; }
    }
    renderBg(bg1, bgMode === "slider" ? items[0] : single);

    if(bgMode === "slider" && items.length > 1) {
        setInterval(() => {
            currentIdx = (currentIdx + 1) % items.length;
            const active = bg1.style.opacity == "1" ? bg1 : bg2, next = active === bg1 ? bg2 : bg1;
            renderBg(next, items[currentIdx]);
            next.style.opacity = 1; active.style.opacity = 0;
        }, 9000);
    }

    window.addEventListener("mousemove", e => {
        targetX = (e.clientX / window.innerWidth - 0.5) * 85;
        targetY = (e.clientY / window.innerHeight - 0.5) * 85;
    });

    window.addEventListener("scroll", () => {
    scrollY = window.scrollY;
    if(hero) {
        let scrollPos = scrollY / 400;
        hero.style.opacity = Math.max(0, 1 - scrollPos);
        hero.style.filter = `blur(${Math.min(10, scrollY * 0.05)}px)`;
    }
});

    function animate() {
        curX += (targetX - curX) * 0.04;
        curY += (targetY - curY) * 0.04;
        const transform = `translate3d(${curX}px, ${curY + (scrollY * 0.12)}px, 0) scale(1.25)`;
        bg1.style.transform = transform;
        bg2.style.transform = transform;
        requestAnimationFrame(animate);
    }
    animate();

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('active'); });
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

    const popup = document.getElementById('imagePopup');
    const popupImg = document.getElementById('popupImg');

    function openPopup(src) {
        popupImg.src = src;
        popup.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    popup.addEventListener('click', (e) => {
        if(e.target !== popupImg) {
            popup.classList.remove('active');
            setTimeout(() => { document.body.style.overflow = ''; }, 600);
        }
    });

    new Swiper(".gallerySwiper", {
        loop: true, speed: 1200, autoplay: { delay: 6000, disableOnInteraction: false },
        slidesPerView: 1, spaceBetween: 40,
        pagination: { el: ".swiper-pagination", clickable: true },
    });

    new Swiper(".videoSwiper", {
        loop: false, autoplay: false, slidesPerView: 1, spaceBetween: 30,
        grabCursor: true,
        pagination: { el: ".swiper-pagination", clickable: true },
        breakpoints: { 1024: { slidesPerView: 2 } }
    });

    function startCountdown() {
    const targetDateStr = "<?php echo $conf['countdown_target']; ?>";
    const targetDate = new Date(targetDateStr).getTime();

    const timerContainer = document.querySelector('.hero div[style*="inline-flex"]');

    function update() {
        const now = new Date().getTime();
        const diff = targetDate - now;

        if (diff <= 0) {
            clearInterval(timerInterval);
            
            timerContainer.innerHTML = `
                <div style="text-align: center; animation: deepSlideUp 1s ease forwards; padding: 10px;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 20px;">
                        <span style="width: 12px; height: 12px; background: #ff0000; border-radius: 50%; box-shadow: 0 0 15px #ff0000; animation: pulse 1.5s infinite;"></span>
                        <p style="font-size: 14px; letter-spacing: 4px; font-weight: 800; color: #fff; text-transform: uppercase; margin: 0;">
                            Service is Live Now
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;">
                        <a href="https://www.youtube.com/@gbisalemba/live" target="_blank" style="
                            padding: 14px 28px; background: #e50914; color: #fff; border-radius: 100px; 
                            text-decoration: none; font-size: 12px; font-weight: 700; display: flex; 
                            align-items: center; gap: 10px; transition: 0.3s; box-shadow: 0 10px 20px rgba(229, 9, 20, 0.3);"
                            onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            GABUNG ONLINE
                        </a>
                        
                        <a href="https://maps.app.goo.gl/uXpQ1Z6hFjS2" target="_blank" style="
                            padding: 14px 28px; background: rgba(255,255,255,0.05); color: #fff; border-radius: 100px; 
                            border: 1px solid rgba(255,255,255,0.2); text-decoration: none; font-size: 12px; 
                            font-weight: 700; display: flex; align-items: center; gap: 10px; transition: 0.3s; backdrop-filter: blur(10px);"
                            onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            LOKASI GEREJA
                        </a>
                    </div>
                </div>
            `;
            return;
        }

        const d = Math.floor(diff / (1000 * 60 * 60 * 24));
        const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((diff % (1000 * 60)) / 1000);

        const dVal = document.getElementById("d");
        const hVal = document.getElementById("h");
        const mVal = document.getElementById("m");
        const sVal = document.getElementById("s");

        if(dVal) dVal.textContent = d.toString().padStart(2, '0');
        if(hVal) hVal.textContent = h.toString().padStart(2, '0');
        if(mVal) mVal.textContent = m.toString().padStart(2, '0');
        if(sVal) sVal.textContent = s.toString().padStart(2, '0');
    }

    const timerInterval = setInterval(update, 1000);
    update();
}
document.addEventListener('DOMContentLoaded', startCountdown);

const audio = document.getElementById('bgMusic');
const musicIcon = document.getElementById('musicIcon');
let isPlaying = false;
let autoStarted = false;

const iconPlay = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H2v6h4l5 4V5zM19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07M11 5L6 9H2v6h4l5 4V5z"/></svg>`;
const iconMute = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H2v6h4l5 4V5zM23 9l-6 6M17 9l6 6"/></svg>`;

function fadeInMusic(targetVol = 0.4) {
    if (!audio) return;
    audio.volume = 0;
    audio.play().then(() => {
        let vol = 0;
        let fade = setInterval(() => {
            if (vol < targetVol) {
                vol += 0.02;
                audio.volume = Math.min(vol, targetVol);
            } else {
                clearInterval(fade);
            }
        }, 100);
    }).catch(err => console.log("Menunggu klik nyata user..."));
}

function fadeOutMusic() {
    if (!audio) return;
    let vol = audio.volume;
    let fade = setInterval(() => {
        if (vol > 0.02) {
            vol -= 0.02;
            audio.volume = Math.max(vol, 0);
        } else {
            audio.pause();
            clearInterval(fade);
        }
    }, 50);
}

function toggleMusic() {
    autoStarted = true;
    if (isPlaying) {
        fadeOutMusic();
        musicIcon.innerHTML = iconMute;
    } else {
        fadeInMusic(0.4);
        musicIcon.innerHTML = iconPlay;
    }
    isPlaying = !isPlaying;
}

function handleInitialClick() {
    if (autoStarted || isPlaying) return;

    autoStarted = true;
    isPlaying = true;
    musicIcon.innerHTML = iconPlay;
    fadeInMusic(0.4);

    document.removeEventListener('click', handleInitialClick);
    document.removeEventListener('keydown', handleInitialClick);
    document.removeEventListener('touchstart', handleInitialClick);
}

document.addEventListener('click', handleInitialClick);
document.addEventListener('keydown', handleInitialClick);
document.addEventListener('touchstart', handleInitialClick);

    const toggleBtn = document.getElementById('musicToggle');
    if(toggleBtn) {
        toggleBtn.onmouseover = () => toggleBtn.style.transform = 'scale(1.1) rotate(5deg)';
        toggleBtn.onmouseout = () => toggleBtn.style.transform = 'scale(1) rotate(0deg)';
    }

</script>
</body>
<footer style="margin-top: 100px; padding: 60px 0; text-align: center; position: relative; z-index: 5;">
    <div style="width: 80%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent); margin: 0 auto 40px;"></div>

    <div class="social-footer" style="margin-top: 0; padding-top: 0; border-top: none; margin-bottom: 50px;">
        <div class="social-icons-wrap" style="display: flex; justify-content: center; gap: 20px;">
            <a href="https://www.instagram.com/gbi.salemba/" class="social-btn ig"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg></a>
            <a href="https://www.youtube.com/@gbisalemba" class="social-btn yt"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.42a2.78 2.78 0 0 0-1.94 2C1 8.11 1 12 1 12s0 3.89.42 5.58a2.78 2.78 0 0 0 1.94 2c1.72.42 8.6.42 8.6.42s6.88 0 8.6-.42a2.78 2.78 0 0 0 1.94-2C23 15.89 23 12 23 12s0-3.89-.42-5.58z"></path><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"></polygon></svg></a>
            <a href="https://www.facebook.com/profile.php?id=61583162131557" class="social-btn fb"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
            <a href="https://www.tiktok.com/@gbisalemba?lang=en" class="social-btn tt">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"></path>
                </svg>
            </a>
        </div>
    </div>

    <p style="font-size: 13px; opacity: 0.4; letter-spacing: 2px; font-weight: 300; margin-bottom: 25px;">
        © 2026 GILBERT. ALL RIGHTS RESERVED.
    </p>

    <a href="admin/login.php" class="admin-btn" style="
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 28px;
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 100px;
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    " onmouseover="this.style.transform='translateY(-5px) scale(1.05)'; this.style.borderColor='var(--accent)'; this.style.color='var(--accent)'; this.style.boxShadow='0 15px 35px rgba(147, 197, 253, 0.2)';" 
       onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.borderColor='rgba(255, 255, 255, 0.1)'; this.style.color='rgba(255, 255, 255, 0.6)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.2)';">
        
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
        Admin Portal
    </a>
</footer>
</html>