# 🚀 QUICK START - SISTEM FORMULIR GBI SALEMBA

## ⚡ Dalam 5 Menit, Siap Digunakan!

### Step 1: Setup Database (2 menit)

#### Option A: Menggunakan phpMyAdmin
1. Buka `http://localhost/phpmyadmin`
2. Login ke database `gbi_salemba`
3. Buat table formulir:
   - Klik "SQL" di top menu
   - Copy seluruh kode dari file `FORMULIR_SAMPLE_DATA.sql`
   - Paste ke text area
   - Klik "Go"
4. ✅ Tabel & sample data siap!

#### Option B: Menggunakan Terminal
```bash
cd c:\xampp\mysql\bin
mysql -u root -p gbi_salemba < "C:\xampp\htdocs\gbisalemba\FORMULIR_SAMPLE_DATA.sql"
# Enter password (kosongkan jika tidak ada)
```

### Step 2: Akses Admin Panel (1 menit)

1. Pastikan XAMPP running
2. Akses: `http://localhost/gbisalemba/admin/formulir.php`
3. Anda sudah login? Great! Lanjut ke Step 3
4. Belum login? Login terlebih dahulu di `http://localhost/gbisalemba/admin/login.php`

### Step 3: Upload Sample PDF (1 menit)

1. Di admin panel, klik tab "Daftar Formulir"
2. Lihat list formulir dari sample data
3. Klik "Edit" pada salah satu formulir
4. Upload PDF file untuk test
5. Klik "Perbarui Formulir"
6. ✅ File berhasil diupload!

### Step 4: Test Frontend (1 menit)

1. Buka: `http://localhost/gbisalemba/formulir.php`
2. Lihat card grid formulir yang aktif
3. Klik "Download" untuk test download PDF
4. ✅ Sempurna! Sistem berjalan normal!

---

## ✨ Quick Features Tour

### Admin Features
| Fitur | Keterangan |
|-------|-----------|
| **Daftar** | Lihat semua formulir dalam tabel |
| **Tambah** | Tambah formulir baru dengan PDF |
| **Edit** | Ubah data & upload PDF baru |
| **Hapus** | Delete formulir & file |
| **Sort** | Order by urutan (drag & drop tidak ada, edit urutan saja) |

### Frontend Features
| Fitur | Keterangan |
|-------|-----------|
| **Grid Card** | Tampilan modern card grid responsive |
| **Filter** | Hanya tampilkan formulir aktif |
| **Download** | Direct download PDF button |
| **Responsive** | Mobile, tablet, desktop friendly |

---

## 🔑 Key Files

| File | Fungsi |
|------|--------|
| `admin/formulir.php` | Admin panel untuk kelola formulir |
| `formulir.php` | Frontend page untuk jemaat download formulir |
| `uploads/formulir/` | Folder penyimpanan file PDF |
| `FORMULIR_SYSTEM_DOCS.md` | Dokumentasi lengkap |
| `FORMULIR_SAMPLE_DATA.sql` | Sample data untuk testing |

---

## 🎨 UI/UX Highlights

### Admin Panel
- 📊 Tab system (Daftar / Tambah)
- 📝 Form validation
- 🎯 Alert success/error
- 📱 Responsive design
- 🎨 Modern styling (consistent dengan design system)

### Frontend
- 🏗️ Card grid layout
- 🎯 Hover effects
- 📥 Download button
- 🎨 Gradient background
- 📱 Mobile-first responsive

---

## 🛡️ Security Built-in

✅ **SQL Injection Protection** - Prepared statements
✅ **XSS Prevention** - htmlspecialchars() escaping
✅ **File Upload Security** - MIME type validation
✅ **File Access Control** - file_exists() check
✅ **Input Validation** - All fields validated

---

## 📋 Sample Data Included

```
- Formulir Pendaftaran Pelayanan
- Formulir Kelompok Sel
- Formulir Pendaftaran Kursus Dasar Iman
- Kartu Kontribusi Persembahan
- Formulir Permintaan Doa
- Formulir Konseling Rohani (Nonaktif = contoh)
```

Anda bisa:
- ✏️ Edit nama, deskripsi, urutan
- 📤 Upload PDF untuk setiap formulir
- 🔄 Change status aktif/nonaktif
- 🗑️ Delete formulir yang tidak diperlukan

---

## 🎯 Next Steps

### Untuk Development
- [ ] Customize sample data sesuai kebutuhan gereja Anda
- [ ] Upload PDF formulir yang sebenarnya
- [ ] Test di berbagai browser
- [ ] Test download PDF
- [ ] Adjust styling sesuai brand gereja

### Untuk Production
- [ ] Backup database
- [ ] Test thoroughly
- [ ] Monitor upload folder permissions
- [ ] Setup regular backups
- [ ] Train admin untuk manage formulir

---

## ❓ FAQ

**Q: Bagaimana cara mengubah urutan formulir?**
A: Edit formulir dan ubah field "Urutan" kemudian save.

**Q: Bisakah saya hide formulir tanpa menghapus?**
A: Ya! Ubah status ke "Nonaktif", tidak akan tampil di frontend.

**Q: File PDF yang diupload kemana?**
A: Tersimpan di folder `uploads/formulir/` dengan nama yang di-rename otomatis.

**Q: Berapa ukuran maksimal file PDF?**
A: 10MB. Bisa diubah di line `define('MAX_UPLOAD_SIZE', ...)`

**Q: Bagaimana jika PDF tidak ter-upload?**
A: Check pesan error di admin panel atau periksa folder permissions.

---

## 📞 Support

Jika ada error atau pertanyaan:

1. Check documentation: `FORMULIR_SYSTEM_DOCS.md`
2. Check database connection: `config/database.php`
3. Check folder permissions: `chmod 755 uploads/formulir/`
4. Review error messages di admin panel
5. Check browser console untuk JavaScript errors

---

**Happy Using! 🎉**  
Sistem formulir siap meningkatkan engagement dengan jemaat!

**Last Updated:** 12 Februari 2026
