-- ====================================================
-- SETUP DATABASE JADWAL - GBI SALEMBA
-- ====================================================

CREATE TABLE IF NOT EXISTS jadwal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(150) NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    hari VARCHAR(20) NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    lokasi VARCHAR(150) DEFAULT NULL,
    catatan TEXT DEFAULT NULL,
    urutan INT NOT NULL,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_urutan (urutan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- SAMPLE DATA (Optional)
-- ====================================================

INSERT INTO jadwal (judul, kategori, hari, jam_mulai, jam_selesai, lokasi, catatan, urutan, status) VALUES
('Ibadah Minggu I', 'Ibadah Umum', 'Minggu', '07:00:00', '09:00:00', 'Gedung Utama', 'Ibadah pagi pertama', 1, 'aktif'),
('Ibadah Minggu II', 'Ibadah Umum', 'Minggu', '10:00:00', '12:00:00', 'Gedung Utama', 'Ibadah pagi kedua', 2, 'aktif'),
('Ibadah Minggu III', 'Ibadah Umum', 'Minggu', '17:00:00', '19:00:00', 'Gedung Utama', 'Ibadah sore', 3, 'aktif'),
('Doa Pagi', 'Doa dan Puasa', 'Selasa', '05:00:00', '06:30:00', 'Ruang Doa', 'Doa pagi bersama', 4, 'aktif'),
('Pemahaman Alkitab', 'Pemahaman Alkitab', 'Rabu', '18:30:00', '20:30:00', 'Gedung Utama', 'Kelas pemahaman Alkitab', 5, 'aktif'),
('Doa Malam', 'Doa dan Puasa', 'Kamis', '19:00:00', '21:00:00', 'Ruang Doa', 'Doa malam bersama', 6, 'aktif'),
('Pemuda Remaja', 'Kelompok Usia', 'Jumat', '18:00:00', '20:00:00', 'Aula Lantai 2', 'Persekutuan pemuda dan remaja', 7, 'aktif'),
('Sekolah Minggu', 'Kelompok Usia', 'Minggu', '10:00:00', '12:00:00', 'Ruang Sekolah Minggu', 'Kelas anak-anak', 8, 'aktif');
