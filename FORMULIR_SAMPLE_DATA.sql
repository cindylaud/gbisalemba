-- =====================================================================
-- FORMULIR SYSTEM - SAMPLE DATA FOR TESTING
-- =====================================================================
-- File ini berisi contoh data untuk testing sistem formulir
-- Run dalam phpMyAdmin atau MySQL client

-- =====================================================================
-- 1. CREATE TABLE (Jika belum ada)
-- =====================================================================
CREATE TABLE IF NOT EXISTS `formulir` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_formulir` VARCHAR(100) NOT NULL,
    `file` VARCHAR(255),
    `foto` VARCHAR(255),
    `foto_posisi_y` TINYINT UNSIGNED NOT NULL DEFAULT 50,
    `deskripsi` TEXT,
    `urutan` INT DEFAULT 0,
    `status` ENUM('aktif','nonaktif') DEFAULT 'aktif',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `status_idx` (`status`),
    INDEX `urutan_idx` (`urutan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. INSERT SAMPLE DATA
-- =====================================================================
INSERT INTO `formulir` 
(`nama_formulir`, `file`, `deskripsi`, `urutan`, `status`) 
VALUES 

-- Formulir Pelayanan
(
    'Formulir Pendaftaran Pelayanan',
    NULL,
    'Gunakan formulir ini untuk mendaftar menjadi bagian dari tim pelayanan di GBI Salemba. Kami membuka peluang untuk berbagai bidang pelayanan sesuai dengan karunia dan passion Anda.',
    1,
    'aktif'
),

-- Formulir Kelompok Sel
(
    'Formulir Kelompok Sel',
    NULL,
    'Formulir untuk mendaftar atau membentuk kelompok sel di area kediaman Anda. Kelompok sel adalah wadah kecil untuk pertumbuhan iman dan persekutuan yang lebih intim.',
    2,
    'aktif'
),

-- Formulir Kursus Dasar Iman
(
    'Formulir Pendaftaran Kursus Dasar Iman',
    NULL,
    'Daftarkan diri Anda untuk mengikuti kursus dasar iman. Kursus ini dirancang untuk membangun fondasi kepercayaan kepada Yesus Kristus yang kuat.',
    3,
    'aktif'
),

-- Formulir Persembahan
(
    'Kartu Kontribusi Persembahan',
    NULL,
    'Gunakan kartu ini jika ingin memberikan persembahan kepada gereja secara terencana. Data ini akan membantu kami merencanakan proyek pelayanan jangka panjang.',
    4,
    'aktif'
),

-- Formulir Doa
(
    'Formulir Permintaan Doa',
    NULL,
    'Tuliskan kebutuhan doa Anda dan tim doa kami akan berdoa untuk Anda. Semua informasi dijaga dengan ketat dan confidential.',
    5,
    'aktif'
),

-- Formulir Counseling (Nonaktif untuk contoh)
(
    'Formulir Konseling Rohani',
    NULL,
    'Formulir ini sedang dalam perbaikan. Hubungi pastor langsung untuk mendapatkan konseling rohani.',
    6,
    'nonaktif'
);

-- =====================================================================
-- 3. VERIFY DATA
-- =====================================================================
SELECT * FROM `formulir` ORDER BY `urutan` ASC;

-- =====================================================================
-- 4. COUNT DATA
-- =====================================================================
-- SELECT COUNT(*) as total_formulir FROM `formulir`;
-- SELECT COUNT(*) as aktif FROM `formulir` WHERE status = 'aktif';
-- SELECT COUNT(*) as nonaktif FROM `formulir` WHERE status = 'nonaktif';

-- =====================================================================
-- NOTES:
-- =====================================================================
-- - File field dibiarkan NULL karena file akan ditambahkan melalui admin panel
-- - Data ini bersifat contoh dan bisa disesuaikan dengan kebutuhan
-- - Timestamp akan otomatis terisi dengan waktu insert
-- - Urutan bisa diubah dari admin panel
-- - Status bisa diubah untuk mengontrol tampilan di frontend

-- =====================================================================
-- TESTING CHECKLIST:
-- =====================================================================
-- 1. ✓ Verify tabel ada dan data terisi
-- 2. [ ] Upload PDF untuk beberapa formulir di admin panel
-- 3. [ ] Verifikasi tampilan di frontend (formulir.php)
-- 4. [ ] Test edit formulir
-- 5. [ ] Test delete formulir
-- 6. [ ] Test change status (aktif/nonaktif)
-- 7. [ ] Test download PDF dari frontend
-- 8. [ ] Test responsiveness di mobile
