# DOKUMENTASI SISTEM FORMULIR - GBI SALEMBA

## 📋 Ringkasan Fitur

Sistem formulir untuk website gereja GBI Salemba telah selesai dibuat dengan dua komponen utama:
- **Admin Panel** (`admin/formulir.php`) - Kelola formulir
- **Frontend** (`formulir.php`) - Tampilkan formulir untuk jemaat

---

## 🔧 ADMIN PANEL: `admin/formulir.php`

### Fitur Utama

#### 1. **Daftar Formulir** (Tab: List)
- Menampilkan semua formulir dalam tabel
- Kolom yang ditampilkan:
  - **Urutan** - Urutan tampil formulir
  - **Nama Formulir** - Judul formulir
  - **Deskripsi** - Preview deskripsi (50 karakter pertama)
  - **File** - Link download + preview file dengan icon PDF
  - **Status** - Badge Aktif/Nonaktif
  - **Aksi** - Tombol Edit & Hapus

- **Sort** - ORDER BY urutan ASC
- **Empty State** - Pesan jika tidak ada data

#### 2. **Tambah Formulir** (Tab: Form)
Form fields:
- **Urutan** (number) - Default: 0
- **Nama Formulir** (text) - Required
- **Deskripsi** (textarea) - Required
- **File PDF** (file upload) - **Required untuk tambah**
  - Validasi MIME type: `application/pdf`
  - Max size: 10MB
  - Rename otomatis: `formulir_TIMESTAMP_RANDOM.pdf`
- **Status** (select) - Pilihan: Aktif / Nonaktif

#### 3. **Edit Formulir**
- Bisa mengubah semua field
- File PDF bersifat **opsional**:
  - Jika upload PDF baru → hapus file lama, gunakan file baru
  - Jika tidak upload → tetap gunakan file lama
- Preview file saat ini ditampilkan
- Preview file baru saat dipilih

#### 4. **Hapus Formulir**
- Konfirmasi sebelum hapus
- Hapus dari database
- Hapus file PDF dari folder `/uploads/formulir/`
- Alert success/error

### Keamanan & Validasi

✅ **SQL Injection Prevention**
- Menggunakan prepared statement MySQLi
- Bind parameter untuk semua query

✅ **File Upload Security**
- Validasi MIME type dengan `finfo_file()` (double check)
- Validasi ukuran file max 10MB
- Validasi nama file
- File rename otomatis (mencegah overwrite)
- Check file_exists sebelum delete

✅ **Output Escaping**
- `htmlspecialchars()` untuk semua output
- Prevent XSS attack

### UI/UX

- **Tab System** - Daftar & Tambah dalam tab terpisah
- **Card Layout** - Form dalam card dengan shadow modern
- **Status Badge** - Indikator visual status (hijau/merah)
- **Alert Messages** - Success/Error notification
- **Responsive** - Mobile-friendly design

---

## 🌐 FRONTEND: `formulir.php`

### Tampilan Formulir untuk Jemaat

#### Header Section
- Gradient background (blue → teal)
- Judul besar: "Formulir"
- Tagline deskriptif

#### Formulir Grid
- **Layout** - Card grid responsive
  - Desktop: 3 kolom (auto-fit minmax 300px)
  - Tablet/Mobile: 1 kolom

#### Formulir Card
Setiap card menampilkan:
- **Icon** - PDF icon dengan gradient background
- **Nama Formulir** - Judul formulir
- **Deskripsi** - Deskripsi singkat
- **Download Button** - Link download PDF dengan icon
- **Hover Effect** - Translate up + shadow grow

#### Filtering & Sorting
- **Filter** - Hanya tampilkan formulir dengan status = 'aktif'
- **Sort** - ORDER BY urutan ASC

#### Security
- `htmlspecialchars()` untuk output nama & deskripsi
- Check `file_exists()` sebelum tampilkan link download
- Auto placeholder jika file tidak tersedia

### Responsive Design
```
Desktop:  3 kolom
Tablet:   2 kolom
Mobile:   1 kolom
```

---

## 📁 File Structure

```
gbisalemba/
├── admin/
│   └── formulir.php          ← Admin panel (NEW)
├── formulir.php              ← Frontend (UPDATED)
├── uploads/
│   └── formulir/             ← Folder upload PDF (auto-create)
└── config/
    └── database.php          ← Database connection (existing)
```

---

## 🗄️ Database Requirements

### Table: formulir

```sql
CREATE TABLE formulir (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_formulir VARCHAR(100) NOT NULL,
    file VARCHAR(255),
    deskripsi TEXT,
    urutan INT DEFAULT 0,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Column Notes
- **nama_formulir** - Nama/judul formulir yang ditampilkan
- **file** - Nama file PDF yang di-upload
- **deskripsi** - Deskripsi singkat formulir
- **urutan** - Urutan tampil di frontend (ascending)
- **status** - Kontrol visibilitas di frontend
- **created_at** - Timestamp pembuatan record

---

## 🚀 Cara Penggunaan

### Untuk Admin (Backend)

1. **Akses Admin Panel**
   ```
   http://localhost/gbisalemba/admin/formulir.php
   ```

2. **Tambah Formulir Baru**
   - Klik tab "Tambah Formulir"
   - Isi semua field
   - Upload file PDF
   - Klik "Tambah Formulir"

3. **Edit Formulir**
   - Dari tab "Daftar", klik tombol "Edit"
   - Ubah field yang ingin diubah
   - Upload PDF baru (opsional)
   - Klik "Perbarui Formulir"

4. **Hapus Formulir**
   - Dari tab "Daftar", klik tombol "Hapus"
   - Konfirmasi penghapusan
   - File dan database akan terhapus

### Untuk Frontend (Jemaat)

1. **Akses Halaman Formulir**
   ```
   http://localhost/gbisalemba/formulir.php
   ```

2. **Download Formulir**
   - Browse card grid formulir
   - Klik tombol "Download" pada formulir yang diinginkan
   - PDF akan diunduh otomatis

---

## 🛡️ Security Checklist

- ✅ Prepared statement (MySQLi) untuk anti SQL injection
- ✅ MIME type validation untuk file upload
- ✅ File rename otomatis (prevent overwrite)
- ✅ htmlspecialchars() untuk XSS prevention
- ✅ file_exists() check sebelum delete & download
- ✅ Folder permission handling (mkdir with 0755)
- ✅ Max file size validation
- ✅ Input validation & sanitization

---

## 💡 Best Practices Implemented

- ✅ **Procedural MySQLi** - Tanpa framework
- ✅ **Clean Code** - Komentar jelas & struktur terorganisir
- ✅ **DRY Principle** - Reuse code & config
- ✅ **Responsive Design** - Mobile-first approach
- ✅ **User Experience** - Tab system, alert messages, preview
- ✅ **Error Handling** - Graceful error messages
- ✅ **Performance** - Optimized queries & minimal dependencies

---

## 📝 Notes

- File PDF di-rename otomatis dengan format: `formulir_TIMESTAMP_RANDOM.pdf`
- Upload folder auto-create jika belum ada
- Frontend hanya menampilkan formulir dengan status "aktif"
- Admin dapat mengatur urutan tampil dengan field "Urutan"
- Styling konsisten dengan design system existing (color vars, responsive)

---

## ❓ Troubleshooting

### Folder Permission Error
```php
// Folder akan auto-create dengan permission 0755
// Jika tetap error, pastikan:
// 1. XAMPP running dengan akses admin
// 2. Folder /uploads/ sudah ada
// 3. Folder /uploads/formulir/ writable
```

### File Upload Gagal
- Check: ukuran file < 10MB
- Check: format file adalah PDF
- Check: MIME type application/pdf
- Check: folder uploads/formulir/ accessible

### Frontend Tidak Menampilkan Formulir
- Check: Database connection berhasil
- Check: Ada formulir dengan status "aktif"
- Check: File PDF ada di folder uploads/formulir/

---

**Dibuat:** 12 Februari 2026  
**Status:** ✅ Complete & Production Ready
