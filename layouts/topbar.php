<?php
/**
 * ====================================================================
 * LAYOUT: Topbar Identitas Pengguna Sesi & Breadcrumb
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$modTitle = [
    'dashboard' => 'Dashboard Overview',
    'produk'    => 'Manajemen Produk',
    'kategori'  => 'Kategori Roti',
    'transaksi' => 'Daftar Transaksi',
    'stok'      => 'Manajemen Stok',
    'laporan'   => 'Laporan Penjualan'
];

$breadcrumbMod = $modTitle[$mod] ?? ucfirst($mod);
$breadcrumbAct = ucfirst($act);
?>
<header class="h-20 bg-rotie-warm border-b border-rotie-beige px-4 sm:px-6 lg:px-8 flex items-center justify-between flex-shrink-0">
    
    <!-- Breadcrumb Navigasi -->
    <div class="flex items-center space-x-2 text-xs">
        <a href="admin.php" class="text-rotie-brown/60 hover:text-rotie-caramel transition-colors font-medium">
            Dashboard
        </a>
        <span class="text-rotie-beige font-bold">/</span>
        <span class="text-rotie-brown font-semibold"><?= e($breadcrumbMod); ?></span>
        <?php if ($act !== 'index' && $act !== 'list'): ?>
            <span class="text-rotie-beige font-bold">/</span>
            <span class="text-rotie-caramel font-bold"><?= e($breadcrumbAct); ?></span>
        <?php endif; ?>
    </div>

    <!-- Right Controls: Kunjungi Toko, Tanggal, User Status -->
    <div class="flex items-center space-x-4">
        
        <span class="hidden md:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rotie-cream text-[11px] font-medium text-rotie-brown border border-rotie-beige/80">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            <span><?= date('d M Y'); ?></span>
        </span>

        <!-- Direct Link to Public Landing Page -->
        <a href="index.php" target="_blank"
           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white border border-rotie-beige text-xs font-semibold text-rotie-brown hover:text-rotie-caramel hover:border-rotie-caramel shadow-sm transition-all">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            <span class="hidden sm:inline">Kunjungi Toko Publik</span>
        </a>

        <!-- User Identity Avatar -->
        <div class="flex items-center gap-2 pl-2 border-l border-rotie-beige">
            <span class="w-8 h-8 rounded-full bg-rotie-caramel text-white text-xs font-bold flex items-center justify-center shadow-sm">
                <?= strtoupper(substr($currentUser['username'] ?? 'A', 0, 1)); ?>
            </span>
            <div class="hidden sm:block text-left">
                <span class="block text-xs font-bold text-rotie-brown leading-tight"><?= e($currentUser['nama_lengkap'] ?? 'Admin'); ?></span>
                <span class="block text-[10px] text-rotie-olive capitalize"><?= e($currentUser['role'] ?? 'admin'); ?></span>
            </div>
        </div>

    </div>

</header>

