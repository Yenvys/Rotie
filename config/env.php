<?php
/**
 * ====================================================================
 * CONFIG: Isolasi Kredensial Lingkungan (Environment Configuration)
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * Pengembang: Muhammad Al Farel (NIM: 41257124)
 * ====================================================================
 * Berkas ini dilarang keras dapat diakses langsung oleh publik.
 * Dilindungi oleh aturan web server Apache (.htaccess).
 */

return [
    // Pengaturan Basis Data MySQL
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'uas_rotie_db',
    'DB_USER' => 'root',
    'DB_PASS' => '',

    // Pengaturan Lingkungan Aplikasi: 'development' atau 'production'
    'APP_ENV' => 'development',

    // Pengaturan Identitas & Kontak UMKM
    'APP_NAME' => 'ROTIÉ — Artisan Bakery',
    'APP_URL' => 'http://localhost/UAS-41257124-Muhammad_Al_farel',
    'WHATSAPP_NUMBER' => '6281234567890', // Nomor WhatsApp resmi toko untuk notifikasi pesanan
    'ADMIN_REGISTER_KEY' => 'ROTIE_IKMI_2026', // Kunci otorisasi khusus untuk registrasi akun admin baru
];

