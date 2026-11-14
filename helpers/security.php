<?php
/**
 * ====================================================================
 * HELPER: Keamanan, Sanitasi Anti-XSS, CSRF & Fungsi Utilitas
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * Pengembang: Muhammad Al Farel (NIM: 41257124)
 * ====================================================================
 */

// Pastikan sesi aktif untuk manajemen CSRF
if (session_status() === PHP_SESSION_NONE) {
    // Pengaturan cookie sesi yang aman
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/**
 * Anti-XSS Sanitizer: Membungkus teks dengan htmlspecialchars
 * Sesuai ketentuan wajib instruksi modul UAS
 */
function e(?string $data): string {
    if ($data === null) {
        return '';
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Inisialisasi atau ambil Anti-CSRF Token dari sesi
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render elemen HTML input hidden untuk Anti-CSRF
 */
function csrf_field(): string {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Verifikasi Anti-CSRF Token dengan hash_equals()
 * Wajib untuk setiap mutasi form POST dan endpoint AJAX
 */
function verify_csrf(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format mata uang Rupiah standar Indonesia
 */
function format_rupiah($nominal): string {
    $val = (float) $nominal;
    return 'Rp' . number_format($val, 0, ',', '.');
}

/**
 * Format tanggal dan waktu standar Indonesia
 */
function format_tanggal($datetime, $withTime = true): string {
    if (empty($datetime)) return '-';
    $time = strtotime($datetime);
    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date('d', $time);
    $m = $bulanIndo[(int)date('m', $time)];
    $y = date('Y', $time);
    
    if ($withTime) {
        $jam = date('H:i', $time);
        return "{$d} {$m} {$y}, {$jam} WIB";
    }
    return "{$d} {$m} {$y}";
}

/**
 * Pembuatan slug ramah URL
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'item-' . time() : $text;
}

/**
 * Respons standar JSON untuk endpoint REST API AJAX
 */
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Pengalihan halaman yang aman (Safe Redirect)
 */
function redirect(string $url): void {
    header("Location: {$url}");
    exit;
}

/**
 * Flash Message Sesi (Pemberitahuan Sukses / Gagal)
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

