# QUICK START - Modul Admin Jadwal Ibadah

## 🚀 Akses Cepat

```
URL Admin: http://localhost/gbisalemba/admin/jadwal_ibadah/
```

## 📦 File yang Dibuat

1. **index.php** - List semua jadwal ibadah
2. **tambah.php** - Form tambah jadwal baru
3. **edit.php** - Form edit jadwal
4. **delete.php** - Hapus jadwal
5. **toggle.php** - Toggle status aktif/nonaktif
6. **move.php** - Swap urutan (jika ada kolom urutan)

## ⚙️ Fitur Otomatis

### Auto-detect Kolom
Modul akan otomatis detect:
- ✅ Kolom `urutan` → Tampilkan fitur Move Up/Down
- ✅ Kolom `kategori` → Tampilkan field kategori di form
- ✅ Jika tidak ada → Feature disembunyikan otomatis

### Auto-ordering
- **Ada kolom urutan:** `ORDER BY urutan ASC`
- **Tidak ada kolom urutan:** `ORDER BY id DESC`

## 🔧 Standar Teknis

```php
✅ MySQLi OOP
✅ Prepared Statement (semua query)
✅ Transaction (swap urutan)
✅ htmlspecialchars (semua output)
✅ Integer validation (semua ID)
✅ PRG Pattern (Post-Redirect-Get)
```

## 📋 Field Wajib vs Opsional

### Wajib:
- Nama Ibadah
- Hari
- Jam

### Opsional:
- Kategori (jika kolom ada)
- Ruangan
- Keterangan
- Status Aktif (default: checked)

## 🎯 Format Jam

```
Single time:    "08:00 WIB"
Multiple times: "08:00 WIB, 10:30 WIB"
```

## 🔐 Keamanan

- Auth check di semua halaman
- Prepared statement (SQL injection safe)
- Input validation (XSS safe)
- Transaction (data integrity safe)

## 📊 Upgrade Database (Opsional)

### Tambah kolom urutan:
```sql
ALTER TABLE jadwal_ibadah ADD COLUMN urutan INT NOT NULL DEFAULT 0;

SET @row_number = 0;
UPDATE jadwal_ibadah SET urutan = (@row_number:=@row_number + 1) ORDER BY id ASC;
```

### Tambah kolom kategori:
```sql
ALTER TABLE jadwal_ibadah ADD COLUMN kategori VARCHAR(100) NULL AFTER nama_ibadah;
```

## ✅ Ready to Use!

Modul sudah production-ready dan siap digunakan langsung tanpa konfigurasi tambahan.
