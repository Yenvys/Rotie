-- ====================================================================
-- SKRIP BASIS DATA: ROTIÉ — ARTISAN BAKERY
-- Ujian Akhir Semester (UAS) Pemrograman Web Dasar - STMIK IKMI Cirebon
-- Pengembang: Muhammad Al Farel (NIM: 41257124)
-- Engine: MySQL / MariaDB (InnoDB, UTF-8 Multilingual)
-- ====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- --------------------------------------------------------------------
-- 1. TABEL: users (Pengguna Admin & Pemilik Toko)
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'owner') NOT NULL DEFAULT 'admin',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 2. TABEL: categories (Kategori Produk Roti & Pastry)
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(60) NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `urutan` INT(11) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 3. TABEL: products (Katalog Komoditas UMKM ROTIÉ)
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_id` INT(11) NOT NULL,
  `nama_produk` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `deskripsi` TEXT NOT NULL,
  `harga` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `stok` INT(11) NOT NULL DEFAULT 0,
  `stok_minimum` INT(11) NOT NULL DEFAULT 5,
  `gambar` VARCHAR(255) NOT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_promo` TINYINT(1) NOT NULL DEFAULT 0,
  `diskon_persen` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('tersedia', 'habis', 'arsip') NOT NULL DEFAULT 'tersedia',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_products_slug` (`slug`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_status` (`status`),
  KEY `idx_products_featured` (`is_featured`),
  KEY `idx_products_promo` (`is_promo`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 4. TABEL: customers (Data Pelanggan Pemesan)
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `alamat` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customers_no_hp` (`no_hp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 5. TABEL: orders (Nota Induk Transaksi Pemesanan)
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_pesanan` VARCHAR(30) NOT NULL,
  `customer_id` INT(11) NOT NULL,
  `total_harga` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending', 'diproses', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'pending',
  `catatan` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_orders_kode` (`kode_pesanan`),
  KEY `idx_orders_customer` (`customer_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_created` (`created_at`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 6. TABEL: order_items (Rincian Item Baris Pesanan)
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `jumlah` INT(11) NOT NULL DEFAULT 1,
  `harga_satuan` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 7. TABEL: stock_movements (Audit Trail Mutasi Stok Produk)
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `order_id` INT(11) DEFAULT NULL,
  `tipe` ENUM('masuk', 'keluar', 'penyesuaian') NOT NULL,
  `jumlah` INT(11) NOT NULL,
  `stok_sebelum` INT(11) NOT NULL,
  `stok_sesudah` INT(11) NOT NULL,
  `keterangan` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_movements_product` (`product_id`),
  KEY `idx_stock_movements_order` (`order_id`),
  KEY `idx_stock_movements_created` (`created_at`),
  CONSTRAINT `fk_stock_movements_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_movements_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ====================================================================
-- SEED DATA AWAL (DEMO SIAP PAKAI)
-- ====================================================================

-- 1. SEED: users (Password admin123 & owner123 di-hash dengan Bcrypt)
INSERT INTO `users` (`id`, `username`, `password`, `email`, `nama_lengkap`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$12$hxGKAlNqwvejuPJeRIeP9..Vb7eQ/wT.V8RB8K68oK6/yYVelrX66', 'admin@rotie.com', 'Administrator ROTIÉ', 'admin', NOW()),
(2, 'owner', '$2y$12$mtS7aDDamqZrmsZoFtFQNunxdh4FpeaNWqkZ5PRTDyBy6zVykLkRm', 'owner@rotie.com', 'Muhammad Al Farel (Owner)', 'owner', NOW());

-- 2. SEED: categories
INSERT INTO `categories` (`id`, `nama_kategori`, `slug`, `deskripsi`, `urutan`, `created_at`) VALUES
(1, 'Bread', 'bread', 'Roti artisan dengan ragi alami dan fermentasi lambat untuk tekstur empuk sempurna.', 1, NOW()),
(2, 'Croissant', 'croissant', 'Laminasi butter murni Prancis menghasilkan lapisan renyah beraroma gurih menggoda.', 2, NOW()),
(3, 'Pastry', 'pastry', 'Pastry manis dan gurih dengan isian selai buah segar serta rempah pilihan.', 3, NOW()),
(4, 'Cake', 'cake', 'Kue bolu dan cake lembut dengan rasa manis seimbang dan visual elegan.', 4, NOW()),
(5, 'Cookies', 'cookies', 'Kue kering artisan renyah dengan cokelat couverture murni dan butter premium.', 5, NOW());

-- 3. SEED: products
INSERT INTO `products` (`id`, `category_id`, `nama_produk`, `slug`, `deskripsi`, `harga`, `stok`, `stok_minimum`, `gambar`, `is_featured`, `is_promo`, `diskon_persen`, `status`, `created_at`) VALUES
(1, 2, 'Croissant Butter', 'croissant-butter', 'Croissant klasik khas Prancis dengan butter murni Elle & Vire, renyah berlapis di luar dan berongga lembut di dalam.', 18000.00, 35, 5, 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=800&q=80', 1, 0, 0.00, 'tersedia', NOW()),
(2, 2, 'Chocolate Croissant', 'chocolate-croissant', 'Pain au chocolat dengan isian dobel batangan cokelat hitam Valrhona yang meleleh lembut di setiap gigitan.', 22000.00, 28, 5, 'https://images.unsplash.com/photo-1530610476181-d83430b64dcd?auto=format&fit=crop&w=800&q=80', 1, 1, 10.00, 'tersedia', NOW()),
(3, 1, 'Japanese Milk Bread', 'japanese-milk-bread', 'Shokupan Jepang super lembut, empuk seperti awan, dibuat dengan teknik tangzhong dan susu segar murni.', 25000.00, 20, 5, 'https://images.unsplash.com/photo-1586444248902-2f64eddc13df?auto=format&fit=crop&w=800&q=80', 1, 0, 0.00, 'tersedia', NOW()),
(4, 1, 'Garlic Cheese Bread', 'garlic-cheese-bread', 'Roti bun lembut berisi cream cheese gurih melimpah, dicelup saus garlic butter dan taburan parsley harum.', 20000.00, 18, 5, 'https://images.unsplash.com/photo-1619535860434-ba1d8fa12536?auto=format&fit=crop&w=800&q=80', 0, 1, 15.00, 'tersedia', NOW()),
(5, 3, 'Cinnamon Roll', 'cinnamon-roll', 'Roti gulung lembut beraroma kayu manis Ceylon pilihan dengan limpahan glaze cream cheese vanila Bourbon.', 19000.00, 25, 5, 'https://images.unsplash.com/photo-1623375477581-eac701d0eebf?auto=format&fit=crop&w=800&q=80', 1, 0, 0.00, 'tersedia', NOW()),
(6, 1, 'Sourdough Loaf', 'sourdough-loaf', 'Roti sourdough artisan dengan ragi liar fermentasi 36 jam. Kulit garing karamelisasi dengan aroma asam khas alami.', 35000.00, 14, 4, 'https://images.unsplash.com/photo-1589367920969-ab8e050bbb04?auto=format&fit=crop&w=800&q=80', 1, 0, 0.00, 'tersedia', NOW()),
(7, 3, 'Matcha Danish', 'matcha-danish', 'Danish pastry berlapis garing dengan vla matcha Uji Kyoto otentik yang harum dan taburan roasted almond slice.', 24000.00, 16, 5, 'https://images.unsplash.com/photo-1738858363241-5b2e99e8a79b?auto=format&fit=crop&w=800&q=80', 0, 1, 10.00, 'tersedia', NOW()),
(8, 5, 'Chocolate Cookies', 'chocolate-cookies', 'Soft-baked cookies dengan lelehan belgian dark chocolate chunks dan taburan kristal garam laut Maldon.', 15000.00, 42, 8, 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=800&q=80', 0, 0, 0.00, 'tersedia', NOW()),
(9, 2, 'Almond Croissant', 'almond-croissant', 'Croissant butter dengan olesan krim frangipane almond lembut di dalam dan taburan kacang almond panggang garing.', 24000.00, 19, 5, 'https://images.unsplash.com/photo-1623334044303-241021148842?auto=format&fit=crop&w=800&q=80', 1, 0, 0.00, 'tersedia', NOW()),
(10, 4, 'Basque Burnt Cheesecake', 'basque-burnt-cheesecake', 'Cheesecake panggang khas Spanyol dengan permukaan karamelisasi gosong eksotis dan bagian tengah super creamy lembut.', 38000.00, 12, 3, 'https://images.unsplash.com/photo-1759303380841-55c09244fd2b?auto=format&fit=crop&w=800&q=80', 1, 0, 0.00, 'tersedia', NOW()),
(11, 1, 'Baguette Tradisi', 'baguette-tradisi', 'Baguette khas Parisian dengan remah berpori lebar dan kulit keemasan yang renyah sempurna.', 22000.00, 15, 5, 'https://images.unsplash.com/photo-1554475659-9fd915c8f156?auto=format&fit=crop&w=800&q=80', 0, 0, 0.00, 'tersedia', NOW()),
(12, 3, 'Red Velvet Pastry Roll', 'red-velvet-pastry-roll', 'Pastry roll merah marun yang elegan dengan kombinasi cream cheese frosting gurih manis dan crumble cokelat renyah.', 23000.00, 8, 5, 'https://images.unsplash.com/photo-1721324390033-661a350668d6?auto=format&fit=crop&w=800&q=80', 0, 1, 20.00, 'tersedia', NOW());

-- 4. SEED: customers
INSERT INTO `customers` (`id`, `nama`, `no_hp`, `email`, `alamat`, `created_at`) VALUES
(1, 'Siti Nurhaliza', '081234567891', 'siti.nur@gmail.com', 'Jl. Siliwangi No. 45, Kejaksan, Cirebon', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'Andi Wicaksono', '081987654321', 'andi.w@gmail.com', 'Komp. Pilang Perdana Blok B-12, Kedawung, Cirebon', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'Dewi Anggraini', '085712345678', 'dewi.ang@gmail.com', 'Jl. Tuparev No. 102, Cirebon', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 'Budi Santoso', '082199887766', 'budi.santoso@yahoo.com', 'Jl. Pemuda No. 15, Sunyaragi, Cirebon', NOW());

-- 5. SEED: orders
INSERT INTO `orders` (`id`, `kode_pesanan`, `customer_id`, `total_harga`, `status`, `catatan`, `created_at`) VALUES
(1, 'ROTIE-20261111-001', 1, 62000.00, 'selesai', 'Mohon dikemas dalam box hampers pita cokelat.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'ROTIE-20261112-002', 2, 74000.00, 'selesai', 'Kirim jam 9 pagi hangat fresh from oven.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'ROTIE-20261113-003', 3, 57000.00, 'diproses', 'Tambahkan lilin ulang tahun kecil jika ada.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 'ROTIE-20261114-004', 4, 38000.00, 'pending', 'Hubungi WhatsApp jika kurir sudah jalan.', NOW());

-- 6. SEED: order_items
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `jumlah`, `harga_satuan`, `subtotal`) VALUES
(1, 1, 1, 2, 18000.00, 36000.00), -- 2 Croissant Butter
(2, 1, 3, 1, 25000.00, 25000.00), -- 1 Japanese Milk Bread (total rounded / promo)
(3, 2, 2, 2, 19800.00, 39600.00), -- 2 Chocolate Croissant (harga diskon 10%)
(4, 2, 6, 1, 35000.00, 35000.00), -- 1 Sourdough Loaf
(5, 3, 5, 3, 19000.00, 57000.00), -- 3 Cinnamon Roll
(6, 4, 10, 1, 38000.00, 38000.00); -- 1 Basque Burnt Cheesecake

-- 7. SEED: stock_movements (Audit Trail Mutasi Stok)
INSERT INTO `stock_movements` (`id`, `product_id`, `order_id`, `tipe`, `jumlah`, `stok_sebelum`, `stok_sesudah`, `keterangan`, `created_at`) VALUES
(1, 1, NULL, 'masuk', 40, 0, 40, 'Baking batch pagi harian awal produksi', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 1, 1, 'keluar', 2, 40, 38, 'Penjualan pesanan ROTIE-20261111-001', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 2, NULL, 'masuk', 35, 0, 35, 'Baking batch pagi chocolate croissant', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 2, 2, 'keluar', 2, 35, 33, 'Penjualan pesanan ROTIE-20261112-002', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 5, NULL, 'masuk', 30, 0, 30, 'Baking batch cinnamon roll fresh', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(6, 5, 3, 'keluar', 3, 30, 27, 'Penjualan pesanan ROTIE-20261113-003', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(7, 10, NULL, 'masuk', 15, 0, 15, 'Produksi basque burnt cheesecake harian', NOW());

