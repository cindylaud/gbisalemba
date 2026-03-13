# MODUL JADWAL - DOKUMENTASI LENGKAP

## 📋 OVERVIEW

Modul Jadwal untuk website GBI Salemba dengan fitur:
- **CRUD lengkap** (Create, Read, Update, Delete)
- **Urutan dinamis** dengan move up/down
- **Toggle status** aktif/nonaktif
- **Grouping by kategori** di frontend
- **Responsive design**
- **Transaction-safe** untuk swap urutan

---

## 📁 STRUKTUR FILE YANG DIBUAT

```
C:\xampp\htdocs\gbisalemba\
│
├── JADWAL_SETUP.sql           # SQL untuk setup database
├── JADWAL_DOCS.md             # File ini (dokumentasi)
├── jadwal.php                 # Frontend halaman jadwal
│
└── admin/
    └── jadwal/
        ├── index.php          # List & kelola jadwal
        ├── tambah.php         # Form tambah jadwal
        ├── edit.php           # Form edit jadwal
        ├── delete.php         # Hapus jadwal
        └── move.php           # Move up/down jadwal
```

---

## 🗄️ DATABASE SETUP

### Langkah 1: Buat Tabel
Jalankan file **JADWAL_SETUP.sql** di phpMyAdmin atau MySQL client:

```bash
mysql -u root gbisalemba < C:\xampp\htdocs\gbisalemba\JADWAL_SETUP.sql
```

Atau buka phpMyAdmin → Import → Pilih JADWAL_SETUP.sql

### Struktur Tabel

```sql
CREATE TABLE jadwal (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Sample Data
File SQL sudah include 8 sample data untuk testing.

---

## 🔧 FITUR ADMIN

### 1. **List Jadwal** (index.php)
- Tampilkan semua jadwal ordered by urutan
- Actions:
  - ✏️ Edit
  - 🗑️ Delete
  - 🔄 Toggle Status
  - ⬆️ Move Up
  - ⬇️ Move Down

### 2. **Tambah Jadwal** (tambah.php)
- Form lengkap untuk input jadwal baru
- Auto-assign urutan dengan: `COALESCE(MAX(urutan), 0) + 1`
- Validation field required
- Kategori & hari menggunakan dropdown

### 3. **Edit Jadwal** (edit.php)
- Edit semua field kecuali urutan
- Pre-filled dengan data existing
- Info urutan & created_at di sidebar

### 4. **Delete Jadwal** (delete.php)
- Hapus jadwal by ID
- Confirmation via JavaScript
- Redirect dengan success/error message

### 5. **Move Jadwal** (move.php)
- **Move Up**: Swap dengan item di atasnya
- **Move Down**: Swap dengan item di bawahnya
- Menggunakan **Transaction** untuk data safety
- Logic:
  ```php
  // Untuk UP: Cari urutan < current, order DESC
  // Untuk DOWN: Cari urutan > current, order ASC
  // Swap dengan 3-step update (temp value)
  ```

---

## 🌐 FRONTEND

### Halaman: jadwal.php

**URL:** `http://localhost/gbisalemba/jadwal.php`

**Fitur:**
- ✅ Hanya tampilkan jadwal dengan status = 'aktif'
- ✅ Group by kategori
- ✅ Responsive card layout
- ✅ Format jam: HH:MM WIB
- ✅ Empty state jika belum ada jadwal
- ✅ Icon untuk hari, jam, lokasi
- ✅ Catatan jika ada

**Design:**
- Hero section dengan gradient background
- Card hover effect
- Mobile responsive
- Clean & modern UI

---

## 🎯 CARA MENGGUNAKAN

### 1. Setup Database
```bash
# Jalankan di MySQL/phpMyAdmin
source C:\xampp\htdocs\gbisalemba\JADWAL_SETUP.sql;
```

### 2. Akses Admin Panel
```
URL: http://localhost/gbisalemba/admin/jadwal/
```

### 3. Tambah Jadwal Baru
1. Klik tombol **"Tambah Jadwal"**
2. Isi form:
   - Judul (wajib)
   - Kategori (pilih dari dropdown)
   - Hari (pilih dari dropdown)
   - Jam Mulai & Selesai (wajib)
   - Lokasi (opsional)
   - Catatan (opsional)
   - Status (aktif/nonaktif)
3. Klik **"Simpan Jadwal"**

### 4. Atur Urutan Jadwal
- Gunakan tombol ⬆️ (Move Up) untuk naik
- Gunakan tombol ⬇️ (Move Down) untuk turun
- Urutan otomatis ter-swap dengan item di atas/bawah

### 5. Toggle Status
- Klik tombol 🔄 (Toggle) untuk aktifkan/nonaktifkan
- Jadwal nonaktif tidak muncul di frontend

### 6. Lihat di Website
```
URL: http://localhost/gbisalemba/jadwal.php
```

---

## 🛡️ KEAMANAN & BEST PRACTICES

### ✅ Yang Sudah Diterapkan:

1. **MySQLi OOP** dengan prepared statement
2. **Transaction** untuk move up/down
3. **htmlspecialchars()** untuk semua output
4. **Validation** input di server-side
5. **Auth check** di semua halaman admin
6. **Type casting** untuk ID (int)
7. **No duplicate query**
8. **Clean code structure**

### 🔒 Security Features:

```php
// Prepared Statement
$stmt = $conn->prepare("SELECT * FROM jadwal WHERE id = ?");
$stmt->bind_param("i", $id);

// Output Escaping
echo htmlspecialchars($jadwal['judul']);

// Transaction
$conn->begin_transaction();
try {
    // ... queries
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
}
```

---

## 📊 DATABASE QUERY EXAMPLES

### Get All Active Jadwal (Frontend)
```php
$stmt = $conn->prepare("SELECT * FROM jadwal WHERE status = 'aktif' ORDER BY urutan ASC");
```

### Get Next Urutan Number
```php
$stmt = $conn->prepare("SELECT COALESCE(MAX(urutan), 0) + 1 AS next_urutan FROM jadwal");
```

### Swap Urutan (Transaction)
```php
$conn->begin_transaction();

// Step 1: Current to temp
UPDATE jadwal SET urutan = -1 WHERE id = ?

// Step 2: Target to current's old urutan
UPDATE jadwal SET urutan = ? WHERE id = ?

// Step 3: Current to target's old urutan
UPDATE jadwal SET urutan = ? WHERE id = ?

$conn->commit();
```

---

## 🎨 KATEGORI JADWAL

Default kategori yang tersedia:
1. **Ibadah Umum** - Ibadah reguler
2. **Doa dan Puasa** - Kegiatan doa
3. **Pemahaman Alkitab** - Kelas PA
4. **Kelompok Usia** - Pemuda, remaja, anak
5. **Persekutuan Khusus** - Persekutuan tertentu
6. **Kegiatan Lainnya** - Kegiatan umum

---

## 📱 RESPONSIVE DESIGN

Modul ini fully responsive untuk:
- 📱 Mobile (< 768px)
- 📱 Tablet (768px - 1024px)
- 💻 Desktop (> 1024px)

---

## ❓ TROUBLESHOOTING

### Error: "Table jadwal doesn't exist"
**Solusi:** Jalankan JADWAL_SETUP.sql

### Error: "Call to undefined function mysqli_connect()"
**Solusi:** Enable extension=mysqli di php.ini

### Move Up/Down Tidak Bekerja
**Solusi:** 
- Pastikan database support transaction (InnoDB)
- Check error di index.php?error=...

### Jadwal Tidak Muncul di Frontend
**Solusi:**
- Pastikan status = 'aktif'
- Check query di jadwal.php
- Verify database connection

---

## 🚀 PENGEMBANGAN LEBIH LANJUT

Ide untuk improvement:
- [ ] Export jadwal ke PDF
- [ ] Import jadwal dari Excel
- [ ] Notification email untuk jadwal baru
- [ ] Filter jadwal by hari/kategori di admin
- [ ] Recurring event (jadwal berulang)
- [ ] Calendar view
- [ ] Search functionality

---

## 📞 SUPPORT

Jika ada pertanyaan atau bug, silakan:
1. Check dokumentasi ini
2. Review kode di file terkait
3. Check error message di browser/console

---

## ✅ CHECKLIST IMPLEMENTASI

- [x] Database table created
- [x] Admin CRUD complete
- [x] Move up/down with transaction
- [x] Toggle status functionality
- [x] Frontend display with grouping
- [x] Responsive design
- [x] Security measures applied
- [x] Sample data included
- [x] Documentation complete

---

## 📝 CHANGELOG

### Version 1.0 (2026-02-21)
- ✅ Initial release
- ✅ Full CRUD functionality
- ✅ Transaction-safe move operations
- ✅ Responsive frontend
- ✅ Security implemented
- ✅ Documentation complete

---

**Developed for GBI Salemba**  
**Tech Stack:** PHP Native, MySQLi OOP, MySQL, Bootstrap 4
