<?php
/**
 * ====================================================================
 * CONTROLLER ROUTER UTAMA DASHBOARD ADMIN (Whitelist Route Protection)
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * Pengembang: Muhammad Al Farel (NIM: 41257124)
 * ====================================================================
 */

require_once __DIR__ . '/helpers/security.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/auth_check.php';

// 1. Guard Autentikasi: Wajib Login
require_auth();

// 2. Tangkap Parameter Routing
$page = trim($_GET['page'] ?? '');
$mod  = trim($_GET['mod'] ?? '');
$act  = trim($_GET['act'] ?? 'index');

// Normalisasi parameter routing agar fleksibel (?page=produk atau ?mod=produk&act=list)
if (!empty($page)) {
    switch ($page) {
        case 'dashboard':
            $mod = 'dashboard';
            $act = 'index';
            break;
        case 'produk':
            $mod = 'produk';
            $act = 'list';
            break;
        case 'produk-live':
            $mod = 'produk';
            $act = 'live_list';
            break;
        case 'kategori':
            $mod = 'kategori';
            $act = 'list';
            break;
        case 'transaksi':
            $mod = 'transaksi';
            $act = 'list';
            break;
        case 'stok':
            $mod = 'stok';
            $act = 'list';
            break;
        case 'stok-riwayat':
            $mod = 'stok';
            $act = 'riwayat';
            break;
        case 'laporan':
            $mod = 'laporan';
            $act = 'index';
            break;
        default:
            $mod = 'dashboard';
            $act = 'index';
            break;
    }
}

if (empty($mod)) {
    $mod = 'dashboard';
    $act = 'index';
}

// 3. Whitelist Routing: Mencegah Local File Inclusion (LFI)
$whitelist = [
    'dashboard' => ['index'],
    'produk'    => ['list', 'live_list', 'tambah', 'edit', 'hapus', 'export', 'import'],
    'kategori'  => ['list', 'tambah', 'edit', 'hapus'],
    'transaksi' => ['list', 'detail', 'update_status'],
    'stok'      => ['list', 'riwayat'],
    'laporan'   => ['index']
];

if (!isset($whitelist[$mod]) || !in_array($act, $whitelist[$mod])) {
    $mod = 'dashboard';
    $act = 'index';
}

$moduleFile = __DIR__ . "/modules/{$mod}/{$act}.php";

if (!file_exists($moduleFile)) {
    die("Modul 'modules/{$mod}/{$act}.php' tidak ditemukan di peladen.");
}

// 4. Modul Download/Export CSV tidak memerlukan layout HTML
$isRawDownload = ($mod === 'produk' && $act === 'export') || 
                 ($mod === 'produk' && $act === 'hapus') || 
                 ($mod === 'kategori' && in_array($act, ['hapus', 'tambah', 'edit']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['view'])) ||
                 ($mod === 'transaksi' && $act === 'update_status');

if ($isRawDownload) {
    require_once $moduleFile;
    exit;
}

// 5. Muat Layout Dashboard Terpadu
$currentUser = current_user();
$pageTitle = ucfirst($mod) . ' — ' . ucfirst($act);

require_once __DIR__ . '/layouts/header.php';
?>
<div class="flex h-screen bg-rotie-cream overflow-hidden">
    <!-- Sidebar Navigasi Terpadu -->
    <?php require_once __DIR__ . '/layouts/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Topbar Identitas & Breadcrumb -->
        <?php require_once __DIR__ . '/layouts/topbar.php'; ?>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-rotie-cream/70">
            <?php 
                $flash = get_flash();
                if ($flash): 
            ?>
                <div class="mb-6 p-4 rounded-2xl flex items-center justify-between text-sm shadow-sm <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'; ?>">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php if ($flash['type'] === 'success'): ?>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            <?php else: ?>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            <?php endif; ?>
                        </svg>
                        <span><?= e($flash['message']); ?></span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-700">
                        &times;
                    </button>
                </div>
            <?php endif; ?>

            <?php require_once $moduleFile; ?>
        </main>
    </div>
</div>
<?php
require_once __DIR__ . '/layouts/footer.php';

