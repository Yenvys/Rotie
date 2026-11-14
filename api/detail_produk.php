<?php
/**
 * ====================================================================
 * API REST: Ambil Rincian Detail Produk Tunggal (Modal View)
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../config/database.php';

$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$slug = trim($_GET['slug'] ?? '');

if ($id <= 0 && empty($slug)) {
    json_response(['status' => false, 'message' => 'Parameter produk tidak valid.'], 400);
}

try {
    $pdo = getDBConnection();

    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT p.*, c.nama_kategori, c.slug AS kategori_slug
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT p.*, c.nama_kategori, c.slug AS kategori_slug
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.slug = :slug LIMIT 1
        ");
        $stmt->execute([':slug' => $slug]);
    }

    $product = $stmt->fetch();

    if (!$product) {
        json_response(['status' => false, 'message' => 'Produk tidak ditemukan.'], 404);
    }

    $hargaAsli = (float) $product['harga'];
    $diskon = (float) $product['diskon_persen'];
    $hargaFinal = $product['is_promo'] && $diskon > 0 ? $hargaAsli - ($hargaAsli * $diskon / 100) : $hargaAsli;

    json_response([
        'status' => true,
        'data'   => [
            'id'            => (int) $product['id'],
            'nama_produk'   => e($product['nama_produk']),
            'slug'          => e($product['slug']),
            'deskripsi'     => e($product['deskripsi']),
            'harga'         => $hargaFinal,
            'harga_rp'      => format_rupiah($hargaFinal),
            'harga_asli'    => $hargaAsli,
            'harga_final'   => $hargaFinal,
            'harga_asli_rp' => format_rupiah($hargaAsli),
            'harga_final_rp'=> format_rupiah($hargaFinal),
            'stok'          => (int) $product['stok'],
            'gambar'        => e($product['gambar']),
            'is_featured'   => (int) $product['is_featured'],
            'is_promo'      => (int) $product['is_promo'],
            'diskon_persen' => $diskon,
            'kategori'      => e($product['nama_kategori']),
            'kategori_slug' => e($product['kategori_slug'])
        ]
    ]);

} catch (PDOException $e) {
    error_log("API detail_produk Error: " . $e->getMessage());
    json_response(['status' => false, 'message' => 'Terjadi kesalahan sistem saat mengambil data produk.'], 500);
}

