/**
 * ====================================================================
 * JAVASCRIPT: Morph Transition & Shared Element Simulation
 * ROTIÉ — Artisan Bakery
 * Mensimulasikan PowerPoint Morph Transition (Card to Detail Modal)
 * ====================================================================
 */

let lastActiveCardImg = null;
let currentModalProduct = null;

/**
 * Buka Modal Detail Produk dengan Efek Morph
 */
async function openProductDetail(productId, triggerElement = null) {
    const modal = document.getElementById('product-detail-modal');
    const modalContent = document.getElementById('product-modal-content');
    const modalImg = document.getElementById('product-modal-img');
    const modalTitle = document.getElementById('product-modal-title');
    const modalCategory = document.getElementById('product-modal-category');
    const modalDesc = document.getElementById('product-modal-desc');
    const modalPrice = document.getElementById('product-modal-price');
    const modalStock = document.getElementById('product-modal-stock');
    const modalAddBtn = document.getElementById('product-modal-add-btn');

    if (!modal) return;

    // Cari elemen gambar asal untuk titik awal Morph
    let originImg = null;
    if (triggerElement) {
        const card = triggerElement.closest('.product-card, .signature-card');
        if (card) {
            originImg = card.querySelector('.product-thumb-img');
        }
    }
    lastActiveCardImg = originImg;

    // Tampilkan Loading State & Modal
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

    try {
        const response = await fetch(`api/detail_produk.php?id=${productId}`);
        const result = await response.json();

        if (!result.status || !result.data) {
            alert('Rincian produk tidak dapat dimuat.');
            closeProductDetail();
            return;
        }

        const p = result.data;
        currentModalProduct = p;

        // Isi konten modal
        modalTitle.textContent = p.nama_produk;
        modalCategory.textContent = p.kategori;
        modalDesc.textContent = p.deskripsi;
        modalImg.src = p.gambar;
        modalImg.alt = p.nama_produk;

        if (p.is_promo && p.diskon_persen > 0) {
            modalPrice.innerHTML = `
                <span class="text-2xl font-bold font-serif text-rotie-caramel">${p.harga_final_rp}</span>
                <span class="text-sm text-gray-400 line-through ml-2">${p.harga_asli_rp}</span>
                <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-rose-100 text-rose-700 rounded-full">Hemat ${parseInt(p.diskon_persen)}%</span>
            `;
        } else {
            modalPrice.innerHTML = `<span class="text-2xl font-bold font-serif text-rotie-brown">${p.harga_final_rp}</span>`;
        }

        modalStock.innerHTML = p.stok > 0 
            ? `<span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Stok Tersedia (${p.stok} porsi)</span>`
            : `<span class="inline-flex items-center gap-1.5 text-xs font-medium text-rose-700 bg-rose-50 px-2.5 py-1 rounded-full"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Habis Terjual</span>`;

        if (modalAddBtn) {
            modalAddBtn.disabled = p.stok <= 0;
            modalAddBtn.onclick = () => {
                window.addToCart(p.id, 1, p);
                closeProductDetail();
            };
        }

        // Eksekusi Animasi Morph Transition (Shared Element)
        executeMorphEntrance(originImg, modalImg, modalContent);

    } catch (err) {
        console.error('Fetch detail error:', err);
        closeProductDetail();
    }
}

/**
 * Animasi Morph Masuk dari Posisi Kartu ke Modal
 */
function executeMorphEntrance(originImg, targetImg, container) {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!originImg || prefersReducedMotion || typeof gsap === 'undefined') {
        container.style.opacity = '1';
        container.style.transform = 'scale(1)';
        return;
    }

    const startRect = originImg.getBoundingClientRect();
    
    gsap.set(container, { opacity: 0, scale: 0.96 });
    
    gsap.to(container, {
        opacity: 1,
        scale: 1,
        duration: 0.45,
        ease: 'power3.out'
    });

    // Buat elemen klona sementara untuk shared element morph
    const ghost = originImg.cloneNode(true);
    ghost.style.position = 'fixed';
    ghost.style.top = `${startRect.top}px`;
    ghost.style.left = `${startRect.left}px`;
    ghost.style.width = `${startRect.width}px`;
    ghost.style.height = `${startRect.height}px`;
    ghost.style.zIndex = '9999';
    ghost.style.borderRadius = '1rem';
    ghost.style.objectFit = 'cover';
    ghost.style.pointerEvents = 'none';
    ghost.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.25)';
    document.body.appendChild(ghost);

    requestAnimationFrame(() => {
        const endRect = targetImg.getBoundingClientRect();
        targetImg.style.visibility = 'hidden';

        gsap.to(ghost, {
            top: endRect.top,
            left: endRect.left,
            width: endRect.width,
            height: endRect.height,
            borderRadius: '1.5rem',
            duration: 0.48,
            ease: 'power3.inOut',
            onComplete: () => {
                targetImg.style.visibility = 'visible';
                ghost.remove();
            }
        });
    });
}

/**
 * Tutup Modal Detail Produk
 */
function closeProductDetail() {
    const modal = document.getElementById('product-detail-modal');
    const modalContent = document.getElementById('product-modal-content');
    if (!modal) return;

    if (typeof gsap !== 'undefined') {
        gsap.to(modalContent, {
            opacity: 0,
            scale: 0.95,
            duration: 0.24,
            ease: 'power2.in',
            onComplete: () => {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });
    } else {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

window.openProductDetail = openProductDetail;
window.closeProductDetail = closeProductDetail;
