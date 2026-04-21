<?php
/**
 * =====================================================================
 * IMAGE PROCESSING HELPER FUNCTIONS
 * =====================================================================
 * Helper functions untuk upload, resize, dan compress image secara
 * otomatis menggunakan GD Library.
 * 
 * Requirements:
 * - PHP GD Library (imagecreatefromjpeg, imagecopyresampled, dll)
 * - PHP Memory limit >= 64MB (untuk proses image besar)
 * =====================================================================
 */

/**
 * Validasi file upload dengan keamanan tinggi
 * 
 * @param array $file - $_FILES array element
 * @param int $max_size - Max file size in bytes (default 50MB)
 * @param array $allowed_types - Allowed MIME types
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateImageUpload($file, $max_size = 52428800, $allowed_types = ['image/jpeg', 'image/png', 'image/webp']) {
    // Check if file exists
    if (!isset($file) || !is_array($file)) {
        return ['valid' => false, 'error' => 'File tidak ditemukan.'];
    }
    
    // Check if upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File melebihi directive upload_max_filesize di php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File melebihi MAX_FILE_SIZE di form',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang dikirim',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ada',
            UPLOAD_ERR_CANT_WRITE => 'Gagal write file ke disk',
            UPLOAD_ERR_EXTENSION => 'Extension file diblokir PHP'
        ];
        return ['valid' => false, 'error' => $upload_errors[$file['error']] ?? 'Error upload tidak diketahui'];
    }
    
    // Check file size (only if max_size is reasonable, not PHP_INT_MAX)
    if ($max_size < PHP_INT_MAX && $file['size'] > $max_size) {
        $max_mb = round($max_size / 1024 / 1024);
        return ['valid' => false, 'error' => "Ukuran file terlalu besar (max {$max_mb}MB)."];
    }
    
    // Validate MIME type (not just extension)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['valid' => false, 'error' => "Tipe file tidak didukung. Gunakan JPG, PNG, atau WebP. (Detected: $mime_type)"];
    }
    
    // Additional check: verify image is valid
    if (@getimagesize($file['tmp_name']) === false) {
        return ['valid' => false, 'error' => 'File bukan gambar yang valid.'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Resize image jika perlu dan simpan selalu sebagai WEBP.
 *
 * @param string $source_path Path file upload sementara
 * @param string $dest_path Path output .webp
 * @param int $max_width Lebar maksimal output
 * @param int $quality Kualitas WEBP 0-100
 * @return array ['success' => bool, 'error' => string|null, 'original_size' => int, 'optimized_size' => int, 'width' => int, 'height' => int]
 */
function optimizeAndSaveImageAsWebp($source_path, $dest_path, $max_width = 1920, $quality = 80) {
    try {
        // Check GD Library
        if (!extension_loaded('gd')) {
            return ['success' => false, 'error' => 'PHP GD Library tidak tersedia'];
        }

        if (!function_exists('imagewebp')) {
            // Fallback: Simpan sebagai JPEG jika WebP tidak tersedia
            if (!function_exists('imagejpeg')) {
                return ['success' => false, 'error' => 'PHP GD Library tidak memiliki image support'];
            }
            $dest_path = preg_replace('/\.webp$/i', '.jpg', $dest_path);
        }

        if (!file_exists($source_path)) {
            return ['success' => false, 'error' => 'File sumber tidak ditemukan'];
        }

        $image_info = @getimagesize($source_path);
        if (!$image_info) {
            return ['success' => false, 'error' => 'Gagal membaca info gambar'];
        }

        list($orig_width, $orig_height, $image_type) = $image_info;

        $source_image = null;
        switch ($image_type) {
            case IMAGETYPE_JPEG:
                $source_image = @imagecreatefromjpeg($source_path);
                break;
            case IMAGETYPE_PNG:
                $source_image = @imagecreatefrompng($source_path);
                break;
            case IMAGETYPE_WEBP:
                $source_image = @imagecreatefromwebp($source_path);
                break;
            default:
                return ['success' => false, 'error' => 'Tipe image tidak didukung'];
        }

        if (!$source_image) {
            return ['success' => false, 'error' => 'Gagal membuat image resource'];
        }

        if ($image_type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($source_path);
            if ($exif && isset($exif['Orientation'])) {
                $angle = 0;
                switch ((int) $exif['Orientation']) {
                    case 3:
                        $angle = 180;
                        break;
                    case 6:
                        $angle = -90;
                        break;
                    case 8:
                        $angle = 90;
                        break;
                }

                if ($angle !== 0) {
                    $rotated_image = @imagerotate($source_image, $angle, 0);
                    if ($rotated_image) {
                        imagedestroy($source_image);
                        $source_image = $rotated_image;
                    }
                }
            }
        }

        $actual_width = imagesx($source_image);
        $actual_height = imagesy($source_image);
        $new_width = $actual_width;
        $new_height = $actual_height;

        if ($actual_width > $max_width) {
            $ratio = $max_width / $actual_width;
            $new_width = $max_width;
            $new_height = (int) round($actual_height * $ratio);
        }

        $output_image = imagecreatetruecolor($new_width, $new_height);
        if (!$output_image) {
            imagedestroy($source_image);
            return ['success' => false, 'error' => 'Gagal menyiapkan canvas output'];
        }

        imagealphablending($output_image, true);
        imagesavealpha($output_image, true);
        $transparent = imagecolorallocatealpha($output_image, 0, 0, 0, 127);
        imagefill($output_image, 0, 0, $transparent);

        if (!imagecopyresampled(
            $output_image,
            $source_image,
            0,
            0,
            0,
            0,
            $new_width,
            $new_height,
            $actual_width,
            $actual_height
        )) {
            imagedestroy($source_image);
            imagedestroy($output_image);
            return ['success' => false, 'error' => 'Gagal resize image'];
        }

        // Save dengan format yang tersedia
        $save_success = false;
        if (function_exists('imagewebp')) {
            $save_success = @imagewebp($output_image, $dest_path, $quality);
        } else if (function_exists('imagejpeg')) {
            // Fallback ke JPEG
            $dest_path = preg_replace('/\.webp$/i', '.jpg', $dest_path);
            $save_success = @imagejpeg($output_image, $dest_path, $quality);
        }

        imagedestroy($source_image);
        imagedestroy($output_image);

        if (!$save_success) {
            return ['success' => false, 'error' => 'Gagal menyimpan file (WEBP/JPEG)'];
        }

        return [
            'success' => true,
            'error' => null,
            'original_size' => filesize($source_path),
            'optimized_size' => filesize($dest_path),
            'width' => $new_width,
            'height' => $new_height
        ];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Resize dan compress image otomatis dengan EXIF orientation fix
 * 
 * Features:
 * - Auto-resize jika lebar > max_width (maintain aspect ratio)
 * - Compress JPEG ke quality yang ditentukan
 * - Handle EXIF orientation untuk JPEG (foto dari HP tidak miring)
 * - Preserve transparency untuk PNG
 * - Support for JPEG, PNG, WebP
 * 
 * EXIF Orientation Support:
 * - Orientation 3: Rotate 180°
 * - Orientation 6: Rotate -90° (landscape rotated right)
 * - Orientation 8: Rotate 90° (landscape rotated left)
 * - Requires: exif_read_data() function (PHP EXIF extension)
 * 
 * @param string $source_path - Path ke file upload sementara
 * @param string $dest_path - Path file tujuan
 * @param int $max_width - Maksimal lebar image (default 1600px)
 * @param int $quality - Kualitas JPEG 0-100 (default 80)
 * @return array ['success' => bool, 'error' => string|null, 'original_size' => int, 'optimized_size' => int]
 */
function optimizeAndSaveImage($source_path, $dest_path, $max_width = 1600, $quality = 80) {
    try {
        // Validate source file exists
        if (!file_exists($source_path)) {
            return ['success' => false, 'error' => 'File sumber tidak ditemukan'];
        }
        
        // Get image type from getimagesize
        $image_info = @getimagesize($source_path);
        if (!$image_info) {
            return ['success' => false, 'error' => 'Gagal membaca info gambar'];
        }
        
        list($orig_width, $orig_height, $image_type) = $image_info;
        
        // ===== STEP 1: LOAD IMAGE RESOURCE FIRST =====
        $source_image = null;
        
        switch ($image_type) {
            case IMAGETYPE_JPEG:
                $source_image = @imagecreatefromjpeg($source_path);
                break;
            case IMAGETYPE_PNG:
                $source_image = @imagecreatefrompng($source_path);
                break;
            case IMAGETYPE_WEBP:
                $source_image = @imagecreatefromwebp($source_path);
                break;
            default:
                return ['success' => false, 'error' => 'Tipe image tidak didukung'];
        }
        
        if (!$source_image) {
            return ['success' => false, 'error' => 'Gagal membuat image resource'];
        }
        
        // ===== STEP 2: HANDLE EXIF ORIENTATION FOR JPEG =====
        if ($image_type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($source_path);
            if ($exif && isset($exif['Orientation'])) {
                $orientation = intval($exif['Orientation']);
                $angle = 0;
                
                // Determine rotation angle based on EXIF orientation
                switch ($orientation) {
                    case 3:
                        $angle = 180;  // Upside down
                        break;
                    case 6:
                        $angle = -90;  // Rotate left 90 degrees (was rotated right)
                        break;
                    case 8:
                        $angle = 90;   // Rotate right 90 degrees (was rotated left)
                        break;
                }
                
                // Apply rotation if needed
                if ($angle !== 0) {
                    $rotated_image = @imagerotate($source_image, $angle, 0);
                    if ($rotated_image) {
                        imagedestroy($source_image);
                        $source_image = $rotated_image;
                    }
                }
            }
        }
        
        // ===== STEP 3: GET ACTUAL DIMENSIONS AFTER ROTATION =====
        $actual_width = imagesx($source_image);
        $actual_height = imagesy($source_image);
        
        // ===== STEP 4: CHECK IF RESIZE NEEDED BASED ON ACTUAL DIMENSIONS =====
        $need_resize = ($actual_width > $max_width);
        $new_width = $actual_width;
        $new_height = $actual_height;
        
        if ($need_resize) {
            // Calculate new dimensions maintaining aspect ratio
            $ratio = $max_width / $actual_width;
            $new_width = $max_width;
            $new_height = intval($actual_height * $ratio);
        }
        
        // ===== STEP 5: RESIZE AND SAVE =====
        if ($need_resize) {
            // Create new image resource with calculated dimensions
            $resized_image = imagecreatetruecolor($new_width, $new_height);
            
            // Preserve transparency untuk PNG
            if ($image_type === IMAGETYPE_PNG) {
                imagealphablending($resized_image, false);
                imagesavealpha($resized_image, true);
            }
            
            // Copy and resize
            if (!imagecopyresampled(
                $resized_image, $source_image,
                0, 0, 0, 0,
                $new_width, $new_height,
                $actual_width, $actual_height
            )) {
                imagedestroy($source_image);
                imagedestroy($resized_image);
                return ['success' => false, 'error' => 'Gagal resize image'];
            }
            
            // Save resized image
            $save_success = false;
            switch ($image_type) {
                case IMAGETYPE_JPEG:
                    $save_success = imagejpeg($resized_image, $dest_path, $quality);
                    break;
                case IMAGETYPE_PNG:
                    $save_success = imagepng($resized_image, $dest_path, 6);
                    break;
                case IMAGETYPE_WEBP:
                    $save_success = imagewebp($resized_image, $dest_path, $quality);
                    break;
            }
            
            imagedestroy($resized_image);
            imagedestroy($source_image);
            
            if (!$save_success) {
                return ['success' => false, 'error' => 'Gagal menyimpan image'];
            }
        } else {
            // No resize needed, just compress and save
            $save_success = false;
            switch ($image_type) {
                case IMAGETYPE_JPEG:
                    $save_success = imagejpeg($source_image, $dest_path, $quality);
                    break;
                case IMAGETYPE_PNG:
                    $save_success = imagepng($source_image, $dest_path, 6);
                    break;
                case IMAGETYPE_WEBP:
                    $save_success = imagewebp($source_image, $dest_path, $quality);
                    break;
            }
            
            imagedestroy($source_image);
            
            if (!$save_success) {
                return ['success' => false, 'error' => 'Gagal menyimpan image'];
            }
        }
        
        // ===== STEP 6: RETURN RESULT WITH FINAL DIMENSIONS =====
        $original_size = filesize($source_path);
        $optimized_size = filesize($dest_path);
        
        return [
            'success' => true,
            'error' => null,
            'original_size' => $original_size,
            'optimized_size' => $optimized_size,
            'width' => $new_width,
            'height' => $new_height
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Generate unique filename untuk upload
 * 
 * @param string $original_filename - Nama file asli dari user
 * @return string - Nama file baru dalam format: prefix_TIMESTAMP_RANDOM.ext
 */
function generateUniqueFilename($original_filename, $prefix = 'pelayanan') {
    $ext = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    $timestamp = time();
    $random = substr(hash('sha256', uniqid()), 0, 6);
    
    return "{$prefix}_{$timestamp}_{$random}.{$ext}";
}

/**
 * Delete file dari server
 * 
 * @param string $file_path - Path lengkap file
 * @return bool - Success/fail
 */
function deleteFile($file_path) {
    if (file_exists($file_path) && is_file($file_path)) {
        return unlink($file_path);
    }
    return true; // Return true jika file tidak ada
}

/**
 * Format file size untuk display (bytes to human readable)
 * 
 * @param int $bytes - Ukuran dalam bytes
 * @return string - Formatted size (e.g., "1.5 MB")
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Get file size limit dari server config
 * 
 * @return int - File size limit dalam bytes
 */
function getServerUploadLimit() {
    $upload_max = ini_get('upload_max_filesize');
    $post_max = ini_get('post_max_size');
    
    $parse_size = function($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        $value = (int)$value;
        
        switch($last) {
            case 't': $value *= 1024;
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        return $value;
    };
    
    return min($parse_size($upload_max), $parse_size($post_max));
}

?>
