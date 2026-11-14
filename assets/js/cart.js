/**
 * ====================================================================
 * JAVASCRIPT: Shopping Cart Dinamis & Checkout WhatsApp Dispatcher
 * ROTIÉ — Artisan Bakery
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

const CART_STORAGE_KEY = 'rotie_artisan_cart';

// State Keranjang Belanja
let cartItems = [];

document.addEventListener('DOMContentLoaded', () => {
    loadCartFromStorage();
    initCartDOMEvents();
});

/**
 * Muat data keranjang dari LocalStorage (dengan sanitasi harga anti-NaN)
 */
function loadCartFromStorage() {
    try {
        const saved = localStorage.getItem(CART_STORAGE_KEY);
        if (saved) {
            const parsed = JSON.parse(saved);
            if (Array.isArray(parsed)) {
                cartItems = parsed.map(item => {
                    let harga = parseFloat(item.harga);
                    if (isNaN(harga) || harga <= 0) {
                        harga = parseFloat(item.harga_final) || parseFloat(item.harga_asli) || 0;
                    }
                    return {
                        id: parseInt(item.id) || 0,
                        nama_produk: item.nama_produk || 'Produk ROTIÉ',
                        harga: harga,
                        harga_rp: formatRupiahJs(harga),
                        gambar: item.gambar || '',
                        kategori: item.kategori || '',
                        jumlah: Math.max(1, parseInt(item.jumlah) || 1)
                    };
                }).filter(item => item.id > 0 && item.harga > 0);
            }
        }
    } catch (e) {
        cartItems = [];
    }
    updateCartUI();
}

/**
 * Simpan data keranjang ke LocalStorage
 */
function saveCartToStorage() {
    try {
        localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cartItems));
    } catch (e) {
        console.error('Gagal menyimpan keranjang ke LocalStorage:', e);
    }
    updateCartUI();
}

/**
 * Tambah Produk ke Keranjang (Mendukung harga, harga_final, dan harga_asli)
 */
function addToCart(productId, qty = 1, product = null) {
    productId = parseInt(productId);
    qty = Math.max(1, parseInt(qty) || 1);

    const existingIndex = cartItems.findIndex(item => item.id === productId);

    let price = 0;
    if (product) {
        price = parseFloat(product.harga_final ?? product.harga ?? product.harga_asli ?? 0);
        if (isNaN(price) || price < 0) price = 0;
    }

    if (existingIndex > -1) {
        cartItems[existingIndex].jumlah += qty;
        // Jika sebelumnya harganya NaN/0, otomatis perbaiki dengan price yang valid
        if (price > 0 && (!cartItems[existingIndex].harga || isNaN(cartItems[existingIndex].harga) || cartItems[existingIndex].harga <= 0)) {
            cartItems[existingIndex].harga = price;
            cartItems[existingIndex].harga_rp = formatRupiahJs(price);
        }
    } else if (product) {
        cartItems.push({
            id: productId,
            nama_produk: product.nama_produk || 'Produk ROTIÉ',
            harga: price,
            harga_rp: formatRupiahJs(price),
            gambar: product.gambar || '',
            kategori: product.kategori || '',
            jumlah: qty
        });
    }

    saveCartToStorage();
    showToastNotification(`Ditambahkan ke keranjang!`);
    
    // Buka drawer keranjang secara otomatis untuk konfirmasi visual
    openCartDrawer();
}

/**
 * Ubah Kuantitas Item
 */
function updateQuantity(productId, delta) {
    productId = parseInt(productId);
    const index = cartItems.findIndex(item => item.id === productId);

    if (index > -1) {
        cartItems[index].jumlah += delta;
        if (cartItems[index].jumlah <= 0) {
            cartItems.splice(index, 1);
        }
        saveCartToStorage();
    }
}

/**
 * Hapus Item dari Keranjang
 */
function removeFromCart(productId) {
    productId = parseInt(productId);
    cartItems = cartItems.filter(item => item.id !== productId);
    saveCartToStorage();
    showToastNotification('Item dihapus dari keranjang.');
}

/**
 * Kosongkan Seluruh Keranjang
 */
function clearCart() {
    cartItems = [];
    saveCartToStorage();
}

/**
 * Perbarui Seluruh Komponen Antarmuka Keranjang (DOM Realtime)
 */
function updateCartUI() {
    const totalCount = cartItems.reduce((sum, item) => sum + (parseInt(item.jumlah) || 1), 0);
    const totalHarga = cartItems.reduce((sum, item) => sum + ((parseFloat(item.harga) || 0) * (parseInt(item.jumlah) || 1)), 0);

    // Update Badges di Navbar & Floating Action Button
    const badgeEls = document.querySelectorAll('.cart-count-badge');
    badgeEls.forEach(el => {
        el.textContent = totalCount;
        if (totalCount > 0) {
            el.classList.remove('hidden');
            el.classList.add('badge-pop');
            setTimeout(() => el.classList.remove('badge-pop'), 400);
        } else {
            el.classList.add('hidden');
        }
    });

    // Update Subtotal & Total
    const totalEls = document.querySelectorAll('.cart-total-price');
    const formattedTotal = formatRupiahJs(totalHarga);
    totalEls.forEach(el => el.textContent = formattedTotal);

    // Render Daftar Item di Drawer
    const listContainer = document.getElementById('cart-items-list');
    const emptyState = document.getElementById('cart-empty-state');
    const checkoutBtn = document.getElementById('cart-checkout-btn');

    if (!listContainer) return;

    if (cartItems.length === 0) {
        listContainer.innerHTML = '';
        emptyState?.classList.remove('hidden');
        if (checkoutBtn) checkoutBtn.disabled = true;
        return;
    }

    emptyState?.classList.add('hidden');
    if (checkoutBtn) checkoutBtn.disabled = false;

    listContainer.innerHTML = '';
    cartItems.forEach(item => {
        const itemHarga = parseFloat(item.harga) || 0;
        const itemJumlah = parseInt(item.jumlah) || 1;
        const subtotal = itemHarga * itemJumlah;
        const itemRow = document.createElement('div');
        itemRow.className = 'flex items-center gap-3 py-3 border-b border-rotie-beige/60';
        
        itemRow.innerHTML = `
            <img src="${item.gambar}" alt="${item.nama_produk}" class="w-14 h-14 object-cover rounded-xl border border-rotie-beige flex-shrink-0">
            <div class="flex-1 min-w-0">
                <h4 class="font-serif font-bold text-sm text-rotie-brown truncate">${item.nama_produk}</h4>
                <p class="text-xs text-rotie-caramel font-semibold">${formatRupiahJs(itemHarga)}</p>
                <div class="flex items-center gap-2 mt-1.5">
                    <button type="button" onclick="updateQuantity(${item.id}, -1)" class="w-6 h-6 rounded-lg bg-rotie-beige/70 hover:bg-rotie-beige text-rotie-brown flex items-center justify-center font-bold text-xs">-</button>
                    <span class="text-xs font-semibold px-1 text-rotie-brown">${itemJumlah}</span>
                    <button type="button" onclick="updateQuantity(${item.id}, 1)" class="w-6 h-6 rounded-lg bg-rotie-beige/70 hover:bg-rotie-beige text-rotie-brown flex items-center justify-center font-bold text-xs">+</button>
                </div>
            </div>
            <div class="text-right flex flex-col items-end justify-between self-stretch">
                <button type="button" onclick="removeFromCart(${item.id})" class="text-gray-400 hover:text-rose-600 transition-colors p-1" title="Hapus">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
                <span class="text-xs font-bold text-rotie-brown">${formatRupiahJs(subtotal)}</span>
            </div>
        `;
        listContainer.appendChild(itemRow);
    });
}

/**
 * Format Rupiah di Sisi Klien (Aman terhadap NaN & Nilai Invalid)
 */
function formatRupiahJs(num) {
    const val = parseFloat(num);
    if (isNaN(val) || !isFinite(val) || val < 0) {
        return 'Rp0';
    }
    return 'Rp' + Math.round(val).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

/**
 * Drawer & Modal Controls
 */
function openCartDrawer() {
    const drawer = document.getElementById('cart-drawer');
    const backdrop = document.getElementById('cart-backdrop');
    if (!drawer) return;
    drawer.classList.remove('translate-x-full');
    backdrop?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeCartDrawer() {
    const drawer = document.getElementById('cart-drawer');
    const backdrop = document.getElementById('cart-backdrop');
    if (!drawer) return;
    drawer.classList.add('translate-x-full');
    backdrop?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

/**
 * Buka Formulir Checkout Pemesanan
 */
function openCheckoutModal() {
    if (cartItems.length === 0) {
        alert('Keranjang belanja Anda masih kosong.');
        return;
    }
    updateCartUI();
    closeCartDrawer();
    const modal = document.getElementById('checkout-modal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
}

function closeCheckoutModal() {
    const modal = document.getElementById('checkout-modal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

/**
 * Inisialisasi Event Listener Formulir Pemesanan
 */
function initCartDOMEvents() {
    const checkoutForm = document.getElementById('checkout-order-form');
    if (!checkoutForm) return;

    checkoutForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitBtn = document.getElementById('btn-submit-order');
        const submitText = document.getElementById('btn-submit-order-text');
        const submitSpinner = document.getElementById('btn-submit-order-spinner');

        // Nonaktifkan tombol saat memproses
        if (submitBtn) submitBtn.disabled = true;
        submitText?.classList.add('opacity-0');
        submitSpinner?.classList.remove('hidden');

        const formData = new FormData(checkoutForm);
        const payload = {
            csrf_token: formData.get('csrf_token'),
            nama: formData.get('nama'),
            no_hp: formData.get('no_hp'),
            email: formData.get('email'),
            alamat: formData.get('alamat'),
            catatan: formData.get('catatan'),
            items: cartItems.map(i => ({ id: i.id, jumlah: i.jumlah }))
        };

        try {
            const res = await fetch('api/buat_pesanan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await res.json();

            if (result.status) {
                // Bersihkan keranjang
                clearCart();
                closeCheckoutModal();

                // Tampilkan Modal Sukses Pemesanan & Tautan WhatsApp
                showOrderSuccessModal(result);
            } else {
                alert('Gagal mengirim pesanan: ' + result.message);
            }
        } catch (err) {
            console.error('Checkout error:', err);
            alert('Terjadi kesalahan jaringan saat memproses pesanan Anda. Silakan coba kembali.');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
            submitText?.classList.remove('opacity-0');
            submitSpinner?.classList.add('hidden');
        }
    });
}

/**
 * Tampilkan Modal Sukses Pemesanan
 */
function showOrderSuccessModal(result) {
    const modal = document.getElementById('order-success-modal');
    const codeEl = document.getElementById('success-order-code');
    const totalEl = document.getElementById('success-order-total');
    const waBtn = document.getElementById('success-wa-btn');

    if (!modal) return;

    if (codeEl) codeEl.textContent = result.kode_pesanan;
    if (totalEl) totalEl.textContent = result.total_harga_rp;
    if (waBtn) {
        waBtn.href = result.whatsapp_url;
    }

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeOrderSuccessModal() {
    const modal = document.getElementById('order-success-modal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

/**
 * Toast Notification Sederhana
 */
function showToastNotification(message) {
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-6 right-6 z-[100] px-4 py-3 bg-rotie-chocolate text-white text-xs font-semibold rounded-xl shadow-2xl flex items-center gap-2 transform translate-y-6 opacity-0 transition-all duration-300';
    toast.innerHTML = `
        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-6', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('translate-y-6', 'opacity-0');
        setTimeout(() => toast.remove(), 350);
    }, 2800);
}

// Global Exports
window.addToCart = addToCart;
window.updateQuantity = updateQuantity;
window.removeFromCart = removeFromCart;
window.openCartDrawer = openCartDrawer;
window.closeCartDrawer = closeCartDrawer;
window.openCheckoutModal = openCheckoutModal;
window.closeCheckoutModal = closeCheckoutModal;
window.closeOrderSuccessModal = closeOrderSuccessModal;

