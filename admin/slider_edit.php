<?php
session_start();
require_once '../config/database.php';

// Check auth
if (!isset($_SESSION['admin_id'])) {
    header('Location: /gbisalemba/admin/login.php');
    exit;
}

$slider_id = intval($_GET['id'] ?? 0);

if ($slider_id == 0) {
    header('Location: /gbisalemba/admin/slider.php');
    exit;
}

// Get slider data
$stmt = $conn->prepare("SELECT id, title, subtitle, image, urutan, is_active FROM slider WHERE id = ?");
$stmt->bind_param("i", $slider_id);
$stmt->execute();
$result = $stmt->get_result();
$slider = $result->fetch_assoc();

if (!$slider) {
    header('Location: /gbisalemba/admin/slider.php');
    exit;
}

// Pass to slider.php as edit
$_GET['edit_id'] = $slider_id;
include 'slider.php';
