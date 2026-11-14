<?php
/**
 * ====================================================================
 * SUB-MODUL: Pemrosesan Hapus Produk Aman (Anti-CSRF)
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$id = (int) ($_GET['id'] ?? 0);
$csrfToken = $_GET['csrf_token'] ?? ($_POST['csrf_token'] ?? '');

if (!verify_csrf($csrfToken)) {
    set_flash('error', 'Token keamanan CSRF tidak valid.');
    redirect('admin.php?mod=produk&act=list');
}

if ($id <= 0) {
    set_flash('error', 'ID produk tidak valid.');
    redirect('admin.php?mod=produk&act=list');
}

try {
    $pdo = getDBConnection();

    // Cek apakah produk pernah dipesan
    $cek = $pdo->prepare("SELECT COUNT(*) AS total FROM order_items WHERE product_id = :id");
    $cek->execute([':id' => $id]);
    $hasOrders = (int) $cek->fetch()['total'] > 0;

    if ($hasOrders) {
        $upd = $pdo->prepare("UPDATE products SET status = 'arsip' WHERE id = :id");
        $upd->execute([':id' => $id]);
        set_flash('warning', 'Produk berhasil diarsipkan karena memiliki riwayat pesanan pelanggan.');
    } else {
        $del = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $del->execute([':id' => $id]);
        set_flash('success', 'Produk berhasil dihapus secara permanen dari katalog.');
    }

} catch (PDOException $e) {
    error_log("Hapus produk error: " . $e->getMessage());
    set_flash('error', 'Terjadi kesalahan basis data saat menghapus produk.');
}

redirect('admin.php?mod=produk&act=list');

