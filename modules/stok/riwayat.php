<?php
/**
 * ====================================================================
 * SUB-MODUL: Log Audit Trail Riwayat Pergerakan Mutasi Stok
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT sm.*, p.nama_produk, p.gambar, o.kode_pesanan
    FROM stock_movements sm
    JOIN products p ON sm.product_id = p.id
    LEFT JOIN orders o ON sm.order_id = o.id
    ORDER BY sm.created_at DESC
    LIMIT 100
");
$movements = $stmt->fetchAll();
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-rotie-brown">Riwayat Log Mutasi Stok</h1>
            <p class="text-xs text-rotie-brown/70 font-light mt-0.5">Audit trail lengkap pergerakan masuk, keluar, dan penyesuaian persediaan roti.</p>
        </div>

        <a href="admin.php?mod=stok&act=list" class="text-xs font-semibold text-rotie-caramel hover:underline">
            &larr; Kembali ke Monitoring Stok
        </a>
    </div>

    <!-- Tabel Riwayat Mutasi -->
    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-3xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-rotie-cream/70 border-b border-rotie-beige text-rotie-olive font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-4">Waktu Mutasi</th>
                        <th class="py-3.5 px-4">Produk</th>
                        <th class="py-3.5 px-4">Tipe Mutasi</th>
                        <th class="py-3.5 px-4 text-center">Jumlah</th>
                        <th class="py-3.5 px-4 text-center">Sebelum &rarr; Sesudah</th>
                        <th class="py-3.5 px-4">Keterangan / Referensi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rotie-beige/60">
                    <?php if (empty($movements)): ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-500">Belum ada catatan mutasi stok pada basis data.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movements as $m): 
                            $tipe = $m['tipe'];
                            $badgeClass = 'bg-gray-100 text-gray-700';
                            if ($tipe === 'masuk') {
                                $badgeClass = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                            } elseif ($tipe === 'keluar') {
                                $badgeClass = 'bg-rose-50 text-rose-700 border border-rose-200';
                            } else {
                                $badgeClass = 'bg-amber-50 text-amber-800 border border-amber-200';
                            }
                        ?>
                            <tr class="hover:bg-rotie-cream/30 transition-colors">
                                <td class="py-3 px-4 font-mono text-[11px] text-gray-500">
                                    <?= date('d M Y, H:i', strtotime($m['created_at'])); ?>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <img src="<?= e($m['gambar']); ?>" alt="" class="w-8 h-8 rounded-lg object-cover border border-rotie-beige bg-amber-50">
                                        <span class="font-serif font-bold text-sm text-rotie-brown"><?= e($m['nama_produk']); ?></span>
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $badgeClass; ?>">
                                        <?= e($tipe); ?>
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-center font-bold text-sm <?= $tipe === 'keluar' ? 'text-rose-600' : 'text-emerald-600'; ?>">
                                    <?= $tipe === 'keluar' ? '-' : '+'; ?><?= (int) $m['jumlah']; ?>
                                </td>

                                <td class="py-3 px-4 text-center text-gray-600 font-mono text-xs">
                                    <?= (int) $m['stok_sebelum']; ?> &rarr; <span class="font-bold text-rotie-brown"><?= (int) $m['stok_sesudah']; ?></span>
                                </td>

                                <td class="py-3 px-4 text-rotie-brown/80">
                                    <span><?= e($m['keterangan']); ?></span>
                                    <?php if (!empty($m['kode_pesanan'])): ?>
                                        <span class="block text-[10px] font-mono text-rotie-caramel font-bold mt-0.5">Nota: <?= e($m['kode_pesanan']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

