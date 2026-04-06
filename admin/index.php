<?php
include __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';

function dashboardCount(mysqli $conn, string $table, string $where = ''): int {
    $sql = "SELECT COUNT(*) AS total FROM {$table}" . ($where !== '' ? " WHERE {$where}" : '');
    try {
        $res = $conn->query($sql);
    } catch (mysqli_sql_exception $e) {
        return 0;
    }
    if (!$res) {
        return 0;
    }
    $row = $res->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}

$stats = [
    'slider_active' => dashboardCount($conn, 'slider', 'is_active = 1'),
    'coming_soon_active' => dashboardCount($conn, 'coming_soon', 'is_active = 1'),
    'jadwal_active' => dashboardCount($conn, 'jadwal_ibadah', 'is_active = 1'),
    'pelayanan_active' => dashboardCount($conn, 'pelayanan', "status = 'aktif'"),
    'renungan_total' => dashboardCount($conn, 'renungan'),
    'formulir_active' => dashboardCount($conn, 'formulir', "status = 'aktif'"),
];

$dashboard_date = date('l, d F Y');
$dashboard_time = date('H:i');
$dashboard_month_label = date('F Y');
$dashboard_first_day = (int) date('N', strtotime(date('Y-m-01')));
$dashboard_days_in_month = (int) date('t');

$dashboard_weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
$dashboard_calendar_days = [];
for ($day = 1; $day <= $dashboard_days_in_month; $day++) {
    $dashboard_calendar_days[] = $day;
}

$dashboard_activity_items = [
    ['icon' => 'fa-images', 'title' => 'Slider', 'text' => $stats['slider_active'] . ' banner aktif siap tampil di beranda'],
    ['icon' => 'fa-calendar-days', 'title' => 'Jadwal', 'text' => $stats['jadwal_active'] . ' jadwal ibadah sedang aktif'],
    ['icon' => 'fa-file-lines', 'title' => 'Formulir', 'text' => $stats['formulir_active'] . ' formulir aktif tersedia untuk jemaat'],
    ['icon' => 'fa-book-open', 'title' => 'Renungan', 'text' => $stats['renungan_total'] . ' konten renungan telah dipublikasikan'],
];

$admin_page_title = 'Dashboard Admin';
include __DIR__ . '/includes/header.php';
?>
<section class="dashboard-workspace">
    <div class="dashboard-main-column">
        <section class="dashboard-hero mb-4">
            <div>
                <p class="dashboard-kicker">GBI Salemba Ministry Panel</p>
                <h2 class="dashboard-hero-title">Kelola konten gereja dengan tampilan yang lebih rapi, cepat, dan nyaman.</h2>
                <p class="dashboard-hero-sub">Dashboard ini merangkum aktivitas utama untuk slider, jadwal, pelayanan, renungan, dan formulir dalam satu panel yang mudah dipantau.</p>
            </div>
            <div class="dashboard-live">
                <div class="dashboard-live-label">Waktu Sekarang</div>
                <div class="dashboard-live-time"><?php echo htmlspecialchars($dashboard_time); ?></div>
                <div class="dashboard-live-date"><?php echo htmlspecialchars($dashboard_date); ?></div>
            </div>
        </section>

        <section class="dashboard-stat-grid mb-4">
            <article class="dashboard-stat-card">
                <span class="label">Slider Aktif</span>
                <strong><?php echo $stats['slider_active']; ?></strong>
            </article>
            <article class="dashboard-stat-card">
                <span class="label">Coming Soon Aktif</span>
                <strong><?php echo $stats['coming_soon_active']; ?></strong>
            </article>
            <article class="dashboard-stat-card">
                <span class="label">Jadwal Aktif</span>
                <strong><?php echo $stats['jadwal_active']; ?></strong>
            </article>
            <article class="dashboard-stat-card">
                <span class="label">Pelayanan Aktif</span>
                <strong><?php echo $stats['pelayanan_active']; ?></strong>
            </article>
            <article class="dashboard-stat-card">
                <span class="label">Renungan</span>
                <strong><?php echo $stats['renungan_total']; ?></strong>
            </article>
            <article class="dashboard-stat-card">
                <span class="label">Formulir Aktif</span>
                <strong><?php echo $stats['formulir_active']; ?></strong>
            </article>
        </section>

        <div class="dashboard-toolbar mb-3">
            <input type="search" id="dashboardSearch" class="dashboard-search" placeholder="Cari menu admin...">
            <div class="dashboard-filter-chips" role="tablist" aria-label="Filter dashboard">
                <button type="button" class="chip active" data-filter="all">Semua</button>
                <button type="button" class="chip" data-filter="konten">Konten</button>
                <button type="button" class="chip" data-filter="layanan">Layanan</button>
            </div>
        </div>

        <div class="dashboard-grid" id="dashboardGrid">
            <a class="dashboard-card" data-category="konten" data-title="slider" href="slider.php">
                <h3><i class="fa-solid fa-images mr-2"></i>Slider</h3>
                <p>Atur banner hero beranda.</p>
            </a>
            <a class="dashboard-card" data-category="konten" data-title="coming soon" href="whatsnew.php">
                <h3><i class="fa-solid fa-bullhorn mr-2"></i>Coming Soon</h3>
                <p>Update informasi terbaru jemaat.</p>
            </a>
            <a class="dashboard-card" data-category="layanan" data-title="jadwal" href="jadwal_ibadah/index.php">
                <h3><i class="fa-solid fa-calendar-days mr-2"></i>Jadwal</h3>
                <p>Kelola jadwal ibadah dan kegiatan.</p>
            </a>
            <a class="dashboard-card" data-category="layanan" data-title="pelayanan" href="pelayanan.php">
                <h3><i class="fa-solid fa-hands-praying mr-2"></i>Pelayanan</h3>
                <p>Atur daftar bidang pelayanan.</p>
            </a>
            <a class="dashboard-card" data-category="konten" data-title="renungan" href="renungan.php">
                <h3><i class="fa-solid fa-book-open mr-2"></i>Renungan</h3>
                <p>Publikasikan renungan harian.</p>
            </a>
            <a class="dashboard-card" data-category="layanan" data-title="formulir" href="formulir.php">
                <h3><i class="fa-solid fa-file-lines mr-2"></i>Formulir</h3>
                <p>Kelola formulir unduhan jemaat.</p>
            </a>
        </div>

        <div id="dashboardEmpty" class="dashboard-empty" hidden>Tidak ada menu yang cocok dengan pencarian saat ini.</div>
    </div>

    <aside class="dashboard-side-column">
        <section class="dashboard-side-card dashboard-profile-card">
            <div class="dashboard-profile-header">
                <div>
                    <p class="dashboard-side-eyebrow">Administrator</p>
                    <h3>Halo, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></h3>
                    <p class="dashboard-side-note">Panel pengelolaan konten gereja.</p>
                </div>
                <div class="dashboard-avatar">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
            </div>
            <div class="dashboard-profile-metrics">
                <div>
                    <span>Total Menu</span>
                    <strong>6</strong>
                </div>
                <div>
                    <span>Panel Aktif</span>
                    <strong>1</strong>
                </div>
            </div>
        </section>

        <section class="dashboard-side-card">
            <div class="dashboard-calendar-head">
                <div>
                    <p class="dashboard-side-eyebrow">Kalender Mini</p>
                    <h4><?php echo htmlspecialchars($dashboard_month_label); ?></h4>
                </div>
                <span class="dashboard-calendar-chip">Hari ini</span>
            </div>
            <div class="dashboard-calendar-grid">
                <?php foreach ($dashboard_weekdays as $weekday): ?>
                    <span class="dashboard-calendar-weekday"><?php echo htmlspecialchars($weekday); ?></span>
                <?php endforeach; ?>

                <?php for ($i = 1; $i < $dashboard_first_day; $i++): ?>
                    <span class="dashboard-calendar-day is-empty"></span>
                <?php endfor; ?>

                <?php foreach ($dashboard_calendar_days as $day): ?>
                    <span class="dashboard-calendar-day <?php echo $day === (int) date('j') ? 'is-today' : ''; ?>"><?php echo $day; ?></span>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dashboard-side-card">
            <div class="dashboard-activity-head">
                <div>
                    <p class="dashboard-side-eyebrow">Aktivitas</p>
                    <h4>Ringkasan Terbaru</h4>
                </div>
                <span class="dashboard-calendar-chip">Live</span>
            </div>
            <div class="dashboard-activity-list">
                <?php foreach ($dashboard_activity_items as $activity): ?>
                    <div class="dashboard-activity-item">
                        <div class="dashboard-activity-icon">
                            <i class="fa-solid <?php echo htmlspecialchars($activity['icon']); ?>"></i>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                            <p><?php echo htmlspecialchars($activity['text']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </aside>
</section>

<script>
(function () {
    const searchInput = document.getElementById('dashboardSearch');
    const chips = Array.from(document.querySelectorAll('.dashboard-filter-chips .chip'));
    const cards = Array.from(document.querySelectorAll('#dashboardGrid .dashboard-card'));
    const emptyState = document.getElementById('dashboardEmpty');
    let activeFilter = 'all';

    function applyFilters() {
        const query = (searchInput?.value || '').trim().toLowerCase();
        let visibleCount = 0;

        cards.forEach(card => {
            const category = (card.dataset.category || '').toLowerCase();
            const title = (card.dataset.title || card.textContent || '').toLowerCase();
            const matchesCategory = activeFilter === 'all' || category === activeFilter;
            const matchesQuery = query === '' || title.includes(query);
            const visible = matchesCategory && matchesQuery;
            card.style.display = visible ? '' : 'none';
            if (visible) visibleCount += 1;
        });

        if (emptyState) {
            emptyState.hidden = visibleCount !== 0;
        }
    }

    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            activeFilter = chip.dataset.filter || 'all';
            chips.forEach(btn => btn.classList.toggle('active', btn === chip));
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    applyFilters();
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

