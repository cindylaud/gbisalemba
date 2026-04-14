<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/renungan-richtext.php';

$pageBodyClass = 'renungan-detail-page';
include __DIR__ . '/includes/header.php';

$id = (int) ($_GET['id'] ?? 0);
$renungan = null;

if ($id > 0) {
	$stmt = $conn->prepare('SELECT id, judul, isi, ayat, tanggal, gambar FROM renungan WHERE id = ? LIMIT 1');
	if ($stmt) {
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$result = $stmt->get_result();
		$renungan = $result ? $result->fetch_assoc() : null;
		$stmt->close();
	}
}
?>

<main class="main-content renungan-detail-main">
	<section class="section renungan-detail-section">
		<div class="container-large">
			<?php if (!$renungan): ?>
				<div class="renungan-empty-state">
					<p>Renungan tidak ditemukan.</p>
					<a class="renungan-read-more" href="renungan.php">Kembali ke daftar renungan</a>
				</div>
			<?php else: ?>
				<?php
				$imageUrl = !empty($renungan['gambar']) ? 'uploads/renungan/' . $renungan['gambar'] : '';
				$hasImage = $imageUrl !== '' && is_file(__DIR__ . '/' . $imageUrl);
				$displayDate = date('d F Y', strtotime((string) $renungan['tanggal']));
				$ayat = trim((string) ($renungan['ayat'] ?? ''));
				?>
				<article class="renungan-detail-card">
					<div class="renungan-detail-header">
						<p class="renungan-kicker">Renungan Harian</p>
						<h1 class="renungan-detail-title"><?php echo htmlspecialchars($renungan['judul']); ?></h1>
						<p class="renungan-date renungan-detail-date"><?php echo htmlspecialchars($displayDate); ?></p>
					</div>

					<?php if ($ayat !== ''): ?>
						<div class="renungan-meta-row">
							<span class="renungan-verse"><?php echo htmlspecialchars($ayat); ?></span>
							<span class="renungan-detail-meta-sep" aria-hidden="true"></span>
						</div>
					<?php endif; ?>

					<?php if ($hasImage): ?>
						<div class="renungan-detail-cover-wrap">
							<img class="renungan-detail-cover" src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($renungan['judul']); ?>">
						</div>
					<?php endif; ?>

					<div class="renungan-detail-body">
						<?php echo gbi_render_renungan_body($renungan['isi']); ?>
					</div>

					<div class="renungan-detail-footer">
						<a href="renungan.php" class="renungan-read-more">Kembali ke daftar renungan</a>
					</div>
				</article>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
