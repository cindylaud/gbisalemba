<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/image-helper.php';

$admin_page_title = 'Tambah Formulir';
$uploadDir = __DIR__ . '/../../uploads/formulir/';
$fotoDir = __DIR__ . '/../../uploads/formulir/foto/';

// Ensure folders exist
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
if (!is_dir($fotoDir)) @mkdir($fotoDir, 0755, true);

$error = '';
$nama_formulir = '';
$deskripsi = '';
$status = 'aktif';
$urutan = '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$nama_formulir = trim($_POST['nama_formulir'] ?? '');
	$deskripsi = trim($_POST['deskripsi'] ?? '');
	$status = trim($_POST['status'] ?? 'aktif');
	$urutan = (int) ($_POST['urutan'] ?? 1);

	if (empty($nama_formulir)) {
		$error = 'Nama formulir tidak boleh kosong.';
	} elseif (empty($deskripsi)) {
		$error = 'Deskripsi tidak boleh kosong.';
	} elseif ($urutan <= 0) {
		$error = 'Urutan harus lebih dari 0.';
	} elseif (!isset($_FILES['file']) || $_FILES['file']['size'] <= 0) {
		$error = 'File PDF harus diupload.';
	} else {
		// Validate PDF
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $_FILES['file']['tmp_name']);
		finfo_close($finfo);

		if ($mime !== 'application/pdf') {
			$error = 'File harus berformat PDF.';
		} else {
			$original_name = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
			$clean_name = strtolower($original_name);
			$clean_name = preg_replace('/[^a-z0-9]/', '', $clean_name);
			$clean_name = preg_replace('/\s+/', '_', $clean_name);
			if (empty($clean_name)) $clean_name = 'formulir';

			$file_name = $clean_name . ".pdf";
			$counter = 1;
			while (file_exists($uploadDir . $file_name)) {
				$file_name = $clean_name . "_" . $counter . ".pdf";
				$counter++;
			}

			if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $file_name)) {
				$error = 'Gagal mengupload file PDF.';
			} else {
				$foto_name = null;
				if (isset($_FILES['foto']) && $_FILES['foto']['size'] > 0) {
					$validation = validateImageUpload($_FILES['foto'], 30 * 1024 * 1024);
					if (!$validation['valid']) {
						$error = $validation['error'];
					} else {
						$foto_name = 'formulir_foto_' . substr(bin2hex(random_bytes(8)), 0, 8) . '.webp';
						$result = optimizeAndSaveImageAsWebp($_FILES['foto']['tmp_name'], $fotoDir . $foto_name, 1600, 80);
						if (!$result['success']) {
							$error = 'Gagal mengupload foto.';
						}
					}
				}

				if (empty($error)) {
					$stmt = $conn->prepare('INSERT INTO formulir (nama_formulir, deskripsi, file, foto, status, urutan) VALUES (?, ?, ?, ?, ?, ?)');
					$stmt->bind_param('ssssi', $nama_formulir, $deskripsi, $file_name, $foto_name, $status, $urutan);

					if ($stmt->execute()) {
						$stmt->close();
						header('Location: index.php?success=created');
						exit;
					} else {
						$error = 'Gagal menyimpan ke database.';
						@unlink($uploadDir . $file_name);
						if ($foto_name) @unlink($fotoDir . $foto_name);
					}
					$stmt->close();
				} else {
					@unlink($uploadDir . $file_name);
					if ($foto_name ?? false) @unlink($fotoDir . $foto_name);
				}
			}
		}
	}
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .add-layout { display: grid; gap: 12px; }
    .panel { background: #ffffff; border: 1px solid rgba(16, 44, 87, 0.08); border-radius: 18px; box-shadow: 0 8px 18px rgba(15, 39, 66, 0.06); }
    .panel-form { padding: 20px; }
    .panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid rgba(16, 44, 87, 0.1); }
    .panel-title { margin: 0; display: inline-flex; align-items: center; gap: 9px; font-size: 22px; color: #102c57; font-weight: 800; }
    .panel-title i { color: #146c94; }
    .form-grid { display: grid; gap: 14px; }
    .form-section { display: grid; gap: 12px; padding-bottom: 14px; border-bottom: 1px solid rgba(16, 44, 87, 0.06); }
    .form-section:last-of-type { padding-bottom: 0; border-bottom: none; }
    .form-section-title { margin: 0 0 2px 0; font-size: 12px; font-weight: 700; color: #7a8fa7; text-transform: uppercase; letter-spacing: 0.4px; }
    .row-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .field { display: grid; gap: 5px; }
    .field label { margin: 0; font-size: 13px; font-weight: 700; color: #234267; }
    .req { color: #dc3545; }
    .input, .select, .textarea { width: 100%; border: 1px solid rgba(16, 44, 87, 0.14); border-radius: 11px; padding: 11px 13px; background: #ffffff; color: #344054; font-size: 13px; font-family: inherit; transition: border-color 0.25s ease, box-shadow 0.25s ease; }
    .input:focus, .select:focus, .textarea:focus { outline: none; border-color: rgba(20, 108, 148, 0.7); box-shadow: 0 0 0 3px rgba(20, 108, 148, 0.08); }
    .hint { margin: 0; color: #6b7c93; font-size: 12px; line-height: 1.45; }
    .form-actions { display: flex; justify-content: space-between; align-items: center; gap: 10px; border-top: 1px solid rgba(16, 44, 87, 0.1); padding-top: 14px; margin-top: 2px; }
    .btn-action { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border-radius: 11px; padding: 11px 16px; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: all 0.2s ease; }
    .btn-action:hover { text-decoration: none; transform: translateY(-1px); }
    .btn-cancel { background: #f5f8fb; border-color: rgba(16, 44, 87, 0.12); color: #2d4e77; }
    .btn-cancel:hover { color: #2d4e77; background: #eef3f9; box-shadow: 0 6px 12px rgba(16, 44, 87, 0.08); }
    .btn-save { background: #146c94; border-color: #0f4568; color: #fff; box-shadow: 0 6px 14px rgba(20, 108, 148, 0.2); }
    .btn-save:hover { color: #fff; background: #127a9d; box-shadow: 0 8px 18px rgba(20, 108, 148, 0.25); }
    .btn-back { display: inline-flex; align-items: center; gap: 8px; padding: 9px 14px; border-radius: 12px; border: 1px solid rgba(16, 44, 87, 0.14); background: #f3f8fc; color: #1f3f6f; font-size: 13px; font-weight: 700; text-decoration: none; transition: transform 0.18s ease; }
    .btn-back:hover { text-decoration: none; color: #1f3f6f; transform: translateY(-1px); box-shadow: 0 8px 16px rgba(16, 44, 87, 0.14); }
    .admin-alert { border-radius: 12px; padding: 12px 14px; font-size: 13px; font-weight: 600; border: 1px solid transparent; }
    .admin-alert.error { background: #fde8e8; color: #8d2d2d; border-color: #f8c7c7; }
    @media (max-width: 640px) { .form-actions { flex-direction: column-reverse; align-items: stretch; } .btn-action { width: 100%; } .row-grid { grid-template-columns: 1fr; } }
</style>

<div style="display: grid; gap: 12px;">
    <?php if ($error !== ''): ?>
        <div class="admin-alert error" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="add-layout">
        <div class="panel panel-form">
            <div class="panel-head">
                <h2 class="panel-title"><i class="fas fa-plus-circle"></i> Tambah Formulir</h2>
                <div><a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a></div>
            </div>

            <form method="post" enctype="multipart/form-data" class="form-grid">

                <!-- SECTION: INFORMASI DASAR -->
                <div class="form-section">
                    <h3 class="form-section-title">Informasi Dasar</h3>
                    
                    <div class="field">
                        <label for="nama_formulir">Nama Formulir <span class="req">*</span></label>
                        <input type="text" class="input" id="nama_formulir" name="nama_formulir" value="<?php echo htmlspecialchars($nama_formulir); ?>" required>
                    </div>

                    <div class="field">
                        <label for="deskripsi">Deskripsi <span class="req">*</span></label>
                        <textarea class="input" id="deskripsi" name="deskripsi" rows="3" required><?php echo htmlspecialchars($deskripsi); ?></textarea>
                    </div>

                    <div class="row-grid">
                        <div class="field">
                            <label for="urutan">Urutan <span class="req">*</span></label>
                            <input type="number" class="input" id="urutan" name="urutan" min="1" value="<?php echo htmlspecialchars((string) $urutan); ?>" required>
                        </div>

                        <div class="field">
                            <label for="status">Status <span class="req">*</span></label>
                            <select class="select" id="status" name="status" required>
                                <option value="aktif" <?php echo $status === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="nonaktif" <?php echo $status === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SECTION: FILE -->
                <div class="form-section">
                    <h3 class="form-section-title">File PDF</h3>
                    
                    <div class="field">
                        <label for="file">File PDF <span class="req">*</span></label>
                        <input type="file" class="input" id="file" name="file" accept=".pdf" required>
                        <p class="hint">Format: PDF. Maksimal 10MB.</p>
                    </div>

                    <div class="field">
                        <label for="foto">Foto Sampul (Opsional)</label>
                        <input type="file" class="input" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp">
                        <p class="hint">Format: JPG/JPEG/PNG/WEBP. Maksimal 8MB.</p>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn-action btn-cancel">
                        <i class="fas fa-xmark"></i> Batal
                    </a>
                    <button type="submit" class="btn-action btn-save">
                        <i class="fas fa-floppy-disk"></i> Simpan Formulir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
