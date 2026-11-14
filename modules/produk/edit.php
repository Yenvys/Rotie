<?php
/**
 * ====================================================================
 * SUB-MODUL: Formulir Ubah (Edit) Data Produk
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('error', 'ID produk tidak valid.');
    redirect('admin.php?mod=produk&act=list');
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('error', 'Produk tidak ditemukan.');
    redirect('admin.php?mod=produk&act=list');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY urutan ASC")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf($csrfToken)) {
        $error = 'Validasi keamanan CSRF Token gagal. Silakan muat ulang halaman.';
    } else {
        $namaProduk   = trim($_POST['nama_produk'] ?? '');
        $categoryId   = (int) ($_POST['category_id'] ?? 0);
        $deskripsi    = trim($_POST['deskripsi'] ?? '');
        $harga        = (float) ($_POST['harga'] ?? 0);
        $stokBaru     = (int) ($_POST['stok'] ?? 0);
        $stokMin      = (int) ($_POST['stok_minimum'] ?? 5);
        $gambarUrl    = trim($_POST['gambar_url'] ?? '');
        $isFeatured   = isset($_POST['is_featured']) ? 1 : 0;
        $isPromo      = isset($_POST['is_promo']) ? 1 : 0;
        $diskon       = (float) ($_POST['diskon_persen'] ?? 0);
        $status       = in_array($_POST['status'] ?? '', ['tersedia', 'habis', 'arsip']) ? $_POST['status'] : 'tersedia';

        if (empty($namaProduk) || $categoryId <= 0 || empty($deskripsi) || $harga <= 0) {
            $error = 'Bidang nama produk, kategori, deskripsi, dan harga wajib diisi.';
        } else {
            $gambarPath = !empty($gambarUrl) ? $gambarUrl : $product['gambar'];

            // Unggahan Foto Baru
            if (isset($_FILES['gambar_file']) && $_FILES['gambar_file']['error'] === UPLOAD_ERR_OK) {
                $fileTmp  = $_FILES['gambar_file']['tmp_name'];
                $fileName = $_FILES['gambar_file']['name'];
                $fileSize = $_FILES['gambar_file']['size'];
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
                $maxBytes = 2 * 1024 * 1024;

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
                    if (move_uploaded_file($fileTmp, $uploadDir . $newFileName)) {
                        $gambarPath = 'uploads/products/' . $newFileName;
                    }
                }
            }

            if (empty($error)) {
                try {
                    $pdo->beginTransaction();

                    $stokLama = (int) $product['stok'];

                    // Update Data Produk
                    $upd = $pdo->prepare("
                        UPDATE products SET
                            category_id = :cid,
                            nama_produk = :nama,
                            deskripsi = :desk,
                            harga = :harga,
                            stok = :stok,
                            stok_minimum = :stok_min,
                            gambar = :gambar,
                            is_featured = :feat,
                            is_promo = :promo,
                            diskon_persen = :diskon,
                            status = :status,
                            updated_at = NOW()
                        WHERE id = :id
                    ");
                    $upd->execute([
                        ':cid'       => $categoryId,
                        ':nama'      => $namaProduk,
                        ':desk'      => $deskripsi,
                        ':harga'     => $harga,
                        ':stok'      => $stokBaru,
                        ':stok_min'  => $stokMin,
                        ':gambar'    => $gambarPath,
                        ':feat'      => $isFeatured,
                        ':promo'     => $isPromo,
                        ':diskon'    => $diskon,
                        ':status'    => $status,
                        ':id'        => $id
                    ]);

                    // Jika ada perbedaan stok, catat di stock_movements
                    if ($stokBaru !== $stokLama) {
                        $selisih = $stokBaru - $stokLama;
                        $tipeMv = $selisih > 0 ? 'masuk' : 'keluar';
                        $insMv = $pdo->prepare("
                            INSERT INTO stock_movements (product_id, tipe, jumlah, stok_sebelum, stok_sesudah, keterangan, created_at)
                            VALUES (:pid, :tipe, :qty, :seb, :ses, 'Penyesuaian stok manual via edit produk', NOW())
                        ");
                        $insMv->execute([
                            ':pid'  => $id,
                            ':tipe' => 'penyesuaian',
                            ':qty'  => abs($selisih),
                            ':seb'  => $stokLama,
                            ':ses'  => $stokBaru
                        ]);
                    }

                    $pdo->commit();

                    set_flash('success', "Perubahan data produk '{$namaProduk}' berhasil disimpan!");
                    redirect('admin.php?mod=produk&act=list');

                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    error_log("Edit produk error: " . $e->getMessage());
                    $error = 'Terjadi kesalahan sistem saat menyimpan perubahan produk.';
                }
            }
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-rotie-brown">Edit Data Produk Roti</h1>
            <p class="text-xs text-rotie-brown/70 font-light mt-0.5">Perbarui informasi, harga, stok, atau unggah foto baru.</p>
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
        <form action="admin.php?mod=produk&act=edit&id=<?= $product['id']; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
            <?= csrf_field(); ?>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-5">
                
                <div class="sm:col-span-8">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Nama Produk Roti *</label>
                    <input type="text" name="nama_produk" value="<?= e($product['nama_produk']); ?>" required
                           class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Kategori *</label>
                    <select name="category_id" required class="w-full px-3 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id']; ?>" <?= (int)$product['category_id'] === (int)$c['id'] ? 'selected' : ''; ?>>
                                <?= e($c['nama_kategori']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sm:col-span-12">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Deskripsi Produk *</label>
                    <textarea name="deskripsi" rows="3" required
                              class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel"><?= e($product['deskripsi']); ?></textarea>
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Harga Satuan (Rp) *</label>
                    <input type="number" step="500" name="harga" value="<?= (float)$product['harga']; ?>" required
                           class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Stok Fisik Saat Ini *</label>
                    <input type="number" name="stok" value="<?= (int)$product['stok']; ?>" required min="0"
                           class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Batas Stok Minimum *</label>
                    <input type="number" name="stok_minimum" value="<?= (int)$product['stok_minimum']; ?>" required min="1"
                           class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                </div>

                <div class="sm:col-span-3">
                    <span class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Foto Saat Ini</span>
                    <img src="<?= e($product['gambar']); ?>" alt="Foto Produk" class="w-24 h-24 object-cover rounded-xl border border-rotie-beige bg-amber-50">
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Ganti Berkas Foto</label>
                    <input type="file" name="gambar_file" accept=".jpg,.jpeg,.png,.webp"
                           class="w-full px-3.5 py-2 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-rotie-cream file:text-rotie-brown hover:file:bg-rotie-beige">
                </div>

                <div class="sm:col-span-5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Atau Masukkan URL Gambar Baru</label>
                    <input type="url" name="gambar_url" value="<?= filter_var($product['gambar'], FILTER_VALIDATE_URL) ? e($product['gambar']) : ''; ?>"
                           placeholder="https://..."
                           class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Status Ketersediaan</label>
                    <select name="status" class="w-full px-3 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                        <option value="tersedia" <?= $product['status'] === 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                        <option value="habis" <?= $product['status'] === 'habis' ? 'selected' : ''; ?>>Habis</option>
                        <option value="arsip" <?= $product['status'] === 'arsip' ? 'selected' : ''; ?>>Diarsipkan</option>
                    </select>
                </div>

                <div class="sm:col-span-4 flex items-center pt-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : ''; ?>
                               class="w-4 h-4 rounded text-rotie-caramel focus:ring-rotie-caramel">
                        <span class="text-xs font-semibold text-rotie-brown">Tandai sebagai Unggulan</span>
                    </label>
                </div>

                <div class="sm:col-span-4 flex items-center pt-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_promo" value="1" <?= $product['is_promo'] ? 'checked' : ''; ?>
                               class="w-4 h-4 rounded text-rotie-caramel focus:ring-rotie-caramel">
                        <span class="text-xs font-semibold text-rotie-brown">Promo Diskon Khusus</span>
                    </label>
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Besaran Diskon (%)</label>
                    <input type="number" step="1" min="0" max="90" name="diskon_persen" value="<?= (float)$product['diskon_persen']; ?>"
                           class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                </div>

            </div>

            <div class="pt-4 border-t border-rotie-beige flex items-center justify-end gap-3">
                <a href="admin.php?mod=produk&act=list" class="px-4 py-2.5 rounded-xl border border-rotie-beige text-xs font-semibold text-rotie-brown hover:bg-rotie-cream">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-rotie-brown hover:bg-rotie-caramel text-white text-xs font-semibold shadow transition-all btn-shimmer">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

