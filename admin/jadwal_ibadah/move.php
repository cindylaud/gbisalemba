<?php
require_once '../includes/auth.php';
require_once '../../config/database.php';

// Check if column 'urutan' exists
$has_urutan_column = false;
$check_columns = $conn->query("SHOW COLUMNS FROM jadwal_ibadah");
while ($col = $check_columns->fetch_assoc()) {
    if ($col['Field'] == 'urutan') {
        $has_urutan_column = true;
        break;
    }
}

// If urutan column doesn't exist, redirect
if (!$has_urutan_column) {
    header("Location: index.php?error=Fitur move tidak tersedia (kolom urutan tidak ada)");
    exit;
}

// Check if ID and direction are provided
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['dir'])) {
    header("Location: index.php?error=Parameter tidak valid");
    exit;
}

$id = (int)$_GET['id'];
$dir = $_GET['dir'];

// Validate direction
if ($dir !== 'up' && $dir !== 'down') {
    header("Location: index.php?error=Arah tidak valid");
    exit;
}

// Get current jadwal data
$stmt_current = $conn->prepare("SELECT id, urutan FROM jadwal_ibadah WHERE id = ?");
$stmt_current->bind_param("i", $id);
$stmt_current->execute();
$result_current = $stmt_current->get_result();

if ($result_current->num_rows == 0) {
    $stmt_current->close();
    header("Location: index.php?error=Jadwal ibadah tidak ditemukan");
    exit;
}

$current = $result_current->fetch_assoc();
$current_urutan = $current['urutan'];
$stmt_current->close();

// Find target jadwal to swap with
if ($dir == 'up') {
    // Find jadwal with urutan less than current (previous item)
    // ORDER BY urutan DESC to get the largest urutan that is still less than current
    $stmt_target = $conn->prepare("SELECT id, urutan FROM jadwal_ibadah WHERE urutan < ? ORDER BY urutan DESC LIMIT 1");
    $stmt_target->bind_param("i", $current_urutan);
} else {
    // Find jadwal with urutan greater than current (next item)
    // ORDER BY urutan ASC to get the smallest urutan that is still greater than current
    $stmt_target = $conn->prepare("SELECT id, urutan FROM jadwal_ibadah WHERE urutan > ? ORDER BY urutan ASC LIMIT 1");
    $stmt_target->bind_param("i", $current_urutan);
}

$stmt_target->execute();
$result_target = $stmt_target->get_result();

// Check if target exists
if ($result_target->num_rows == 0) {
    $stmt_target->close();
    $message = ($dir == 'up') ? 'Jadwal ibadah sudah berada di posisi paling atas' : 'Jadwal ibadah sudah berada di posisi paling bawah';
    header("Location: index.php?error={$message}");
    exit;
}

$target = $result_target->fetch_assoc();
$target_id = $target['id'];
$target_urutan = $target['urutan'];
$stmt_target->close();

// Start transaction for safe swap
$conn->begin_transaction();

try {
    // Swap urutan - use temporary value to avoid unique constraint issues if any
    $temp_urutan = -999999;
    
    // Step 1: Set current to temporary value
    $stmt1 = $conn->prepare("UPDATE jadwal_ibadah SET urutan = ? WHERE id = ?");
    $stmt1->bind_param("ii", $temp_urutan, $id);
    $stmt1->execute();
    $stmt1->close();
    
    // Step 2: Set target to current's old urutan
    $stmt2 = $conn->prepare("UPDATE jadwal_ibadah SET urutan = ? WHERE id = ?");
    $stmt2->bind_param("ii", $current_urutan, $target_id);
    $stmt2->execute();
    $stmt2->close();
    
    // Step 3: Set current to target's old urutan
    $stmt3 = $conn->prepare("UPDATE jadwal_ibadah SET urutan = ? WHERE id = ?");
    $stmt3->bind_param("ii", $target_urutan, $id);
    $stmt3->execute();
    $stmt3->close();
    
    // Commit transaction
    $conn->commit();
    
    header("Location: index.php?success=Urutan jadwal ibadah berhasil diubah");
    exit;
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    header("Location: index.php?error=Gagal mengubah urutan: " . htmlspecialchars($e->getMessage()));
    exit;
}
