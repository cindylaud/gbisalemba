<?php
// Setup database untuk Pelayanan
require_once 'config/database.php';

// 1. Create table pelayanan
$sql_create = "CREATE TABLE IF NOT EXISTS pelayanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT NOT NULL,
    foto VARCHAR(255),
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql_create) === TRUE) {
    echo "✓ Tabel 'pelayanan' berhasil dibuat/sudah ada.<br>";
} else {
    echo "✗ Error create table: " . $conn->error . "<br>";
}

// 2. Check if table already has data
$check = $conn->query("SELECT COUNT(*) as cnt FROM pelayanan");
$row = $check->fetch_assoc();

if ($row['cnt'] == 0) {
    // 3. Insert initial data
    $sql_insert = "INSERT INTO pelayanan (judul, deskripsi, status, urutan) VALUES 
    ('Pelayanan Pernikahan', 'Awal yang baru membangun rumah tangga bersama Kristus', 'aktif', 1),
    ('Pelayanan Penyerahan Anak', 'Keluarga bersatu dan berkomitmen membesarkan anak dalam kasih Kristus', 'aktif', 2),
    ('Pelayanan Baptisan Selam', 'Disempurnakan menjadi seperti Kristus', 'aktif', 3),
    ('Pelayanan Kedukaan & Kematian', 'Melayani dengan kasih dan penghiburan kepada keluarga yang ditinggalkan', 'aktif', 4),
    ('Pelayanan Pengajaran (KOM)', 'Kami rindu setiap jemaat Tuhan bertumbuh dalam Kristus', 'aktif', 5)";
    
    if ($conn->query($sql_insert) === TRUE) {
        echo "✓ Data initial pelayanan berhasil diinsert.<br>";
    } else {
        echo "✗ Error insert data: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ Data pelayanan sudah ada (" . $row['cnt'] . " records).<br>";
}

echo "<br><a href='pelayanan.php'>← Kembali ke Halaman Pelayanan</a>";
?>
