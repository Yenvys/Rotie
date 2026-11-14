# 🥐 ROTIÉ — Artisan Bakery

<p align="center">
  <img src="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1200&q=80" alt="ROTIE Artisan Bakery Banner" width="100%" style="border-radius: 12px; max-height: 400px; object-fit: cover;" />
</p>

<p align="center">
  <a href="https://github.com/Yenvys/Rotie"><img src="https://img.shields.io/badge/GitHub-Yenvys%2FRotie-181717?style=for-the-badge&logo=github&logoColor=white" alt="GitHub Repo"></a>
  <img src="https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/Vanilla_JS-ES6%2B-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
</p>

---

> **Aplikasi Website UMKM Modern & Sistem Manajemen Toko Roti Premium**  
> Tugas Capstone Ujian Akhir Semester (UAS) Pemrograman Web Dasar — **STMIK IKMI Cirebon**  
> Dosen Pengampu: **Khaerul Anam, M.Kom**  
> Pengembang: **Muhammad Al Farel** ([@Yenvys](https://github.com/Yenvys)) — **NIM: 41257124**

---

## 📖 1. Tentang Aplikasi

**ROTIÉ — Artisan Bakery** adalah platform aplikasi web UMKM modern terpadu yang memadukan estetika desain editorial mewah (*modern editorial luxury*) dengan sistem pengelolaan operasional toko roti (*back-office ERP-lite*) yang tangguh.

Aplikasi ini dibangun menggunakan **PHP Native, PDO (PHP Data Objects), MySQL, HTML5 Semantik, Tailwind CSS CDN, Modern Vanilla JavaScript, GSAP ScrollTrigger, dan Chart.js** tanpa framework berat, memenuhi 100% instruksi dan capaian pembelajaran mata kuliah Pemrograman Web Dasar.

### Dua Ekosistem Terintegrasi:
1. **Public Landing Page (Pelanggan):** Halaman depan interaktif dengan 9 section editorial sinematik, katalog asinkron (Fetch API), transisi morphing (*PowerPoint-like shared element transition*), keranjang belanja realtime DOM, dan pemesanan WhatsApp otomatis yang langsung tersimpan di database MySQL.
2. **Protected Admin Dashboard (Pengelola):** Panel operasional terproteksi peran (*Role-Based Access Control: Admin & Owner*) dengan visualisasi analitik Chart.js, manajemen produk lengkap dengan ekspor/impor CSV, manajemen kategori, pengelolaan transaksi dengan automasi mutasi stok, serta laporan penjualan komprehensif.

---

## ✨ 2. Fitur Utama Sistem

### A. Sisi Pelanggan (Public Landing Page)
- **9 Section Editorial Sinematik:**
  1. *Hero Section:* Tipografi *Playfair Display*, parallax foto roti, dual CTA, dan floating badges (*36-Hour Sourdough* & *French Butter*).
  2. *Brand Story:* Tata letak asimetris majalah kuliner bercerita tentang fermentasi alami tanpa bahan pengawet artifisial dengan efek *mask reveal*.
  3. *Signature Products:* Showcase kreasi masterpiece unggulan bakery.
  4. *Product Collection:* Katalog interaktif dengan tab kategori (*All, Bread, Croissant, Pastry, Cake, Cookies*), live search ber-debounce 300ms, dan sorting harga.
  5. *Our Process:* 4 filosofi langkah mutu (*Selected Ingredients, Handmade Preparation, Freshly Baked, Ready to Serve*).
  6. *Special Promotion:* Banner promo hangat dengan **JavaScript Real-time Countdown Timer**.
  7. *Customer Experience:* Slider testimoni interaktif dengan navigasi dot dan rating bintang.
  8. *Visit Our Bakery:* Alamat gerai di Cirebon (Jl. Siliwangi No. 88), jam buka harian, tautan peta Google Maps, dan tombol kontak WhatsApp.
  9. *Footer:* Informasi hak cipta, navigasi cepat ramah SEO, dan jam operasional.
- **PowerPoint Morph-Like Transition:** Transisi gambar kartu katalog yang membesar dan meluncur berpindah posisi secara mulus (*shared element transition*) ke dalam modal detail tanpa jeda patah visual.
- **Shopping Cart Dinamis:** Slide-over drawer, kalkulasi total realtime di DOM, penyimpanan persisten di `localStorage`, serta modal pengisian data pemesan.
- **Direct WhatsApp Dispatcher:** Form pemesanan menyimpan transaksi ke MySQL dan menghasilkan pesan WhatsApp otomatis yang siap dikirim dengan satu sentuhan.

### B. Sisi Pengelola (Admin Dashboard Terproteksi)
- **Autentikasi Aman:** Bcrypt hash (`password_hash`), regenerasi session ID pasca login (`session_regenerate_id(true)`), dan anti-session fixation dengan toggle lihat password.
- **Dashboard Overview:** 6 kartu KPI statistik, grafik omzet 7 hari terakhir (Line Chart), komposisi kategori (Doughnut Chart), serta peringatan stok rendah.
- **Manajemen Produk:** CRUD lengkap, unggah berkas gambar (JPG/PNG/WEBP hingga 2MB) ke folder `uploads/products/`, serta pengarsipan aman jika produk memiliki riwayat transaksi.
- **Pertukaran Data CSV:** Ekspor katalog produk ke format spreadsheet CSV UTF-8 BOM dan pipeline impor massal CSV.
- **Manajemen Kategori:** Tambah, edit, dan hapus kategori roti dengan validasi relasi produk aktif.
- **Manajemen Transaksi & Mutasi Stok Otomatis:** Pembaruan status pesanan via AJAX. Saat status pesanan diubah ke *Diproses* atau *Selesai*, sistem otomatis memotong persediaan stok fisik pada tabel `products` dan mencatatnya ke tabel `stock_movements`.
- **Monitoring Stok & Audit Trail:** Pemantauan batas kritis stok dan tabel log riwayat mutasi masuk/keluar dengan saldo sebelum dan sesudah.
- **Laporan Penjualan:** Filter periode (Hari Ini, Bulan Ini, Semua, Kustom), ranking produk terlaris (*best sellers*), dan fitur Ekspor Laporan CSV.

---

## 🔒 3. Empat Lapisan Pengamanan (Security Hardening)

Sesuai instruksi soal praktikum UAS STMIK IKMI Cirebon, aplikasi ini mengimplementasikan 4 lapisan pengerasan keamanan:

1. **Isolasi Kredensial Basis Data (Environment Config Isolation):**
   - Kredensial disimpan dalam `config/env.php`.
   - Berkas `config/database.php` membaca konfigurasi secara terisolasi tanpa mengekspos kredensial rahasia ke ruang publik.
   - Tersedia berkas templat `config/env.example.php`.

2. **Pengerasan Server Apache (.htaccess):**
   - Menonaktifkan penjelajahan isi direktori: `Options -Indexes`.
   - Memblokir seluruh akses publik ke berkas sensitif: `.(sql|env|ini|log|sh|bak|json)$`.
   - Mengunci akses langsung ke berkas konfigurasi internal: `env.php`, `database.php`, `auth_check.php`.
   - Mematikan eksekusi skrip berbahaya di dalam direktori `uploads/` melalui `uploads/.htaccess`.
   - Menolak seluruh akses publik ke direktori log melalui `logs/.htaccess`.

3. **Pencegahan Serangan XSS & Proteksi Anti-CSRF:**
   - **Anti-XSS:** Seluruh luaran teks dari database dibungkus dengan fungsi sanitasi `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')`.
   - **Anti-CSRF:** Setiap form POST dan endpoint AJAX mutasi data memvalidasi token sesi acak menggunakan `hash_equals($_SESSION['csrf_token'], $token)`.

4. **Pemadaman Pesan Galat di Server Produksi:**
   - Logika otomatis pada `config/database.php`: jika `APP_ENV === 'production'`, `display_errors` dan `display_startup_errors` dimatikan (`0`), pelaporan galat dialihkan ke berkas terlindung `logs/error.log`.

---

## 🔑 4. Kredensial Pengujian Akun (Siap Pakai)

Untuk mempermudah pengujian dan evaluasi aplikasi, telah disiapkan akun bawaan:

| Peran Akun | Nama Pengguna (Username) | Kata Sandi (Password) | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Manajemen produk, kategori, stok, dan transaksi |
| **Owner (Pemilik)** | `owner` | `owner123` | Akses penuh operasional dan laporan analitik omzet |
| **Kunci Registrasi** | — | `ROTIE_IKMI_2026` | Kunci otorisasi form pendaftaran admin baru |

---

## 🚀 5. Panduan Instalasi & Menjalankan Aplikasi

### Persyaratan Sistem (System Requirements)
- **Web Server:** Apache 2.4+ (dengan modul `mod_rewrite` & `mod_headers` aktif).
- **PHP:** Versi 8.0, 8.1, 8.2, atau 8.3 (Ekstensi `pdo`, `pdo_mysql`, `session`, `mbstring`, `fileinfo` aktif).
- **Database:** MySQL 5.7+ atau MariaDB 10.4+.
- **Browser:** Google Chrome, Mozilla Firefox, Microsoft Edge, atau Safari modern.

---

### Langkah 1: Kloning Repositori Git

Buka terminal / Command Prompt di direktori web server Anda (`htdocs` pada XAMPP atau `www` pada Laragon):

```bash
# Untuk Laragon (C:\laragon\www) atau XAMPP (C:\xampp\htdocs)
git clone https://github.com/Yenvys/Rotie.git
cd Rotie
```

---

### Langkah 2: Impor Basis Data MySQL

1. Buka XAMPP / Laragon Control Panel, lalu jalankan modul **Apache** dan **MySQL**.
2. Akses phpMyAdmin di [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
3. Buat database baru dengan nama: `uas_rotie_db` (*collation* `utf8mb4_unicode_ci`).
4. Pilih database `uas_rotie_db`, klik tab **Import**, pilih berkas `database.sql` yang ada di root direktori proyek, lalu klik **Go / Kirim**.
5. Seluruh 7 tabel relasional dan data *seeding* awal akan terbuat secara sempurna.

---

### Langkah 3: Konfigurasi Lingkungan (`config/env.php`)

Salin `config/env.example.php` menjadi `config/env.php` (jika belum ada) dan sesuaikan pengaturannya:

```php
return [
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'uas_rotie_db',
    'DB_USER' => 'root',
    'DB_PASS' => '',           // Kosongkan untuk default XAMPP/Laragon
    'APP_ENV' => 'development', // Ubah ke 'production' saat hosting publik
    'APP_NAME' => 'ROTIÉ — Artisan Bakery',
    'APP_URL' => 'http://localhost/Rotie',
    'WHATSAPP_NUMBER' => '628981295171',
    'ADMIN_REGISTER_KEY' => 'ROTIE_IKMI_2026',
];
```

---

### Langkah 4: Akses Aplikasi di Browser

Buka peramban web dan akses URL berikut:

- **Halaman Depan Pelanggan (Landing Page):**  
  `http://localhost/Rotie/index.php`
- **Portal Pengelola (Login Admin / Owner):**  
  `http://localhost/Rotie/admin.php` (atau `http://localhost/Rotie/auth/login.php`)

---

## 📁 6. Struktur Direktori Proyek

Susunan berkas telah diorganisasikan secara modular dan bersih:

```
Rotie/
├── .htaccess                      # Pengerasan peladen Apache (Anti-Listing & Protection)
├── .gitignore                     # Filter berkas sensitif, vendor & build artifact
├── index.php                      # Public Landing Page 9 Section & Katalog Interaktif
├── admin.php                      # Controller Router Utama Dashboard (Whitelist Routing)
├── database.sql                   # Skrip DDL/DML, 7 Tabel Relasional & Seeding Data
├── README.md                      # Panduan Instalasi, Akun Demo & Dokumentasi Lengkap
│
├── config/
│   ├── env.php                    # Isolasi kredensial basis data & konfigurasi lingkungan
│   ├── env.example.php            # Templat referensi kredensial aman repositori
│   └── database.php               # Inisialisasi PDO Singleton, Mode Exception, UTF-8 & Log Galat
│
├── auth/
│   ├── auth_check.php             # Guard Middleware sesi & inisialisasi Anti-CSRF Token
│   ├── login.php                  # Form & otentikasi login (Bcrypt verify & toggle view password)
│   ├── registrasi.php             # Form pendaftaran akun pengelola baru (password_hash)
│   └── logout.php                 # Penghancuran sesi login & pembersihan cookie browser
│
├── layouts/
│   ├── header.php                 # Template pembuka HTML, CDN Tailwind CSS, Chart.js
│   ├── sidebar.php                # Navigasi sidebar terpadu dengan active menu state
│   ├── topbar.php                 # Bar identitas pengguna sesi & breadcrumb navigasi
│   └── footer.php                 # Penutup layout HTML & skrip pendukung
│
├── modules/
│   ├── dashboard/index.php        # Ringkasan metrik KPI, alert stok kritis & Chart.js
│   ├── produk/                    # CRUD komoditas roti, live katalog, ekspor & impor massal CSV
│   ├── kategori/                  # Manajemen klasifikasi menu kategori produk
│   ├── transaksi/                 # Monitoring pesanan pelanggan, nota detail, & update status
│   ├── stok/                      # Monitoring fisik & audit log riwayat mutasi stok
│   └── laporan/index.php          # Laporan penjualan, produk terlaris & ekspor CSV
│
├── api/
│   ├── cari_produk.php            # Endpoint REST JSON: Live search & filter kategori asinkron
│   ├── detail_produk.php          # Endpoint REST JSON: Ambil rincian item untuk modal morph
│   ├── tambah_keranjang.php       # Endpoint REST JSON: Validasi kuantitas & stok
│   ├── buat_pesanan.php           # Endpoint REST JSON: Pembuatan pesanan (PDO Transaction)
│   ├── hapus_produk.php           # Endpoint REST JSON: Penghapusan produk asinkron + CSRF
│   └── update_status.php          # Endpoint REST JSON: Update status transaksi & potong stok
│
├── assets/
│   ├── css/style.css              # Custom styling, fonts, glassmorphism, cursor & keyframes
│   └── js/
│       ├── main.js                # Navigasi scroll, drawer mobile, magnetic button, cursor
│       ├── animations.js          # GSAP timelines, parallax, promo countdown, slider
│       ├── morph-transitions.js   # Shared element morph transition (Card to Modal)
│       ├── produk_async.js        # Modern Fetch API live search & debouncing 300ms
│       ├── cart.js                # Dynamic shopping cart DOM, checkout & WhatsApp generator
│       └── dashboard.js           # Chart.js integration & status handler
│
├── uploads/
│   ├── .htaccess                  # Proteksi anti-eksekusi skrip PHP di folder unggahan
│   └── products/                  # Direktori unggahan berkas gambar roti
│
└── logs/
    ├── .htaccess                  # Pemblokiran seluruh akses publik ke berkas log
    └── error.log                  # Log galat internal server pada mode produksi
```

---

## 👤 7. Pengembang & Repositori

- **Nama Pengembang:** Muhammad Al Farel
- **NIM:** 41257124
- **Program Studi:** Teknik Informatika / Rekayasa Perangkat Lunak (RPL)
- **Perguruan Tinggi:** STMIK IKMI Cirebon
- **Akun GitHub:** [@Yenvys](https://github.com/Yenvys)
- **Repositori Resmi:** [https://github.com/Yenvys/Rotie](https://github.com/Yenvys/Rotie)

---

## 📄 8. Lisensi & Hak Cipta

&copy; 2026 **ROTIÉ — Artisan Bakery**. Developed by [Yenvys](https://github.com/Yenvys) (Muhammad Al Farel).  
Projek ini dibuat untuk keperluan Capstone Ujian Akhir Semester Pemrograman Web Dasar, STMIK IKMI Cirebon. Dilindungi di bawah lisensi MIT.
