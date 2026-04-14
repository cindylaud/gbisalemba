<?php

if (!defined('HEADLINE_TABLE')) {
    define('HEADLINE_TABLE', 'headline_settings');
}

if (!defined('HEADLINE_UPLOAD_DIR')) {
    define('HEADLINE_UPLOAD_DIR', __DIR__ . '/../uploads/headline/');
}

if (!function_exists('headline_ensure_storage_dir')) {
    function headline_ensure_storage_dir(): void {
        if (!is_dir(HEADLINE_UPLOAD_DIR)) {
            @mkdir(HEADLINE_UPLOAD_DIR, 0755, true);
        }
    }
}

if (!function_exists('headline_ensure_table')) {
    function headline_ensure_table(mysqli $conn): void {
        $conn->query(
            "CREATE TABLE IF NOT EXISTS " . HEADLINE_TABLE . " (
                id INT AUTO_INCREMENT PRIMARY KEY,
                section_key VARCHAR(50) NOT NULL UNIQUE,
                image VARCHAR(255) DEFAULT NULL,
                pos_y TINYINT UNSIGNED NOT NULL DEFAULT 50,
                zoom TINYINT UNSIGNED NOT NULL DEFAULT 100,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        );

        $seeds = [
            ['jadwal', 30, 100],
            ['pelayanan', 28, 102],
            ['formulir', 28, 102],
        ];

        $stmt = $conn->prepare(
            "INSERT IGNORE INTO " . HEADLINE_TABLE . " (section_key, pos_y, zoom) VALUES (?, ?, ?)"
        );

        if ($stmt) {
            foreach ($seeds as $seed) {
                $section = $seed[0];
                $posY = $seed[1];
                $zoom = $seed[2];
                $stmt->bind_param('sii', $section, $posY, $zoom);
                $stmt->execute();
            }
            $stmt->close();
        }

        headline_ensure_storage_dir();
    }
}

if (!function_exists('headline_normalize_setting')) {
    function headline_normalize_setting(array $row, array $fallback = []): array {
        $image = (string) ($row['image'] ?? ($fallback['image'] ?? ''));
        $posY = isset($row['pos_y']) ? (int) $row['pos_y'] : (int) ($fallback['pos_y'] ?? 50);
        $zoom = isset($row['zoom']) ? (int) $row['zoom'] : (int) ($fallback['zoom'] ?? 100);

        if ($posY < 0) {
            $posY = 0;
        } elseif ($posY > 100) {
            $posY = 100;
        }

        if ($zoom < 50) {
            $zoom = 50;
        } elseif ($zoom > 150) {
            $zoom = 150;
        }

        return [
            'image' => $image,
            'pos_y' => $posY,
            'zoom' => $zoom,
        ];
    }
}

if (!function_exists('headline_get_setting')) {
    function headline_get_setting(mysqli $conn, string $sectionKey, array $fallback = []): array {
        $fallbackData = headline_normalize_setting([], $fallback);

        $stmt = $conn->prepare(
            "SELECT image, pos_y, zoom FROM " . HEADLINE_TABLE . " WHERE section_key = ? LIMIT 1"
        );

        if (!$stmt) {
            return $fallbackData;
        }

        $stmt->bind_param('s', $sectionKey);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return $fallbackData;
        }

        return headline_normalize_setting($row, $fallback);
    }
}

if (!function_exists('headline_resolve_public_image')) {
    function headline_resolve_public_image(?string $filename): string {
        $name = trim((string) $filename);
        if ($name === '') {
            return '';
        }

        $path = HEADLINE_UPLOAD_DIR . $name;
        if (!is_file($path)) {
            return '';
        }

        return 'uploads/headline/' . rawurlencode($name);
    }
}

if (!function_exists('headline_resolve_admin_image')) {
    function headline_resolve_admin_image(?string $filename): string {
        $name = trim((string) $filename);
        if ($name === '') {
            return '';
        }

        $path = HEADLINE_UPLOAD_DIR . $name;
        if (!is_file($path)) {
            return '';
        }

        return '../uploads/headline/' . rawurlencode($name);
    }
}

if (!function_exists('headline_delete_image_file')) {
    function headline_delete_image_file(?string $filename): void {
        $name = trim((string) $filename);
        if ($name === '') {
            return;
        }

        $path = HEADLINE_UPLOAD_DIR . $name;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
