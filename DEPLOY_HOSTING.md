# Deployment Hosting Checklist (GBI Salemba)

Agar kode berjalan di hosting seperti di local, pastikan langkah ini dilakukan.

## 1) Isi environment variable database di hosting
Set di panel hosting (.htaccess/SetEnv, cPanel, atau fitur Env Vars):

- DB_HOST
- DB_PORT (opsional, default 3306)
- DB_USER
- DB_PASS
- DB_NAME
- DB_CHARSET (opsional, default utf8mb4)
- APP_TIMEZONE (opsional, default Asia/Jakarta)

Contoh nilai:

- DB_HOST=localhost
- DB_PORT=3306
- DB_USER=u1234567_app
- DB_PASS=********
- DB_NAME=u1234567_gbisalemba
- DB_CHARSET=utf8mb4
- APP_TIMEZONE=Asia/Jakarta

## 2) Import database awal
Jalankan SQL awal sesuai kebutuhan fitur:

- FORMULIR_SAMPLE_DATA.sql
- JADWAL_SETUP.sql
- RENUNGAN_SETUP.sql

## 3) Pastikan permission folder upload
Folder harus bisa ditulis oleh PHP:

- uploads/
- uploads/formulir/
- uploads/gembala/
- uploads/pelayanan/
- uploads/renungan/
- uploads/slider/
- uploads/whatsnew/

## 4) Pastikan ekstensi PHP aktif
Minimal:

- mysqli
- mbstring
- fileinfo
- gd (untuk resize/optimize image)

## 5) Jangan hardcode domain/path
Aplikasi sudah dibuat path-relatif. Jika pindah domain/subfolder, link internal tetap aman.

## 6) Quick smoke test setelah upload
Cek ini sesudah deploy:

- Frontend home tampil
- Admin login/logout normal
- Upload slider berhasil
- Upload coming soon berhasil
- Tombol Simpan Perubahan dan Tambah Baru berfungsi

