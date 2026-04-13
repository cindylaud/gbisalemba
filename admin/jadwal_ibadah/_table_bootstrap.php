<?php

function ensureJadwalIbadahTable($conn)
{
    $create_table_sql =
        "CREATE TABLE IF NOT EXISTS jadwal_ibadah (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama_ibadah VARCHAR(255) NOT NULL,
            kategori VARCHAR(100) DEFAULT NULL,
            hari VARCHAR(120) NOT NULL,
            jam VARCHAR(255) NOT NULL,
            ruangan VARCHAR(255) DEFAULT NULL,
            keterangan TEXT DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            urutan INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

    $safe_query = static function (mysqli $conn_obj, string $sql) {
        try {
            return $conn_obj->query($sql);
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    };

    $has_jadwal_ibadah = false;
    $has_jadwal_legacy = false;

    $check_jadwal_ibadah = $safe_query($conn, "SHOW TABLES LIKE 'jadwal_ibadah'");
    if ($check_jadwal_ibadah instanceof mysqli_result && $check_jadwal_ibadah->num_rows > 0) {
        $has_jadwal_ibadah = true;
    }

    $check_jadwal_legacy = $safe_query($conn, "SHOW TABLES LIKE 'jadwal'");
    if ($check_jadwal_legacy instanceof mysqli_result && $check_jadwal_legacy->num_rows > 0) {
        $has_jadwal_legacy = true;
    }

    $columns = [];
    $need_recreate = false;

    if ($has_jadwal_ibadah) {
        $check_columns = $safe_query($conn, "SHOW COLUMNS FROM jadwal_ibadah");
        if ($check_columns instanceof mysqli_result) {
            while ($col = $check_columns->fetch_assoc()) {
                $columns[$col['Field']] = true;
            }
        } else {
            // Table metadata is broken (e.g. "doesn't exist in engine"), recreate it.
            $need_recreate = true;
        }
    }

    if (!$has_jadwal_ibadah || $need_recreate) {
        if ($need_recreate) {
            $safe_query($conn, "DROP TABLE IF EXISTS jadwal_ibadah");
        }
        $safe_query($conn, $create_table_sql);
        $has_jadwal_ibadah = true;
        $columns = [];
        $check_columns = $safe_query($conn, "SHOW COLUMNS FROM jadwal_ibadah");
        if ($check_columns instanceof mysqli_result) {
            while ($col = $check_columns->fetch_assoc()) {
                $columns[$col['Field']] = true;
            }
        }
    }

    if (!isset($columns['kategori'])) {
        $safe_query($conn, "ALTER TABLE jadwal_ibadah ADD COLUMN kategori VARCHAR(100) DEFAULT NULL AFTER nama_ibadah");
        $columns['kategori'] = true;
    }

    if (!isset($columns['urutan'])) {
        $safe_query($conn, "ALTER TABLE jadwal_ibadah ADD COLUMN urutan INT NOT NULL DEFAULT 0 AFTER is_active");
        $columns['urutan'] = true;
    }

    if (!isset($columns['image'])) {
        $safe_query($conn, "ALTER TABLE jadwal_ibadah ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER keterangan");
        $columns['image'] = true;
    }

    if (!isset($columns['image_fit'])) {
        $safe_query($conn, "ALTER TABLE jadwal_ibadah ADD COLUMN image_fit VARCHAR(50) DEFAULT 'cover' AFTER image");
        $columns['image_fit'] = true;
    }

    if (!isset($columns['image_pos_y'])) {
        $safe_query($conn, "ALTER TABLE jadwal_ibadah ADD COLUMN image_pos_y INT DEFAULT 50 AFTER image_fit");
        $columns['image_pos_y'] = true;
    }

    if ($has_jadwal_legacy) {
        $count_result = $safe_query($conn, "SELECT COUNT(*) AS total FROM jadwal_ibadah");
        $current_total = 0;
        if ($count_result instanceof mysqli_result) {
            $count_row = $count_result->fetch_assoc();
            $current_total = (int) ($count_row['total'] ?? 0);
        }

        if ($current_total === 0) {
            $legacy_rows = $safe_query(
                $conn,
                "SELECT judul, kategori, hari, jam_mulai, jam_selesai, lokasi, catatan, urutan, status
                 FROM jadwal
                 ORDER BY urutan ASC, id ASC"
            );

            if ($legacy_rows instanceof mysqli_result) {
                try {
                    $insert_stmt = $conn->prepare(
                        "INSERT INTO jadwal_ibadah
                        (nama_ibadah, kategori, hari, jam, ruangan, keterangan, is_active, urutan)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    if ($insert_stmt) {
                        while ($legacy = $legacy_rows->fetch_assoc()) {
                            $nama_ibadah = (string) ($legacy['judul'] ?? 'Ibadah');
                            $kategori = (string) ($legacy['kategori'] ?? '');
                            $hari = (string) ($legacy['hari'] ?? '');
                            $jam_mulai = !empty($legacy['jam_mulai']) ? date('H:i', strtotime((string) $legacy['jam_mulai'])) . ' WIB' : '';
                            $jam_selesai = !empty($legacy['jam_selesai']) ? date('H:i', strtotime((string) $legacy['jam_selesai'])) . ' WIB' : '';
                            $jam = trim($jam_mulai);
                            if ($jam_selesai !== '') {
                                $jam = $jam !== '' ? ($jam . ', ' . $jam_selesai) : $jam_selesai;
                            }
                            if ($jam === '') {
                                $jam = '-';
                            }
                            $ruangan = (string) ($legacy['lokasi'] ?? '');
                            $keterangan = (string) ($legacy['catatan'] ?? '');
                            $is_active = ((string) ($legacy['status'] ?? 'aktif') === 'aktif') ? 1 : 0;
                            $urutan = (int) ($legacy['urutan'] ?? 0);

                            $insert_stmt->bind_param(
                                'ssssssii',
                                $nama_ibadah,
                                $kategori,
                                $hari,
                                $jam,
                                $ruangan,
                                $keterangan,
                                $is_active,
                                $urutan
                            );
                            $insert_stmt->execute();
                        }

                        $insert_stmt->close();
                    }
                } catch (mysqli_sql_exception $e) {
                    // Ignore insert migration failure to keep admin pages accessible.
                }
            }
        }
    }

    $seed_result = $safe_query($conn, "SELECT id, urutan FROM jadwal_ibadah ORDER BY urutan ASC, id ASC");
    if ($seed_result instanceof mysqli_result) {
        try {
            $seq = 1;
            $update_stmt = $conn->prepare("UPDATE jadwal_ibadah SET urutan = ? WHERE id = ?");
            while ($seed_row = $seed_result->fetch_assoc()) {
                $id = (int) $seed_row['id'];
                $urutan = (int) ($seed_row['urutan'] ?? 0);
                if ($urutan !== $seq && $update_stmt) {
                    $update_stmt->bind_param('ii', $seq, $id);
                    $update_stmt->execute();
                }
                $seq++;
            }
            if ($update_stmt) {
                $update_stmt->close();
            }
        } catch (mysqli_sql_exception $e) {
            // Ignore resequencing failure to avoid fatal errors on admin pages.
        }
    }

    return [
        'has_urutan_column' => true,
        'has_kategori_column' => true,
        'has_image_columns' => isset($columns['image']) && isset($columns['image_fit']) && isset($columns['image_pos_y']),
    ];
}
