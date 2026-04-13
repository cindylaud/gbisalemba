<?php
require_once __DIR__ . '/config/database.php';
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
	<section class="section glass-default renungan-detail-section">
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
				?>
				<article class="renungan-detail-card">
					<p class="renungan-kicker">Daily Bread</p>
					<h1 class="renungan-detail-title"><?php echo htmlspecialchars($renungan['judul']); ?></h1>

					<div class="renungan-meta-row">
						<span class="renungan-verse"><?php echo htmlspecialchars($renungan['ayat']); ?></span>
						<span class="renungan-date"><?php echo htmlspecialchars(date('d F Y', strtotime((string) $renungan['tanggal']))); ?></span>
					</div>

					<?php if ($hasImage): ?>
						<div class="renungan-detail-cover-wrap">
							<img class="renungan-detail-cover" src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($renungan['judul']); ?>">
						</div>
					<?php endif; ?>

					<div class="renungan-detail-body">
						<?php echo nl2br(htmlspecialchars((string) $renungan['isi'])); ?>
					</div>

					<a href="renungan.php" class="renungan-read-more">Kembali ke daftar renungan</a>
				</article>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
