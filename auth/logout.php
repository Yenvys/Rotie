<?php
/**
 * ====================================================================
 * AUTH: Penghancuran Sesi Login & Pembersihan Cookie Browser
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * Pengembang: Muhammad Al Farel (NIM: 41257124)
 * ====================================================================
 */

require_once __DIR__ . '/../helpers/security.php';

// 1. Kosongkan seluruh variabel dalam sesi
$_SESSION = [];

// 2. Hapus cookie sesi dari browser jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Hancurkan sesi di server
session_destroy();

// 4. Buka sesi baru singkat untuk menyimpan flash message perpisahan
session_start();
set_flash('success', 'Anda telah berhasil keluar dari sistem pengelola.');
redirect('login.php');

