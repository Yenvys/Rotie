<?php
/**
 * ====================================================================
 * SUB-MODUL: Aksi Hapus Kategori Aman
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();
$id = (int) ($_GET['id'] ?? 0);
$csrfToken = $_GET['csrf_token'] ?? ($_POST['csrf_token'] ?? '');

if (!verify_csrf($csrfToken)) {
    set_flash('error', 'Token keamanan CSRF tidak valid.');
    redirect('admin.php?mod=kategori&act=list');
}

if ($id <= 0) {
    set_flash('error', 'ID kategori tidak valid.');
    redirect('admin.php?mod=kategori&act=list');
}

try {
    // Cek apakah ada produk yang masih menggunakan kategori ini
    $cek = $pdo->prepare("SELECT COUNT(*) AS total FROM products WHERE category_id = :id AND status != 'arsip'");
    $cek->execute([':id' => $id]);
    $totalProd = (int) $cek->fetch()['total'];

    if ($totalProd > 0) {
        set_flash('error', "Kategori tidak dapat dihapus karena masih menaungi {$totalProd} produk aktif. Pindahkan produk terlebih dahulu.");
    } else {
        $del = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $del->execute([':id' => $id]);
        set_flash('success', 'Kategori berhasil dihapus dari sistem.');
    }

} catch (PDOException $e) {
    error_log("Hapus kategori error: " . $e->getMessage());
    set_flash('error', 'Terjadi kesalahan basis data saat menghapus kategori.');
}

redirect('admin.php?mod=kategori&act=list');

