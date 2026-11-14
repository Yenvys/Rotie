<?php
/**
 * ====================================================================
 * SUB-MODUL: Daftar Pesanan Pelanggan & Manajemen Status
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();

$statusFilter = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');
$tglMulai     = trim($_GET['tgl_mulai'] ?? '');
$tglSelesai   = trim($_GET['tgl_selesai'] ?? '');

$sql = "
    SELECT o.*, c.nama AS nama_pelanggan, c.no_hp, c.alamat,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS total_items
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE 1=1
";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND o.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (o.kode_pesanan LIKE :kw OR c.nama LIKE :kw_nama OR c.no_hp LIKE :kw_hp)";
    $params[':kw'] = "%{$search}%";
    $params[':kw_nama'] = "%{$search}%";
    $params[':kw_hp'] = "%{$search}%";
}

if (!empty($tglMulai)) {
    $sql .= " AND DATE(o.created_at) >= :tgl_m";
    $params[':tgl_m'] = $tglMulai;
}

if (!empty($tglSelesai)) {
    $sql .= " AND DATE(o.created_at) <= :tgl_s";
    $params[':tgl_s'] = $tglSelesai;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Hitung total dari hasil filter
$filteredTotalNominal = array_sum(array_column($orders, 'total_harga'));
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-bold text-rotie-brown">Daftar Transaksi Pesanan</h1>
            <p class="text-xs text-rotie-brown/70 font-light mt-0.5">Kelola alur konfirmasi, pemrosesan roti hangat, dan pengiriman pelanggan.</p>
        </div>

        <div class="p-3 bg-rotie-warm border border-rotie-beige rounded-2xl flex items-center gap-4 text-xs">
            <div>
                <span class="text-gray-500 block text-[10px] uppercase font-bold">Total Pesanan:</span>
                <span class="font-bold font-serif text-rotie-brown text-sm"><?= count($orders); ?> Transaksi</span>
            </div>
            <div class="border-l border-rotie-beige pl-4">
                <span class="text-gray-500 block text-[10px] uppercase font-bold">Nilai Akumulasi:</span>
                <span class="font-bold font-serif text-rotie-caramel text-sm"><?= format_rupiah($filteredTotalNominal); ?></span>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="admin.php" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <input type="hidden" name="mod" value="transaksi">
            <input type="hidden" name="act" value="list">

            <div class="sm:col-span-4">
                <input type="text" name="search" value="<?= e($search); ?>" 
                       placeholder="Cari kode pesanan, nama, nomor WhatsApp..."
                       class="w-full px-3.5 py-2 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
            </div>

            <div class="sm:col-span-3">
                <select name="status" class="w-full px-3 py-2 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                    <option value="">Semua Status</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending (Menunggu)</option>
                    <option value="diproses" <?= $statusFilter === 'diproses' ? 'selected' : ''; ?>>Diproses (Baking)</option>
                    <option value="selesai" <?= $statusFilter === 'selesai' ? 'selected' : ''; ?>>Selesai (Terkirim)</option>
                    <option value="dibatalkan" <?= $statusFilter === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <input type="date" name="tgl_mulai" value="<?= e($tglMulai); ?>"
                       class="w-full px-2.5 py-2 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
            </div>

            <div class="sm:col-span-2">
                <input type="date" name="tgl_selesai" value="<?= e($tglSelesai); ?>"
                       class="w-full px-2.5 py-2 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
            </div>

            <div class="sm:col-span-1 flex items-center gap-1">
                <button type="submit" class="w-full py-2 px-3 bg-rotie-brown text-white text-xs font-semibold rounded-xl hover:bg-rotie-caramel transition-colors shadow-sm">
                    Cari
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Transaksi -->
    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-3xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-rotie-cream/70 border-b border-rotie-beige text-rotie-olive font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-4">Kode Transaksi</th>
                        <th class="py-3.5 px-4">Pelanggan & Kontak</th>
                        <th class="py-3.5 px-4">Total Tagihan</th>
                        <th class="py-3.5 px-4">Status Pesanan</th>
                        <th class="py-3.5 px-4">Waktu Pemesanan</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rotie-beige/60">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-500">
                                Tidak ada transaksi yang sesuai dengan parameter filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr class="hover:bg-rotie-cream/30 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-rotie-brown">
                                    <a href="admin.php?mod=transaksi&act=detail&id=<?= $o['id']; ?>" class="hover:text-rotie-caramel underline">
                                        <?= e($o['kode_pesanan']); ?>
                                    </a>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-sm text-rotie-brown block"><?= e($o['nama_pelanggan']); ?></span>
                                    <span class="text-[11px] text-gray-500"><?= e($o['no_hp']); ?></span>
                                    <span class="text-[10px] text-gray-400 block truncate max-w-xs"><?= e($o['alamat']); ?></span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="font-serif font-bold text-sm text-rotie-caramel block"><?= format_rupiah($o['total_harga']); ?></span>
                                    <span class="text-[10px] text-gray-400"><?= (int)$o['total_items']; ?> item dipesan</span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <!-- Dynamic Select Status AJAX -->
                                    <select data-order-id="<?= $o['id']; ?>" data-csrf="<?= csrf_token(); ?>"
                                            class="tx-status-select py-1 px-2.5 rounded-lg text-xs font-semibold border border-rotie-beige bg-white focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                                        <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="diproses" <?= $o['status'] === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                                        <option value="selesai" <?= $o['status'] === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                        <option value="dibatalkan" <?= $o['status'] === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                                    </select>
                                </td>

                                <td class="py-3.5 px-4 text-gray-500 text-[11px]">
                                    <?= date('d M Y, H:i', strtotime($o['created_at'])); ?> WIB
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <a href="admin.php?mod=transaksi&act=detail&id=<?= $o['id']; ?>" 
                                       class="px-3 py-1.5 rounded-lg bg-rotie-cream hover:bg-rotie-caramel hover:text-white text-rotie-brown text-xs font-semibold transition-all">
                                        Rincian Nota
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

