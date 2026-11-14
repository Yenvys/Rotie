<?php
/**
 * ====================================================================
 * LAYOUT: Sidebar Navigasi Terpadu (Active Menu State)
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$currentMod = $mod ?? 'dashboard';
$currentAct = $act ?? 'index';

function is_active_menu($targetMod, $targetAct = null) {
    global $currentMod, $currentAct;
    if ($targetAct === null) {
        return $currentMod === $targetMod;
    }
    return ($currentMod === $targetMod) && ($currentAct === $targetAct);
}

function menu_classes($isActive) {
    if ($isActive) {
        return 'bg-rotie-caramel text-white font-semibold shadow-md';
    }
    return 'text-rotie-brown hover:bg-rotie-beige/60 hover:text-rotie-chocolate transition-colors';
}
?>
<aside class="w-64 bg-rotie-warm border-r border-rotie-beige flex flex-col justify-between flex-shrink-0">
    <div>
        <!-- Brand Header -->
        <div class="h-20 flex items-center px-6 border-b border-rotie-beige/70">
            <a href="admin.php" class="flex items-center gap-2 group">
                <span class="w-9 h-9 rounded-full bg-rotie-caramel text-white flex items-center justify-center font-display text-lg font-bold shadow-sm">
                    R
                </span>
                <div class="flex flex-col">
                    <span class="font-display text-xl font-bold tracking-wider text-rotie-brown">
                        ROTIÉ
                    </span>
                    <span class="text-[9px] uppercase tracking-widest text-rotie-olive font-semibold -mt-1">
                        Portal Pengelola
                    </span>
                </div>
            </a>
        </div>

        <!-- Menu Navigation List -->
        <div class="p-4 space-y-6 overflow-y-auto max-h-[calc(100vh-140px)]">
            
            <div>
                <p class="px-3 text-[10px] uppercase font-bold tracking-widest text-rotie-olive/80 mb-2">Utama</p>
                <nav class="space-y-1">
                    <a href="admin.php?page=dashboard" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs <?= menu_classes(is_active_menu('dashboard')); ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard Overview</span>
                    </a>
                </nav>
            </div>

            <div>
                <p class="px-3 text-[10px] uppercase font-bold tracking-widest text-rotie-olive/80 mb-2">Katalog & Produk</p>
                <nav class="space-y-1">
                    <a href="admin.php?mod=produk&act=list" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs <?= menu_classes(is_active_menu('produk', 'list')); ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span>Kelola Produk</span>
                    </a>

                    <a href="admin.php?mod=produk&act=live_list" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs <?= menu_classes(is_active_menu('produk', 'live_list')); ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                        </svg>
                        <span>Live Katalog (AJAX)</span>
                    </a>

                    <a href="admin.php?mod=kategori&act=list" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs <?= menu_classes(is_active_menu('kategori')); ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span>Kategori Roti</span>
                    </a>
                </nav>
            </div>

            <div>
                <p class="px-3 text-[10px] uppercase font-bold tracking-widest text-rotie-olive/80 mb-2">Penjualan & Inventaris</p>
                <nav class="space-y-1">
                    <a href="admin.php?mod=transaksi&act=list" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs <?= menu_classes(is_active_menu('transaksi')); ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>Daftar Transaksi</span>
                    </a>

                    <a href="admin.php?mod=stok&act=list" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs <?= menu_classes(is_active_menu('stok', 'list')); ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>Monitoring Stok</span>
                    </a>

                    <a href="admin.php?mod=stok&act=riwayat" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs <?= menu_classes(is_active_menu('stok', 'riwayat')); ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Riwayat Mutasi Stok</span>
                    </a>

                    <a href="admin.php?mod=laporan&act=index" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs <?= menu_classes(is_active_menu('laporan')); ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Laporan Penjualan</span>
                    </a>
                </nav>
            </div>

        </div>
    </div>

    <!-- User Profile & Logout Bottom Bar -->
    <div class="p-4 border-t border-rotie-beige/80 bg-rotie-cream/50">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-8 h-8 rounded-full bg-rotie-brown text-white text-xs font-bold flex items-center justify-center flex-shrink-0">
                    <?= strtoupper(substr($currentUser['username'] ?? 'A', 0, 1)); ?>
                </span>
                <div class="truncate">
                    <p class="text-xs font-bold text-rotie-brown truncate"><?= e($currentUser['nama_lengkap'] ?? 'Admin'); ?></p>
                    <p class="text-[10px] text-rotie-olive capitalize"><?= e($currentUser['role'] ?? 'admin'); ?></p>
                </div>
            </div>

            <a href="auth/logout.php" title="Keluar dari Sistem" 
               class="p-1.5 text-gray-400 hover:text-rose-600 transition-colors rounded-lg hover:bg-rose-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </a>
        </div>
    </div>
</aside>

