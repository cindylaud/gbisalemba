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
<section class="dashboard-workspace dashboard-workspace-single">
    <div class="dashboard-main-column">
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

    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

