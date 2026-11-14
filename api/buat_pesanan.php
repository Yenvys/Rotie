<?php
/**
 * ====================================================================
 * API REST: Pembuatan Pesanan Pelanggan (PDO Transaction & WhatsApp URL)
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => false, 'message' => 'Metode permintaan tidak valid.'], 405);
}

// Tangkap payload (bisa berupa form-encoded atau JSON stream)
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$csrfToken = $data['csrf_token'] ?? '';
if (!verify_csrf($csrfToken)) {
    json_response(['status' => false, 'message' => 'Validasi keamanan (CSRF Token) tidak valid atau kedaluwarsa.'], 403);
}

$nama    = trim($data['nama'] ?? '');
$noHp    = trim($data['no_hp'] ?? '');
$email   = trim($data['email'] ?? '');
$alamat  = trim($data['alamat'] ?? '');
$catatan = trim($data['catatan'] ?? '');
$items   = $data['items'] ?? [];

// Validasi Form
if (empty($nama) || empty($noHp) || empty($alamat)) {
    json_response(['status' => false, 'message' => 'Nama lengkap, nomor WhatsApp, dan alamat pengiriman wajib diisi.'], 422);
}

if (empty($items) || !is_array($items)) {
    json_response(['status' => false, 'message' => 'Keranjang belanja Anda masih kosong.'], 422);
}

try {
    $pdo = getDBConnection();
    // Memulai Transaksi Database (ACID)
    $pdo->beginTransaction();

    // 1. Simpan atau Temukan Data Pelanggan
    $stmtCust = $pdo->prepare("SELECT id FROM customers WHERE no_hp = :no_hp LIMIT 1");
    $stmtCust->execute([':no_hp' => $noHp]);
    $existingCust = $stmtCust->fetch();

    if ($existingCust) {
        $customerId = (int) $existingCust['id'];
        // Perbarui alamat dan nama terbaru
        $updCust = $pdo->prepare("UPDATE customers SET nama = :nama, email = :email, alamat = :alamat WHERE id = :id");
        $updCust->execute([
            ':nama'   => $nama,
            ':email'  => !empty($email) ? $email : null,
            ':alamat' => $alamat,
            ':id'     => $customerId
        ]);
    } else {
        $insCust = $pdo->prepare("INSERT INTO customers (nama, no_hp, email, alamat, created_at) VALUES (:nama, :no_hp, :email, :alamat, NOW())");
        $insCust->execute([
            ':nama'   => $nama,
            ':no_hp'  => $noHp,
            ':email'  => !empty($email) ? $email : null,
            ':alamat' => $alamat
        ]);
        $customerId = (int) $pdo->lastInsertId();
    }

    // 2. Validasi Stok & Hitung Rincian Item
    $totalHarga = 0.00;
    $orderItemsData = [];
    $ringkasanPesanan = [];

    $stmtCheckProd = $pdo->prepare("SELECT id, nama_produk, harga, stok, is_promo, diskon_persen FROM products WHERE id = :id FOR UPDATE");

    foreach ($items as $item) {
        $productId = (int) ($item['id'] ?? 0);
        $qty       = (int) ($item['jumlah'] ?? 0);

        if ($productId <= 0 || $qty <= 0) {
            continue;
        }

        $stmtCheckProd->execute([':id' => $productId]);
        $prod = $stmtCheckProd->fetch();

        if (!$prod) {
            throw new Exception("Produk dengan ID #{$productId} tidak ditemukan dalam katalog.");
        }

        if ($prod['stok'] < $qty) {
            throw new Exception("Stok untuk '{$prod['nama_produk']}' tidak mencukupi (Tersisa: {$prod['stok']}, Dipesan: {$qty}).");
        }

        $hargaAsli = (float) $prod['harga'];
        $diskon = (float) $prod['diskon_persen'];
        $hargaSatuan = ($prod['is_promo'] && $diskon > 0) ? $hargaAsli - ($hargaAsli * $diskon / 100) : $hargaAsli;
        $subtotal = $hargaSatuan * $qty;
        $totalHarga += $subtotal;

        $orderItemsData[] = [
            'product_id'   => $productId,
            'nama_produk'  => $prod['nama_produk'],
            'jumlah'       => $qty,
            'harga_satuan' => $hargaSatuan,
            'subtotal'     => $subtotal
        ];

        $ringkasanPesanan[] = "• {$qty}x {$prod['nama_produk']} (" . format_rupiah($subtotal) . ")";
    }

    if (empty($orderItemsData)) {
        throw new Exception("Tidak ada item valid yang diproses dalam pesanan.");
    }

    // 3. Buat Kode Pesanan Unik (Format: ROTIE-YYYYMMDD-XXXX)
    $todayStr = date('Ymd');
    $randomCode = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    $kodePesanan = "ROTIE-{$todayStr}-{$randomCode}";

    // 4. Simpan ke Tabel orders
    $stmtOrder = $pdo->prepare("
        INSERT INTO orders (kode_pesanan, customer_id, total_harga, status, catatan, created_at)
        VALUES (:kode, :cust_id, :total, 'pending', :catatan, NOW())
    ");
    $stmtOrder->execute([
        ':kode'    => $kodePesanan,
        ':cust_id' => $customerId,
        ':total'   => $totalHarga,
        ':catatan' => !empty($catatan) ? $catatan : null
    ]);
    $orderId = (int) $pdo->lastInsertId();

    // 5. Simpan Rincian ke Tabel order_items
    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, jumlah, harga_satuan, subtotal)
        VALUES (:order_id, :product_id, :jumlah, :harga, :subtotal)
    ");
    foreach ($orderItemsData as $oi) {
        $stmtItem->execute([
            ':order_id'   => $orderId,
            ':product_id' => $oi['product_id'],
            ':jumlah'     => $oi['jumlah'],
            ':harga'      => $oi['harga_satuan'],
            ':subtotal'   => $oi['subtotal']
        ]);
    }

    // Commit Transaksi ke MySQL
    $pdo->commit();

    // 6. Susun Pesan WhatsApp Resmi Otomatis
    $configEnv = require __DIR__ . '/../config/env.php';
    $waNumber = preg_replace('/[^0-9]/', '', $configEnv['WHATSAPP_NUMBER'] ?? '628981295171');

    $pesanWA = "Halo ROTIÉ — Artisan Bakery,\n";
    $pesanWA .= "Saya ingin mengonfirmasi pesanan baru dari website:\n\n";
    $pesanWA .= "*Kode Pesanan:* " . $kodePesanan . "\n";
    $pesanWA .= "*Nama Pelanggan:* " . $nama . "\n";
    $pesanWA .= "*No. WhatsApp:* " . $noHp . "\n";
    $pesanWA .= "*Alamat Pengiriman:* " . $alamat . "\n";
    if (!empty($catatan)) {
        $pesanWA .= "*Catatan Khusus:* " . $catatan . "\n";
    }
    $pesanWA .= "\n*Daftar Roti & Pastry:*\n";
    $pesanWA .= implode("\n", $ringkasanPesanan) . "\n\n";
    $pesanWA .= "*Total Pembayaran:* " . format_rupiah($totalHarga) . "\n\n";
    $pesanWA .= "Mohon informasi rekening pembayaran dan jadwal pengiriman hangatnya. Terima kasih!";

    $waUrl = "https://wa.me/{$waNumber}?text=" . rawurlencode($pesanWA);

    json_response([
        'status'         => true,
        'message'        => 'Pesanan berhasil disimpan ke sistem basis data!',
        'kode_pesanan'   => $kodePesanan,
        'total_harga'    => $totalHarga,
        'total_harga_rp' => format_rupiah($totalHarga),
        'whatsapp_url'   => $waUrl,
        'items_count'    => count($orderItemsData)
    ]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("API buat_pesanan Error: " . $e->getMessage());
    json_response([
        'status'  => false,
        'message' => $e->getMessage()
    ], 400);
}

