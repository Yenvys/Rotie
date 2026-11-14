<?php
/**
 * ====================================================================
 * SUB-MODUL: Monitoring Persediaan & Penyesuaian Stok Fisik
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();
$error = '';

// Proses Form Penyesuaian Stok Manual (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_stock') {
    $csrfToken   = $_POST['csrf_token'] ?? '';
    $productId   = (int) ($_POST['product_id'] ?? 0);
    $tipeMutasi  = $_POST['tipe'] ?? 'masuk'; // 'masuk', 'keluar', 'penyesuaian'
    $jumlah      = (int) ($_POST['jumlah'] ?? 0);
    $keterangan  = trim($_POST['keterangan'] ?? '');

    if (!verify_csrf($csrfToken)) {
        $error = 'Validasi keamanan CSRF Token gagal.';
    } elseif ($productId <= 0 || $jumlah <= 0) {
        $error = 'ID produk dan jumlah mutasi harus lebih besar dari 0.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmtP = $pdo->prepare("SELECT id, nama_produk, stok FROM products WHERE id = :id FOR UPDATE");
            $stmtP->execute([':id' => $productId]);
            $p = $stmtP->fetch();

            if ($p) {
                $stokAwal = (int) $p['stok'];
                $stokAkhir = $stokAwal;

                if ($tipeMutasi === 'masuk') {
                    $stokAkhir = $stokAwal + $jumlah;
                } elseif ($tipeMutasi === 'keluar') {
                    $stokAkhir = max(0, $stokAwal - $jumlah);
                } elseif ($tipeMutasi === 'penyesuaian') {
                    $stokAkhir = $jumlah;
                    $jumlah = abs($stokAkhir - $stokAwal);
                }

                // Update Stok Produk
                $updP = $pdo->prepare("UPDATE products SET stok = :stok, status = IF(:stok_chk <= 0, 'habis', 'tersedia') WHERE id = :id");
                $updP->execute([':stok' => $stokAkhir, ':stok_chk' => $stokAkhir, ':id' => $productId]);

                // Catat ke Tabel stock_movements
                $insMv = $pdo->prepare("
                    INSERT INTO stock_movements (product_id, tipe, jumlah, stok_sebelum, stok_sesudah, keterangan, created_at)
                    VALUES (:pid, :tipe, :qty, :seb, :ses, :ket, NOW())
                ");
                $insMv->execute([
                    ':pid'  => $productId,
                    ':tipe' => $tipeMutasi,
                    ':qty'  => $jumlah,
                    ':seb'  => $stokAwal,
                    ':ses'  => $stokAkhir,
                    ':ket'  => !empty($keterangan) ? $keterangan : "Penyesuaian stok manual ({$tipeMutasi})"
                ]);

                $pdo->commit();
                set_flash('success', "Stok untuk '{$p['nama_produk']}' berhasil diperbarui (Stok Baru: {$stokAkhir}).");
                redirect('admin.php?mod=stok&act=list');
            }

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Adjust stock error: " . $e->getMessage());
            $error = 'Terjadi kesalahan sistem saat memperbarui stok.';
        }
    }
}

// Ambil Data Produk & Status Stok
$stmt = $pdo->query("
    SELECT p.*, c.nama_kategori
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.status != 'arsip'
    ORDER BY (p.stok <= p.stok_minimum) DESC, p.stok ASC
");
$products = $stmt->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-bold text-rotie-brown">Monitoring & Manajemen Stok</h1>
            <p class="text-xs text-rotie-brown/70 font-light mt-0.5">Pantau persediaan harian dan lakukan penyesuaian batch panggangan baru.</p>
        </div>

        <a href="admin.php?mod=stok&act=riwayat" 
           class="px-4 py-2.5 bg-white hover:bg-rotie-warm border border-rotie-beige text-rotie-brown rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5 self-start">
            <svg class="w-4 h-4 text-rotie-olive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Lihat Log Riwayat Mutasi</span>
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="p-3.5 rounded-xl bg-rose-50 text-rose-800 border border-rose-200 text-xs">
            <span>⚠️ <?= e($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Tabel Monitoring Stok -->
    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-3xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-rotie-cream/70 border-b border-rotie-beige text-rotie-olive font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-4">Nama Produk Roti</th>
                        <th class="py-3.5 px-4">Kategori</th>
                        <th class="py-3.5 px-4 text-center">Stok Terkini</th>
                        <th class="py-3.5 px-4 text-center">Batas Minimum</th>
                        <th class="py-3.5 px-4">Status Kondisi</th>
                        <th class="py-3.5 px-4 text-right">Penyesuaian Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rotie-beige/60">
                    <?php foreach ($products as $p): 
                        $stok = (int) $p['stok'];
                        $min  = (int) $p['stok_minimum'];
                        $isLow = $stok <= $min;
                        $isOut = $stok <= 0;
                    ?>
                        <tr class="hover:bg-rotie-cream/30 transition-colors">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <img src="<?= e($p['gambar']); ?>" alt="" class="w-10 h-10 object-cover rounded-lg border border-rotie-beige bg-amber-50">
                                    <span class="font-serif font-bold text-sm text-rotie-brown"><?= e($p['nama_produk']); ?></span>
                                </div>
                            </td>

                            <td class="py-3 px-4 font-semibold text-rotie-olive">
                                <?= e($p['nama_kategori']); ?>
                            </td>

                            <td class="py-3 px-4 text-center">
                                <span class="font-serif font-bold text-base <?= $isLow ? 'text-rose-600' : 'text-rotie-brown'; ?>">
                                    <?= $stok; ?>
                                </span>
                            </td>

                            <td class="py-3 px-4 text-center text-gray-500 font-medium">
                                <?= $min; ?>
                            </td>

                            <td class="py-3 px-4">
                                <?php if ($isOut): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        Habis Kosong
                                    </span>
                                <?php elseif ($isLow): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        Stok Menipis
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aman
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="py-3 px-4 text-right">
                                <!-- Form Penyesuaian Cepat Modal Trigger -->
                                <button type="button" 
                                        onclick="openStockModal(<?= $p['id']; ?>, '<?= addslashes(e($p['nama_produk'])); ?>', <?= $stok; ?>)"
                                        class="px-3 py-1.5 rounded-lg bg-rotie-brown hover:bg-rotie-caramel text-white text-xs font-semibold shadow-sm transition-all">
                                    + / - Update Stok
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form Penyesuaian Stok -->
<div id="stock-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closeStockModal()" class="fixed inset-0 bg-rotie-chocolate/60 backdrop-blur-sm"></div>

    <div class="relative bg-rotie-warm border border-rotie-beige rounded-3xl shadow-2xl max-w-md w-full p-6 z-10 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-rotie-beige">
            <div>
                <h3 class="font-serif font-bold text-lg text-rotie-brown">Penyesuaian Stok Fisik</h3>
                <p id="modal-stock-prod-name" class="text-xs text-rotie-caramel font-semibold">Produk</p>
            </div>
            <button type="button" onclick="closeStockModal()" class="text-gray-400 hover:text-gray-700">&times;</button>
        </div>

        <form action="admin.php?mod=stok&act=list" method="POST" class="space-y-4">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="adjust_stock">
            <input type="hidden" id="modal-stock-prod-id" name="product_id" value="">

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Jenis Perubahan</label>
                <select name="tipe" class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                    <option value="masuk">Stok Masuk (Baking Pagi/Batch Baru)</option>
                    <option value="keluar">Stok Keluar (Rusak/Kadaluwarsa/Sample)</option>
                    <option value="penyesuaian">Koreksi Saldo Fisik Langsung</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Jumlah Kuantitas</label>
                <input type="number" name="jumlah" required min="1" placeholder="Contoh: 15"
                       class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Keterangan / Alasan</label>
                <input type="text" name="keterangan" placeholder="Contoh: Hasil panggangan batch sore 14:00"
                       class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
            </div>

            <div class="pt-3 border-t border-rotie-beige flex items-center justify-end gap-2">
                <button type="button" onclick="closeStockModal()" class="px-4 py-2 rounded-xl border border-rotie-beige text-xs font-semibold text-rotie-brown">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rotie-brown hover:bg-rotie-caramel text-white text-xs font-semibold shadow transition-all">
                    Simpan Perubahan Stok
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openStockModal(id, name, current) {
    document.getElementById('modal-stock-prod-id').value = id;
    document.getElementById('modal-stock-prod-name').textContent = `${name} (Stok Saat Ini: ${current})`;
    document.getElementById('stock-modal').classList.remove('hidden');
}

function closeStockModal() {
    document.getElementById('stock-modal').classList.add('hidden');
}
</script>

