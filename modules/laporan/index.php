<?php
/**
 * ====================================================================
 * SUB-MODUL: Laporan Penjualan, Tren Produk Terlaris & Ekspor CSV
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();

$periode = trim($_GET['periode'] ?? 'semua');
$tglMulai = trim($_GET['tgl_mulai'] ?? '');
$tglSelesai = trim($_GET['tgl_selesai'] ?? '');

// Penentuan Rentang Tanggal
if ($periode === 'hari_ini') {
    $tglMulai = date('Y-m-d');
    $tglSelesai = date('Y-m-d');
} elseif ($periode === 'bulan_ini') {
    $tglMulai = date('Y-m-01');
    $tglSelesai = date('Y-m-t');
}

// 1. Ekspor CSV Laporan jika diminta
if (isset($_GET['export_csv']) && $_GET['export_csv'] === '1') {
    $csvFile = 'rotie_laporan_penjualan_' . date('Ymd_His') . '.csv';

    if (ob_get_level()) ob_end_clean();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $csvFile . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($out, ['LAPORAN PENJUALAN ROTIÉ ARTISAN BAKERY']);
    fputcsv($out, ['Periode:', (!empty($tglMulai) ? $tglMulai . ' s/d ' . $tglSelesai : 'Semua Periode')]);
    fputcsv($out, []);
    fputcsv($out, ['Kode Transaksi', 'Pelanggan', 'No WhatsApp', 'Total Tagihan (Rp)', 'Status', 'Tanggal & Waktu']);

    $qExp = "SELECT o.*, c.nama, c.no_hp FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.status IN ('diproses', 'selesai')";
    $pExp = [];
    if (!empty($tglMulai)) {
        $qExp .= " AND DATE(o.created_at) >= :tm";
        $pExp[':tm'] = $tglMulai;
    }
    if (!empty($tglSelesai)) {
        $qExp .= " AND DATE(o.created_at) <= :ts";
        $pExp[':ts'] = $tglSelesai;
    }
    $qExp .= " ORDER BY o.created_at DESC";

    $stmtExp = $pdo->prepare($qExp);
    $stmtExp->execute($pExp);

    while ($r = $stmtExp->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $r['kode_pesanan'],
            $r['nama'],
            $r['no_hp'],
            (float) $r['total_harga'],
            $r['status'],
            $r['created_at']
        ]);
    }

    fclose($out);
    exit;
}

// 2. Query Agregasi Metrik Finansial
$sqlBase = "WHERE o.status IN ('diproses', 'selesai')";
$params = [];

if (!empty($tglMulai)) {
    $sqlBase .= " AND DATE(o.created_at) >= :tm";
    $params[':tm'] = $tglMulai;
}
if (!empty($tglSelesai)) {
    $sqlBase .= " AND DATE(o.created_at) <= :ts";
    $params[':ts'] = $tglSelesai;
}

// Total Omzet & Total Transaksi Sukses
$stmtOmzet = $pdo->prepare("SELECT COALESCE(SUM(total_harga), 0) AS omzet, COUNT(*) AS total_tx FROM orders o {$sqlBase}");
$stmtOmzet->execute($params);
$kpiFinansial = $stmtOmzet->fetch();
$totalOmzet = (float) $kpiFinansial['omzet'];
$totalTxSukses = (int) $kpiFinansial['total_tx'];
$aov = $totalTxSukses > 0 ? $totalOmzet / $totalTxSukses : 0;

// Total Roti Terjual (Kuantitas)
$stmtQty = $pdo->prepare("
    SELECT COALESCE(SUM(oi.jumlah), 0) AS total_qty
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    {$sqlBase}
");
$stmtQty->execute($params);
$totalQtyTerjual = (int) $stmtQty->fetchColumn();

// 3. Produk Terlaris (Top Selling Ranking)
$stmtTop = $pdo->prepare("
    SELECT p.nama_produk, p.gambar, c.nama_kategori,
           SUM(oi.jumlah) AS unit_terjual,
           SUM(oi.subtotal) AS omzet_produk
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    {$sqlBase}
    GROUP BY p.id
    ORDER BY unit_terjual DESC
    LIMIT 6
");
$stmtTop->execute($params);
$topProducts = $stmtTop->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-bold text-rotie-brown">Laporan Kinerja Penjualan</h1>
            <p class="text-xs text-rotie-brown/70 font-light mt-0.5">Analisis pendapatan, efektivitas omzet, dan tren roti paling diminati pasar.</p>
        </div>

        <!-- Tombol Ekspor CSV Laporan -->
        <a href="admin.php?mod=laporan&act=index&export_csv=1&periode=<?= urlencode($periode); ?>&tgl_mulai=<?= urlencode($tglMulai); ?>&tgl_selesai=<?= urlencode($tglSelesai); ?>" 
           class="px-4 py-2.5 bg-rotie-brown hover:bg-rotie-caramel text-white rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5 self-start btn-shimmer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>Unduh Laporan CSV</span>
        </a>
    </div>

    <!-- Filter Periode Laporan -->
    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="admin.php" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <input type="hidden" name="mod" value="laporan">
            <input type="hidden" name="act" value="index">

            <div class="sm:col-span-4">
                <select name="periode" onchange="this.form.submit()"
                        class="w-full px-3.5 py-2 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                    <option value="semua" <?= $periode === 'semua' ? 'selected' : ''; ?>>Semua Periode</option>
                    <option value="hari_ini" <?= $periode === 'hari_ini' ? 'selected' : ''; ?>>Hari Ini (<?= date('d M Y'); ?>)</option>
                    <option value="bulan_ini" <?= $periode === 'bulan_ini' ? 'selected' : ''; ?>>Bulan Ini (<?= date('F Y'); ?>)</option>
                    <option value="kustom" <?= $periode === 'kustom' ? 'selected' : ''; ?>>Rentang Tanggal Kustom</option>
                </select>
            </div>

            <div class="sm:col-span-3">
                <input type="date" name="tgl_mulai" value="<?= e($tglMulai); ?>"
                       class="w-full px-2.5 py-2 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
            </div>

            <div class="sm:col-span-3">
                <input type="date" name="tgl_selesai" value="<?= e($tglSelesai); ?>"
                       class="w-full px-2.5 py-2 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="w-full py-2 px-3 bg-rotie-brown text-white text-xs font-semibold rounded-xl hover:bg-rotie-caramel transition-colors shadow-sm">
                    Terapkan
                </button>
            </div>
        </form>
    </div>

    <!-- 4 KPI Laporan Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-5 shadow-sm">
            <span class="text-[10px] uppercase tracking-wider font-semibold text-rotie-olive block mb-1">Total Omzet Bersih</span>
            <span class="font-serif font-bold text-2xl text-rotie-caramel block"><?= format_rupiah($totalOmzet); ?></span>
            <span class="text-[10px] text-gray-500 mt-1 block">Pesanan berstatus terkonfirmasi</span>
        </div>

        <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-5 shadow-sm">
            <span class="text-[10px] uppercase tracking-wider font-semibold text-rotie-olive block mb-1">Kuantitas Terjual</span>
            <span class="font-serif font-bold text-2xl text-rotie-brown block"><?= $totalQtyTerjual; ?> Porsi</span>
            <span class="text-[10px] text-gray-500 mt-1 block">Total unit roti & pastry</span>
        </div>

        <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-5 shadow-sm">
            <span class="text-[10px] uppercase tracking-wider font-semibold text-rotie-olive block mb-1">Transaksi Sukses</span>
            <span class="font-serif font-bold text-2xl text-rotie-brown block"><?= $totalTxSukses; ?> Nota</span>
            <span class="text-[10px] text-gray-500 mt-1 block">Terselesaikan / diproses</span>
        </div>

        <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-5 shadow-sm">
            <span class="text-[10px] uppercase tracking-wider font-semibold text-rotie-olive block mb-1">Rata-rata Nota (AOV)</span>
            <span class="font-serif font-bold text-2xl text-rotie-brown block"><?= format_rupiah($aov); ?></span>
            <span class="text-[10px] text-gray-500 mt-1 block">Rata-rata belanja per pesanan</span>
        </div>

    </div>

    <!-- Tabel Peringkat Produk Terlaris -->
    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-3xl p-6 shadow-sm space-y-4">
        <div>
            <h3 class="font-display font-bold text-lg text-rotie-brown">Peringkat Produk Paling Diminati (Best Sellers)</h3>
            <p class="text-xs text-gray-500">Daftar varian roti dengan volume penjualan tertinggi pada periode yang dipilih</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-rotie-cream/70 border-b border-rotie-beige text-rotie-olive font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-4 w-12 text-center">Rank</th>
                        <th class="py-3 px-4">Nama Produk</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4 text-center">Volume Terjual</th>
                        <th class="py-3 px-4 text-right">Kontribusi Omzet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rotie-beige/60">
                    <?php if (empty($topProducts)): ?>
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500">Belum ada transaksi penjualan pada periode ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php $rank = 1; foreach ($topProducts as $tp): ?>
                            <tr class="hover:bg-rotie-cream/30 transition-colors">
                                <td class="py-3 px-4 text-center font-serif font-bold text-sm text-rotie-caramel">
                                    #<?= $rank++; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <img src="<?= e($tp['gambar']); ?>" alt="" class="w-8 h-8 rounded-lg object-cover border border-rotie-beige bg-amber-50">
                                        <span class="font-serif font-bold text-sm text-rotie-brown"><?= e($tp['nama_produk']); ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-semibold text-rotie-olive">
                                    <?= e($tp['nama_kategori']); ?>
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-sm text-rotie-brown">
                                    <?= (int) $tp['unit_terjual']; ?> porsi
                                </td>
                                <td class="py-3 px-4 text-right font-serif font-bold text-sm text-rotie-caramel">
                                    <?= format_rupiah($tp['omzet_produk']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

