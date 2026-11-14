<?php
/**
 * ====================================================================
 * SUB-MODUL: Aksi Update Status Transaksi (Form POST Fallback)
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();

$orderId   = (int) ($_POST['order_id'] ?? ($_GET['id'] ?? 0));
$newStatus = trim($_POST['status'] ?? ($_GET['status'] ?? ''));
$csrfToken = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');

if (!verify_csrf($csrfToken)) {
    set_flash('error', 'Token keamanan CSRF tidak valid.');
    redirect('admin.php?mod=transaksi&act=list');
}

$allowed = ['pending', 'diproses', 'selesai', 'dibatalkan'];
if ($orderId <= 0 || !in_array($newStatus, $allowed)) {
    set_flash('error', 'Parameter perubahan status tidak valid.');
    redirect('admin.php?mod=transaksi&act=list');
}

try {
    $pdo->beginTransaction();

    $stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE id = :id FOR UPDATE");
    $stmtOrder->execute([':id' => $orderId]);
    $order = $stmtOrder->fetch();

    if ($order) {
        // Cek riwayat pemotongan stok
        $stmtCheckMv = $pdo->prepare("SELECT COUNT(*) AS total FROM stock_movements WHERE order_id = :oid AND tipe = 'keluar'");
        $stmtCheckMv->execute([':oid' => $orderId]);
        $alreadyDeducted = (int) $stmtCheckMv->fetch()['total'] > 0;

        if (in_array($newStatus, ['diproses', 'selesai']) && !$alreadyDeducted) {
            $stmtItems = $pdo->prepare("SELECT product_id, jumlah FROM order_items WHERE order_id = :oid");
            $stmtItems->execute([':oid' => $orderId]);
            $items = $stmtItems->fetchAll();

            foreach ($items as $item) {
                $pid = (int) $item['product_id'];
                $qty = (int) $item['jumlah'];

                $stmtP = $pdo->prepare("SELECT stok FROM products WHERE id = :pid FOR UPDATE");
                $stmtP->execute([':pid' => $pid]);
                $p = $stmtP->fetch();

                if ($p) {
                    $stokAwal = (int) $p['stok'];
                    $stokAkhir = max(0, $stokAwal - $qty);

                    $updP = $pdo->prepare("UPDATE products SET stok = :stok, status = IF(:stok_chk <= 0, 'habis', 'tersedia') WHERE id = :pid");
                    $updP->execute([':stok' => $stokAkhir, ':stok_chk' => $stokAkhir, ':pid' => $pid]);

                    $insMv = $pdo->prepare("
                        INSERT INTO stock_movements (product_id, order_id, tipe, jumlah, stok_sebelum, stok_sesudah, keterangan, created_at)
                        VALUES (:pid, :oid, 'keluar', :qty, :seb, :ses, :ket, NOW())
                    ");
                    $insMv->execute([
                        ':pid' => $pid,
                        ':oid' => $orderId,
                        ':qty' => $qty,
                        ':seb' => $stokAwal,
                        ':ses' => $stokAkhir,
                        ':ket' => "Pemotongan stok pesanan #{$order['kode_pesanan']} status: {$newStatus}"
                    ]);
                }
            }
        }

        $updOrder = $pdo->prepare("UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id");
        $updOrder->execute([':status' => $newStatus, ':id' => $orderId]);
    }

    $pdo->commit();
    set_flash('success', "Status transaksi #{$order['kode_pesanan']} berhasil diubah menjadi '{$newStatus}'.");

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Update status error: " . $e->getMessage());
    set_flash('error', 'Gagal memperbarui status transaksi: ' . $e->getMessage());
}

redirect("admin.php?mod=transaksi&act=detail&id={$orderId}");

