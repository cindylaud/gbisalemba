<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

$baseUrl = '/' . basename(dirname(__DIR__, 2));
$adminBaseUrl = $baseUrl . '/admin';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$defaultTitle = ucwords(str_replace(['-', '_'], ' ', pathinfo($_SERVER['SCRIPT_NAME'] ?? '', PATHINFO_FILENAME)));
$pageTitle = $admin_page_title ?? ($defaultTitle ?: 'Dashboard Admin');
$adminThemePath = dirname(__DIR__, 2) . '/assets/css/admin-theme.css';
$adminThemeVersion = is_file($adminThemePath) ? filemtime($adminThemePath) : time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars($pageTitle); ?> - GBI Salemba</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>/assets/css/admin-theme.css?v=<?php echo urlencode((string) $adminThemeVersion); ?>">
</head>
<body class="admin-theme">
<div class="admin-shell">
	<?php include __DIR__ . '/sidebar.php'; ?>
	<main class="admin-main">
		<header class="admin-topbar">
			<div>
				<h1><?php echo htmlspecialchars($pageTitle); ?></h1>
			</div>
		</header>
		<div class="admin-content">
