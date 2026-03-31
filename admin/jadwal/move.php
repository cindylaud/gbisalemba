<?php
require_once __DIR__ . '/../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? '';
$dir = ($action === 'down') ? 'down' : 'up';

$target = '../jadwal_ibadah/move.php?dir=' . urlencode($dir);
if ($id > 0) {
    $target .= '&id=' . $id;
}

header('Location: ' . $target);
exit;

// Check if ID and action are provided
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['action'])) {
    header("Location: index.php?error=Parameter tidak valid");
    exit;
}

$id = (int)$_GET['id'];
$action = $_GET['action'];

// Validate action
if ($action !== 'up' && $action !== 'down') {
    header("Location: index.php?error=Aksi tidak valid");
    exit;
}

// Get current jadwal data
$stmt_current = $conn->prepare("SELECT id, urutan FROM jadwal WHERE id = ?");
$stmt_current->bind_param("i", $id);
$stmt_current->execute();
$result_current = $stmt_current->get_result();

if ($result_current->num_rows == 0) {
    $stmt_current->close();
    header("Location: index.php?error=Jadwal tidak ditemukan");
    exit;
}

$current = $result_current->fetch_assoc();
$current_urutan = $current['urutan'];
$stmt_current->close();

// Find target jadwal to swap with
if ($action == 'up') {
    // Find jadwal with urutan less than current (previous item)
    $stmt_target = $conn->prepare("SELECT id, urutan FROM jadwal WHERE urutan < ? ORDER BY urutan DESC LIMIT 1");
    $stmt_target->bind_param("i", $current_urutan);
} else {
    // Find jadwal with urutan greater than current (next item)
    $stmt_target = $conn->prepare("SELECT id, urutan FROM jadwal WHERE urutan > ? ORDER BY urutan ASC LIMIT 1");
    $stmt_target->bind_param("i", $current_urutan);
}

$stmt_target->execute();
$result_target = $stmt_target->get_result();

// Check if target exists
if ($result_target->num_rows == 0) {
    $stmt_target->close();
    $message = ($action == 'up') ? 'Jadwal sudah berada di posisi paling atas' : 'Jadwal sudah berada di posisi paling bawah';
    header("Location: index.php?error={$message}");
    exit;
}

$target = $result_target->fetch_assoc();
$target_id = $target['id'];
$target_urutan = $target['urutan'];
$stmt_target->close();

// Start transaction
$conn->begin_transaction();

try {
    // Swap urutan - use temporary value to avoid unique constraint issues
    $temp_urutan = -1;
    
    // Step 1: Set current to temporary value
    $stmt1 = $conn->prepare("UPDATE jadwal SET urutan = ? WHERE id = ?");
    $stmt1->bind_param("ii", $temp_urutan, $id);
    $stmt1->execute();
    $stmt1->close();
    
    // Step 2: Set target to current's old urutan
    $stmt2 = $conn->prepare("UPDATE jadwal SET urutan = ? WHERE id = ?");
    $stmt2->bind_param("ii", $current_urutan, $target_id);
    $stmt2->execute();
    $stmt2->close();
    
    // Step 3: Set current to target's old urutan
    $stmt3 = $conn->prepare("UPDATE jadwal SET urutan = ? WHERE id = ?");
    $stmt3->bind_param("ii", $target_urutan, $id);
    $stmt3->execute();
    $stmt3->close();
    
    // Commit transaction
    $conn->commit();
    
    header("Location: index.php?success=Urutan jadwal berhasil diubah");
    exit;
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    header("Location: index.php?error=Gagal mengubah urutan: " . $e->getMessage());
    exit;
}



