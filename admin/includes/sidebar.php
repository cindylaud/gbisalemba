<?php
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$adminPos = strpos($scriptPath, '/admin/');
$baseUrl = $adminPos !== false
	? substr($scriptPath, 0, $adminPos)
	: rtrim(str_replace('\\', '/', dirname($scriptPath)), '/');
if ($baseUrl === '/') {
	$baseUrl = '';
}
$adminBaseUrl = $baseUrl . '/admin';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

function isMenuActive($currentPath, $href) {
	if (strpos($href, '/jadwal_ibadah/index.php') !== false && strpos($currentPath, '/jadwal/') !== false) {
		return true;
	}

	if ($currentPath === $href) {
		return true;
	}

	if (substr($href, -10) === '/index.php') {
		$sectionPrefix = substr($href, 0, -9);
		return strpos($currentPath, $sectionPrefix . '/') === 0;
	}

	return false;
}

$menuItems = [
	['href' => $adminBaseUrl . '/index.php', 'icon' => 'fa-gauge', 'label' => 'Dashboard'],
	['href' => $adminBaseUrl . '/slider.php', 'icon' => 'fa-images', 'label' => 'Kelola Slider'],
	['href' => $adminBaseUrl . '/whatsnew.php', 'icon' => 'fa-bullhorn', 'label' => 'Kelola Coming Soon'],
	['href' => $adminBaseUrl . '/jadwal_ibadah/index.php', 'icon' => 'fa-calendar-days', 'label' => 'Kelola Jadwal'],
	['href' => $adminBaseUrl . '/pelayanan.php', 'icon' => 'fa-hands-praying', 'label' => 'Kelola Pelayanan'],
	['href' => $adminBaseUrl . '/renungan.php', 'icon' => 'fa-book-open', 'label' => 'Kelola Renungan'],
	['href' => $adminBaseUrl . '/formulir.php', 'icon' => 'fa-file-lines', 'label' => 'Kelola Formulir'],
	['href' => $adminBaseUrl . '/logout.php', 'icon' => 'fa-right-from-bracket', 'label' => 'Logout'],
];
?>
<aside class="admin-sidebar">
	<div class="admin-brand">
		<img src="<?php echo htmlspecialchars($baseUrl); ?>/assets/images/logo/logo%20gbi.png" alt="Logo GBI">
		<div>
			<p class="admin-brand-title">GBI Salemba</p>
		</div>
	</div>

	<ul class="admin-nav">
		<?php foreach ($menuItems as $item): ?>
			<?php $isActive = isMenuActive($currentPath, $item['href']); ?>
			<li>
				<a href="<?php echo htmlspecialchars($item['href']); ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
					<i class="fa-solid <?php echo htmlspecialchars($item['icon']); ?>"></i>
					<span><?php echo htmlspecialchars($item['label']); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</aside>
