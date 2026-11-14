/**
 * ====================================================================
 * JAVASCRIPT: Live Search & AJAX Filter Katalog Produk (Fetch API)
 * ROTIÉ — Artisan Bakery
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

let currentCategory = 'all';
let currentSort = 'terbaru';
let searchDebounceTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    initCatalogEvents();
});

/**
 * Inisialisasi Event Listener Katalog Produk
 */
function initCatalogEvents() {
    const searchInput = document.getElementById('catalog-search');
    const sortSelect = document.getElementById('catalog-sort');
    const categoryButtons = document.querySelectorAll('.category-filter-btn');

    // 1. Live Search dengan Debounce 300ms
    searchInput?.addEventListener('input', (e) => {
        clearTimeout(searchDebounceTimer);
        const query = e.target.value.trim();
        searchDebounceTimer = setTimeout(() => {
            fetchProducts(query, currentCategory, currentSort);
        }, 300);
    });

    // 2. Filter Kategori Tabs
    categoryButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const cat = btn.getAttribute('data-category') || 'all';
            currentCategory = cat;

            // Update UI Active Tab
            categoryButtons.forEach(b => {
                b.classList.remove('bg-rotie-brown', 'text-white', 'shadow-md');
                b.classList.add('bg-white', 'text-rotie-brown', 'hover:bg-rotie-beige/50');
            });
            btn.classList.add('bg-rotie-brown', 'text-white', 'shadow-md');
            btn.classList.remove('bg-white', 'hover:bg-rotie-beige/50');

            const query = searchInput?.value.trim() || '';
            fetchProducts(query, currentCategory, currentSort);
        });
    });

    // 3. Sorting Dropdown
    sortSelect?.addEventListener('change', (e) => {
        currentSort = e.target.value;
        const query = searchInput?.value.trim() || '';
        fetchProducts(query, currentCategory, currentSort);
    });
}

/**
 * Fetch Data Produk secara Asinkron
 */
async function fetchProducts(query = '', category = 'all', sort = 'terbaru', promo = '') {
    const container = document.getElementById('products-grid-container');
    const loadingEl = document.getElementById('catalog-loading');
    const emptyEl = document.getElementById('catalog-empty');

    if (!container) return;

    // Tampilkan Loading State
    loadingEl?.classList.remove('hidden');
    container.classList.add('opacity-40');
    emptyEl?.classList.add('hidden');

    try {
        const params = new URLSearchParams({
            q: query,
            kategori: category,
            sort: sort
        });
        if (promo) params.append('promo', promo);

        const res = await fetch(`api/cari_produk.php?${params.toString()}`);
        const result = await res.json();

        loadingEl?.classList.add('hidden');
        container.classList.remove('opacity-40');

        if (!result.status || result.data.length === 0) {
            container.innerHTML = '';
            emptyEl?.classList.remove('hidden');
            return;
        }

        renderProductGrid(result.data, container);

    } catch (err) {
        console.error('Fetch products error:', err);
        loadingEl?.classList.add('hidden');
        container.classList.remove('opacity-40');
    }
}

/**
 * Render Kartu Produk ke DOM
 */
function renderProductGrid(products, container) {
    container.innerHTML = '';

    products.forEach((p, index) => {
        const isOutOfStock = p.stok <= 0;
        
        let promoBadge = '';
        if (p.is_promo && p.diskon_persen > 0) {
            promoBadge = `
                <div class="absolute top-3 left-3 z-10 px-2.5 py-1 rounded-full bg-rose-600 text-white text-[11px] font-bold tracking-wider uppercase shadow-md flex items-center gap-1">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.316.492-.633.984-.95 1.477a1.002 1.002 0 01-1.39.297c-.347-.23-.615-.558-.823-.88-.316-.492-.633-.984-.95-1.477a1 1 0 00-1.45-.385 1 1 0 00-.385 1.45c.23.346.558.614.88.822.492.316.984.633 1.477.95a1.002 1.002 0 01.297 1.39c-.23.347-.558.615-.88.823-.492.316-.984.633-1.477.95a1 1 0 00.767 1.835c.346-.23.614-.558.822-.88.316-.492.633-.984.95-1.477a1.002 1.002 0 011.39-.297c.347.23.615.558.823.88.316.492.633.984.95 1.477a1 1 0 001.45.385 1 1 0 00.385-1.45c-.23-.346-.558-.614-.88-.822-.492-.316-.984-.633-1.477-.95a1.002 1.002 0 01-.297-1.39c.23-.347.558-.615.88-.823.492-.316.984-.633 1.477-.95a1 1 0 00-.767-1.835z" clip-rule="evenodd" />
                    </svg>
                    <span>Diskon ${parseInt(p.diskon_persen)}%</span>
                </div>
            `;
        }

        let priceHtml = '';
        if (p.is_promo && p.diskon_persen > 0) {
            priceHtml = `
                <div class="flex items-baseline gap-2">
                    <span class="text-lg font-bold font-serif text-rotie-caramel">${p.harga_final_rp}</span>
                    <span class="text-xs text-gray-400 line-through">${p.harga_asli_rp}</span>
                </div>
            `;
        } else {
            priceHtml = `
                <div class="text-lg font-bold font-serif text-rotie-brown">${p.harga_final_rp}</div>
            `;
        }

        const card = document.createElement('div');
        card.className = 'product-card group bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-xl hover:border-rotie-caramel/40 transition-all duration-300 flex flex-col justify-between';
        
        card.innerHTML = `
            <div>
                <!-- Image Wrapper with Morph Origin Reference (Tinggi Proporsional Widescreen) -->
                <div class="image-reveal-wrapper relative w-full h-60 sm:h-64 rounded-xl overflow-hidden mb-4 bg-amber-50 cursor-pointer" onclick="openProductDetail(${p.id}, this)">
                    ${promoBadge}
                    <img src="${p.gambar}" alt="${p.nama_produk}" loading="lazy"
                         class="product-thumb-img w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    
                    <!-- Quick View Overlay -->
                    <div class="absolute inset-0 bg-rotie-chocolate/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <span class="px-4 py-2 rounded-full bg-white/95 text-rotie-brown text-xs font-semibold shadow-md transform translate-y-2 group-hover:translate-y-0 transition-transform">
                            Lihat Rincian
                        </span>
                    </div>
                </div>

                <!-- Category & Stock Status -->
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <span class="text-[11px] uppercase tracking-wider font-semibold text-rotie-olive">
                        ${p.kategori}
                    </span>
                    ${isOutOfStock 
                        ? `<span class="text-[11px] font-medium text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">Habis</span>`
                        : `<span class="text-[11px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">Stok: ${p.stok}</span>`
                    }
                </div>

                <!-- Product Name -->
                <h3 class="font-serif font-bold text-base text-rotie-brown group-hover:text-rotie-caramel transition-colors line-clamp-1 mb-1.5 cursor-pointer" onclick="openProductDetail(${p.id}, this)">
                    ${p.nama_produk}
                </h3>

                <!-- Product Excerpt -->
                <p class="text-xs text-rotie-brown/70 line-clamp-2 leading-relaxed mb-4">
                    ${p.deskripsi}
                </p>
            </div>

            <!-- Price & Add to Cart Action -->
            <div class="pt-3 border-t border-rotie-beige/60 flex items-center justify-between gap-2">
                ${priceHtml}
                <button type="button"
                    ${isOutOfStock ? 'disabled' : ''}
                    onclick="addToCart(${p.id}, 1, ${JSON.stringify(p).replace(/"/g, '&quot;')})"
                    class="p-2.5 rounded-xl ${isOutOfStock ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-rotie-brown hover:bg-rotie-caramel text-white shadow-sm hover:shadow active:scale-95'} transition-all duration-200 flex items-center justify-center"
                    title="${isOutOfStock ? 'Stok Habis' : 'Tambah ke Keranjang'}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
            </div>
        `;

        container.appendChild(card);
    });
}

/**
 * Reset Filter ke Kondisi Semula
 */
function resetCatalogFilters() {
    const searchInput = document.getElementById('catalog-search');
    if (searchInput) searchInput.value = '';
    currentCategory = 'all';
    currentSort = 'terbaru';

    const categoryButtons = document.querySelectorAll('.category-filter-btn');
    categoryButtons.forEach(b => {
        if (b.getAttribute('data-category') === 'all') {
            b.classList.add('bg-rotie-brown', 'text-white', 'shadow-md');
            b.classList.remove('bg-white', 'hover:bg-rotie-beige/50');
        } else {
            b.classList.remove('bg-rotie-brown', 'text-white', 'shadow-md');
            b.classList.add('bg-white', 'text-rotie-brown', 'hover:bg-rotie-beige/50');
        }
    });

    fetchProducts('', 'all', 'terbaru');
}

window.fetchProducts = fetchProducts;
window.resetCatalogFilters = resetCatalogFilters;

