<?php
/**
 * ====================================================================
 * SUB-MODUL: Dashboard Overview & Ringkasan Metrik Statistik
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

try {
    $pdo = getDBConnection();

    // 1. Ringkasan Metrik KPI
    $totalProduk = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status != 'arsip'")->fetchColumn();
    $totalStok   = (int) $pdo->query("SELECT COALESCE(SUM(stok), 0) FROM products WHERE status != 'arsip'")->fetchColumn();
    $totalTx     = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $omzet       = (float) $pdo->query("SELECT COALESCE(SUM(total_harga), 0) FROM orders WHERE status IN ('diproses', 'selesai')")->fetchColumn();
    $pendingTx   = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
    $lowStock    = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE stok <= stok_minimum AND status != 'arsip'")->fetchColumn();

    // 2. Daftar Produk Stok Rendah (Alert)
    $stmtLow = $pdo->query("
        SELECT id, nama_produk, stok, stok_minimum, gambar 
        FROM products 
        WHERE stok <= stok_minimum AND status != 'arsip'
        ORDER BY stok ASC 
        LIMIT 5
    ");
    $lowStockProducts = $stmtLow->fetchAll();

    // 3. Transaksi Terbaru
    $stmtRecent = $pdo->query("
        SELECT o.*, c.nama AS nama_pelanggan, c.no_hp,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS total_items
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        ORDER BY o.created_at DESC
        LIMIT 6
    ");
    $recentOrders = $stmtRecent->fetchAll();

    // 4. Data untuk Grafik Distribusi Kategori
    $stmtCatDist = $pdo->query("
        SELECT c.nama_kategori, COUNT(p.id) as total_produk
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.status != 'arsip'
        GROUP BY c.id
    ");
    $catDist = $stmtCatDist->fetchAll();
    $catLabels = array_column($catDist, 'nama_kategori');
    $catCounts = array_map('intval', array_column($catDist, 'total_produk'));

    // 5. Data Grafik Omzet 7 Hari Terakhir
    $salesDays = [];
    $salesValues = [];
    for ($i = 6; $i >= 0; $i--) {
        $dateStr = date('Y-m-d', strtotime("-{$i} days"));
        $dayName = date('D', strtotime("-{$i} days"));
        $dayIndo = [
            'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa',
            'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'
        ][$dayName] ?? $dayName;

        $stmtD = $pdo->prepare("SELECT COALESCE(SUM(total_harga), 0) FROM orders WHERE DATE(created_at) = :d AND status IN ('diproses', 'selesai')");
        $stmtD->execute([':d' => $dateStr]);
        $val = (float) $stmtD->fetchColumn();

        $salesDays[] = $dayIndo;
        $salesValues[] = $val;
    }

} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $totalProduk = $totalStok = $totalTx = $pendingTx = $lowStock = 0;
    $omzet = 0;
    $lowStockProducts = [];
    $recentOrders = [];
    $catLabels = [];
    $catCounts = [];
    $salesDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    $salesValues = [0, 0, 0, 0, 0, 0, 0];
}
?>

<div class="space-y-8">
    
    <!-- Welcome Header & Quick Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl sm:text-3xl font-bold text-rotie-brown">
                Selamat Datang, <?= e($currentUser['nama_lengkap'] ?? 'Admin'); ?>!
            </h1>
            <p class="text-xs text-rotie-brown/70 mt-1 font-light">
                Berikut adalah ringkasan performa penjualan dan inventaris stok ROTIÉ Artisan Bakery hari ini.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="admin.php?mod=produk&act=tambah" 
               class="px-4 py-2.5 bg-rotie-brown hover:bg-rotie-caramel text-white rounded-xl text-xs font-semibold shadow-sm hover:shadow transition-all flex items-center gap-1.5 btn-shimmer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Produk</span>
            </a>
            <a href="admin.php?mod=produk&act=export" 
               class="px-4 py-2.5 bg-white hover:bg-rotie-warm border border-rotie-beige text-rotie-brown rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                <svg class="w-4 h-4 text-rotie-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Ekspor CSV</span>
            </a>
        </div>
    </div>

    <!-- Alert Stok Rendah jika ada -->
    <?php if ($lowStock > 0): ?>
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex items-start gap-3">
            <span class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold flex-shrink-0">
                ⚠️
            </span>
            <div class="flex-1 text-xs">
                <h4 class="font-bold text-amber-900 mb-0.5">Peringatan: Terdapat <?= $lowStock; ?> Produk dengan Stok Menipis!</h4>
                <p class="text-amber-800">
                    Beberapa varian roti telah menyentuh atau berada di bawah batas stok minimum. Segera lakukan penambahan stok pada menu 
                    <a href="admin.php?mod=stok&act=list" class="font-bold underline text-amber-950">Monitoring Stok</a>.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- 6 KPI Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        
        <!-- Total Pendapatan -->
        <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-4 shadow-sm">
            <span class="text-[10px] uppercase tracking-wider font-semibold text-rotie-olive block mb-1">Total Omzet</span>
            <span class="font-serif font-bold text-lg sm:text-xl text-rotie-caramel block"><?= format_rupiah($omzet); ?></span>
            <span class="text-[10px] text-gray-500 mt-1 block">Pesanan berstatus sukses</span>
        </div>

        <!-- Total Transaksi -->
        <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-4 shadow-sm">
            <span class="text-[10px] uppercase tracking-wider font-semibold text-rotie-olive block mb-1">Total Pesanan</span>
            <span class="font-serif font-bold text-xl sm:text-2xl text-rotie-brown block"><?= $totalTx; ?></span>
            <span class="text-[10px] text-gray-500 mt-1 block">Transaksi masuk</span>
        </div>

        <!-- Pesanan Pending -->
        <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-4 shadow-sm">
            <span class="text-[10px] uppercase tracking-wider font-semibold text-rotie-olive block mb-1">Perlu Diproses</span>
            <span class="font-serif font-bold text-xl sm:text-2xl <?= $pendingTx > 0 ? 'text-amber-600' : 'text-rotie-brown'; ?> block"><?= $pendingTx; ?></span>
            <span class="text-[10px] text-gray-500 mt-1 block">Status pending</span>
        </div>

        <!-- Total Produk -->
        <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-4 shadow-sm">
            <span class="text-[10px] uppercase tracking-wider font-semibold text-rotie-olive block mb-1">Total Produk</span>
            <span class="font-serif font-bold text-xl sm:text-2xl text-rotie-brown block"><?= $totalProduk; ?></span>
            <span class="text-[10px] text-gray-500 mt-1 block">Katalog aktif</span>
        </div>

        <!-- Total Stok Fisik -->
        <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-4 shadow-sm">
            <span class="text-[10px] uppercase tracking-wider font-semibold text-rotie-olive block mb-1">Total Fisik Stok</span>
            <span class="font-serif font-bold text-xl sm:text-2xl text-rotie-brown block"><?= $totalStok; ?></span>
            <span class="text-[10px] text-gray-500 mt-1 block">Porsi roti & pastry</span>
        </div>

        <!-- Stok Rendah -->
        <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-4 shadow-sm">
            <span class="text-[10px] uppercase tracking-wider font-semibold text-rotie-olive block mb-1">Stok Kritis</span>
            <span class="font-serif font-bold text-xl sm:text-2xl <?= $lowStock > 0 ? 'text-rose-600' : 'text-emerald-600'; ?> block"><?= $lowStock; ?></span>
            <span class="text-[10px] text-gray-500 mt-1 block">&le; batas minimum</span>
        </div>

    </div>

    <!-- Charts Section: Line Chart & Doughnut Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Tren Penjualan 7 Hari -->
        <div class="lg:col-span-8 bg-rotie-warm border border-rotie-beige/80 rounded-3xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-display font-bold text-base text-rotie-brown">Tren Penjualan (7 Hari Terakhir)</h3>
                    <p class="text-xs text-gray-500">Omzet transaksi pesanan yang telah diproses/selesai</p>
                </div>
                <span class="px-3 py-1 rounded-full bg-rotie-cream border border-rotie-beige text-[11px] font-semibold text-rotie-caramel">
                    Chart.js Line
                </span>
            </div>

            <div class="h-64 relative">
                <canvas id="salesChart" 
                        data-labels='<?= json_encode($salesDays); ?>'
                        data-values='<?= json_encode($salesValues); ?>'>
                </canvas>
            </div>
        </div>

        <!-- Distribusi Kategori -->
        <div class="lg:col-span-4 bg-rotie-warm border border-rotie-beige/80 rounded-3xl p-6 shadow-sm">
            <div class="mb-4">
                <h3 class="font-display font-bold text-base text-rotie-brown">Distribusi Kategori</h3>
                <p class="text-xs text-gray-500">Komposisi jumlah menu roti per kategori</p>
            </div>

            <div class="h-64 relative">
                <canvas id="categoryChart"
                        data-labels='<?= json_encode($catLabels); ?>'
                        data-counts='<?= json_encode($catCounts); ?>'>
                </canvas>
            </div>
        </div>

    </div>

    <!-- Recent Orders Table & Status Management -->
    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-3xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-display font-bold text-lg text-rotie-brown">Daftar Transaksi Terbaru</h3>
                <p class="text-xs text-gray-500">Pesanan pelanggan terbaru yang masuk ke sistem</p>
            </div>
            <a href="admin.php?mod=transaksi&act=list" class="text-xs font-semibold text-rotie-caramel hover:underline">
                Lihat Semua Transaksi &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-rotie-beige text-rotie-olive font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-3">Kode Pesanan</th>
                        <th class="py-3 px-3">Pelanggan</th>
                        <th class="py-3 px-3">Total Tagihan</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3">Waktu</th>
                        <th class="py-3 px-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rotie-beige/60">
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-500">Belum ada transaksi pesanan yang tercatat.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr class="hover:bg-rotie-cream/40 transition-colors">
                                <td class="py-3 px-3 font-mono font-bold text-rotie-brown">
                                    <?= e($ro['kode_pesanan']); ?>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="font-bold text-rotie-brown block"><?= e($ro['nama_pelanggan']); ?></span>
                                    <span class="text-gray-500 text-[10px]"><?= e($ro['no_hp']); ?></span>
                                </td>
                                <td class="py-3 px-3 font-serif font-bold text-rotie-caramel">
                                    <?= format_rupiah($ro['total_harga']); ?>
                                </td>
                                <td class="py-3 px-3">
                                    <!-- Dynamic Select Updater AJAX -->
                                    <select data-order-id="<?= $ro['id']; ?>" data-csrf="<?= csrf_token(); ?>"
                                            class="tx-status-select py-1 px-2.5 rounded-lg text-xs font-semibold border border-rotie-beige bg-white focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                                        <option value="pending" <?= $ro['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="diproses" <?= $ro['status'] === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                                        <option value="selesai" <?= $ro['status'] === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                        <option value="dibatalkan" <?= $ro['status'] === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                                    </select>
                                </td>
                                <td class="py-3 px-3 text-gray-500 text-[11px]">
                                    <?= date('d M Y, H:i', strtotime($ro['created_at'])); ?>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <a href="admin.php?mod=transaksi&act=detail&id=<?= $ro['id']; ?>" 
                                       class="px-2.5 py-1 rounded-lg bg-rotie-cream hover:bg-rotie-caramel hover:text-white text-rotie-brown text-[11px] font-semibold transition-all">
                                        Rincian
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

