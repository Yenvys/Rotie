<?php
/**
 * ====================================================================
 * API REST: Penghapusan Produk Asinkron via POST + Anti-CSRF
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth_check.php';

// Cek autentikasi admin
if (!is_authenticated()) {
    json_response(['status' => false, 'message' => 'Akses ditolak: Anda belum masuk sebagai pengelola.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => false, 'message' => 'Metode permintaan tidak diizinkan.'], 405);
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

$csrfToken = $data['csrf_token'] ?? '';
if (!verify_csrf($csrfToken)) {
    json_response(['status' => false, 'message' => 'Validasi keamanan (CSRF Token) gagal.'], 403);
}

$id = (int) ($data['id'] ?? 0);
if ($id <= 0) {
    json_response(['status' => false, 'message' => 'ID produk tidak valid.'], 400);
}

try {
    $pdo = getDBConnection();

    // Cek apakah produk pernah dipesan di order_items
    $cek = $pdo->prepare("SELECT COUNT(*) AS total FROM order_items WHERE product_id = :id");
    $cek->execute([':id' => $id]);
    $hasOrders = (int) $cek->fetch()['total'] > 0;

    if ($hasOrders) {
        // Jika pernah dipesan, ubah status ke 'arsip' agar relasi nota historis tidak rusak
        $upd = $pdo->prepare("UPDATE products SET status = 'arsip' WHERE id = :id");
        $upd->execute([':id' => $id]);
        json_response([
            'status'  => true,
            'message' => 'Produk diarsipkan karena memiliki riwayat transaksi penjualan.',
            'action'  => 'archived'
        ]);
    } else {
        // Hapus fisik jika belum pernah memiliki transaksi
        $del = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $del->execute([':id' => $id]);
        json_response([
            'status'  => true,
            'message' => 'Produk berhasil dihapus secara permanen dari katalog.',
            'action'  => 'deleted'
        ]);
    }

} catch (PDOException $e) {
    error_log("API hapus_produk Error: " . $e->getMessage());
    json_response(['status' => false, 'message' => 'Terjadi kesalahan sistem saat menghapus produk.'], 500);
}

