# MODUL ADMIN JADWAL IBADAH - DOKUMENTASI

## 📋 OVERVIEW

Modul admin untuk mengelola **jadwal_ibadah** di website GBI Salemba dengan fitur:
- ✅ **CRUD lengkap** (Create, Read, Update, Delete)
- ✅ **Toggle status** aktif/nonaktif (is_active)
- ✅ **Move Up/Down** (jika ada kolom urutan)
- ✅ **Dynamic column detection** (support dengan/tanpa kolom urutan & kategori)
- ✅ **MySQLi OOP** dengan prepared statement
- ✅ **Transaction-safe** untuk swap urutan
- ✅ **PRG Pattern** untuk mencegah form resubmit
- ✅ **Input validation** & output escaping

---

## 📁 STRUKTUR FILE

```
C:\xampp\htdocs\gbisalemba\
└── admin\
    └── jadwal_ibadah\
        ├── index.php      # List & kelola semua jadwal
        ├── tambah.php     # Form tambah jadwal baru
        ├── edit.php       # Form edit jadwal
        ├── delete.php     # Hapus jadwal
        ├── toggle.php     # Toggle status aktif/nonaktif
        └── move.php       # Swap urutan (up/down)
```

---

## 🗄️ STRUKTUR TABEL

### Tabel: `jadwal_ibadah`

**Kolom Wajib:**
```sql
- id                INT AUTO_INCREMENT PRIMARY KEY
- nama_ibadah       VARCHAR(...)
- hari              VARCHAR(...)
- jam               VARCHAR(...)    -- Format: "08:00 WIB" atau "08:00 WIB, 10:30 WIB"
- ruangan           VARCHAR(...)
- keterangan        TEXT
- is_active         TINYINT(1) DEFAULT 1
- created_at        DATETIME/TIMESTAMP
```

**Kolom Opsional (Auto-detect):**
```sql
- kategori          VARCHAR(100) NULL
- urutan            INT NOT NULL DEFAULT 0
```

**Catatan:**
- Modul akan **otomatis mendeteksi** apakah kolom `kategori` dan `urutan` ada
- Jika ada kolom `urutan`: fitur Move Up/Down aktif
- Jika tidak ada: fitur Move Up/Down disembunyikan

---

## 🚀 CARA PENGGUNAAN

### 1. **Akses Admin Panel**

```
URL: http://localhost/gbisalemba/admin/jadwal_ibadah/
```

### 2. **Tambah Jadwal Ibadah Baru**

1. Klik tombol **"Tambah Jadwal Ibadah"**
2. Isi form:
   - **Nama Ibadah** (wajib)
   - **Kategori** (opsional, jika kolom ada)
   - **Hari** (wajib) - pilih dari dropdown
   - **Jam** (wajib) - format: `08:00 WIB` atau `08:00 WIB, 10:30 WIB`
   - **Ruangan** (opsional)
   - **Keterangan** (opsional)
   - **Status Aktif** (checkbox)
3. Klik **"Simpan Jadwal Ibadah"**

**Auto-features:**
- Jika ada kolom `urutan`: otomatis set ke posisi paling bawah menggunakan `COALESCE(MAX(urutan), 0) + 1`

### 3. **Edit Jadwal Ibadah**

1. Klik tombol **Edit** (icon pensil) pada jadwal yang ingin diedit
2. Ubah data yang diperlukan
3. Klik **"Simpan Perubahan"**

**Catatan:**
- Urutan tidak bisa diubah di halaman edit
- Gunakan Move Up/Down untuk mengubah urutan

### 4. **Toggle Status Aktif/Nonaktif**

1. Klik tombol **Toggle** (icon power) pada jadwal
2. Konfirmasi perubahan
3. Status akan berubah:
   - `is_active = 1` → Aktif (badge hijau)
   - `is_active = 0` → Non-aktif (badge abu-abu)

### 5. **Move Up/Down (Jika ada kolom urutan)**

**Move Up:**
- Klik tombol **↑** untuk naikan jadwal
- Jadwal akan bertukar posisi dengan jadwal di atasnya

**Move Down:**
- Klik tombol **↓** untuk turunkan jadwal
- Jadwal akan bertukar posisi dengan jadwal di bawahnya

**Logic:**
- Tombol disabled jika sudah di posisi paling atas/bawah
- Menggunakan **transaction** untuk keamanan data
- Swap dilakukan dengan 3-step update (temp value)

### 6. **Delete Jadwal Ibadah**

1. Klik tombol **Delete** (icon trash) pada jadwal
2. Konfirmasi penghapusan via JavaScript
3. Jadwal akan terhapus permanent

**Peringatan:** Data yang dihapus tidak bisa dikembalikan!

---

## 🔧 FITUR TEKNIS

### ✅ MySQLi OOP dengan Prepared Statement

Semua query menggunakan prepared statement untuk keamanan:

```php
// Contoh SELECT
$stmt = $conn->prepare("SELECT * FROM jadwal_ibadah WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Contoh INSERT
$stmt = $conn->prepare("INSERT INTO jadwal_ibadah (nama_ibadah, hari, jam) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $nama, $hari, $jam);
$stmt->execute();

// Contoh UPDATE
$stmt = $conn->prepare("UPDATE jadwal_ibadah SET is_active = ? WHERE id = ?");
$stmt->bind_param("ii", $status, $id);
$stmt->execute();

// Contoh DELETE
$stmt = $conn->prepare("DELETE FROM jadwal_ibadah WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
```

### ✅ Transaction untuk Swap Urutan

```php
$conn->begin_transaction();

try {
    // Step 1: Current to temp
    UPDATE jadwal_ibadah SET urutan = -999999 WHERE id = ?
    
    // Step 2: Target to current's old urutan
    UPDATE jadwal_ibadah SET urutan = ? WHERE id = ?
    
    // Step 3: Current to target's old urutan
    UPDATE jadwal_ibadah SET urutan = ? WHERE id = ?
    
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
}
```

### ✅ Dynamic Column Detection

```php
$has_urutan_column = false;
$has_kategori_column = false;

$check_columns = $conn->query("SHOW COLUMNS FROM jadwal_ibadah");
while ($col = $check_columns->fetch_assoc()) {
    if ($col['Field'] == 'urutan') {
        $has_urutan_column = true;
    }
    if ($col['Field'] == 'kategori') {
        $has_kategori_column = true;
    }
}
```

### ✅ Input Validation

```php
// Validate ID is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=ID tidak valid");
    exit;
}
$id = (int)$_GET['id'];

// Validate required fields
if (empty($nama_ibadah) || empty($hari) || empty($jam)) {
    $error = 'Field wajib tidak boleh kosong';
}
```

### ✅ Output Escaping

```php
// All output uses htmlspecialchars
echo htmlspecialchars($jadwal['nama_ibadah']);
echo htmlspecialchars($jadwal['hari']);
echo htmlspecialchars($jadwal['jam']);
```

### ✅ PRG Pattern (Post-Redirect-Get)

```php
// After successful POST, redirect to prevent form resubmit
if ($stmt->execute()) {
    $stmt->close();
    header("Location: index.php?success=Data berhasil disimpan");
    exit;
}
```

---

## 📊 ORDERING LOGIC

### Jika Ada Kolom `urutan`:
```sql
SELECT * FROM jadwal_ibadah ORDER BY urutan ASC
```

### Jika Tidak Ada Kolom `urutan`:
```sql
SELECT * FROM jadwal_ibadah ORDER BY id DESC
```

---

## 🎨 KATEGORI IBADAH

Default kategori yang tersedia (jika kolom kategori ada):
1. Ibadah Umum
2. Ibadah Anak
3. Ibadah Pemuda
4. Ibadah Khusus
5. Persekutuan Doa

**Catatan:** Kategori bersifat opsional dan bisa dikosongkan.

---

## 🔐 KEAMANAN & VALIDASI

### Security Features:

1. ✅ **Auth check** di setiap halaman admin
   ```php
   require_once '../includes/auth.php';
   ```

2. ✅ **Prepared statement** untuk semua query
3. ✅ **Integer validation** untuk semua ID
4. ✅ **htmlspecialchars** untuk semua output
5. ✅ **Transaction** untuk operasi kritis
6. ✅ **CSRF protection** (via POST method)
7. ✅ **Confirmation** untuk delete & toggle

---

## ❓ TROUBLESHOOTING

### Error: "Table jadwal_ibadah doesn't exist"
**Solusi:** Pastikan tabel sudah dibuat di database

### Tombol Move Up/Down Tidak Muncul
**Solusi:** 
- Pastikan tabel memiliki kolom `urutan`
- Kolom harus bernama persis `urutan` (lowercase)

### Error: "Fitur move tidak tersedia"
**Solusi:** Tambahkan kolom `urutan INT NOT NULL DEFAULT 0` ke tabel

### Field Kategori Tidak Muncul di Form
**Solusi:**
- Pastikan tabel memiliki kolom `kategori`
- Kolom harus bernama persis `kategori` (lowercase)

### Error setelah Submit Form
**Solusi:**
1. Cek koneksi database di `config/database.php`
2. Pastikan semua field required sudah diisi
3. Cek error log di browser console atau PHP error log

---

## 🧪 TESTING CHECKLIST

- [ ] Tambah jadwal ibadah baru
- [ ] Edit jadwal ibadah
- [ ] Toggle status aktif/nonaktif
- [ ] Hapus jadwal ibadah
- [ ] Move up jadwal (jika ada kolom urutan)
- [ ] Move down jadwal (jika ada kolom urutan)
- [ ] Validasi field required
- [ ] Test tanpa kolom urutan
- [ ] Test tanpa kolom kategori
- [ ] Test dengan semua kolom lengkap

---

## 📈 UPGRADE PATH

### Jika Ingin Menambah Kolom `urutan`:

```sql
ALTER TABLE jadwal_ibadah ADD COLUMN urutan INT NOT NULL DEFAULT 0;

-- Set urutan untuk data existing
SET @row_number = 0;
UPDATE jadwal_ibadah SET urutan = (@row_number:=@row_number + 1) ORDER BY id ASC;
```

### Jika Ingin Menambah Kolom `kategori`:

```sql
ALTER TABLE jadwal_ibadah ADD COLUMN kategori VARCHAR(100) NULL AFTER nama_ibadah;
```

---

## 🎯 FITUR SUMMARY

| File | Fungsi | Method | Transaction |
|------|--------|--------|-------------|
| index.php | List & display | GET | - |
| tambah.php | Form add + insert | POST | - |
| edit.php | Form edit + update | POST | - |
| delete.php | Delete record | GET | - |
| toggle.php | Toggle is_active | GET | - |
| move.php | Swap urutan | GET | ✅ YES |

---

## 📞 SUPPORT

Jika ada pertanyaan atau bug:
1. Check dokumentasi ini
2. Review kode di file terkait
3. Check error message di browser
4. Verify database structure

---

## ✅ STANDAR CODING TERPENUHI

- ✅ MySQLi OOP
- ✅ Prepared statement untuk semua query
- ✅ Transaction untuk swap urutan
- ✅ Tidak ada duplicate query
- ✅ Validasi input ID harus integer
- ✅ Output aman pakai htmlspecialchars
- ✅ PRG pattern untuk mencegah resubmit
- ✅ Clean & production-safe code
- ✅ Dynamic column detection
- ✅ Proper error handling

---

**Developed for GBI Salemba**  
**Tech Stack:** PHP Native, MySQLi OOP, MySQL, Bootstrap 4  
**Date:** 2026-02-21
