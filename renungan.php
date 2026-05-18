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

// Pagination: 5 items per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

// Total rows (for page count)
$totalRows = 0;
if ($countRes = $conn->query("SELECT COUNT(*) AS total FROM renungan")) {
    $r = $countRes->fetch_assoc();
    $totalRows = isset($r['total']) ? (int) $r['total'] : 0;
}
$totalPages = $perPage > 0 ? (int) ceil($totalRows / $perPage) : 1;

// Fetch page items (use prepared statement; fallback to direct query)
$stmt = $conn->prepare("SELECT id, judul, isi, ayat, tanggal FROM renungan ORDER BY tanggal DESC, id DESC LIMIT ?, ?");
if ($stmt) {
    $stmt->bind_param("ii", $offset, $perPage);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $renunganItems[] = $row;
    }
    $stmt->close();
} else {
    $query = $conn->query("SELECT id, judul, isi, ayat, tanggal FROM renungan ORDER BY tanggal DESC, id DESC LIMIT $offset, $perPage");
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $renunganItems[] = $row;
        }
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

            <?php if ($totalPages > 1): ?>
                <nav class="renungan-pagination" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a class="prev" href="?page=<?= $page - 1 ?>">« Sebelumnya</a>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a class="page-num <?= $p === $page ? 'active' : '' ?>" href="?page=<?= $p ?>"><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a class="next" href="?page=<?= $page + 1 ?>">Berikutnya »</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>

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
@import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&display=swap');

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
    letter-spacing: 0.6px;
    text-shadow: 0 2px 8px rgba(18, 56, 95, 0.1);
    position: relative;
}

.renungan-hero-inner h1::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, transparent, #5f95c7, transparent);
    border-radius: 2px;
}

.renungan-hero-inner p {
    margin: 28px auto 0;
    max-width: 960px;
    font-family: 'Dancing Script', cursive;
    font-size: clamp(22px, 3.8vw, 38px);
    line-height: 1.05;
    font-weight: 700;
    color: rgba(18, 56, 95, 0.75);
    letter-spacing: 0;
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
    background: linear-gradient(135deg, rgba(236, 244, 253, 0.85), rgba(244, 249, 255, 0.7));
    border: 1px solid rgba(18, 56, 95, 0.08);
    border-left: 3px solid rgba(95, 149, 199, 0.3);
    backdrop-filter: blur(14px);
    border-radius: 22px;
    padding: 28px;
    box-shadow: 0 10px 30px rgba(18, 45, 73, 0.08);
    transition: all 0.35s ease;
    position: relative;
    overflow: hidden;
}

.renungan-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: -40%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(93, 143, 197, 0.08), transparent);
    border-radius: 50%;
    pointer-events: none;
}

.renungan-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 50px rgba(18, 45, 73, 0.15);
    border-left-color: rgba(95, 149, 199, 0.5);
    background: linear-gradient(135deg, rgba(236, 244, 253, 0.95), rgba(244, 249, 255, 0.85));
}

/* =========================
   FEATURED CARD (SAME AS REGULAR)
========================= */

.renungan-card.featured {
    /* Styling identical to regular cards */
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
    margin: 12px 0 12px;
    font-size: 26px;
    line-height: 1.4;
    color: #12385f;
    font-weight: 800;
    position: relative;
    padding-left: 22px;
}

.renungan-title::before {
    content: '✦';
    position: absolute;
    left: 0;
    color: #5f95c7;
    font-size: 18px;
    opacity: 0.6;
}

/* =========================
   VERSE
========================= */

.renungan-verse {
    font-style: italic;
    color: rgba(49, 85, 121, 0.75);
    margin-bottom: 14px;
    padding-left: 14px;
    border-left: 2px solid rgba(95, 149, 199, 0.3);
    padding-bottom: 0;
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

/* Pagination */
.renungan-pagination {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin: 32px 0 12px;
    flex-wrap: wrap;
}
.renungan-pagination a {
    padding: 8px 12px;
    border-radius: 8px;
    background: transparent;
    color: #12385f;
    text-decoration: none;
    border: 1px solid rgba(18,56,95,0.06);
    font-weight: 600;
}
.renungan-pagination a.active {
    background: #5f95c7;
    color: #fff;
    border-color: transparent;
}
.renungan-pagination a:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(18,45,73,0.06);
}

/* =========================
   MOBILE
========================= */

@media (max-width: 768px) {

    .renungan-hero-inner h1 {
        font-size: 30px;
        letter-spacing: 0.4px;
    }

    .renungan-hero-inner h1::after {
        width: 45px;
        height: 2px;
    }

    .renungan-hero-inner p {
        font-size: clamp(16px, 6.5vw, 28px);
        line-height: 1.08;
        margin-top: 24px;
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