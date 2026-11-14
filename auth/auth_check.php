<?php
/**
 * ====================================================================
 * AUTH: Guard Middleware Sesi & Inisialisasi Anti-CSRF Token
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * Pengembang: Muhammad Al Farel (NIM: 41257124)
 * ====================================================================
 */

require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../config/database.php';

// Pastikan token CSRF telah diinisialisasi untuk sesi ini
csrf_token();

/**
 * Cek apakah pengguna saat ini telah terautentikasi
 */
function is_authenticated(): bool {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Ambil data profil admin yang sedang login
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Guard Sesi Wajib: Lindungi halaman admin
 * Jika belum login, alihkan otomatis ke form login
 */
function require_auth(): void {
    if (!is_authenticated()) {
        set_flash('error', 'Silakan login terlebih dahulu untuk mengakses dashboard pengelola.');
        redirect('auth/login.php');
    }
}

/**
 * Cek hak akses role tertentu (misal 'owner' atau 'admin')
 */
function require_role(string $role): void {
    require_auth();
    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        set_flash('error', 'Akses ditolak: Anda tidak memiliki wewenang untuk membuka halaman ini.');
        redirect('admin.php?page=dashboard');
    }
}

