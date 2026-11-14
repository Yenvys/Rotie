/**
 * ====================================================================
 * JAVASCRIPT: Admin Dashboard Interactivity & Chart.js Integration
 * ROTIÉ — Artisan Bakery
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    initSalesChart();
    initCategoryChart();
    initTransactionStatusHandlers();
});

/**
 * Inisialisasi Grafik Penjualan (Chart.js)
 */
function initSalesChart() {
    const canvas = document.getElementById('salesChart');
    if (!canvas || typeof Chart === 'undefined') return;

    // Ambil data dari atribut data-canvas jika ada
    const labels = JSON.parse(canvas.getAttribute('data-labels') || '["Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu", "Minggu"]');
    const values = JSON.parse(canvas.getAttribute('data-values') || '[450000, 620000, 580000, 890000, 1250000, 1850000, 1600000]');

    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: values,
                borderColor: '#B87945',
                backgroundColor: 'rgba(184, 121, 69, 0.12)',
                borderWidth: 3,
                tension: 0.35,
                fill: true,
                pointBackgroundColor: '#B87945',
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' Omzet: Rp ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(232, 217, 199, 0.5)'
                    },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + (value / 1000).toLocaleString('id-ID') + 'k';
                        },
                        font: {
                            family: 'Inter',
                            size: 11
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Inter',
                            size: 11
                        }
                    }
                }
            }
        }
    });
}

/**
 * Inisialisasi Grafik Distribusi Kategori (Doughnut Chart)
 */
function initCategoryChart() {
    const canvas = document.getElementById('categoryChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const labels = JSON.parse(canvas.getAttribute('data-labels') || '["Bread", "Croissant", "Pastry", "Cake", "Cookies"]');
    const counts = JSON.parse(canvas.getAttribute('data-counts') || '[4, 3, 3, 1, 1]');

    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
                backgroundColor: [
                    '#4A3028',
                    '#B87945',
                    '#85836A',
                    '#D97706',
                    '#65A30D'
                ],
                borderWidth: 3,
                borderColor: '#FFFCF7'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        font: {
                            family: 'Inter',
                            size: 11
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
}

/**
 * Penanganan Pembaruan Status Transaksi Asinkron (Fetch API)
 */
function initTransactionStatusHandlers() {
    const statusSelects = document.querySelectorAll('.tx-status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', async (e) => {
            const orderId = select.getAttribute('data-order-id');
            const newStatus = select.value;
            const csrfToken = select.getAttribute('data-csrf');

            if (!orderId || !newStatus) return;

            select.disabled = true;
            try {
                const res = await fetch('api/update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: csrfToken,
                        order_id: orderId,
                        status: newStatus
                    })
                });

                const result = await res.json();
                if (result.status) {
                    showAdminToast('Status pesanan berhasil diperbarui!');
                    // Jika stok dipotong, refresh ringkasan jika di dashboard
                } else {
                    alert('Gagal memperbarui status: ' + result.message);
                }
            } catch (err) {
                console.error('Update status error:', err);
                alert('Terjadi kesalahan jaringan.');
            } finally {
                select.disabled = false;
            }
        });
    });
}

/**
 * Konfirmasi Penghapusan Produk Asinkron via AJAX
 */
async function hapusProdukAsync(productId, csrfToken, trElement) {
    if (!confirm('Apakah Anda yakin ingin menghapus produk ini dari katalog?')) {
        return;
    }

    try {
        const res = await fetch('api/hapus_produk.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: csrfToken,
                id: productId
            })
        });

        const result = await res.json();
        if (result.status) {
            trElement?.classList.add('opacity-0', 'transition-opacity', 'duration-300');
            setTimeout(() => trElement?.remove(), 300);
            showAdminToast('Produk berhasil dihapus.');
        } else {
            alert('Gagal menghapus produk: ' + result.message);
        }
    } catch (e) {
        console.error('Hapus error:', e);
        alert('Gagal menghapus produk.');
    }
}

/**
 * Toast Notifikasi Ringan Dashboard
 */
function showAdminToast(msg) {
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-6 right-6 z-50 bg-rotie-chocolate text-white text-xs font-semibold px-4 py-3 rounded-xl shadow-xl flex items-center gap-2 transform translate-y-4 opacity-0 transition-all';
    toast.innerHTML = `<span>✓</span><span>${msg}</span>`;
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.classList.remove('translate-y-4', 'opacity-0'));
    setTimeout(() => {
        toast.classList.add('translate-y-4', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

window.hapusProdukAsync = hapusProdukAsync;
window.showAdminToast = showAdminToast;

