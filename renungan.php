<?php
require_once __DIR__ . '/config/database.php';
include __DIR__ . '/includes/header.php';

if (!function_exists('gbi_excerpt')) {
	function gbi_excerpt($text, $limit = 140)
	{
		$plain = trim(strip_tags((string) $text));
		if ($plain === '') {
			return '';
		}

		if (function_exists('mb_strlen') && function_exists('mb_substr')) {
			return mb_strlen($plain) > $limit ? mb_substr($plain, 0, $limit) . '...' : $plain;
		}

		return strlen($plain) > $limit ? substr($plain, 0, $limit) . '...' : $plain;
	}
}

$renunganItems = [];
$query = $conn->query('SELECT id, judul, isi, ayat, tanggal FROM renungan ORDER BY tanggal DESC, id DESC');
if ($query) {
	while ($row = $query->fetch_assoc()) {
		$renunganItems[] = $row;
	}
}
?>

<main class="main-content renungan-page-main renungan-cool-page">
	<section class="section glass-default renungan-page-section">
		<div class="container-large">
			<div class="renungan-heading-wrap">
				<h1 class="renungan-page-title">Renungan Harian</h1>
			</div>

			<?php if (empty($renunganItems)): ?>
				<div class="renungan-empty-state">
					<p>Belum ada renungan tersedia saat ini.</p>
				</div>
			<?php else: ?>
				<div class="renungan-list-layout">
					<?php foreach ($renunganItems as $item): ?>
						<article class="renungan-list-card reveal-on-scroll" data-reveal="up">
							<div class="renungan-list-date" aria-label="Tanggal renungan">
								<strong><?php echo htmlspecialchars(date('d', strtotime((string) $item['tanggal']))); ?></strong>
								<small><?php echo htmlspecialchars(strtoupper(date('F Y', strtotime((string) $item['tanggal'])))); ?></small>
							</div>
							<div class="renungan-list-content">
								<h2 class="renungan-card-title"><?php echo htmlspecialchars($item['judul']); ?></h2>
								<p class="renungan-excerpt"><?php echo htmlspecialchars(gbi_excerpt($item['isi'], 180)); ?></p>
								<a href="renungan-detail.php?id=<?php echo (int) $item['id']; ?>" class="renungan-read-more">Selengkapnya</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<script>
(function () {
	var items = document.querySelectorAll('.reveal-on-scroll[data-reveal="up"]');
	if (!('IntersectionObserver' in window) || !items.length) {
		return;
	}

	var observer = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (entry.isIntersecting) {
				entry.target.classList.add('is-visible');
				observer.unobserve(entry.target);
			}
		});
	}, { threshold: 0.15 });

	items.forEach(function (item) {
		observer.observe(item);
	});
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
