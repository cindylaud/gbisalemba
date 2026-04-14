<?php
session_start();
session_destroy();

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/admin/logout.php');
$adminPos = strpos($scriptPath, '/admin/');
$basePath = $adminPos !== false ? substr($scriptPath, 0, $adminPos) : rtrim(dirname($scriptPath), '/');
if ($basePath === '/') {
	$basePath = '';
}

header('Location: ' . $basePath . '/index.php');
exit();
?>

