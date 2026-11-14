<?php
/**
 * ====================================================================
 * SUB-MODUL: Manajemen Kategori Roti & Pastry
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) AS total_produk
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.status != 'arsip'
    GROUP BY c.id
    ORDER BY c.urutan ASC, c.id ASC
");
$categories = $stmt->fetchAll();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-bold text-rotie-brown">Kategori Produk</h1>
            <p class="text-xs text-rotie-brown/70 font-light mt-0.5">Kelola klasifikasi menu bakery untuk filter katalog toko.</p>
        </div>

        <a href="admin.php?mod=kategori&act=tambah" 
           class="px-4 py-2.5 bg-rotie-brown hover:bg-rotie-caramel text-white rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5 btn-shimmer self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah Kategori Baru</span>
        </a>
    </div>

    <!-- Tabel Kategori -->
    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-3xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-rotie-cream/70 border-b border-rotie-beige text-rotie-olive font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-4 w-16 text-center">Urutan</th>
                        <th class="py-3.5 px-4">Nama Kategori</th>
                        <th class="py-3.5 px-4">Slug URL</th>
                        <th class="py-3.5 px-4">Deskripsi</th>
                        <th class="py-3.5 px-4 text-center">Jumlah Produk</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-rotie-beige/60">
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-500">Belum ada kategori yang dibuat.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-rotie-cream/30 transition-colors">
                                <td class="py-3 px-4 text-center font-bold text-rotie-brown">
                                    <?= (int) $cat['urutan']; ?>
                                </td>
                                <td class="py-3 px-4 font-serif font-bold text-sm text-rotie-brown">
                                    <?= e($cat['nama_kategori']); ?>
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-gray-500">
                                    <?= e($cat['slug']); ?>
                                </td>
                                <td class="py-3 px-4 text-rotie-brown/70 max-w-xs truncate">
                                    <?= e($cat['deskripsi'] ?? '-'); ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rotie-cream text-rotie-brown border border-rotie-beige">
                                        <?= (int) $cat['total_produk']; ?> item
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right space-x-1.5">
                                    <a href="admin.php?mod=kategori&act=edit&id=<?= $cat['id']; ?>" 
                                       class="px-2.5 py-1.5 rounded-lg bg-rotie-cream hover:bg-rotie-beige text-rotie-brown text-xs font-semibold transition-colors">
                                        Edit
                                    </a>

                                    <a href="admin.php?mod=kategori&act=hapus&id=<?= $cat['id']; ?>&csrf_token=<?= csrf_token(); ?>" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')"
                                       class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold transition-colors">
                                        Hapus
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

