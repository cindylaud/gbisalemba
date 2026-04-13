-- =====================================================
-- SETUP TABEL RENUNGAN
-- =====================================================
CREATE TABLE IF NOT EXISTS renungan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    isi TEXT NOT NULL,
    ayat VARCHAR(150) NOT NULL,
    tanggal DATE NOT NULL,
    gambar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_renungan_tanggal (tanggal),
    INDEX idx_renungan_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
