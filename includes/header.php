<!DOCTYPE html>
<html lang="id">
<head>
    <?php
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($basePath === '/') {
        $basePath = '';
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GBI Salemba</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- WAJIB ABSOLUTE PATH -->
    <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header>
    <div class="header-nav">
        <div class="container">
            <a href="<?php echo $basePath; ?>/index.php" class="logo" aria-label="Beranda GBI Salemba">
                <img src="<?php echo $basePath; ?>/assets/images/logo/logo%20gbi.png" alt="Logo GBI Salemba" class="logo-img">
                <span class="logo-text">GBI Salemba</span>
            </a>
            <button class="header-menu-toggle" type="button" aria-expanded="false" aria-controls="mainNav" aria-label="Buka menu navigasi">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <nav class="navbar" id="mainNav">
                <ul>
                    <li><a href="<?php echo $basePath; ?>/index.php">Home</a></li>
                    <li><a href="<?php echo $basePath; ?>/about.php">Tentang Kami</a></li>
                    <li><a href="<?php echo $basePath; ?>/pelayanan.php">Pelayanan</a></li>
                    <li><a href="<?php echo $basePath; ?>/jadwal.php">Jadwal</a></li>
                    <li><a href="<?php echo $basePath; ?>/cool.php">COOL</a></li>
                    <li><a href="<?php echo $basePath; ?>/renungan.php">Renungan</a></li>
                    <li><a href="<?php echo $basePath; ?>/formulir.php">Formulir</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<script>
(function () {
    var headerNav = document.querySelector('.header-nav');
    var toggle = document.querySelector('.header-menu-toggle');
    var nav = document.getElementById('mainNav');

    if (!headerNav || !toggle || !nav) {
        return;
    }

    var closeMenu = function () {
        headerNav.classList.remove('nav-open');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('menu-open');
    };

    toggle.addEventListener('click', function () {
        var willOpen = !headerNav.classList.contains('nav-open');
        headerNav.classList.toggle('nav-open', willOpen);
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        document.body.classList.toggle('menu-open', willOpen && window.innerWidth <= 900);
    });

    nav.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            closeMenu();
        });
    });

    document.addEventListener('click', function (event) {
        if (window.innerWidth > 900) {
            return;
        }

        if (!headerNav.contains(event.target)) {
            closeMenu();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) {
            closeMenu();
        }
    });
})();
</script>
