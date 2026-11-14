<?php
/**
 * ====================================================================
 * API REST: Live Search & Filter Produk Asinkron (Fetch API)
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();

    // Parameter Query
    $q = trim($_GET['q'] ?? '');
    $kategori = trim($_GET['kategori'] ?? 'all');
    $sort = trim($_GET['sort'] ?? 'terbaru');
    $promoOnly = isset($_GET['promo']) && $_GET['promo'] === '1';

    // Base Query
    $sql = "
        SELECT p.*, c.nama_kategori, c.slug AS kategori_slug
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'tersedia'
    ";
    $params = [];

    // Filter Kategori
    if (!empty($kategori) && $kategori !== 'all') {
        $sql .= " AND (c.slug = :kategori OR c.id = :kategori_id)";
        $params[':kategori'] = $kategori;
        $params[':kategori_id'] = is_numeric($kategori) ? (int)$kategori : 0;
    }

    // Filter Promo Khusus
    if ($promoOnly) {
        $sql .= " AND p.is_promo = 1";
    }

    // Pencarian Teks (Live Search dengan Debounce)
    if (!empty($q)) {
        $sql .= " AND (p.nama_produk LIKE :keyword OR p.deskripsi LIKE :keyword_desc)";
        $params[':keyword'] = "%{$q}%";
        $params[':keyword_desc'] = "%{$q}%";
    }

    // Pengurutan (Sorting)
    switch ($sort) {
        case 'termurah':
            $sql .= " ORDER BY (p.harga - (p.harga * p.diskon_persen / 100)) ASC";
            break;
        case 'termahal':
            $sql .= " ORDER BY (p.harga - (p.harga * p.diskon_persen / 100)) DESC";
            break;
        case 'populer':
            $sql .= " ORDER BY p.is_featured DESC, p.id DESC";
            break;
        case 'nama_asc':
            $sql .= " ORDER BY p.nama_produk ASC";
            break;
        case 'terbaru':
        default:
            $sql .= " ORDER BY p.id DESC";
            break;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Format & Sanitasi Data Output (Anti-XSS)
    $formatted = [];
    foreach ($products as $row) {
        $hargaAsli = (float) $row['harga'];
        $diskon = (float) $row['diskon_persen'];
        $hargaFinal = $row['is_promo'] && $diskon > 0 ? $hargaAsli - ($hargaAsli * $diskon / 100) : $hargaAsli;

        $formatted[] = [
            'id'            => (int) $row['id'],
            'nama_produk'   => e($row['nama_produk']),
            'slug'          => e($row['slug']),
            'deskripsi'     => e($row['deskripsi']),
            'harga'         => $hargaFinal,
            'harga_rp'      => format_rupiah($hargaFinal),
            'harga_asli'    => $hargaAsli,
            'harga_final'   => $hargaFinal,
            'harga_asli_rp' => format_rupiah($hargaAsli),
            'harga_final_rp'=> format_rupiah($hargaFinal),
            'stok'          => (int) $row['stok'],
            'gambar'        => e($row['gambar']),
            'is_featured'   => (int) $row['is_featured'],
            'is_promo'      => (int) $row['is_promo'],
            'diskon_persen' => $diskon,
            'kategori'      => e($row['nama_kategori']),
            'kategori_slug' => e($row['kategori_slug'])
        ];
    }

    json_response([
        'status' => true,
        'count'  => count($formatted),
        'query'  => ['q' => $q, 'kategori' => $kategori, 'sort' => $sort],
        'data'   => $formatted
    ]);

} catch (PDOException $e) {
    error_log("API cari_produk Error: " . $e->getMessage());
    json_response([
        'status'  => false,
        'message' => 'Gagal memuat katalog produk dari basis data.',
        'data'    => []
    ], 500);
}

