<?php
/**
 * ====================================================================
 * API REST: Validasi Ketersediaan Stok Item Keranjang
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../config/database.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$productId = (int) ($data['product_id'] ?? ($data['id'] ?? 0));
$qty       = (int) ($data['qty'] ?? ($data['jumlah'] ?? 1));

if ($productId <= 0) {
    json_response(['status' => false, 'message' => 'ID produk tidak valid.'], 400);
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, nama_produk, harga, stok, is_promo, diskon_persen, gambar, status FROM products WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $productId]);
    $prod = $stmt->fetch();

    if (!$prod) {
        json_response(['status' => false, 'message' => 'Produk tidak ditemukan.'], 404);
    }

    if ($prod['status'] !== 'tersedia' || (int)$prod['stok'] <= 0) {
        json_response(['status' => false, 'message' => "Maaf, '{$prod['nama_produk']}' saat ini sedang habis terjual."], 422);
    }

    if ($qty > (int)$prod['stok']) {
        json_response(['status' => false, 'message' => "Stok tidak mencukupi. Tersisa {$prod['stok']} porsi."], 422);
    }

    $hargaAsli = (float) $prod['harga'];
    $diskon = (float) $prod['diskon_persen'];
    $hargaFinal = ($prod['is_promo'] && $diskon > 0) ? $hargaAsli - ($hargaAsli * $diskon / 100) : $hargaAsli;

    json_response([
        'status'         => true,
        'message'        => "Produk '{$prod['nama_produk']}' siap ditambahkan ke keranjang.",
        'data'           => [
            'id'            => (int) $prod['id'],
            'nama_produk'   => e($prod['nama_produk']),
            'harga'         => $hargaFinal,
            'harga_rp'      => format_rupiah($hargaFinal),
            'stok_tersedia' => (int) $prod['stok'],
            'gambar'        => e($prod['gambar'])
        ]
    ]);

} catch (PDOException $e) {
    error_log("API tambah_keranjang Error: " . $e->getMessage());
    json_response(['status' => false, 'message' => 'Terjadi kesalahan sistem saat memvalidasi stok.'], 500);
}

