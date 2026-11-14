<?php
/**
 * ====================================================================
 * SUB-MODUL: Rincian Detail Transaksi & Nota Pesanan
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('error', 'ID transaksi tidak valid.');
    redirect('admin.php?mod=transaksi&act=list');
}

$stmt = $pdo->prepare("
    SELECT o.*, c.nama AS nama_pelanggan, c.no_hp, c.email, c.alamat
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = :id LIMIT 1
");
$stmt->execute([':id' => $id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Nota transaksi tidak ditemukan.');
    redirect('admin.php?mod=transaksi&act=list');
}

// Ambil Item-Item Pesanan
$stmtItems = $pdo->prepare("
    SELECT oi.*, p.nama_produk, p.gambar, p.slug
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = :oid
");
$stmtItems->execute([':oid' => $id]);
$items = $stmtItems->fetchAll();
?>

<div class="max-w-4xl mx-auto space-y-6">
    
    <!-- Top Action Bar -->
    <div class="flex items-center justify-between">
        <a href="admin.php?mod=transaksi&act=list" class="text-xs font-semibold text-rotie-caramel hover:underline">
            &larr; Kembali ke Daftar Transaksi
        </a>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" 
                    class="px-3.5 py-1.5 rounded-xl bg-white border border-rotie-beige text-xs font-semibold text-rotie-brown hover:bg-rotie-cream shadow-sm transition-all flex items-center gap-1.5">
                <svg class="w-4 h-4 text-rotie-brown" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Nota</span>
            </button>
        </div>
    </div>

    <!-- Kartu Nota Transaksi -->
    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-3xl p-6 sm:p-10 shadow-sm space-y-8">
        
        <!-- Header Nota -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-rotie-beige/80 gap-4">
            <div>
                <span class="text-[10px] uppercase font-bold tracking-widest text-rotie-olive">Nota Pesanan Resmi</span>
                <h2 class="font-display font-bold text-2xl sm:text-3xl text-rotie-brown mt-0.5">
                    <?= e($order['kode_pesanan']); ?>
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Waktu Transaksi: <?= date('d F Y, H:i:s', strtotime($order['created_at'])); ?> WIB
                </p>
            </div>

            <div class="flex flex-col sm:items-end">
                <span class="text-xs text-gray-500 mb-1">Status Operasional:</span>
                <select data-order-id="<?= $order['id']; ?>" data-csrf="<?= csrf_token(); ?>"
                        class="tx-status-select py-1.5 px-3 rounded-xl text-xs font-bold border border-rotie-beige bg-white focus:outline-none focus:ring-1 focus:ring-rotie-caramel shadow-sm">
                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending (Menunggu)</option>
                    <option value="diproses" <?= $order['status'] === 'diproses' ? 'selected' : ''; ?>>Diproses (Baking)</option>
                    <option value="selesai" <?= $order['status'] === 'selesai' ? 'selected' : ''; ?>>Selesai (Terkirim)</option>
                    <option value="dibatalkan" <?= $order['status'] === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                </select>
            </div>
        </div>

        <!-- Data Pelanggan & Alamat Pengiriman -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 p-5 bg-rotie-cream/50 rounded-2xl border border-rotie-beige/60 text-xs">
            <div>
                <h4 class="font-bold text-rotie-brown uppercase tracking-wider mb-2 text-[10px]">Informasi Pemesan:</h4>
                <p class="font-serif font-bold text-sm text-rotie-brown mb-1"><?= e($order['nama_pelanggan']); ?></p>
                <p class="text-rotie-brown/80 flex items-center gap-1.5 mb-1">
                    <span>📱 No. WhatsApp:</span>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $order['no_hp']); ?>" target="_blank" class="font-mono text-rotie-caramel font-bold hover:underline">
                        <?= e($order['no_hp']); ?>
                    </a>
                </p>
                <?php if (!empty($order['email'])): ?>
                    <p class="text-rotie-brown/70">✉️ Email: <?= e($order['email']); ?></p>
                <?php endif; ?>
            </div>

            <div>
                <h4 class="font-bold text-rotie-brown uppercase tracking-wider mb-2 text-[10px]">Alamat Pengiriman & Catatan:</h4>
                <p class="text-rotie-brown leading-relaxed font-medium mb-2">
                    <?= nl2br(e($order['alamat'])); ?>
                </p>
                <?php if (!empty($order['catatan'])): ?>
                    <div class="p-2.5 rounded-xl bg-amber-50/80 border border-amber-200/80 text-[11px] text-amber-900">
                        <span class="font-bold">Catatan Khusus:</span> <?= e($order['catatan']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabel Rincian Item Roti -->
        <div>
            <h4 class="font-serif font-bold text-base text-rotie-brown mb-3">Rincian Roti & Pastry yang Dipesan</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-rotie-cream/80 border-b border-rotie-beige text-rotie-olive font-bold uppercase tracking-wider text-[10px]">
                            <th class="py-3 px-3">Item Produk</th>
                            <th class="py-3 px-3 text-center">Jumlah</th>
                            <th class="py-3 px-3 text-right">Harga Satuan</th>
                            <th class="py-3 px-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rotie-beige/60">
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-3">
                                        <img src="<?= e($item['gambar']); ?>" alt="" class="w-10 h-10 object-cover rounded-lg border border-rotie-beige bg-amber-50">
                                        <span class="font-serif font-bold text-rotie-brown"><?= e($item['nama_produk']); ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-rotie-brown">
                                    <?= (int) $item['jumlah']; ?>x
                                </td>
                                <td class="py-3 px-3 text-right text-gray-600">
                                    <?= format_rupiah($item['harga_satuan']); ?>
                                </td>
                                <td class="py-3 px-3 text-right font-serif font-bold text-rotie-brown">
                                    <?= format_rupiah($item['subtotal']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-rotie-beige">
                            <td colspan="3" class="py-4 px-3 text-right font-bold text-sm text-rotie-brown">Total Pembayaran:</td>
                            <td class="py-4 px-3 text-right font-serif font-bold text-xl text-rotie-caramel">
                                <?= format_rupiah($order['total_harga']); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</div>

