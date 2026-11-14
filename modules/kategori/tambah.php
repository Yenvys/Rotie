<?php
/**
 * ====================================================================
 * SUB-MODUL: Formulir Tambah Kategori Baru
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();
$error = '';
$form = ['nama_kategori' => '', 'deskripsi' => '', 'urutan' => '1'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($csrfToken)) {
        $error = 'Validasi keamanan CSRF Token gagal.';
    } else {
        $namaKat   = trim($_POST['nama_kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $urutan    = (int) ($_POST['urutan'] ?? 0);

        $form['nama_kategori'] = $namaKat;
        $form['deskripsi'] = $deskripsi;
        $form['urutan'] = $urutan;

        if (empty($namaKat)) {
            $error = 'Nama kategori wajib diisi.';
        } else {
            try {
                $slug = slugify($namaKat);

                // Cek keunikan slug
                $cek = $pdo->prepare("SELECT id FROM categories WHERE slug = :s LIMIT 1");
                $cek->execute([':s' => $slug]);
                if ($cek->fetch()) {
                    $slug .= '-' . time();
                }

                $ins = $pdo->prepare("
                    INSERT INTO categories (nama_kategori, slug, deskripsi, urutan, created_at)
                    VALUES (:nama, :slug, :desk, :urutan, NOW())
                ");
                $ins->execute([
                    ':nama'   => $namaKat,
                    ':slug'   => $slug,
                    ':desk'   => !empty($deskripsi) ? $deskripsi : null,
                    ':urutan' => $urutan
                ]);

                set_flash('success', "Kategori '{$namaKat}' berhasil ditambahkan!");
                redirect('admin.php?mod=kategori&act=list');

            } catch (PDOException $e) {
                error_log("Tambah kategori error: " . $e->getMessage());
                $error = 'Gagal menyimpan kategori baru.';
            }
        }
    }
}
?>

<div class="max-w-xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-rotie-brown">Tambah Kategori Baru</h1>
            <p class="text-xs text-rotie-brown/70 font-light mt-0.5">Klasifikasi menu untuk navigasi filter landing page.</p>
        </div>
        <a href="admin.php?mod=kategori&act=list" class="text-xs font-semibold text-rotie-caramel hover:underline">
            &larr; Kembali
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="p-3.5 rounded-xl bg-rose-50 text-rose-800 border border-rose-200 text-xs">
            <span>⚠️ <?= e($error); ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-3xl p-6 sm:p-8 shadow-sm">
        <form action="admin.php?mod=kategori&act=tambah" method="POST" class="space-y-4">
            <?= csrf_field(); ?>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Nama Kategori *</label>
                <input type="text" name="nama_kategori" value="<?= e($form['nama_kategori']); ?>" required
                       placeholder="Contoh: Viennoiserie"
                       class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Deskripsi Singkat</label>
                <textarea name="deskripsi" rows="3" placeholder="Penjelasan ragam roti pada kategori ini..."
                          class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel"><?= e($form['deskripsi']); ?></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Urutan Tampilan</label>
                <input type="number" name="urutan" value="<?= (int)$form['urutan']; ?>" required min="0"
                       class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
                <span class="text-[10px] text-gray-500 mt-1 block">Angka lebih kecil akan ditampilkan lebih dahulu pada tab katalog.</span>
            </div>

            <div class="pt-4 border-t border-rotie-beige flex items-center justify-end gap-3">
                <a href="admin.php?mod=kategori&act=list" class="px-4 py-2 rounded-xl border border-rotie-beige text-xs font-semibold text-rotie-brown hover:bg-rotie-cream">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-rotie-brown hover:bg-rotie-caramel text-white text-xs font-semibold shadow transition-all btn-shimmer">
                    Simpan Kategori
                </button>
            </div>
        </form>
    </div>
</div>

