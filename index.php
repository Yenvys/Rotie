<?php
/**
 * ====================================================================
 * PUBLIC LANDING PAGE: ROTIÉ — Artisan Bakery
 * Editorial Modern, Immersive Storytelling, Morph Animation & Live Catalog
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * Pengembang: Muhammad Al Farel (NIM: 41257124)
 * ====================================================================
 */

require_once __DIR__ . '/helpers/security.php';
require_once __DIR__ . '/config/database.php';

// Inisialisasi Token Anti-CSRF untuk formulir pemesanan
$csrfToken = csrf_token();

// Ambil Kategori dan Produk Unggulan Awal dari MySQL PDO
$categories = [];
$signatureProducts = [];
$initialProducts = [];
$dbError = null;

try {
    $pdo = getDBConnection();

    // 1. Ambil Seluruh Kategori
    $stmtCat = $pdo->query("SELECT * FROM categories ORDER BY urutan ASC, id ASC");
    $categories = $stmtCat->fetchAll();

    // 2. Ambil Produk Unggulan (Signature Products)
    $stmtSig = $pdo->query("
        SELECT p.*, c.nama_kategori 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.is_featured = 1 AND p.status = 'tersedia'
        ORDER BY p.id ASC 
        LIMIT 3
    ");
    $signatureProducts = $stmtSig->fetchAll();

    // 3. Ambil Seluruh Produk untuk Katalog Awal
    $stmtProd = $pdo->query("
        SELECT p.*, c.nama_kategori, c.slug AS kategori_slug 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'tersedia'
        ORDER BY p.id DESC
    ");
    $initialProducts = $stmtProd->fetchAll();

} catch (Exception $e) {
    error_log("Landing Page DB Error: " . $e->getMessage());
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROTIÉ — Artisan Bakery | A Little Taste of Happiness</title>
    <meta name="description" content="ROTIÉ Artisan Bakery menghadirkan roti artisan dan pastry segar harian dengan bahan alami terbaik, fermentasi lambat, dan sentuhan pengerjaan tangan otentik di Cirebon.">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        rotie: {
                            cream: '#F7F2E9',
                            warm: '#FFFCF7',
                            brown: '#4A3028',
                            chocolate: '#2B1D18',
                            caramel: '#B87945',
                            beige: '#E8D9C7',
                            olive: '#85836A'
                        }
                    },
                    fontFamily: {
                        display: ['"Playfair Display"', 'Georgia', 'serif'],
                        editorial: ['"Cormorant Garamond"', 'Garamond', 'serif'],
                        sans: ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts & Custom CSS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- GSAP & ScrollTrigger CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
</head>
<body class="bg-rotie-cream text-rotie-chocolate font-sans antialiased selection:bg-rotie-caramel selection:text-white">

    <!-- Scroll Progress Indicator (Top Bar) -->
    <div id="scroll-progress"></div>

    <!-- Widescreen Editorial Framing Rails (Mengisi estetika sisi kiri & kanan pada monitor desktop lebar) -->
    <div class="fixed left-3 2xl:left-6 top-1/2 -translate-y-1/2 hidden xl:flex flex-col items-center gap-6 z-30 pointer-events-none select-none opacity-40">
        <span class="w-px h-20 bg-gradient-to-b from-transparent via-rotie-brown to-transparent"></span>
        <span class="text-[9px] uppercase tracking-[0.35em] text-rotie-brown font-serif" style="writing-mode: vertical-rl; transform: rotate(180deg);">ROTIÉ • ARTISAN BAKERY • CIREBON</span>
        <span class="w-px h-20 bg-gradient-to-b from-transparent via-rotie-brown to-transparent"></span>
    </div>

    <div class="fixed right-3 2xl:right-6 top-1/2 -translate-y-1/2 hidden xl:flex flex-col items-center gap-6 z-30 pointer-events-none select-none opacity-40">
        <span class="w-px h-20 bg-gradient-to-b from-transparent via-rotie-brown to-transparent"></span>
        <span class="text-[9px] uppercase tracking-[0.35em] text-rotie-brown font-serif" style="writing-mode: vertical-rl;">EST. 2024 • SLOW-FERMENTED TRADITION</span>
        <span class="w-px h-20 bg-gradient-to-b from-transparent via-rotie-brown to-transparent"></span>
    </div>

    <!-- ============================================================== -->
    <!-- NOTIFIKASI BAR: Fresh From the Oven Jam Panggangan Segar       -->
    <!-- ============================================================== -->
    <div class="bg-rotie-chocolate text-rotie-cream py-2 px-4 text-center text-xs tracking-wider font-medium flex items-center justify-center gap-2">
        <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span>Fresh from the Oven Hari Ini: Batch Pagi <strong>07:00 WIB</strong> & Batch Sore <strong>14:00 WIB</strong></span>
        <span class="hidden md:inline text-rotie-beige/50">|</span>
        <span class="hidden md:inline text-rotie-beige/90">Gerai Cirebon: Jl. Siliwangi No. 88</span>
    </div>

    <!-- ============================================================== -->
    <!-- NAVBAR UTAMA (Transparan & Glassmorphism saat Scroll)          -->
    <!-- ============================================================== -->
    <nav id="main-navbar" class="fixed top-8 left-0 right-0 z-50 transition-all duration-300 py-5 bg-transparent">
        <div class="max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 xl:px-16">
            <div class="flex items-center justify-between">
                
                <!-- Brand Logo -->
                <a href="#hero" class="flex items-center gap-2 group">
                    <span class="w-10 h-10 rounded-full bg-rotie-caramel text-white flex items-center justify-center font-display text-xl font-bold shadow-md group-hover:scale-105 transition-transform">
                        R
                    </span>
                    <div class="flex flex-col">
                        <span class="font-display text-2xl font-bold tracking-wider text-rotie-brown group-hover:text-rotie-caramel transition-colors">
                            ROTIÉ
                        </span>
                        <span class="text-[9px] uppercase tracking-[0.25em] text-rotie-olive font-semibold -mt-1">
                            Artisan Bakery
                        </span>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <div class="hidden lg:flex items-center space-x-8 text-sm font-medium text-rotie-brown">
                    <a href="#hero" class="nav-link-item hover-underline transition-colors hover:text-rotie-caramel">Beranda</a>
                    <a href="#story" class="nav-link-item hover-underline transition-colors hover:text-rotie-caramel">Cerita Kami</a>
                    <a href="#signature" class="nav-link-item hover-underline transition-colors hover:text-rotie-caramel">Produk Unggulan</a>
                    <a href="#catalog" class="nav-link-item hover-underline transition-colors hover:text-rotie-caramel">Katalog Menu</a>
                    <a href="#process" class="nav-link-item hover-underline transition-colors hover:text-rotie-caramel">Filosofi Proses</a>
                    <a href="#promo" class="nav-link-item hover-underline transition-colors hover:text-rotie-caramel">Promo Spesial</a>
                    <a href="#contact" class="nav-link-item hover-underline transition-colors hover:text-rotie-caramel">Kunjungi Gerai</a>
                </div>

                <!-- Action Buttons: Cart Drawer & Admin Portal -->
                <div class="flex items-center space-x-3">
                    <!-- Shopping Cart Trigger Button -->
                    <button type="button" onclick="openCartDrawer()"
                        class="relative p-2.5 rounded-full bg-rotie-warm/90 hover:bg-white text-rotie-brown border border-rotie-beige/80 shadow-sm hover:shadow transition-all duration-200 flex items-center justify-center group"
                        aria-label="Keranjang Belanja">
                        <svg class="w-5 h-5 text-rotie-brown group-hover:text-rotie-caramel transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span class="cart-count-badge hidden absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rotie-caramel text-white text-[10px] font-bold flex items-center justify-center shadow">
                            0
                        </span>
                    </button>

                    <!-- Portal Admin Link -->
                    <a href="admin.php" 
                        class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold px-4 py-2.5 rounded-full bg-rotie-brown hover:bg-rotie-caramel text-white shadow-sm hover:shadow transition-all duration-200 btn-shimmer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>Portal Pengelola</span>
                    </a>

                    <!-- Mobile Menu Toggle Button -->
                    <button id="mobile-menu-toggle" type="button"
                        class="lg:hidden p-2 rounded-xl bg-rotie-warm/90 border border-rotie-beige text-rotie-brown focus:outline-none"
                        aria-label="Buka Menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>

            </div>
        </div>
    </nav>

    <!-- Mobile Menu Drawer & Backdrop -->
    <div id="mobile-menu-backdrop" class="fixed inset-0 bg-rotie-chocolate/40 backdrop-blur-sm z-50 hidden transition-opacity duration-300"></div>
    <div id="mobile-menu-drawer" class="fixed top-0 right-0 bottom-0 w-72 bg-rotie-warm z-50 p-6 shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between pb-4 border-b border-rotie-beige/80 mb-6">
                <span class="font-display font-bold text-xl text-rotie-brown">ROTIÉ Menu</span>
                <button id="mobile-menu-close" class="p-2 text-rotie-brown hover:text-rotie-caramel">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="flex flex-col space-y-4 text-base font-medium text-rotie-brown">
                <a href="#hero" class="mobile-nav-link hover:text-rotie-caramel py-1">Beranda</a>
                <a href="#story" class="mobile-nav-link hover:text-rotie-caramel py-1">Cerita Kami</a>
                <a href="#signature" class="mobile-nav-link hover:text-rotie-caramel py-1">Produk Unggulan</a>
                <a href="#catalog" class="mobile-nav-link hover:text-rotie-caramel py-1">Katalog Menu</a>
                <a href="#process" class="mobile-nav-link hover:text-rotie-caramel py-1">Filosofi Proses</a>
                <a href="#promo" class="mobile-nav-link hover:text-rotie-caramel py-1">Promo Spesial</a>
                <a href="#contact" class="mobile-nav-link hover:text-rotie-caramel py-1">Kunjungi Gerai</a>
            </nav>
        </div>
        <div class="pt-6 border-t border-rotie-beige/80">
            <a href="admin.php" class="w-full py-3 px-4 rounded-xl bg-rotie-brown hover:bg-rotie-caramel text-white text-center text-sm font-semibold flex items-center justify-center gap-2 shadow">
                <span>Portal Pengelola</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>
    </div>


    <!-- ============================================================== -->
    <!-- SECTION 1: CINEMATIC HERO (Editorial Typography & Parallax)   -->
    <!-- ============================================================== -->
    <section id="hero" class="relative min-h-screen pt-36 pb-20 flex items-center overflow-hidden">
        <!-- Giant Ambient Typography Watermark (Mengisi atmosfer lebar kiri-kanan) -->
        <div class="absolute inset-0 flex items-center justify-between pointer-events-none select-none opacity-[0.035] overflow-hidden -z-0">
            <span class="font-display text-[16vw] font-bold text-rotie-brown tracking-tighter -ml-16 leading-none">ARTISAN</span>
            <span class="font-display text-[16vw] font-bold text-rotie-brown tracking-tighter -mr-16 leading-none">BAKERY</span>
        </div>

        <!-- Floating Bakery Element Badges (Ditempatkan anggun di area tepi kiri & kanan) -->
        <div class="absolute top-40 left-4 sm:left-8 xl:left-14 hidden md:flex items-center gap-3 p-3.5 bg-white/85 backdrop-blur-md rounded-2xl border border-rotie-beige shadow-xl animate-float z-20">
            <span class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-xl shadow-inner">🌾</span>
            <div>
                <p class="text-[10px] uppercase font-bold tracking-wider text-rotie-olive">Fermentasi Alami</p>
                <p class="text-xs font-serif font-bold text-rotie-brown">36-Hour Sourdough</p>
            </div>
        </div>

        <div class="absolute bottom-24 right-4 sm:right-8 xl:right-14 hidden md:flex items-center gap-3 p-3.5 bg-white/85 backdrop-blur-md rounded-2xl border border-rotie-beige shadow-xl animate-float-reverse z-20">
            <span class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-xl shadow-inner">🥐</span>
            <div>
                <p class="text-[10px] uppercase font-bold tracking-wider text-rotie-olive">Laminasi Prancis</p>
                <p class="text-xs font-serif font-bold text-rotie-brown">Pure Elle & Vire Butter</p>
            </div>
        </div>

        <div class="max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 xl:px-16 w-full relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 xl:gap-20 items-center">
                
                <!-- Hero Left: Typography & Call to Action -->
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <div class="hero-badge inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-rotie-warm border border-rotie-beige text-xs font-semibold text-rotie-caramel tracking-wide shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-rotie-caramel"></span>
                        <span>Artisan Bakery & Viennoiserie Otentik</span>
                    </div>

                    <h1 class="hero-headline font-display text-4xl sm:text-6xl lg:text-7xl font-bold text-rotie-brown tracking-tight leading-[1.1]">
                        <span class="block">A Little Taste</span>
                        <span class="block italic font-normal font-editorial text-rotie-caramel">of Pure Happiness.</span>
                    </h1>

                    <p class="hero-subheadline text-base sm:text-lg text-rotie-brown/80 max-w-2xl mx-auto lg:mx-0 font-light leading-relaxed">
                        Dipanggang segar setiap pagi dengan tepung organik pilihan, ragi alami lambat, dan mentega murni Prancis. Sebuah simfoni kerenyahan dan kelembutan tiada tara di setiap gigitan.
                    </p>

                    <div class="hero-cta-group pt-4 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        <a href="#catalog" 
                            class="btn-magnetic w-full sm:w-auto px-8 py-4 rounded-full bg-rotie-brown hover:bg-rotie-caramel text-white font-medium text-sm shadow-xl hover:shadow-2xl transition-all duration-300 transform active:scale-95 flex items-center justify-center gap-2 btn-shimmer">
                            <span>Jelajahi Menu Bakery</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </a>
                        <button type="button" onclick="openCartDrawer()"
                            class="btn-magnetic w-full sm:w-auto px-8 py-4 rounded-full bg-white hover:bg-rotie-warm text-rotie-brown border border-rotie-beige font-medium text-sm shadow-sm hover:shadow transition-all duration-300 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-rotie-caramel" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span>Pesanan Saya</span>
                        </button>
                    </div>

                    <!-- Micro Metric / Social Proof (4 Kolom Lengkap) -->
                    <div class="pt-6 grid grid-cols-2 sm:grid-cols-4 gap-4 border-t border-rotie-beige/70 max-w-xl mx-auto lg:mx-0">
                        <div>
                            <span class="block font-display text-2xl font-bold text-rotie-brown">100%</span>
                            <span class="text-[11px] text-rotie-olive uppercase tracking-wider font-semibold">Bahan Alami</span>
                        </div>
                        <div>
                            <span class="block font-display text-2xl font-bold text-rotie-brown">36 Jam</span>
                            <span class="text-[11px] text-rotie-olive uppercase tracking-wider font-semibold">Fermentasi</span>
                        </div>
                        <div>
                            <span class="block font-display text-2xl font-bold text-rotie-brown">Daily</span>
                            <span class="text-[11px] text-rotie-olive uppercase tracking-wider font-semibold">Fresh Baked</span>
                        </div>
                        <div>
                            <span class="block font-display text-2xl font-bold text-rotie-caramel">4.9 ★</span>
                            <span class="text-[11px] text-rotie-olive uppercase tracking-wider font-semibold">2.500+ Ulasan</span>
                        </div>
                    </div>
                </div>

                <!-- Hero Right: Parallax Hero Image Visual & Rich Badges -->
                <div class="lg:col-span-5 relative">
                    <div class="hero-image-container relative mx-auto max-w-lg lg:max-w-none">
                        <!-- Decorative Circular Glow -->
                        <div class="absolute inset-0 bg-rotie-caramel/15 rounded-full filter blur-3xl transform scale-90 -z-10"></div>
                        
                        <div class="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white/80 bg-amber-50">
                            <img src="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1200&q=85" 
                                 alt="ROTIÉ Artisan Bread Hero"
                                 class="hero-parallax-img w-full h-[480px] sm:h-[540px] lg:h-[580px] xl:h-[620px] object-cover hover:scale-105 transition-transform duration-700">
                            
                            <div class="absolute inset-0 bg-gradient-to-t from-rotie-chocolate/75 via-transparent to-transparent flex items-end p-6 sm:p-8">
                                <div class="text-white">
                                    <span class="px-2.5 py-1 rounded-full bg-rotie-caramel/90 text-[10px] font-bold tracking-wider uppercase mb-2 inline-block shadow">Signature Creation</span>
                                    <h3 class="font-display text-xl sm:text-2xl font-bold">Classic French Viennoiserie</h3>
                                    <p class="text-xs text-white/80 font-light mt-1">Kerenyahan berlapis mentega Elle & Vire murni dengan karamelisasi emas</p>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Live Social Proof Card -->
                        <div class="absolute -bottom-6 -left-6 sm:-left-8 bg-white/95 backdrop-blur-md rounded-2xl p-4 border border-rotie-beige shadow-2xl flex items-center gap-3 animate-float hidden sm:flex z-20">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold text-lg">
                                ★
                            </div>
                            <div>
                                <div class="flex items-center gap-1 text-amber-500 text-xs">★★★★★</div>
                                <p class="text-xs font-bold text-rotie-brown mt-0.5">4.9 / 5.0 (2.500+ Ulasan)</p>
                                <p class="text-[10px] text-rotie-olive">Pilihan Utama Pencinta Pastry Cirebon</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- ============================================================== -->
    <!-- SECTION 2: BRAND STORY (Asymmetric Editorial Layout)          -->
    <!-- ============================================================== -->
    <section id="story" class="py-24 bg-rotie-warm border-y border-rotie-beige/80 relative">
        <div class="max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 xl:px-16">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 xl:gap-20 items-center">
                
                <!-- Story Visual: Dual Overlapping Images -->
                <div class="lg:col-span-6 relative">
                    <div class="relative w-full max-w-xl mx-auto">
                        <div class="story-image-reveal rounded-3xl overflow-hidden shadow-2xl border-4 border-white">
                            <img src="https://images.unsplash.com/photo-1589367920969-ab8e050bbb04?auto=format&fit=crop&w=1000&q=80" 
                                 alt="Proses Pembuatan Sourdough Alami" 
                                 class="w-full h-88 sm:h-[420px] xl:h-[460px] object-cover">
                        </div>
                        <div class="absolute -bottom-8 -right-4 sm:-right-8 w-56 sm:w-72 rounded-2xl overflow-hidden shadow-2xl border-4 border-white bg-white">
                            <img src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=600&q=80" 
                                 alt="Laminasi Croissant Emas" 
                                 class="w-full h-44 sm:h-56 object-cover">
                        </div>
                    </div>
                </div>

                <!-- Story Text Editorial -->
                <div class="lg:col-span-6 space-y-6 story-text-stagger">
                    <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-rotie-olive">
                        <span class="w-8 h-px bg-rotie-olive"></span>
                        <span>Cerita & Dedikasi Kami</span>
                    </div>

                    <h2 class="font-display text-3xl sm:text-5xl font-bold text-rotie-brown leading-tight">
                        Meracik Roti dengan Jiwa, Bukan Sekadar Adonan.
                    </h2>

                    <p class="text-sm sm:text-base text-rotie-brown/80 leading-relaxed font-light">
                        Di <strong>ROTIÉ</strong>, kami meyakini bahwa roti terbaik lahir dari kesabaran dan kejujuran rasa. Mengabaikan ragi instan dan zat kimia pengembang cepat, kami mengandalkan starter ragi liar alami yang kami rawat dengan cermat, melewati fermentasi dingin lambat selama 36 jam.
                    </p>

                    <p class="text-sm sm:text-base text-rotie-brown/80 leading-relaxed font-light">
                        Dipadukan dengan butter murni Prancis kaya rasa dan temperatur oven batu presisi tinggi, setiap produk menghasilkan tekstur kerak karamelisasi emas yang renyah dengan remah berpori lembut, mudah dicerna, dan menyehatkan tubuh.
                    </p>

                    <div class="pt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-4 rounded-2xl bg-rotie-cream border border-rotie-beige/70">
                            <span class="font-display text-base font-bold text-rotie-brown block mb-1">Tanpa Pengawet</span>
                            <span class="text-xs text-rotie-brown/70 leading-relaxed block">100% bebas kimia aditif, aman untuk konsumsi harian keluarga.</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-rotie-cream border border-rotie-beige/70">
                            <span class="font-display text-base font-bold text-rotie-brown block mb-1">Handmade Batch</span>
                            <span class="text-xs text-rotie-brown/70 leading-relaxed block">Diproduksi terbatas harian 2x sehari untuk kesegaran mutlak.</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-rotie-cream border border-rotie-beige/70">
                            <span class="font-display text-base font-bold text-rotie-brown block mb-1">Elle & Vire Butter</span>
                            <span class="text-xs text-rotie-brown/70 leading-relaxed block">Mentega murni 84% fat Prancis untuk lapisan renyah harum.</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- ============================================================== -->
    <!-- SECTION 3: SIGNATURE PRODUCTS (Showcase Produk Unggulan)       -->
    <!-- ============================================================== -->
    <section id="signature" class="py-24 bg-rotie-cream relative">
        <div class="max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 xl:px-16">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-rotie-olive block mb-2">Masterpiece Pilihan</span>
                <h2 class="font-display text-3xl sm:text-5xl font-bold text-rotie-brown">Kreasi Unggulan ROTIÉ</h2>
                <p class="text-sm sm:text-base text-rotie-brown/70 font-light mt-3">
                    Koleksi karya roti dan pastry yang paling dicintai pelanggan dengan perpaduan rasa otentik yang tak terlupakan.
                </p>
            </div>

            <!-- Grid 3 Signature Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 xl:gap-10">
                <?php if (!empty($signatureProducts)): ?>
                    <?php foreach ($signatureProducts as $sp): ?>
                        <div class="signature-card group bg-rotie-warm border border-rotie-beige/90 rounded-3xl p-6 sm:p-7 xl:p-8 shadow-md hover:shadow-2xl hover:border-rotie-caramel/50 transition-all duration-300 flex flex-col justify-between">
                            <div>
                                <div class="image-reveal-wrapper w-full h-72 sm:h-80 xl:h-88 rounded-2xl overflow-hidden mb-6 bg-amber-50 cursor-pointer" onclick="openProductDetail(<?= $sp['id']; ?>, this)">
                                    <img src="<?= e($sp['gambar']); ?>" alt="<?= e($sp['nama_produk']); ?>" 
                                         class="product-thumb-img w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                </div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-rotie-olive"><?= e($sp['nama_kategori']); ?></span>
                                    <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">Signature</span>
                                </div>
                                <h3 class="font-display font-bold text-xl sm:text-2xl text-rotie-brown group-hover:text-rotie-caramel transition-colors cursor-pointer" onclick="openProductDetail(<?= $sp['id']; ?>, this)">
                                    <?= e($sp['nama_produk']); ?>
                                </h3>
                                <p class="text-xs sm:text-sm text-rotie-brown/70 leading-relaxed font-light mt-2 line-clamp-3">
                                    <?= e($sp['deskripsi']); ?>
                                </p>
                            </div>

                            <div class="pt-6 mt-6 border-t border-rotie-beige/70 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] text-gray-500 uppercase tracking-wider block">Harga Satuan</span>
                                    <span class="font-serif font-bold text-xl sm:text-2xl text-rotie-brown"><?= format_rupiah($sp['harga']); ?></span>
                                </div>
                                <button type="button" 
                                    onclick="openProductDetail(<?= $sp['id']; ?>, this)"
                                    class="px-5 py-2.5 rounded-xl bg-rotie-brown hover:bg-rotie-caramel text-white text-xs font-semibold shadow hover:shadow-md transition-all">
                                    Lihat Detail
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>


    <!-- ============================================================== -->
    <!-- SECTION 4: PRODUCT COLLECTION (Katalog & Live Fetch Search)     -->
    <!-- ============================================================== -->
    <section id="catalog" class="py-24 bg-rotie-warm border-t border-rotie-beige/80 relative">
        <div class="max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 xl:px-16">
            
            <div class="text-center max-w-3xl mx-auto mb-12">
                <span class="text-xs font-bold uppercase tracking-widest text-rotie-olive block mb-2">Katalog Menu Segar</span>
                <h2 class="font-display text-3xl sm:text-5xl font-bold text-rotie-brown">Koleksi Roti & Pastry</h2>
                <p class="text-sm sm:text-base text-rotie-brown/70 font-light mt-2">
                    Gunakan filter kategori atau pencarian instan untuk menemukan varian roti artisan favorit Anda.
                </p>
            </div>

            <!-- Interactivity Toolbar: Live Search & Category Tabs & Sorting -->
            <div class="bg-rotie-cream/80 border border-rotie-beige rounded-2xl p-4 sm:p-6 mb-10 shadow-sm space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                    
                    <!-- Live Search Bar dengan Debounce -->
                    <div class="md:col-span-8 relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-rotie-brown/40">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" id="catalog-search" 
                            placeholder="Cari nama roti, croissant, cookies, atau bahan..."
                            class="w-full pl-10 pr-4 py-3 bg-white border border-rotie-beige rounded-xl text-sm text-rotie-brown placeholder-rotie-brown/40 focus:outline-none focus:ring-2 focus:ring-rotie-caramel focus:border-transparent transition-all">
                    </div>

                    <!-- Sort Price Dropdown -->
                    <div class="md:col-span-4 flex items-center gap-2">
                        <label for="catalog-sort" class="text-xs font-semibold uppercase tracking-wider text-rotie-brown whitespace-nowrap hidden sm:inline">Urutkan:</label>
                        <select id="catalog-sort" 
                            class="w-full py-3 px-3.5 bg-white border border-rotie-beige rounded-xl text-sm text-rotie-brown focus:outline-none focus:ring-2 focus:ring-rotie-caramel transition-all">
                            <option value="terbaru">Terbaru</option>
                            <option value="populer">Paling Populer</option>
                            <option value="termurah">Harga: Rendah ke Tinggi</option>
                            <option value="termahal">Harga: Tinggi ke Rendah</option>
                            <option value="nama_asc">Nama: A - Z</option>
                        </select>
                    </div>

                </div>

                <!-- Category Tabs (All, Bread, Croissant, Pastry, Cake, Cookies) -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none" id="category-tabs">
                    <button type="button" data-category="all"
                        class="category-filter-btn px-4 py-2 rounded-full text-xs font-semibold whitespace-nowrap bg-rotie-brown text-white shadow-md transition-all">
                        Semua Produk
                    </button>
                    <?php foreach ($categories as $cat): ?>
                        <button type="button" data-category="<?= e($cat['slug']); ?>"
                            class="category-filter-btn px-4 py-2 rounded-full text-xs font-semibold whitespace-nowrap bg-white text-rotie-brown hover:bg-rotie-beige/50 border border-rotie-beige transition-all">
                            <?= e($cat['nama_kategori']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Loading Indicator Asinkron -->
            <div id="catalog-loading" class="hidden text-center py-12">
                <div class="inline-flex items-center gap-2 text-rotie-caramel font-medium text-sm">
                    <svg class="animate-spin w-5 h-5 text-rotie-caramel" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memperbarui katalog...</span>
                </div>
            </div>

            <!-- Empty State -->
            <div id="catalog-empty" class="hidden text-center py-16 bg-rotie-cream/50 rounded-2xl border border-dashed border-rotie-beige">
                <span class="w-16 h-16 rounded-full bg-amber-100 text-rotie-brown flex items-center justify-center text-3xl mx-auto mb-3">🥐</span>
                <h3 class="font-display font-bold text-lg text-rotie-brown">Produk Tidak Ditemukan</h3>
                <p class="text-xs text-rotie-brown/70 max-w-sm mx-auto mt-1 mb-4">
                    Maaf, tidak ada produk roti yang cocok dengan kata kunci atau filter yang Anda pilih.
                </p>
                <button type="button" onclick="resetCatalogFilters()"
                    class="px-4 py-2 rounded-xl bg-rotie-brown hover:bg-rotie-caramel text-white text-xs font-semibold transition-all">
                    Reset Filter Pencarian
                </button>
            </div>

            <!-- Products Dynamic Grid Container (4 Kolom Lebih Lebar & Tinggi) -->
            <div id="products-grid-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 xl:gap-8 transition-opacity duration-300">
                <?php if (!empty($initialProducts)): ?>
                    <?php foreach ($initialProducts as $p): 
                        $isPromo = (bool) $p['is_promo'];
                        $diskon = (float) $p['diskon_persen'];
                        $hargaAsli = (float) $p['harga'];
                        $hargaFinal = ($isPromo && $diskon > 0) ? $hargaAsli - ($hargaAsli * $diskon / 100) : $hargaAsli;
                        $isOutOfStock = $p['stok'] <= 0;
                    ?>
                        <div class="product-card group bg-rotie-warm border border-rotie-beige/80 rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-xl hover:border-rotie-caramel/40 transition-all duration-300 flex flex-col justify-between">
                            <div>
                                <!-- Image with Morph Trigger (Tinggi Proporsional Widescreen) -->
                                <div class="image-reveal-wrapper relative w-full h-60 sm:h-64 rounded-xl overflow-hidden mb-4 bg-amber-50 cursor-pointer" onclick="openProductDetail(<?= $p['id']; ?>, this)">
                                    <?php if ($isPromo && $diskon > 0): ?>
                                        <div class="absolute top-3 left-3 z-10 px-2.5 py-1 rounded-full bg-rose-600 text-white text-[11px] font-bold tracking-wider uppercase shadow-md flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.316.492-.633.984-.95 1.477a1.002 1.002 0 01-1.39.297c-.347-.23-.615-.558-.823-.88-.316-.492-.633-.984-.95-1.477a1 1 0 00-1.45-.385 1 1 0 00-.385 1.45c.23.346.558.614.88.822.492.316.984.633 1.477.95a1.002 1.002 0 01.297 1.39c-.23.347-.558.615-.88.823-.492.316-.984.633-1.477.95a1 1 0 00.767 1.835c.346-.23.614-.558.822-.88.316-.492.633-.984.95-1.477a1.002 1.002 0 011.39-.297c.347.23.615.558.823.88.316.492.633.984.95 1.477a1 1 0 001.45.385 1 1 0 00.385-1.45c-.23-.346-.558-.614-.88-.822-.492-.316-.984-.633-1.477-.95a1.002 1.002 0 01-.297-1.39c.23-.347.558-.615.88-.823.492-.316.984-.633 1.477-.95a1 1 0 00-.767-1.835z" clip-rule="evenodd" />
                                            </svg>
                                            <span>Diskon <?= (int)$diskon; ?>%</span>
                                        </div>
                                    <?php endif; ?>

                                    <img src="<?= e($p['gambar']); ?>" alt="<?= e($p['nama_produk']); ?>" loading="lazy"
                                         class="product-thumb-img w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    
                                    <div class="absolute inset-0 bg-rotie-chocolate/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <span class="px-4 py-2 rounded-full bg-white/95 text-rotie-brown text-xs font-semibold shadow-md transform translate-y-2 group-hover:translate-y-0 transition-transform">
                                            Lihat Rincian
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <span class="text-[11px] uppercase tracking-wider font-semibold text-rotie-olive">
                                        <?= e($p['nama_kategori']); ?>
                                    </span>
                                    <?php if ($isOutOfStock): ?>
                                        <span class="text-[11px] font-medium text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">Habis</span>
                                    <?php else: ?>
                                        <span class="text-[11px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">Stok: <?= (int)$p['stok']; ?></span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="font-serif font-bold text-base sm:text-lg text-rotie-brown group-hover:text-rotie-caramel transition-colors line-clamp-1 mb-1.5 cursor-pointer" onclick="openProductDetail(<?= $p['id']; ?>, this)">
                                    <?= e($p['nama_produk']); ?>
                                </h3>

                                <p class="text-xs text-rotie-brown/70 line-clamp-2 leading-relaxed mb-4">
                                    <?= e($p['deskripsi']); ?>
                                </p>
                            </div>

                            <div class="pt-3 border-t border-rotie-beige/60 flex items-center justify-between gap-2">
                                <div>
                                    <?php if ($isPromo && $diskon > 0): ?>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-lg sm:text-xl font-bold font-serif text-rotie-caramel"><?= format_rupiah($hargaFinal); ?></span>
                                            <span class="text-xs text-gray-400 line-through"><?= format_rupiah($hargaAsli); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-lg sm:text-xl font-bold font-serif text-rotie-brown"><?= format_rupiah($hargaFinal); ?></div>
                                    <?php endif; ?>
                                </div>

                                <button type="button"
                                    <?= $isOutOfStock ? 'disabled' : ''; ?>
                                    onclick="addToCart(<?= $p['id']; ?>, 1, {
                                        id: <?= $p['id']; ?>,
                                        nama_produk: '<?= addslashes(e($p['nama_produk'])); ?>',
                                        harga: <?= (float) $hargaFinal; ?>,
                                        harga_final: <?= (float) $hargaFinal; ?>,
                                        harga_asli: <?= (float) $hargaAsli; ?>,
                                        harga_rp: '<?= format_rupiah($hargaFinal); ?>',
                                        harga_final_rp: '<?= format_rupiah($hargaFinal); ?>',
                                        gambar: '<?= addslashes(e($p['gambar'])); ?>',
                                        kategori: '<?= addslashes(e($p['nama_kategori'])); ?>'
                                    })"
                                    class="p-2.5 rounded-xl <?= $isOutOfStock ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-rotie-brown hover:bg-rotie-caramel text-white shadow-sm hover:shadow active:scale-95'; ?> transition-all duration-200 flex items-center justify-center"
                                    title="<?= $isOutOfStock ? 'Stok Habis' : 'Tambah ke Keranjang'; ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </section>


    <!-- ============================================================== -->
    <!-- SECTION 5: OUR PROCESS (Filosofi 4 Langkah Pembuatan)          -->
    <!-- ============================================================== -->
    <section id="process" class="py-24 bg-rotie-cream relative">
        <div class="max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 xl:px-16">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-rotie-olive block mb-2">Dedikasi Kualitas</span>
                <h2 class="font-display text-3xl sm:text-5xl font-bold text-rotie-brown">Filosofi Proses Artisan</h2>
                <p class="text-sm sm:text-base text-rotie-brown/70 font-light mt-2">
                    Setiap potong roti melewati empat tahap ketelitian tanpa kompromi demi menghasilkan cita rasa terbaik.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 xl:gap-8">
                <!-- Step 1 -->
                <div class="process-card p-7 xl:p-8 bg-rotie-warm border border-rotie-beige/80 rounded-3xl shadow-sm hover:shadow-xl transition-all duration-300">
                    <span class="font-display text-4xl font-bold text-rotie-caramel/40 block mb-3">01</span>
                    <h3 class="font-display font-bold text-lg sm:text-xl text-rotie-brown mb-2">Selected Ingredients</h3>
                    <p class="text-xs sm:text-sm text-rotie-brown/70 leading-relaxed font-light">
                        Hanya menggunakan gandum organik impor bersertifikasi, ragi liar murni, dan mentega Prancis beraroma gurih alami.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="process-card p-7 xl:p-8 bg-rotie-warm border border-rotie-beige/80 rounded-3xl shadow-sm hover:shadow-xl transition-all duration-300">
                    <span class="font-display text-4xl font-bold text-rotie-caramel/40 block mb-3">02</span>
                    <h3 class="font-display font-bold text-lg sm:text-xl text-rotie-brown mb-2">Handmade Preparation</h3>
                    <p class="text-xs sm:text-sm text-rotie-brown/70 leading-relaxed font-light">
                        Pengadukan tangan teliti, teknik lipatan laminasi berulang, dan fermentasi dingin lambat selama 24 hingga 36 jam.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="process-card p-7 xl:p-8 bg-rotie-warm border border-rotie-beige/80 rounded-3xl shadow-sm hover:shadow-xl transition-all duration-300">
                    <span class="font-display text-4xl font-bold text-rotie-caramel/40 block mb-3">03</span>
                    <h3 class="font-display font-bold text-lg sm:text-xl text-rotie-brown mb-2">Freshly Baked</h3>
                    <p class="text-xs sm:text-sm text-rotie-brown/70 leading-relaxed font-light">
                        Dipanggang harian di atas lempengan batu oven bersuhu presisi hingga mencapai warna kerak keemasan sempurna.
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="process-card p-7 xl:p-8 bg-rotie-warm border border-rotie-beige/80 rounded-3xl shadow-sm hover:shadow-xl transition-all duration-300">
                    <span class="font-display text-4xl font-bold text-rotie-caramel/40 block mb-3">04</span>
                    <h3 class="font-display font-bold text-lg sm:text-xl text-rotie-brown mb-2">Ready to Serve</h3>
                    <p class="text-xs sm:text-sm text-rotie-brown/70 leading-relaxed font-light">
                        Disajikan dalam keadaan hangat dan harum, siap memberikan momen kebahagiaan manis di setiap gigitan Anda.
                    </p>
                </div>
            </div>
        </div>
    </section>


    <!-- ============================================================== -->
    <!-- SECTION 6: SPECIAL PROMOTION (Banner Diskon & Countdown JS)    -->
    <!-- ============================================================== -->
    <section id="promo" class="py-20 lg:py-24 bg-gradient-to-r from-rotie-chocolate via-[#38241d] to-rotie-chocolate text-white relative overflow-hidden">
        <!-- Background Ambient Glows (Tampak megah di layar lebar) -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-rotie-caramel/25 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-rotie-olive/25 rounded-full filter blur-3xl pointer-events-none"></div>

        <div class="max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 xl:px-16 relative z-10">
            <div class="bg-rotie-brown/80 border border-rotie-caramel/40 rounded-3xl p-8 sm:p-12 xl:p-16 shadow-2xl backdrop-blur-md">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 xl:gap-12 items-center">
                    
                    <!-- Promo Left Column: Text & Points -->
                    <div class="lg:col-span-5 space-y-4">
                        <span class="px-3.5 py-1 rounded-full bg-rotie-caramel text-white text-[11px] font-bold tracking-widest uppercase inline-block shadow">
                            Promo Spesial Akhir Pekan
                        </span>
                        <h2 class="font-display text-3xl sm:text-5xl font-bold leading-tight">
                            Nikmati Diskon Hingga 20% untuk Menu Pilihan
                        </h2>
                        <p class="text-sm sm:text-base text-rotie-beige font-light leading-relaxed">
                            Rasakan kenikmatan Chocolate Croissant dan Garlic Cheese Bread dengan potongan harga eksklusif khusus pemesanan hari ini.
                        </p>
                        <div class="pt-2 space-y-2 text-xs text-rotie-beige/90">
                            <div class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Menggunakan 100% mentega murni Elle & Vire Prancis</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Bebas bahan kimia pengawet dan pemanis buatan</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-emerald-400 font-bold">✓</span>
                                <span>Dipanggang segar harian dalam jumlah terbatas (Fresh Batch)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Promo Center Column: Product Visual Showcase -->
                    <div class="lg:col-span-3 hidden lg:flex flex-col items-center justify-center">
                        <div class="relative group">
                            <div class="w-48 h-48 xl:w-56 xl:h-56 rounded-full overflow-hidden border-4 border-rotie-caramel/60 shadow-2xl p-1 bg-amber-50/10">
                                <img src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=600&q=80" 
                                     alt="Promo Croissant" 
                                     class="w-full h-full object-cover rounded-full group-hover:scale-110 transition-transform duration-500">
                            </div>
                            <div class="absolute -bottom-3 inset-x-0 mx-auto w-max px-4 py-1 rounded-full bg-rose-600 text-white text-[11px] font-bold uppercase tracking-wider shadow-lg">
                                Hemat 20% Hari Ini
                            </div>
                        </div>
                    </div>

                    <!-- Promo Right Column: Countdown Timer JavaScript Interaktif -->
                    <div class="lg:col-span-4 flex flex-col items-center lg:items-end justify-center">
                        <p class="text-xs uppercase tracking-widest text-rotie-beige/80 mb-3 font-medium">Batas Waktu Flash Sale:</p>
                        <div class="flex items-center gap-3">
                            <div class="text-center p-3 sm:p-4 bg-rotie-chocolate/90 border border-rotie-caramel/40 rounded-2xl min-w-[75px] shadow-lg">
                                <span id="promo-hours" class="font-display text-2xl sm:text-4xl font-bold text-rotie-caramel">08</span>
                                <span class="text-[10px] text-gray-300 block uppercase mt-0.5">Jam</span>
                            </div>
                            <span class="text-2xl font-bold text-rotie-caramel">:</span>
                            <div class="text-center p-3 sm:p-4 bg-rotie-chocolate/90 border border-rotie-caramel/40 rounded-2xl min-w-[75px] shadow-lg">
                                <span id="promo-minutes" class="font-display text-2xl sm:text-4xl font-bold text-rotie-caramel">45</span>
                                <span class="text-[10px] text-gray-300 block uppercase mt-0.5">Menit</span>
                            </div>
                            <span class="text-2xl font-bold text-rotie-caramel">:</span>
                            <div class="text-center p-3 sm:p-4 bg-rotie-chocolate/90 border border-rotie-caramel/40 rounded-2xl min-w-[75px] shadow-lg">
                                <span id="promo-seconds" class="font-display text-2xl sm:text-4xl font-bold text-rotie-caramel">30</span>
                                <span class="text-[10px] text-gray-300 block uppercase mt-0.5">Detik</span>
                            </div>
                        </div>

                        <a href="#catalog" 
                            class="btn-magnetic mt-8 px-8 py-4 rounded-full bg-rotie-caramel hover:bg-white hover:text-rotie-brown text-white font-semibold text-xs tracking-wider uppercase transition-all duration-300 shadow-xl btn-shimmer">
                            Klaim Promo Sekarang
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </section>


    <!-- ============================================================== -->
    <!-- SECTION 7: CUSTOMER EXPERIENCE (Slider Testimonial & Rating)  -->
    <!-- ============================================================== -->
    <section class="py-24 bg-rotie-warm border-b border-rotie-beige/80 relative">
        <div class="max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 xl:px-16">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-rotie-olive block mb-2">Suara Pelanggan</span>
                <h2 class="font-display text-3xl sm:text-5xl font-bold text-rotie-brown">Pengalaman Menikmati ROTIÉ</h2>
                <p class="text-sm sm:text-base text-rotie-brown/70 font-light mt-2">
                    Kepercayaan dan kebahagiaan para penikmat roti artisan sejati di Cirebon dan sekitarnya.
                </p>
            </div>

            <!-- Testimonial Expansive Layout with Trust Card -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                
                <!-- Trust Proof Sidebar (Left 4 Cols) -->
                <div class="lg:col-span-4 bg-rotie-cream border border-rotie-beige rounded-3xl p-8 flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-900 text-[10px] font-bold tracking-wider uppercase">Google Verified</span>
                            <span class="text-xs text-rotie-olive">Kota Cirebon</span>
                        </div>
                        <div class="font-display text-4xl sm:text-5xl font-bold text-rotie-brown">4.9<span class="text-xl text-rotie-brown/60"> / 5.0</span></div>
                        <div class="flex items-center gap-1 text-amber-500 text-lg my-2">★★★★★</div>
                        <p class="text-xs sm:text-sm text-rotie-brown/80 leading-relaxed font-light mt-3">
                            Berdasarkan lebih dari <strong>2.500+ ulasan</strong> pecinta pastry, penikmat kopi, dan keluarga pelanggan setia ROTIÉ.
                        </p>
                    </div>

                    <div class="pt-6 border-t border-rotie-beige/80 mt-6 space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-rotie-warm flex items-center justify-center text-sm shadow-sm">🏆</span>
                            <span class="text-xs font-medium text-rotie-brown">Best Artisan Bakery Choice 2024</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-rotie-warm flex items-center justify-center text-sm shadow-sm">🥐</span>
                            <span class="text-xs font-medium text-rotie-brown">100% Elle & Vire French Butter Certified</span>
                        </div>
                    </div>
                </div>

                <!-- Testimonial Carousel Wrapper (Right 8 Cols) -->
                <div id="testimonial-wrapper" class="lg:col-span-8 relative bg-rotie-cream border border-rotie-beige rounded-3xl p-8 sm:p-12 xl:p-14 shadow-sm flex flex-col justify-between">
                    
                    <div class="min-h-[220px] flex items-center justify-center">
                        
                        <!-- Slide 1 -->
                        <div class="testimonial-slide text-left space-y-4 transition-all duration-500 w-full">
                            <div class="flex items-center gap-1 text-amber-500 text-base">
                                ★★★★★
                            </div>
                            <p class="font-editorial italic text-xl sm:text-2xl lg:text-3xl text-rotie-brown leading-relaxed max-w-3xl">
                                "Croissant Butter dari ROTIÉ adalah yang paling otentik di Cirebon. Lapisan menteganya wangi sekali dan renyah luar biasa. Cocok sekali menemani kopi pagi."
                            </p>
                            <div class="pt-2">
                                <h4 class="font-serif font-bold text-base text-rotie-brown">Siti Nurhaliza</h4>
                                <p class="text-xs text-rotie-olive">Pelanggan Setia Kejaksan, Cirebon • Pembeli Terverifikasi</p>
                            </div>
                        </div>

                        <!-- Slide 2 -->
                        <div class="testimonial-slide text-left space-y-4 hidden transition-all duration-500 w-full">
                            <div class="flex items-center gap-1 text-amber-500 text-base">
                                ★★★★★
                            </div>
                            <p class="font-editorial italic text-xl sm:text-2xl lg:text-3xl text-rotie-brown leading-relaxed max-w-3xl">
                                "Sourdough loaf-nya memiliki aroma fermentasi yang kaya dan crust yang sangat garing. Sangat ramah untuk lambung saya yang sensitif ragi instan."
                            </p>
                            <div class="pt-2">
                                <h4 class="font-serif font-bold text-base text-rotie-brown">Andi Wicaksono</h4>
                                <p class="text-xs text-rotie-olive">Home Baker & Kopi Enthusiast • Pembeli Terverifikasi</p>
                            </div>
                        </div>

                        <!-- Slide 3 -->
                        <div class="testimonial-slide text-left space-y-4 hidden transition-all duration-500 w-full">
                            <div class="flex items-center gap-1 text-amber-500 text-base">
                                ★★★★★
                            </div>
                            <p class="font-editorial italic text-xl sm:text-2xl lg:text-3xl text-rotie-brown leading-relaxed max-w-3xl">
                                "Kemasan box hampers sangat elegan dan proses konfirmasi via WhatsApp sangat cepat. Japanese Milk Bread-nya luar biasa lembut seperti kapas!"
                            </p>
                            <div class="pt-2">
                                <h4 class="font-serif font-bold text-base text-rotie-brown">Dewi Anggraini</h4>
                                <p class="text-xs text-rotie-olive">Food Reviewer Cirebon • Pembeli Terverifikasi</p>
                            </div>
                        </div>

                    </div>

                    <!-- Slider Navigation Controls -->
                    <div class="flex items-center justify-between mt-8 pt-6 border-t border-rotie-beige/60">
                        <button id="testi-prev" class="p-3 rounded-full bg-white hover:bg-rotie-beige text-rotie-brown transition-colors shadow-sm" aria-label="Sebelumnya">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <!-- Dot Indicators -->
                        <div id="testi-dots" class="flex items-center gap-2"></div>

                        <button id="testi-next" class="p-3 rounded-full bg-white hover:bg-rotie-beige text-rotie-brown transition-colors shadow-sm" aria-label="Berikutnya">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                </div>

            </div>
        </div>
    </section>


    <!-- ============================================================== -->
    <!-- SECTION 8: VISIT OUR BAKERY (Alamat, Jam Buka & Kontak WA)     -->
    <!-- ============================================================== -->
    <section id="contact" class="py-24 bg-rotie-cream relative">
        <div class="max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 xl:px-16">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 xl:gap-16 items-center">
                
                <div class="lg:col-span-5 space-y-6">
                    <span class="text-xs font-bold uppercase tracking-widest text-rotie-olive block">Kunjungi Gerai Kami</span>
                    <h2 class="font-display text-3xl sm:text-5xl font-bold text-rotie-brown">
                        Hangatnya Roti, Akrabnya Senyuman.
                    </h2>
                    <p class="text-sm text-rotie-brown/80 font-light leading-relaxed">
                        Nikmati aroma panggangan mentega segar langsung di gerai kami atau pesan secara daring untuk pengiriman hangat ke pintu rumah Anda.
                    </p>

                    <div class="space-y-4 pt-2">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-rotie-warm border border-rotie-beige flex items-center justify-center text-rotie-caramel flex-shrink-0 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-serif font-bold text-sm sm:text-base text-rotie-brown">Alamat Gerai Pusat</h4>
                                <p class="text-xs sm:text-sm text-rotie-brown/70 mt-0.5">Jl. Siliwangi No. 88, Kec. Kejaksan, Kota Cirebon, Jawa Barat 45121</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-rotie-warm border border-rotie-beige flex items-center justify-center text-rotie-caramel flex-shrink-0 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-serif font-bold text-sm sm:text-base text-rotie-brown">Jam Operasional Harian</h4>
                                <p class="text-xs sm:text-sm text-rotie-brown/70 mt-0.5">Senin – Minggu: 07:00 – 21:00 WIB (Baking 2x sehari)</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-rotie-warm border border-rotie-beige flex items-center justify-center text-rotie-caramel flex-shrink-0 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-serif font-bold text-sm sm:text-base text-rotie-brown">Layanan Pelanggan WhatsApp</h4>
                                <p class="text-xs sm:text-sm text-rotie-brown/70 mt-0.5">+62 812-3456-7890 (Respon cepat konfirmasi pesanan)</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a href="https://wa.me/6281234567890?text=Halo%20ROTI%C3%89,%20saya%20ingin%20bertanya%20mengenai%20produk%20roti%20hari%20ini" 
                           target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 px-6 py-3.5 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-xs tracking-wider uppercase shadow-md hover:shadow-lg transition-all">
                            <span>Hubungi via WhatsApp</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Google Maps / Bakery Storefront Preview Card (Lebih Luas & Imersif) -->
                <div class="lg:col-span-7">
                    <div class="rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-rotie-warm p-2.5">
                        <div class="relative w-full h-88 sm:h-[420px] xl:h-[460px] rounded-2xl overflow-hidden bg-amber-50">
                            <iframe 
                                title="Peta Lokasi ROTIÉ Cirebon"
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3962.383838421884!2d108.5529457!3d-6.7099684!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6ee2653205022f%3A0xe54fb72565618774!2sJl.%20Siliwangi%2C%20Kota%20Cirebon%2C%20Jawa%20Barat!5e0!3m2!1sid!2sid!4v1715664000000!5m2!1sid!2sid" 
                                class="w-full h-full border-0" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- ============================================================== -->
    <!-- SECTION 9: FOOTER                                              -->
    <!-- ============================================================== -->
    <footer class="bg-rotie-chocolate text-rotie-cream pt-16 pb-12 border-t border-rotie-brown">
        <div class="max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 xl:px-16">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 pb-12 border-b border-white/10">
                
                <!-- Col 1: Brand Info -->
                <div class="space-y-4 md:col-span-2">
                    <div class="flex items-center gap-2">
                        <span class="w-9 h-9 rounded-full bg-rotie-caramel text-white flex items-center justify-center font-display text-lg font-bold">
                            R
                        </span>
                        <span class="font-display text-2xl font-bold tracking-wider text-white">
                            ROTIÉ
                        </span>
                    </div>
                    <p class="text-xs text-rotie-beige/80 font-light max-w-sm leading-relaxed">
                        Artisan bakery modern yang menyajikan kebahagiaan hangat dari panggangan oven setiap hari dengan dedikasi pada kemurnian bahan alami dan fermentasi lambat.
                    </p>
                    <p class="text-[11px] text-rotie-beige/60">
                        Proyek Capstone Ujian Akhir Semester (UAS) Pemrograman Web Dasar — STMIK IKMI Cirebon.
                    </p>
                </div>

                <!-- Col 2: Navigasi Cepat -->
                <div>
                    <h4 class="font-serif font-bold text-sm text-white mb-4 uppercase tracking-wider">Navigasi</h4>
                    <ul class="space-y-2 text-xs text-rotie-beige/80">
                        <li><a href="#hero" class="hover:text-rotie-caramel transition-colors">Beranda</a></li>
                        <li><a href="#story" class="hover:text-rotie-caramel transition-colors">Cerita Bakery</a></li>
                        <li><a href="#signature" class="hover:text-rotie-caramel transition-colors">Menu Unggulan</a></li>
                        <li><a href="#catalog" class="hover:text-rotie-caramel transition-colors">Katalog Lengkap</a></li>
                        <li><a href="#process" class="hover:text-rotie-caramel transition-colors">Proses Pembuatan</a></li>
                        <li><a href="admin.php" class="text-rotie-caramel font-semibold hover:underline">Portal Pengelola (Login)</a></li>
                    </ul>
                </div>

                <!-- Col 3: Hubungi Kami -->
                <div>
                    <h4 class="font-serif font-bold text-sm text-white mb-4 uppercase tracking-wider">Kontak & Jam Buka</h4>
                    <ul class="space-y-2 text-xs text-rotie-beige/80">
                        <li>Jl. Siliwangi No. 88, Cirebon</li>
                        <li>Buka: 07:00 – 21:00 WIB</li>
                        <li>WhatsApp: +62 812-3456-7890</li>
                        <li>Email: hello@rotie-bakery.com</li>
                    </ul>
                </div>

            </div>

            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-rotie-beige/60 gap-4">
                <p>&copy; <?= date('Y'); ?> ROTIÉ — Artisan Bakery. Muhammad Al Farel (NIM: 41257124). Hak Cipta Dilindungi.</p>
                <div class="flex items-center space-x-6">
                    <span class="hover:text-white transition-colors cursor-pointer">Kebijakan Privasi</span>
                    <span class="hover:text-white transition-colors cursor-pointer">Syarat & Ketentuan</span>
                    <a href="admin.php" class="hover:text-rotie-caramel transition-colors">Admin Dashboard</a>
                </div>
            </div>
        </div>
    </footer>


    <!-- ============================================================== -->
    <!-- MODAL: SHARED ELEMENT MORPH DETAIL PRODUK                     -->
    <!-- ============================================================== -->
    <div id="product-detail-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 sm:p-6">
        <!-- Backdrop -->
        <div id="product-modal-backdrop" onclick="closeProductDetail()" class="fixed inset-0 bg-rotie-chocolate/60 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal Dialog Content -->
        <div id="product-modal-content" class="relative bg-rotie-warm border border-rotie-beige/90 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden z-10 transform scale-95 opacity-0 transition-all duration-300">
            
            <button type="button" onclick="closeProductDetail()" class="absolute top-4 right-4 z-20 p-2 rounded-full bg-white/80 hover:bg-white text-rotie-brown shadow transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="grid grid-cols-1 sm:grid-cols-12">
                <!-- Target Image Container for Morph -->
                <div class="sm:col-span-6 bg-amber-50 h-64 sm:h-auto relative overflow-hidden">
                    <img id="product-modal-img" src="" alt="" class="w-full h-full object-cover">
                </div>

                <!-- Product Details -->
                <div class="sm:col-span-6 p-6 sm:p-8 flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                        <span id="product-modal-category" class="text-[11px] uppercase tracking-widest font-bold text-rotie-olive"></span>
                        <h3 id="product-modal-title" class="font-display font-bold text-2xl text-rotie-brown leading-snug"></h3>
                        <div id="product-modal-stock" class="pt-1"></div>
                        <p id="product-modal-desc" class="text-xs text-rotie-brown/80 font-light leading-relaxed pt-2"></p>
                    </div>

                    <div class="pt-4 border-t border-rotie-beige/70 space-y-4">
                        <div id="product-modal-price" class="flex items-baseline"></div>

                        <button id="product-modal-add-btn" type="button"
                            class="w-full py-3 px-4 rounded-xl bg-rotie-brown hover:bg-rotie-caramel text-white font-semibold text-xs tracking-wider uppercase shadow-md transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span>Tambah ke Keranjang</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <!-- ============================================================== -->
    <!-- DRAWER: SLIDE-OVER KERANJANG BELANJA DINAMIS                  -->
    <!-- ============================================================== -->
    <div id="cart-backdrop" onclick="closeCartDrawer()" class="fixed inset-0 bg-rotie-chocolate/40 backdrop-blur-sm z-50 hidden transition-opacity"></div>
    <div id="cart-drawer" class="fixed top-0 right-0 bottom-0 w-full max-w-md bg-rotie-warm z-50 shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col justify-between">
        
        <!-- Header Keranjang -->
        <div class="p-6 border-b border-rotie-beige flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-rotie-caramel text-white flex items-center justify-center font-bold text-sm">
                    🛒
                </span>
                <div>
                    <h3 class="font-serif font-bold text-lg text-rotie-brown">Keranjang Belanja</h3>
                    <p class="text-xs text-rotie-brown/60">Pilihan roti & pastry segar Anda</p>
                </div>
            </div>
            <button type="button" onclick="closeCartDrawer()" class="p-2 text-rotie-brown hover:text-rotie-caramel transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Daftar Item Keranjang -->
        <div class="flex-1 overflow-y-auto p-6 space-y-2">
            <div id="cart-items-list"></div>

            <!-- Empty State Keranjang -->
            <div id="cart-empty-state" class="text-center py-16">
                <span class="w-16 h-16 rounded-full bg-amber-50 text-rotie-brown/50 flex items-center justify-center text-3xl mx-auto mb-3">🧺</span>
                <h4 class="font-serif font-bold text-base text-rotie-brown">Keranjang Masih Kosong</h4>
                <p class="text-xs text-rotie-brown/60 max-w-xs mx-auto mt-1 mb-4">
                    Belum ada roti lezat yang ditambahkan. Jelajahi katalog kami dan temukan favorit Anda!
                </p>
                <button type="button" onclick="closeCartDrawer()" class="px-4 py-2 rounded-xl bg-rotie-brown text-white text-xs font-semibold">
                    Mulai Belanja
                </button>
            </div>
        </div>

        <!-- Footer Ringkasan & Checkout -->
        <div class="p-6 bg-rotie-cream border-t border-rotie-beige space-y-4">
            <div class="flex items-center justify-between text-sm">
                <span class="text-rotie-brown/70">Subtotal Belanja:</span>
                <span class="cart-total-price font-serif font-bold text-lg text-rotie-brown">Rp0</span>
            </div>

            <button id="cart-checkout-btn" type="button" disabled onclick="openCheckoutModal()"
                class="w-full py-3.5 px-4 rounded-xl bg-rotie-brown hover:bg-rotie-caramel disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold text-xs tracking-wider uppercase shadow-md transition-all duration-200 flex items-center justify-center gap-2">
                <span>Lanjut ke Pengisian Pesanan</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </div>

    </div>


    <!-- ============================================================== -->
    <!-- MODAL: FORMULIR PEMESANAN CHECKOUT (MySQL & Anti-CSRF)        -->
    <!-- ============================================================== -->
    <div id="checkout-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
        <div onclick="closeCheckoutModal()" class="fixed inset-0 bg-rotie-chocolate/60 backdrop-blur-sm"></div>

        <div class="relative bg-rotie-warm border border-rotie-beige rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-8 z-10 my-8">
            <div class="flex items-center justify-between pb-4 border-b border-rotie-beige mb-5">
                <div>
                    <h3 class="font-serif font-bold text-xl text-rotie-brown">Data Pemesan</h3>
                    <p class="text-xs text-rotie-brown/60">Lengkapi data untuk pengiriman roti hangat</p>
                </div>
                <button type="button" onclick="closeCheckoutModal()" class="p-1.5 text-rotie-brown hover:text-rotie-caramel">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="checkout-order-form" class="space-y-4">
                <?= csrf_field(); ?>

                <div>
                    <label for="order-nama" class="block text-xs font-semibold uppercase tracking-wider text-rotie-brown mb-1">
                        Nama Lengkap *
                    </label>
                    <input type="text" id="order-nama" name="nama" required
                        placeholder="Contoh: Siti Nurhaliza"
                        class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rotie-caramel">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="order-hp" class="block text-xs font-semibold uppercase tracking-wider text-rotie-brown mb-1">
                            No. WhatsApp Aktif *
                        </label>
                        <input type="tel" id="order-hp" name="no_hp" required
                            placeholder="081234567890"
                            class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rotie-caramel">
                    </div>

                    <div>
                        <label for="order-email" class="block text-xs font-semibold uppercase tracking-wider text-rotie-brown mb-1">
                            Email (Opsional)
                        </label>
                        <input type="email" id="order-email" name="email"
                            placeholder="siti@example.com"
                            class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rotie-caramel">
                    </div>
                </div>

                <div>
                    <label for="order-alamat" class="block text-xs font-semibold uppercase tracking-wider text-rotie-brown mb-1">
                        Alamat Pengiriman Lengkap *
                    </label>
                    <textarea id="order-alamat" name="alamat" rows="2" required
                        placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, patokan lokasi..."
                        class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rotie-caramel"></textarea>
                </div>

                <div>
                    <label for="order-catatan" class="block text-xs font-semibold uppercase tracking-wider text-rotie-brown mb-1">
                        Catatan Khusus (Opsional)
                    </label>
                    <input type="text" id="order-catatan" name="catatan"
                        placeholder="Contoh: Jangan terlalu manis / box pita kado"
                        class="w-full px-3.5 py-2.5 bg-white border border-rotie-beige rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rotie-caramel">
                </div>

                <div class="pt-3 border-t border-rotie-beige flex items-center justify-between">
                    <div>
                        <span class="text-xs text-gray-500 block">Total Tagihan:</span>
                        <span class="cart-total-price font-serif font-bold text-lg text-rotie-brown">Rp0</span>
                    </div>

                    <button id="btn-submit-order" type="submit"
                        class="relative px-6 py-3 rounded-xl bg-rotie-brown hover:bg-rotie-caramel text-white font-semibold text-xs tracking-wider uppercase shadow-md transition-all">
                        <span id="btn-submit-order-text">Kirim Pesanan Sekarang</span>
                        <span id="btn-submit-order-spinner" class="hidden absolute inset-0 flex items-center justify-center">
                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>


    <!-- ============================================================== -->
    <!-- MODAL: SUKSES PEMESANAN & GENERATOR PESAN WHATSAPP            -->
    <!-- ============================================================== -->
    <div id="order-success-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 sm:p-6">
        <div onclick="closeOrderSuccessModal()" class="fixed inset-0 bg-rotie-chocolate/60 backdrop-blur-sm"></div>

        <div class="relative bg-rotie-warm border border-rotie-beige rounded-3xl shadow-2xl max-w-md w-full p-6 sm:p-8 z-10 text-center space-y-4">
            <span class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto shadow-sm">
                ✓
            </span>

            <h3 class="font-display font-bold text-2xl text-rotie-brown">Pesanan Berhasil Disimpan!</h3>
            <p class="text-xs text-rotie-brown/70 leading-relaxed">
                Terima kasih atas pesanan Anda. Data pesanan telah tercatat di sistem kami dengan rincian:
            </p>

            <div class="bg-white/80 border border-rotie-beige rounded-2xl p-4 text-left text-xs space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-500">Kode Transaksi:</span>
                    <strong id="success-order-code" class="font-mono text-rotie-brown font-bold">ROTIE-XXXX</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Pembayaran:</span>
                    <strong id="success-order-total" class="font-serif text-rotie-caramel font-bold">Rp0</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Status Awal:</span>
                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-semibold text-[10px]">Menunggu Konfirmasi</span>
                </div>
            </div>

            <p class="text-[11px] text-gray-500 italic">
                Klik tombol di bawah untuk membuka obrolan resmi WhatsApp toko kami dengan pesan pesanan yang telah diformat otomatis:
            </p>

            <div class="pt-2 space-y-2">
                <a id="success-wa-btn" href="#" target="_blank" rel="noopener noreferrer"
                    class="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs tracking-wider uppercase shadow-lg transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                    </svg>
                    <span>Kirim Konfirmasi ke WhatsApp Toko</span>
                </a>

                <button type="button" onclick="closeOrderSuccessModal()"
                    class="w-full py-2.5 text-xs font-semibold text-rotie-brown/70 hover:text-rotie-brown">
                    Tutup Jendela Ini
                </button>
            </div>
        </div>
    </div>


    <!-- ============================================================== -->
    <!-- SCRIPTS PENDUKUNG                                              -->
    <!-- ============================================================== -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/animations.js"></script>
    <script src="assets/js/morph-transitions.js"></script>
    <script src="assets/js/produk_async.js"></script>
    <script src="assets/js/cart.js"></script>

</body>
</html>

