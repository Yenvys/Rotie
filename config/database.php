<?php
/**
 * ====================================================================
 * CONFIG: Koneksi Basis Data PDO & Manajemen Galat (Database & Error Handling)
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * Pengembang: Muhammad Al Farel (NIM: 41257124)
 * ====================================================================
 */

// 1. Muat Kredensial Terisolasi
$envFile = __DIR__ . '/env.php';
if (!file_exists($envFile)) {
    die("Berkas konfigurasi lingkungan (config/env.php) tidak ditemukan. Silakan salin dari config/env.example.php.");
}
$config = require $envFile;

// 2. Pemadaman Pesan Galat di Server Produksi (Security Hardening Layer 4)
if (isset($config['APP_ENV']) && $config['APP_ENV'] === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    ini_set('error_log', $logDir . '/error.log');
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// 3. Inisialisasi Koneksi PDO (Singleton Pattern)
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        global $config;

        $host = $config['DB_HOST'] ?? '127.0.0.1';
        $port = $config['DB_PORT'] ?? '3306';
        $dbname = $config['DB_NAME'] ?? 'uas_rotie_db';
        $user = $config['DB_USER'] ?? 'root';
        $pass = $config['DB_PASS'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            // Catat galat ke log tanpa mengekspos rincian sensitif ke pengguna
            error_log("Database Connection Error: " . $e->getMessage());
            
            if (isset($config['APP_ENV']) && $config['APP_ENV'] === 'production') {
                die("Maaf, layanan sedang mengalami gangguan koneksi basis data. Silakan hubungi pengelola sistem.");
            } else {
                die("<h3>Galat Koneksi Database (Development Mode):</h3>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
            }
        }
    }

    return $pdo;
}

// Global variable PDO untuk kemudahan akses skrip prosedural jika diperlukan
$pdo = getDBConnection();

