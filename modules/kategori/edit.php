<?php
/**
 * ====================================================================
 * SUB-MODUL: Formulir Ubah (Edit) Kategori
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

$pdo = getDBConnection();
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('error', 'ID kategori tidak valid.');
    redirect('admin.php?mod=kategori&act=list');
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$category = $stmt->fetch();

if (!$category) {
    set_flash('error', 'Kategori tidak ditemukan.');
    redirect('admin.php?mod=kategori&act=list');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($csrfToken)) {
        $error = 'Validasi keamanan CSRF Token gagal.';
    } else {
        $namaKat   = trim($_POST['nama_kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $urutan    = (int) ($_POST['urutan'] ?? 0);

        if (empty($namaKat)) {
            $error = 'Nama kategori wajib diisi.';
        } else {
            try {
                $upd = $pdo->prepare("
                    UPDATE categories SET
                        nama_kategori = :nama,
                        deskripsi = :desk,
                        urutan = :urutan
                    WHERE id = :id
                ");
                $upd->execute([
                    ':nama'   => $namaKat,
                    ':desk'   => !empty($deskripsi) ? $deskripsi : null,
                    ':urutan' => $urutan,
                    ':id'     => $id
                ]);

                set_flash('success', "Kategori '{$namaKat}' berhasil diperbarui!");
                redirect('admin.php?mod=kategori&act=list');

            } catch (PDOException $e) {
                error_log("Edit kategori error: " . $e->getMessage());
                $error = 'Gagal menyimpan perubahan kategori.';
            }
        }
    }
}
?>

<div class="max-w-xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-rotie-brown">Edit Kategori</h1>
            <p class="text-xs text-rotie-brown/70 font-light mt-0.5">Perbarui nama atau urutan tata letak tab kategori.</p>
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
        <form action="admin.php?mod=kategori&act=edit&id=<?= $category['id']; ?>" method="POST" class="space-y-4">
            <?= csrf_field(); ?>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Nama Kategori *</label>
                <input type="text" name="nama_kategori" value="<?= e($category['nama_kategori']); ?>" required
                       class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Deskripsi Singkat</label>
                <textarea name="deskripsi" rows="3"
                          class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel"><?= e($category['deskripsi'] ?? ''); ?></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-rotie-brown mb-1.5">Urutan Tampilan</label>
                <input type="number" name="urutan" value="<?= (int)$category['urutan']; ?>" required min="0"
                       class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-1 focus:ring-rotie-caramel">
            </div>

            <div class="pt-4 border-t border-rotie-beige flex items-center justify-end gap-3">
                <a href="admin.php?mod=kategori&act=list" class="px-4 py-2 rounded-xl border border-rotie-beige text-xs font-semibold text-rotie-brown hover:bg-rotie-cream">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-rotie-brown hover:bg-rotie-caramel text-white text-xs font-semibold shadow transition-all btn-shimmer">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

