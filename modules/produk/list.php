<?php
/**
 * ====================================================================
 * SUB-MODUL: Tabel Komoditas Produk & Aksi Cepat
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();

$kategoriFilter = trim($_GET['kategori'] ?? '');
$searchKeyword  = trim($_GET['search'] ?? '');

$sql = "
    SELECT p.*, c.nama_kategori 
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE 1=1
";
$params = [];

if (!empty($kategoriFilter)) {
    $sql .= " AND (c.slug = :kat OR c.id = :kat_id)";
    $params[':kat'] = $kategoriFilter;
    $params[':kat_id'] = is_numeric($kategoriFilter) ? (int)$kategoriFilter : 0;
}

if (!empty($searchKeyword)) {
    $sql .= " AND (p.nama_produk LIKE :kw OR p.deskripsi LIKE :kw_desc)";
    $params[':kw'] = "%{$searchKeyword}%";
    $params[':kw_desc'] = "%{$searchKeyword}%";
}

$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Ambil Kategori untuk dropdown filter
$allCategories = $pdo->query("SELECT * FROM categories ORDER BY urutan ASC")->fetchAll();
?>

<div class="space-y-6">
    
    <!-- Header Modul & Action Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-bold text-rotie-brown">Katalog Komoditas Produk</h1>
            <p class="text-xs text-rotie-brown/70 font-light mt-0.5">Kelola seluruh varian roti, pastry, cake, harga dan persediaan fisik.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="admin.php?mod=produk&act=tambah" 
               class="px-4 py-2.5 bg-rotie-brown hover:bg-rotie-caramel text-white rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5 btn-shimmer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Produk</span>
            </a>

            <a href="admin.php?mod=produk&act=import" 
               class="px-3.5 py-2.5 bg-white hover:bg-rotie-warm border border-rotie-beige text-rotie-brown rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                <svg class="w-4 h-4 text-rotie-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <span>Impor CSV</span>
            </a>

            <a href="admin.php?mod=produk&act=export" 
               class="px-3.5 py-2.5 bg-white hover:bg-rotie-warm border border-rotie-beige text-rotie-brown rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                <svg class="w-4 h-4 text-rotie-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Ekspor CSV</span>
            </a>

            <a href="admin.php?mod=produk&act=live_list" 
               class="px-3.5 py-2.5 bg-rotie-cream hover:bg-rotie-beige/80 text-rotie-brown border border-rotie-beige rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5"
               title="Tampilan Interaktif AJAX">
                <span>⚡ Live AJAX</span>
            </a>
        </div>
    </div>

    <!-- Filter & Pencarian Bar -->
    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-4 shadow-sm">
        <form method="GET" action="admin.php" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <input type="hidden" name="mod" value="produk">
            <input type="hidden" name="act" value="list">

            <div class="sm:col-span-6 relative">
                <input type="text" name="search" value="<?= e($searchKeyword); ?>" 
                       placeholder="Cari berdasarkan nama atau deskripsi..."
                       class="w-full px-3.5 py-2 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
            </div>

            <div class="sm:col-span-4">
                <select name="kategori" class="w-full px-3 py-2 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($allCategories as $c): ?>
                        <option value="<?= e($c['slug']); ?>" <?= $kategoriFilter === $c['slug'] ? 'selected' : ''; ?>>
                            <?= e($c['nama_kategori']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="w-full py-2 px-3 bg-rotie-brown text-white text-xs font-semibold rounded-xl hover:bg-rotie-caramel transition-colors shadow-sm">
                    Filter
                </button>
                <?php if (!empty($searchKeyword) || !empty($kategoriFilter)): ?>
                    <a href="admin.php?mod=produk&act=list" class="py-2 px-3 bg-gray-100 text-gray-600 text-xs font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabel Data Produk -->
    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-3xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-rotie-cream/70 border-b border-rotie-beige text-rotie-olive font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-4">Produk</th>
                        <th class="py-3.5 px-4">Kategori</th>
                        <th class="py-3.5 px-4">Harga Jual</th>
                        <th class="py-3.5 px-4">Stok Fisik</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rotie-beige/60">
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-500">
                                Tidak ada data produk yang sesuai dengan kriteria pencarian.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): 
                            $isLow = $p['stok'] <= $p['stok_minimum'];
                            $hargaAsli = (float) $p['harga'];
                            $diskon = (float) $p['diskon_persen'];
                            $isPromo = (bool) $p['is_promo'];
                            $hargaFinal = ($isPromo && $diskon > 0) ? $hargaAsli - ($hargaAsli * $diskon / 100) : $hargaAsli;
                        ?>
                            <tr id="row-prod-<?= $p['id']; ?>" class="hover:bg-rotie-cream/30 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <img src="<?= e($p['gambar']); ?>" alt="<?= e($p['nama_produk']); ?>" 
                                             class="w-12 h-12 object-cover rounded-xl border border-rotie-beige/80 flex-shrink-0 bg-amber-50">
                                        <div>
                                            <span class="font-serif font-bold text-sm text-rotie-brown block"><?= e($p['nama_produk']); ?></span>
                                            <span class="text-[10px] text-gray-400 font-mono">slug: <?= e($p['slug']); ?></span>
                                            <?php if ($p['is_featured']): ?>
                                                <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 text-[9px] font-bold">Unggulan</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3 px-4 font-semibold text-rotie-olive">
                                    <?= e($p['nama_kategori']); ?>
                                </td>

                                <td class="py-3 px-4">
                                    <?php if ($isPromo && $diskon > 0): ?>
                                        <span class="font-serif font-bold text-rotie-caramel block"><?= format_rupiah($hargaFinal); ?></span>
                                        <span class="text-[10px] text-gray-400 line-through"><?= format_rupiah($hargaAsli); ?> (-<?= (int)$diskon; ?>%)</span>
                                    <?php else: ?>
                                        <span class="font-serif font-bold text-rotie-brown"><?= format_rupiah($hargaFinal); ?></span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-sm <?= $isLow ? 'text-rose-600' : 'text-rotie-brown'; ?>">
                                            <?= (int)$p['stok']; ?>
                                        </span>
                                        <span class="text-[10px] text-gray-400">(Min: <?= (int)$p['stok_minimum']; ?>)</span>
                                    </div>
                                    <?php if ($isLow): ?>
                                        <span class="text-[9px] font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded block mt-0.5 w-fit">Stok Rendah</span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3 px-4">
                                    <?php if ($p['status'] === 'tersedia'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Tersedia</span>
                                    <?php elseif ($p['status'] === 'habis'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Habis</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200">Diarsip</span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3 px-4 text-right space-x-1.5">
                                    <a href="admin.php?mod=produk&act=edit&id=<?= $p['id']; ?>" 
                                       class="px-2.5 py-1.5 rounded-lg bg-rotie-cream hover:bg-rotie-beige text-rotie-brown text-xs font-semibold transition-colors">
                                        Edit
                                    </a>

                                    <button type="button" 
                                            onclick="hapusProdukAsync(<?= $p['id']; ?>, '<?= csrf_token(); ?>', document.getElementById('row-prod-<?= $p['id']; ?>'))"
                                            class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold transition-colors">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

