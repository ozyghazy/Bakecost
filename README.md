# BAKECOST (Baking Converter & Cost Calculator)

Aplikasi web untuk membantu pelaku usaha baking mengonversi kebutuhan bahan sesuai target produksi dan menghitung estimasi biaya bahan baku serta HPP (Harga Pokok Penjualan).

---

## 🚀 Panduan Menjalankan Aplikasi di Komputer Kamu

Karena laptop kamu sudah memiliki **XAMPP** di `C:\xampp`, ikuti 3 langkah mudah ini:

### Langkah 1: Pindahkan / Copy Folder Proyek ke XAMPP
Salin folder `bakecost` ini ke dalam folder `htdocs` XAMPP:
- Dari: `C:\Users\ozygh\Documents\bakecost`
- Ke: `C:\xampp\htdocs\bakecost`

*(Bisa via File Explorer: klik kanan folder `bakecost` di Documents -> Copy -> Buka `C:\xampp\htdocs` -> Paste).*

---

### Langkah 2: Nyalakan XAMPP & Import Database
1. Buka aplikasi **XAMPP Control Panel**.
2. Klik tombol **Start** pada baris **Apache** dan **MySQL** (hingga keduanya berwarna hijau).
3. Buka browser (Chrome/Edge), lalu akses:
   ```
   http://localhost/phpmyadmin
   ```
4. Klik tab **Import** (atau buat database baru bernama `bakecost` lalu klik tab Import).
5. Klik **Choose File**, pilih file:
   `C:\xampp\htdocs\bakecost\bakecost.sql`
6. Gulir ke bawah lalu klik **Import / Go**.

---

### Langkah 3: Buka Aplikasi Web
Buka tab baru di browser kamu dan akses alamat:
```
http://localhost/bakecost
```

🎉 **Selesai!** Kamu akan disambut oleh halaman Welcome BAKECOST.
1. Klik tombol **Mulai**.
2. Klik **Daftar sekarang** untuk membuat akun pengguna pertamamu.
3. Setelah login, kamu bisa langsung mencoba seluruh fiturnya:
   - Input bahan baku
   - Buat resep manual atau via **✨ AI Generator**
   - Konversi takaran resep (Step 1-2-3)
   - Hitung HPP lengkap (termasuk tenaga kerja, overhead & packaging)
   - Uji penyusutan adonan panggang (**Bake Loss**)
   - Cek riwayat perhitungan dan perbandingan HPP

