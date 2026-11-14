<?php
/**
 * ====================================================================
 * SUB-MODUL & ENDPOINT: Pipeline Impor Massal Berkas CSV
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();
$error = '';
$importResults = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($csrfToken)) {
        $error = 'Validasi keamanan CSRF Token gagal.';
    } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Silakan pilih berkas CSV yang valid untuk diimpor.';
    } else {
        $tmpFile = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            $error = 'Ekstensi berkas harus berformat .csv.';
        } else {
            $handle = fopen($tmpFile, 'r');
            if ($handle !== false) {
                // Lewati BOM UTF-8 jika ada
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    rewind($handle);
                }

                // Baca baris header
                $header = fgetcsv($handle, 2000, ',');
                
                $totalSuccess = 0;
                $totalSkipped = 0;
                $logs = [];

                $pdo->beginTransaction();

                try {
                    $rowNum = 1;
                    while (($row = fgetcsv($handle, 2000, ',')) !== false) {
                        $rowNum++;
                        // Minimal butuh 4 kolom: Nama, Kategori, Harga, Stok
                        if (count($row) < 4) {
                            $totalSkipped++;
                            $logs[] = "Baris {$rowNum}: Kolom tidak lengkap, dilewati.";
                            continue;
                        }

                        $namaProduk = trim($row[0]);
                        $namaKat    = trim($row[1]);
                        $harga      = (float) preg_replace('/[^0-9.]/', '', $row[2]);
                        $stok       = (int) preg_replace('/[^0-9]/', '', $row[3]);
                        $deskripsi  = isset($row[4]) && !empty(trim($row[4])) ? trim($row[4]) : "Varian lezat {$namaProduk} dari dapur artisan ROTIÉ.";

                        if (empty($namaProduk) || empty($namaKat) || $harga <= 0) {
                            $totalSkipped++;
                            $logs[] = "Baris {$rowNum}: Data '{$namaProduk}' tidak valid, dilewati.";
                            continue;
                        }

                        // 1. Cari atau Buat Kategori
                        $stmtKat = $pdo->prepare("SELECT id FROM categories WHERE nama_kategori = :nk LIMIT 1");
                        $stmtKat->execute([':nk' => $namaKat]);
                        $cat = $stmtKat->fetch();

                        if ($cat) {
                            $catId = (int) $cat['id'];
                        } else {
                            $slugKat = slugify($namaKat);
                            $insKat = $pdo->prepare("INSERT INTO categories (nama_kategori, slug, created_at) VALUES (:nk, :slug, NOW())");
                            $insKat->execute([':nk' => $namaKat, ':slug' => $slugKat]);
                            $catId = (int) $pdo->lastInsertId();
                        }

                        // 2. Buat Slug Produk Unik
                        $slugProd = slugify($namaProduk);
                        $stmtCekSlug = $pdo->prepare("SELECT id FROM products WHERE slug = :slug LIMIT 1");
                        $stmtCekSlug->execute([':slug' => $slugProd]);
                        if ($stmtCekSlug->fetch()) {
                            $slugProd .= '-' . time() . '-' . rand(10, 99);
                        }

                        $defaultImg = 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80';

                        // 3. Masukkan Produk
                        $insProd = $pdo->prepare("
                            INSERT INTO products (category_id, nama_produk, slug, deskripsi, harga, stok, stok_minimum, gambar, status, created_at)
                            VALUES (:cid, :nama, :slug, :desk, :harga, :stok, 5, :img, 'tersedia', NOW())
                        ");
                        $insProd->execute([
                            ':cid'   => $catId,
                            ':nama'  => $namaProduk,
                            ':slug'  => $slugProd,
                            ':desk'  => $deskripsi,
                            ':harga' => $harga,
                            ':stok'  => $stok,
                            ':img'   => $defaultImg
                        ]);
                        $newId = (int) $pdo->lastInsertId();

                        // 4. Catat mutasi awal stok
                        if ($stok > 0) {
                            $insMv = $pdo->prepare("
                                INSERT INTO stock_movements (product_id, tipe, jumlah, stok_sebelum, stok_sesudah, keterangan, created_at)
                                VALUES (:pid, 'masuk', :jml, 0, :sesudah, 'Impor massal data dari file CSV', NOW())
                            ");
                            $insMv->execute([':pid' => $newId, ':jml' => $stok, ':sesudah' => $stok]);
                        }

                        $totalSuccess++;
                    }

                    $pdo->commit();
                    fclose($handle);

                    $importResults = [
                        'success' => $totalSuccess,
                        'skipped' => $totalSkipped,
                        'logs'    => $logs
                    ];

                    set_flash('success', "Proses impor selesai! {$totalSuccess} produk berhasil ditambahkan, {$totalSkipped} dilewati.");

                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    fclose($handle);
                    error_log("Impor CSV error: " . $e->getMessage());
                    $error = 'Terjadi kesalahan sistem saat memproses impor CSV: ' . $e->getMessage();
                }
            } else {
                $error = 'Gagal membuka berkas CSV yang diunggah.';
            }
        }
    }
}
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-rotie-brown">Pipeline Impor Massal CSV</h1>
            <p class="text-xs text-rotie-brown/70 font-light mt-0.5">Unggah data katalog produk dalam jumlah banyak sekaligus via berkas CSV.</p>
        </div>
        <a href="admin.php?mod=produk&act=list" class="text-xs font-semibold text-rotie-caramel hover:underline">
            &larr; Kembali ke Daftar Produk
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="p-3.5 rounded-xl bg-rose-50 text-rose-800 border border-rose-200 text-xs flex items-center gap-2">
            <span>⚠️ <?= e($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Format Petunjuk CSV -->
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 text-xs text-amber-900 space-y-2">
        <h4 class="font-bold text-amber-950 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-rotie-caramel" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <span>Format Struktur Kolom Berkas CSV:</span>
        </h4>
        <p>Pastikan baris pertama (header) berurutan sebagai berikut:</p>
        <div class="bg-white/70 p-2.5 rounded-xl font-mono text-[11px] text-rotie-brown border border-amber-200/60 overflow-x-auto">
            Nama Produk, Kategori, Harga, Stok, Deskripsi
        </div>
        <p class="text-[11px] text-amber-800">
            Contoh baris data: <code class="font-bold">Matcha Croissant, Croissant, 24000, 20, Croissant butter vla matcha京都</code>
        </p>
    </div>

    <!-- Form Unggah CSV -->
    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-3xl p-6 sm:p-8 shadow-sm">
        <form action="admin.php?mod=produk&act=import" method="POST" enctype="multipart/form-data" class="space-y-6">
            <?= csrf_field(); ?>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-2">
                    Pilih Berkas CSV (.csv) *
                </label>
                <input type="file" name="csv_file" accept=".csv" required
                       class="w-full px-4 py-3 bg-white border border-rotie-beige rounded-2xl text-xs text-rotie-brown file:mr-4 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-rotie-brown file:text-white hover:file:bg-rotie-caramel cursor-pointer">
            </div>

            <div class="pt-4 border-t border-rotie-beige flex items-center justify-end gap-3">
                <a href="admin.php?mod=produk&act=list" class="px-4 py-2.5 rounded-xl border border-rotie-beige text-xs font-semibold text-rotie-brown hover:bg-rotie-cream">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-rotie-brown hover:bg-rotie-caramel text-white text-xs font-semibold shadow transition-all btn-shimmer">
                    Mulai Impor Produk
                </button>
            </div>
        </form>
    </div>

    <?php if ($importResults): ?>
        <div class="bg-rotie-warm border border-rotie-beige rounded-3xl p-6 shadow-sm space-y-3 text-xs">
            <h4 class="font-bold text-sm text-rotie-brown">Rincian Hasil Impor:</h4>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-3 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200">
                    <span class="block text-[10px] uppercase font-bold text-emerald-700">Berhasil Diimpor</span>
                    <span class="font-bold text-lg font-serif"><?= $importResults['success']; ?> Produk</span>
                </div>
                <div class="p-3 bg-amber-50 text-amber-800 rounded-xl border border-amber-200">
                    <span class="block text-[10px] uppercase font-bold text-amber-700">Dilewati</span>
                    <span class="font-bold text-lg font-serif"><?= $importResults['skipped']; ?> Baris</span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

