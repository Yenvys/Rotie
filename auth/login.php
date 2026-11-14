<?php
/**
 * ====================================================================
 * AUTH: Form & Verifikasi Login (Bcrypt Verify & Anti-Session Fixation)
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * Pengembang: Muhammad Al Farel (NIM: 41257124)
 * ====================================================================
 */

require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../config/database.php';

// Jika pengguna sudah login, langsung arahkan ke dashboard admin
if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    redirect('../admin.php');
}

$error = '';
$usernameVal = '';

// Proses Autentikasi Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $usernameVal = $username;

    // 1. Verifikasi Anti-CSRF Token
    if (!verify_csrf($csrfToken)) {
        $error = 'Validasi keamanan sesi (CSRF Token) gagal atau telah kedaluwarsa. Silakan muat ulang halaman.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Silakan isi nama pengguna/email dan kata sandi dengan lengkap.';
    } else {
        try {
            $pdo = getDBConnection();
            // 2. Query Akun berdasarkan Username atau Email (Prepared Statement dengan parameter unik)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u OR email = :e LIMIT 1");
            $stmt->execute([
                ':u' => $username,
                ':e' => $username,
            ]);
            $user = $stmt->fetch();

            // 3. Verifikasi Kata Sandi dengan password_verify() (Bcrypt)
            if ($user && password_verify($password, $user['password'])) {
                // 4. Pencegahan Serangan Session Fixation: Regenerasi Session ID
                session_regenerate_id(true);

                // Buat token CSRF baru yang segar
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                // 5. Simpan Data Sesi Pengguna
                $_SESSION['user'] = [
                    'id'           => (int) $user['id'],
                    'username'     => $user['username'],
                    'email'        => $user['email'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'role'         => $user['role'],
                    'avatar'       => $user['avatar'] ?? null,
                ];

                // 6. Perbarui Waktu Login Terakhir
                $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $upd->execute([':id' => $user['id']]);

                set_flash('success', 'Selamat datang kembali, ' . $user['nama_lengkap'] . '!');
                redirect('../admin.php');
            } else {
                $error = 'Nama pengguna atau kata sandi yang Anda masukkan tidak sesuai.';
            }
        } catch (PDOException $e) {
            error_log("Login Query Error: " . $e->getMessage());
            $error = 'Terjadi kesalahan sistem saat memproses login. Silakan coba kembali.';
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Pengelola — ROTIÉ Artisan Bakery</title>
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
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                        sans: ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-rotie-cream text-rotie-chocolate font-sans min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8 antialiased selection:bg-rotie-caramel selection:text-white">

    <div class="w-full max-w-md">
        <!-- Brand Header & Logo -->
        <div class="text-center mb-8">
            <a href="../index.php" class="inline-flex items-center gap-2 group mb-3">
                <span class="w-10 h-10 rounded-full bg-rotie-caramel text-white flex items-center justify-center font-serif text-xl font-bold shadow-md group-hover:scale-105 transition-transform">
                    R
                </span>
                <span class="font-serif text-2xl font-bold tracking-wider text-rotie-brown group-hover:text-rotie-caramel transition-colors">
                    ROTIÉ
                </span>
            </a>
            <p class="text-xs uppercase tracking-widest text-rotie-olive font-medium">Portal Pengelola & Dashboard</p>
            <h1 class="text-2xl font-serif font-bold text-rotie-brown mt-1">Masuk ke Sistem</h1>
        </div>

        <!-- Kartu Formulir Login -->
        <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl shadow-xl shadow-rotie-brown/5 p-6 sm:p-8">
            
            <!-- Notifikasi Flash -->
            <?php if ($flash): ?>
                <div class="mb-5 p-3.5 rounded-xl text-sm flex items-start gap-2.5 <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <?php if ($flash['type'] === 'success'): ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <?php else: ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <?php endif; ?>
                    </svg>
                    <span><?= e($flash['message']); ?></span>
                </div>
            <?php endif; ?>

            <!-- Notifikasi Error Login -->
            <?php if (!empty($error)): ?>
                <div class="mb-5 p-3.5 rounded-xl text-sm flex items-start gap-2.5 bg-rose-50 text-rose-800 border border-rose-200">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><?= e($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-4">
                <!-- Proteksi Anti-CSRF Token -->
                <?= csrf_field(); ?>

                <div>
                    <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-rotie-brown mb-1.5">
                        Nama Pengguna atau Email
                    </label>
                    <div class="relative">
                        <input type="text" id="username" name="username" value="<?= e($usernameVal); ?>" required autofocus
                            placeholder="admin atau email@rotie.com"
                            class="w-full px-4 py-2.5 bg-white border border-rotie-beige rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rotie-caramel focus:border-transparent transition-all">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-rotie-brown">
                            Kata Sandi
                        </label>
                    </div>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                            placeholder="••••••••"
                            class="w-full pl-4 pr-11 py-2.5 bg-white border border-rotie-beige rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rotie-caramel focus:border-transparent transition-all">
                        <button type="button" id="toggle-password-btn" onclick="togglePasswordVisibility('password', 'toggle-eye-icon')"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-rotie-brown/60 hover:text-rotie-caramel focus:outline-none transition-colors"
                            aria-label="Tampilkan atau sembunyikan kata sandi">
                            <svg id="toggle-eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full py-3 px-4 bg-rotie-brown hover:bg-rotie-caramel text-white font-medium text-sm rounded-xl shadow-md hover:shadow-lg transition-all duration-200 transform active:scale-[0.99] flex items-center justify-center gap-2">
                        <span>Masuk ke Dashboard</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-rotie-beige/60 flex items-center justify-between text-xs text-rotie-brown/70">
                <a href="../index.php" class="inline-flex items-center gap-1 hover:text-rotie-caramel transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Toko</span>
                </a>
                <a href="registrasi.php" class="text-rotie-caramel font-semibold hover:underline">
                    Daftar Akun Baru
                </a>
            </div>

        </div>

        <p class="text-center text-xs text-rotie-brown/50 mt-6">
            &copy; <?= date('Y'); ?> ROTIÉ — Artisan Bakery. UAS STMIK IKMI Cirebon.
        </p>
    </div>

    <script>
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                `;
            } else {
                input.type = 'password';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                `;
            }
        }
    </script>
</body>
</html>

