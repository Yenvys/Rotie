<?php
/**
 * ====================================================================
 * ENDPOINT: Ekspor Data Katalog Produk ke Berkas Spreadsheet CSV
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();

$filename = 'rotie_katalog_produk_' . date('Ymd_His') . '.csv';

// Bersihkan buffer output sebelum mengirim header berkas
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Sisipkan UTF-8 BOM agar terbaca sempurna di Microsoft Excel
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header Kolom CSV
fputcsv($output, [
    'ID Produk',
    'Nama Produk',
    'Slug URL',
    'Kategori',
    'Harga (Rp)',
    'Stok Fisik',
    'Batas Stok Minimum',
    'Status Ketersediaan',
    'Diskon (%)',
    'Unggulan (Signature)',
    'Waktu Ditambahkan'
]);

$stmt = $pdo->query("
    SELECT p.id, p.nama_produk, p.slug, c.nama_kategori, p.harga, p.stok, p.stok_minimum, p.status, p.diskon_persen, p.is_featured, p.created_at
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.id ASC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['nama_produk'],
        $row['slug'],
        $row['nama_kategori'],
        (float) $row['harga'],
        (int) $row['stok'],
        (int) $row['stok_minimum'],
        $row['status'],
        (float) $row['diskon_persen'],
        $row['is_featured'] ? 'Ya' : 'Tidak',
        $row['created_at']
    ]);
}

fclose($output);
exit;

