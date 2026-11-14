<?php
/**
 * ====================================================================
 * SUB-MODUL: Katalog Interaktif Real-time via Fetch API AJAX
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-bold text-rotie-brown">Live Katalog Produk (Fetch API Asinkron)</h1>
            <p class="text-xs text-rotie-brown/70 font-light mt-0.5">
                Pencarian dan pemfilteran katalog secara asinkron tanpa memuat ulang halaman (*Zero Page Reload*).
            </p>
        </div>

        <a href="admin.php?mod=produk&act=list" class="text-xs font-semibold text-rotie-caramel hover:underline">
            &larr; Kembali ke Tabel Produk
        </a>
    </div>

    <!-- Live Search Toolbar -->
    <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-8 relative">
                <input type="text" id="live-search-input" 
                       placeholder="Ketik nama roti (live fetch dengan debouncing)..."
                       class="w-full px-4 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-2 focus:ring-rotie-caramel">
            </div>
            <div class="sm:col-span-4">
                <select id="live-sort-input" class="w-full px-3 py-2.5 bg-white border border-rotie-beige rounded-xl text-xs text-rotie-brown focus:outline-none focus:ring-2 focus:ring-rotie-caramel">
                    <option value="terbaru">Terbaru</option>
                    <option value="populer">Paling Populer</option>
                    <option value="termurah">Harga Termurah</option>
                    <option value="termahal">Harga Termahal</option>
                </select>
            </div>
        </div>
    </div>

    <div id="live-loading" class="hidden text-center py-6 text-rotie-caramel text-xs font-semibold">
        <span>Memuat data via Fetch API...</span>
    </div>

    <!-- Grid Hasil Fetch API -->
    <div id="live-results-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Dimuat via JavaScript -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('live-search-input');
    const sortInput = document.getElementById('live-sort-input');
    let timer = null;

    async function doLiveSearch() {
        const q = searchInput.value.trim();
        const sort = sortInput.value;
        const grid = document.getElementById('live-results-grid');
        const loading = document.getElementById('live-loading');

        loading.classList.remove('hidden');

        try {
            const res = await fetch(`api/cari_produk.php?q=${encodeURIComponent(q)}&sort=${sort}`);
            const data = await res.json();

            loading.classList.add('hidden');

            if (!data.status || data.data.length === 0) {
                grid.innerHTML = '<div class="col-span-3 text-center py-12 text-gray-500 text-xs">Tidak ada produk ditemukan.</div>';
                return;
            }

            grid.innerHTML = data.data.map(p => `
                <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-4 shadow-sm flex flex-col justify-between">
                    <div>
                        <img src="${p.gambar}" alt="${p.nama_produk}" class="w-full h-40 object-cover rounded-xl mb-3 bg-amber-50">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-rotie-olive">${p.kategori}</span>
                        <h4 class="font-serif font-bold text-sm text-rotie-brown mt-0.5">${p.nama_produk}</h4>
                        <p class="text-[11px] text-rotie-brown/70 line-clamp-2 mt-1">${p.deskripsi}</p>
                    </div>
                    <div class="pt-3 mt-3 border-t border-rotie-beige flex items-center justify-between">
                        <span class="font-bold text-xs text-rotie-caramel">${p.harga_final_rp}</span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full ${p.stok > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}">
                            Stok: ${p.stok}
                        </span>
                    </div>
                </div>
            `).join('');

        } catch (e) {
            loading.classList.add('hidden');
            grid.innerHTML = '<div class="col-span-3 text-center py-12 text-rose-500 text-xs">Gagal mengambil data dari API.</div>';
        }
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(doLiveSearch, 250);
    });

    sortInput.addEventListener('change', doLiveSearch);
    doLiveSearch();
});
</script>

