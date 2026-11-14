<?php
/**
 * ====================================================================
 * API REST: Pembaruan Status Transaksi & Pemotongan Stok Otomatis
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth_check.php';

if (!is_authenticated()) {
    json_response(['status' => false, 'message' => 'Akses ditolak: Anda belum terautentikasi.'], 401);
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

$orderId   = (int) ($data['order_id'] ?? 0);
$newStatus = trim($data['status'] ?? '');

$allowedStatus = ['pending', 'diproses', 'selesai', 'dibatalkan'];
if ($orderId <= 0 || !in_array($newStatus, $allowedStatus)) {
    json_response(['status' => false, 'message' => 'Data status atau ID transaksi tidak valid.'], 400);
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();

    // 1. Ambil data pesanan saat ini
    $stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE id = :id FOR UPDATE");
    $stmtOrder->execute([':id' => $orderId]);
    $order = $stmtOrder->fetch();

    if (!$order) {
        throw new Exception("Pesanan dengan ID #{$orderId} tidak ditemukan.");
    }

    $oldStatus = $order['status'];

    // 2. Ambil item-item pesanan
    $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
    $stmtItems->execute([':order_id' => $orderId]);
    $items = $stmtItems->fetchAll();

    // 3. Cek apakah pemotongan stok sudah pernah dilakukan untuk pesanan ini
    $stmtCheckMv = $pdo->prepare("SELECT COUNT(*) AS total FROM stock_movements WHERE order_id = :order_id AND tipe = 'keluar'");
    $stmtCheckMv->execute([':order_id' => $orderId]);
    $alreadyDeducted = (int) $stmtCheckMv->fetch()['total'] > 0;

    // Logika Pemotongan Stok Otomatis saat pesanan diproses atau selesai
    if (in_array($newStatus, ['diproses', 'selesai']) && !$alreadyDeducted) {
        foreach ($items as $item) {
            $pId = (int) $item['product_id'];
            $qty = (int) $item['jumlah'];

            // Cek stok saat ini
            $stmtProd = $pdo->prepare("SELECT stok, nama_produk FROM products WHERE id = :id FOR UPDATE");
            $stmtProd->execute([':id' => $pId]);
            $prod = $stmtProd->fetch();

            if ($prod) {
                $stokSebelum = (int) $prod['stok'];
                $stokSesudah = max(0, $stokSebelum - $qty);

                // Update stok di tabel products
                $updStok = $pdo->prepare("UPDATE products SET stok = :stok, status = IF(:stok_chk <= 0, 'habis', 'tersedia') WHERE id = :id");
                $updStok->execute([':stok' => $stokSesudah, ':stok_chk' => $stokSesudah, ':id' => $pId]);

                // Catat mutasi pada stock_movements
                $insMv = $pdo->prepare("
                    INSERT INTO stock_movements (product_id, order_id, tipe, jumlah, stok_sebelum, stok_sesudah, keterangan, created_at)
                    VALUES (:pid, :oid, 'keluar', :qty, :seb, :ses, :ket, NOW())
                ");
                $insMv->execute([
                    ':pid' => $pId,
                    ':oid' => $orderId,
                    ':qty' => $qty,
                    ':seb' => $stokSebelum,
                    ':ses' => $stokSesudah,
                    ':ket' => "Pemotongan stok pesanan #{$order['kode_pesanan']} status: {$newStatus}"
                ]);
            }
        }
    }

    // Logika Pengembalian Stok jika pesanan dibatalkan setelah sebelumnya terpotong
    if ($newStatus === 'dibatalkan' && $alreadyDeducted) {
        foreach ($items as $item) {
            $pId = (int) $item['product_id'];
            $qty = (int) $item['jumlah'];

            $stmtProd = $pdo->prepare("SELECT stok FROM products WHERE id = :id FOR UPDATE");
            $stmtProd->execute([':id' => $pId]);
            $prod = $stmtProd->fetch();

            if ($prod) {
                $stokSebelum = (int) $prod['stok'];
                $stokSesudah = $stokSebelum + $qty;

                $updStok = $pdo->prepare("UPDATE products SET stok = :stok, status = 'tersedia' WHERE id = :id");
                $updStok->execute([':stok' => $stokSesudah, ':id' => $pId]);

                $insMv = $pdo->prepare("
                    INSERT INTO stock_movements (product_id, order_id, tipe, jumlah, stok_sebelum, stok_sesudah, keterangan, created_at)
                    VALUES (:pid, :oid, 'masuk', :qty, :seb, :ses, :ket, NOW())
                ");
                $insMv->execute([
                    ':pid' => $pId,
                    ':oid' => $orderId,
                    ':qty' => $qty,
                    ':seb' => $stokSebelum,
                    ':ses' => $stokSesudah,
                    ':ket' => "Pengembalian stok pembatalan pesanan #{$order['kode_pesanan']}"
                ]);
            }
        }
    }

    // 4. Update status pesanan di orders
    $updOrder = $pdo->prepare("UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id");
    $updOrder->execute([':status' => $newStatus, ':id' => $orderId]);

    $pdo->commit();

    json_response([
        'status'     => true,
        'message'    => "Status pesanan #{$order['kode_pesanan']} berhasil diubah menjadi {$newStatus}.",
        'new_status' => $newStatus
    ]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("API update_status Error: " . $e->getMessage());
    json_response(['status' => false, 'message' => $e->getMessage()], 500);
}

