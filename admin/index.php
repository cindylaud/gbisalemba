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

$admin_page_title = 'Dashboard Admin';
include __DIR__ . '/includes/header.php';
?>
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

<div class="dashboard-grid" id="dashboardGrid">
    <a class="dashboard-card" data-category="konten" href="slider.php">
        <h3><i class="fa-solid fa-images mr-2"></i>Slider</h3>
        <p>Atur banner hero beranda.</p>
    </a>
    <a class="dashboard-card" data-category="konten" href="whatsnew.php">
        <h3><i class="fa-solid fa-bullhorn mr-2"></i>Coming Soon</h3>
        <p>Update informasi terbaru jemaat.</p>
    </a>
    <a class="dashboard-card" data-category="layanan" href="jadwal_ibadah/index.php">
        <h3><i class="fa-solid fa-calendar-days mr-2"></i>Jadwal</h3>
        <p>Kelola jadwal ibadah dan kegiatan.</p>
    </a>
    <a class="dashboard-card" data-category="layanan" href="pelayanan.php">
        <h3><i class="fa-solid fa-hands-praying mr-2"></i>Pelayanan</h3>
        <p>Atur daftar bidang pelayanan.</p>
    </a>
    <a class="dashboard-card" data-category="konten" href="renungan.php">
        <h3><i class="fa-solid fa-book-open mr-2"></i>Renungan</h3>
        <p>Publikasikan renungan harian.</p>
    </a>
    <a class="dashboard-card" data-category="layanan" href="formulir.php">
        <h3><i class="fa-solid fa-file-lines mr-2"></i>Formulir</h3>
        <p>Kelola formulir unduhan jemaat.</p>
    </a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

