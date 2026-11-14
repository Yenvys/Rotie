<?php
/**
 * ====================================================================
 * SUB-MODUL: Formulir Tambah Produk & Unggah Berkas Gambar
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();
$categories = $pdo->query("SELECT * FROM categories ORDER BY urutan ASC")->fetchAll();

$error = '';
$form = [
    'nama_produk'   => '',
    'category_id'   => '',
    'deskripsi'     => '',
    'harga'         => '',
    'stok'          => '20',
    'stok_minimum'  => '5',
    'gambar_url'    => '',
    'is_featured'   => 0,
    'is_promo'      => 0,
    'diskon_persen' => '0',
    'status'        => 'tersedia'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // 1. Verifikasi Anti-CSRF Token
    if (!verify_csrf($csrfToken)) {
        $error = 'Validasi keamanan CSRF Token gagal. Silakan muat ulang halaman.';
    } else {
        $namaProduk   = trim($_POST['nama_produk'] ?? '');
        $categoryId   = (int) ($_POST['category_id'] ?? 0);
        $deskripsi    = trim($_POST['deskripsi'] ?? '');
        $harga        = (float) ($_POST['harga'] ?? 0);
        $stok         = (int) ($_POST['stok'] ?? 0);
        $stokMin      = (int) ($_POST['stok_minimum'] ?? 5);
        $gambarUrl    = trim($_POST['gambar_url'] ?? '');
        $isFeatured   = isset($_POST['is_featured']) ? 1 : 0;
        $isPromo      = isset($_POST['is_promo']) ? 1 : 0;
        $diskon       = (float) ($_POST['diskon_persen'] ?? 0);
        $status       = in_array($_POST['status'] ?? '', ['tersedia', 'habis', 'arsip']) ? $_POST['status'] : 'tersedia';

        $form = array_merge($form, $_POST);

        if (empty($namaProduk) || $categoryId <= 0 || empty($deskripsi) || $harga <= 0) {
            $error = 'Bidang nama produk, kategori, deskripsi, dan harga wajib diisi.';
        } else {
            $gambarPath = $gambarUrl;

            // 2. Pemrosesan Unggahan Berkas Gambar
            if (isset($_FILES['gambar_file']) && $_FILES['gambar_file']['error'] === UPLOAD_ERR_OK) {
                $fileTmp  = $_FILES['gambar_file']['tmp_name'];
                $fileName = $_FILES['gambar_file']['name'];
                $fileSize = $_FILES['gambar_file']['size'];
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
                $maxBytes = 2 * 1024 * 1024; // 2MB

                if (!in_array($ext, $allowedExts)) {
                    $error = 'Format berkas gambar harus JPG, JPEG, PNG, atau WEBP.';
                } elseif ($fileSize > $maxBytes) {
                    $error = 'Ukuran berkas gambar maksimal 2MB.';
                } else {
                    $uploadDir = __DIR__ . '/../../uploads/products/';
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0755, true);
                    }
                    $newFileName = 'rotie-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $targetPath = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmp, $targetPath)) {
                        $gambarPath = 'uploads/products/' . $newFileName;
                    } else {
                        $error = 'Gagal menyimpan berkas gambar ke direktori server.';
                    }
                }
            }

            if (empty($gambarPath) && empty($error)) {
                $gambarPath = 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80';
            }

            if (empty($error)) {
                try {
                    $slug = slugify($namaProduk);
                    // Cek keunikan slug
                    $stmtCek = $pdo->prepare("SELECT id FROM products WHERE slug = :slug LIMIT 1");
                    $stmtCek->execute([':slug' => $slug]);
                    if ($stmtCek->fetch()) {
                        $slug .= '-' . time();
                    }

                    $pdo->beginTransaction();

                    $ins = $pdo->prepare("
                        INSERT INTO products (category_id, nama_produk, slug, deskripsi, harga, stok, stok_minimum, gambar, is_featured, is_promo, diskon_persen, status, created_at)
                        VALUES (:cid, :nama, :slug, :desk, :harga, :stok, :stok_min, :gambar, :feat, :promo, :diskon, :status, NOW())
                    ");
                    $ins->execute([
                        ':cid'       => $categoryId,
                        ':nama'      => $namaProduk,
                        ':slug'      => $slug,
                        ':desk'      => $deskripsi,
                        ':harga'     => $harga,
                        ':stok'      => $stok,
                        ':stok_min'  => $stokMin,
                        ':gambar'    => $gambarPath,
                        ':feat'      => $isFeatured,
                        ':promo'     => $isPromo,
                        ':diskon'    => $diskon,
                        ':status'    => $status
                    ]);
                    $newProdId = (int) $pdo->lastInsertId();

                    // Catat Stok Awal di stock_movements
                    if ($stok > 0) {
                        $insMv = $pdo->prepare("
                            INSERT INTO stock_movements (product_id, tipe, jumlah, stok_sebelum, stok_sesudah, keterangan, created_at)
                            VALUES (:pid, 'masuk', :jml, 0, :sesudah, 'Stok awal penambahan produk baru', NOW())
                        ");
                        $insMv->execute([':pid' => $newProdId, ':jml' => $stok, ':sesudah' => $stok]);
                    }

                    $pdo->commit();

                    set_flash('success', "Produk '{$namaProduk}' berhasil ditambahkan ke katalog!");
                    redirect('admin.php?mod=produk&act=list');

                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    error_log("Tambah produk error: " . $e->getMessage());
                    $error = 'Terjadi kesalahan basis data saat menyimpan produk.';
                }
            }
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-rotie-brown">Tambah Produk Roti Baru</h1>
            <p class="text-xs text-rotie-brown/70 font-light mt-0.5">Lengkapi spesifikasi komoditas bakery dan atur persediaan awal.</p>
        </div>
        <a href="admin.php?mod=produk&act=list" class="text-xs font-semibold text-rotie-caramel hover:underline">
            &larr; Kembali
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="p-3.5 rounded-xl bg-rose-50 text-rose-800 border border-rose-200 text-xs flex items-center gap-2">
            <span>⚠️ <?= e($error); ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-3xl p-6 sm:p-8 shadow-sm">
        <form action="admin.php?mod=produk&act=tambah" method="POST" enctype="multipart/form-data" class="space-y-6">
            <?= csrf_field(); ?>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-5">
                
                <div class="sm:col-span-8">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Nama Produk Roti *</label>
                    <input type="text" name="nama_produk" value="<?= e($form['nama_produk']); ?>" required
                           placeholder="Contoh: Almond Croissant"
                           class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Kategori *</label>
                    <select name="category_id" required class="w-full px-3 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id']; ?>" <?= (int)$form['category_id'] === (int)$c['id'] ? 'selected' : ''; ?>>
                                <?= e($c['nama_kategori']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sm:col-span-12">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Deskripsi Produk *</label>
                    <textarea name="deskripsi" rows="3" required placeholder="Jelaskan aroma, tekstur kerak, remah, bahan butter yang digunakan..."
                              class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel"><?= e($form['deskripsi']); ?></textarea>
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Harga Satuan (Rp) *</label>
                    <input type="number" step="500" name="harga" value="<?= e($form['harga']); ?>" required placeholder="Contoh: 25000"
                           class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Stok Awal Fisik *</label>
                    <input type="number" name="stok" value="<?= e($form['stok']); ?>" required min="0"
                           class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Batas Stok Minimum *</label>
                    <input type="number" name="stok_minimum" value="<?= e($form['stok_minimum']); ?>" required min="1"
                           class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                </div>

                <div class="sm:col-span-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Unggah Berkas Foto (JPG/PNG/WEBP)</label>
                    <input type="file" name="gambar_file" accept=".jpg,.jpeg,.png,.webp"
                           class="w-full px-3.5 py-2 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-rotie-cream file:text-rotie-brown hover:file:bg-rotie-beige">
                </div>

                <div class="sm:col-span-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Atau URL Gambar Eksternal</label>
                    <input type="url" name="gambar_url" value="<?= e($form['gambar_url']); ?>" placeholder="https://images.unsplash.com/..."
                           class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Status Ketersediaan</label>
                    <select name="status" class="w-full px-3 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                        <option value="tersedia">Tersedia</option>
                        <option value="habis">Habis</option>
                        <option value="arsip">Diarsipkan</option>
                    </select>
                </div>

                <div class="sm:col-span-4 flex items-center pt-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" <?= $form['is_featured'] ? 'checked' : ''; ?>
                               class="w-4 h-4 rounded text-rotie-caramel focus:ring-rotie-caramel">
                        <span class="text-xs font-semibold text-rotie-brown">Tandai sebagai Unggulan (Signature)</span>
                    </label>
                </div>

                <div class="sm:col-span-4 flex items-center pt-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_promo" value="1" <?= $form['is_promo'] ? 'checked' : ''; ?>
                               class="w-4 h-4 rounded text-rotie-caramel focus:ring-rotie-caramel">
                        <span class="text-xs font-semibold text-rotie-brown">Promo Diskon Khusus</span>
                    </label>
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Besaran Diskon (%)</label>
                    <input type="number" step="1" min="0" max="90" name="diskon_persen" value="<?= e($form['diskon_persen']); ?>"
                           class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                </div>

            </div>

            <div class="pt-4 border-t border-rotie-beige flex items-center justify-end gap-3">
                <a href="admin.php?mod=produk&act=list" class="px-4 py-2.5 rounded-xl border border-rotie-beige text-xs font-semibold text-rotie-brown hover:bg-rotie-cream">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-rotie-brown hover:bg-rotie-caramel text-white text-xs font-semibold shadow transition-all btn-shimmer">
                    Simpan Produk Baru
                </button>
            </div>
        </form>
    </div>
</div>

