<?php
require_once __DIR__ . '/config/database.php';
include __DIR__ . '/includes/header.php';

function gbi_excerpt($text, $limit = 140)
{
    $plain = trim(strip_tags((string) $text));
    if ($plain === '') return '';

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($plain) > $limit ? mb_substr($plain, 0, $limit) . '...' : $plain;
    }

    return strlen($plain) > $limit ? substr($plain, 0, $limit) . '...' : $plain;
}

$renunganItems = [];
$query = $conn->query("SELECT id, judul, isi, ayat, tanggal FROM renungan ORDER BY tanggal DESC, id DESC");

if ($query) {
    while ($row = $query->fetch_assoc()) {
        $renunganItems[] = $row;
    }
}
?>

<main class="renungan-page">

    <!-- HERO -->
    <section class="renungan-hero">
        <div class="renungan-hero-inner">
            <h1>Ruang Teduh</h1>
            <p>Nourish Your Spirit Through God's Word.</p>
        </div>
    </section>

    <!-- CONTENT -->
    <section class="renungan-container">

        <?php if (empty($renunganItems)): ?>

            <div class="renungan-empty">
                Belum ada renungan tersedia saat ini.
            </div>

        <?php else: ?>

            <div class="renungan-flow">

                <?php foreach ($renunganItems as $index => $item): ?>

                    <article class="renungan-card <?= $index === 0 ? 'featured' : '' ?>">

                        <div class="renungan-meta">
                            <span><?= date('d M Y', strtotime($item['tanggal'])) ?></span>
                        </div>

                        <h2 class="renungan-title">
                            <?= htmlspecialchars($item['judul']) ?>
                        </h2>

                        <?php if (!empty($item['ayat'])): ?>
                            <div class="renungan-verse">
                                "<?= htmlspecialchars($item['ayat']) ?>"
                            </div>
                        <?php endif; ?>

                        <p class="renungan-text">
                            <?= htmlspecialchars(gbi_excerpt($item['isi'], 240)) ?>
                        </p>

                        <a class="renungan-link"
                           href="renungan-detail.php?id=<?= (int)$item['id'] ?>">
                            Baca selengkapnya →
                        </a>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

<style>

/* =========================
   BASE (COOL DESIGN SYSTEM)
========================= */

.renungan-page {
    background:
        radial-gradient(circle at top left, rgba(93, 143, 197, 0.2), transparent 24%),
        radial-gradient(circle at right 12%, rgba(73, 120, 175, 0.14), transparent 22%),
        linear-gradient(180deg, #e9f1fb 0%, #dce7f4 52%, #edf4fb 100%);
    font-family: 'Inter', sans-serif;
    color: #12385f;
    padding-bottom: 80px;
}

/* =========================
   HERO
========================= */

.renungan-hero {
    padding: 70px 20px 40px;
    text-align: center;
}

.renungan-hero-inner h1 {
    font-size: 42px;
    margin: 0;
    font-weight: 800;
    color: #12385f;
}

.renungan-hero-inner p {
    margin-top: 10px;
    font-size: 14px;
    color: rgba(18, 56, 95, 0.65);
    letter-spacing: 0.08em;
}

/* =========================
   CONTAINER
========================= */

.renungan-container {
    max-width: 760px;
    margin: 0 auto;
    padding: 0 20px;
}

.renungan-intro {
    text-align: center;
    margin-bottom: 30px;
    color: rgba(18, 56, 95, 0.6);
}

/* =========================
   FLOW (NO MORE KAKU LIST)
========================= */

.renungan-flow {
    display: flex;
    flex-direction: column;
    gap: 26px;
}

/* =========================
   CARD (SOFT + BREATHABLE)
========================= */

.renungan-card {
    background: rgba(236, 244, 253, 0.78);
    border: 1px solid rgba(18, 56, 95, 0.06);
    backdrop-filter: blur(14px);
    border-radius: 22px;
    padding: 28px;
    box-shadow: 0 10px 30px rgba(18, 45, 73, 0.08);
    transition: all 0.35s ease;
}

.renungan-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 18px 50px rgba(18, 45, 73, 0.12);
}

/* =========================
   FEATURED CARD (HIERARCHY)
========================= */

.renungan-card.featured {
    background: rgba(236, 244, 253, 0.95);
    border-left: 4px solid #5f95c7;
    padding: 34px;
}

/* =========================
   META DATE
========================= */

.renungan-meta span {
    font-size: 11px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(18, 56, 95, 0.55);
}

/* =========================
   TITLE
========================= */

.renungan-title {
    margin: 10px 0 10px;
    font-size: 26px;
    line-height: 1.4;
    color: #12385f;
    font-weight: 800;
}

/* =========================
   VERSE
========================= */

.renungan-verse {
    font-style: italic;
    color: rgba(49, 85, 121, 0.75);
    margin-bottom: 12px;
}

/* =========================
   TEXT
========================= */

.renungan-text {
    font-size: 15px;
    line-height: 1.9;
    color: rgba(18, 56, 95, 0.78);
}

/* =========================
   LINK (SMOOTH UX)
========================= */

.renungan-link {
    display: inline-block;
    margin-top: 16px;
    font-weight: 600;
    color: #5f95c7;
    text-decoration: none;
    transition: 0.2s;
}

.renungan-link:hover {
    color: #356ba2;
    transform: translateX(4px);
}

/* =========================
   EMPTY STATE
========================= */

.renungan-empty {
    text-align: center;
    padding: 60px 20px;
    color: rgba(18, 56, 95, 0.5);
}

/* =========================
   MOBILE
========================= */

@media (max-width: 768px) {

    .renungan-hero-inner h1 {
        font-size: 30px;
    }

    .renungan-card {
        padding: 18px;
        border-radius: 16px;
    }

    .renungan-card.featured {
        padding: 22px;
    }

    .renungan-title {
        font-size: 20px;
    }

    .renungan-text {
        font-size: 14px;
    }
}

</style>

<?php include __DIR__ . '/includes/footer.php'; ?>