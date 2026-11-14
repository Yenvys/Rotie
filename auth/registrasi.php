<?php
/**
 * ====================================================================
 * AUTH: Form & Pendaftaran Akun Pengelola Baru (password_hash Bcrypt)
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * Pengembang: Muhammad Al Farel (NIM: 41257124)
 * ====================================================================
 */

require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../config/database.php';

$error = '';
$form = [
    'nama_lengkap' => '',
    'username'     => '',
    'email'        => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken       = $_POST['csrf_token'] ?? '';
    $namaLengkap     = trim($_POST['nama_lengkap'] ?? '');
    $username        = trim($_POST['username'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $konfirmasiPass  = $_POST['konfirmasi_password'] ?? '';
    $regKey          = trim($_POST['register_key'] ?? '');

    $form['nama_lengkap'] = $namaLengkap;
    $form['username']     = $username;
    $form['email']        = $email;

    // 1. Validasi Anti-CSRF Token
    if (!verify_csrf($csrfToken)) {
        $error = 'Validasi sesi (CSRF Token) gagal atau kedaluwarsa. Silakan muat ulang halaman.';
    } elseif (empty($namaLengkap) || empty($username) || empty($email) || empty($password)) {
        $error = 'Semua bidang formulir wajib diisi dengan lengkap.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid.';
    } elseif (strlen($username) < 4 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Nama pengguna minimal 4 karakter (hanya huruf, angka, dan garis bawah).';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal 6 karakter.';
    } elseif ($password !== $konfirmasiPass) {
        $error = 'Konfirmasi kata sandi tidak cocok dengan kata sandi yang dimasukkan.';
    } elseif ($regKey !== ($config['ADMIN_REGISTER_KEY'] ?? 'ROTIE_IKMI_2026')) {
        $error = 'Kunci otorisasi pengelola salah. Gunakan: ROTIE_IKMI_2026';
    } else {
        try {
            $pdo = getDBConnection();

            // 2. Cek apakah Username atau Email sudah terdaftar
            $cek = $pdo->prepare("SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1");
            $cek->execute([':u' => $username, ':e' => $email]);
            if ($cek->fetch()) {
                $error = 'Nama pengguna atau alamat email sudah terdaftar dalam sistem.';
            } else {
                // 3. Enkripsi Kata Sandi dengan password_hash() (Bcrypt)
                $hashPassword = password_hash($password, PASSWORD_BCRYPT);

                // 4. Simpan ke Basis Data via Prepared Statement
                $ins = $pdo->prepare("
                    INSERT INTO users (username, password, email, nama_lengkap, role, created_at)
                    VALUES (:username, :password, :email, :nama, 'admin', NOW())
                ");
                $ins->execute([
                    ':username' => $username,
                    ':password' => $hashPassword,
                    ':email'    => $email,
                    ':nama'     => $namaLengkap
                ]);

                set_flash('success', 'Pendaftaran akun berhasil! Silakan masuk dengan akun baru Anda.');
                redirect('login.php');
            }
        } catch (PDOException $e) {
            error_log("Registration Error: " . $e->getMessage());
            $error = 'Terjadi kesalahan sistem saat menyimpan data pengguna baru.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Pengelola — ROTIÉ Artisan Bakery</title>
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

    <div class="w-full max-w-lg my-8">
        <!-- Brand Header & Logo -->
        <div class="text-center mb-6">
            <a href="../index.php" class="inline-flex items-center gap-2 group mb-2">
                <span class="w-10 h-10 rounded-full bg-rotie-caramel text-white flex items-center justify-center font-serif text-xl font-bold shadow-md group-hover:scale-105 transition-transform">
                    R
                </span>
                <span class="font-serif text-2xl font-bold tracking-wider text-rotie-brown group-hover:text-rotie-caramel transition-colors">
                    ROTIÉ
                </span>
            </a>
            <p class="text-xs uppercase tracking-widest text-rotie-olive font-medium">Registrasi Akun Baru</p>
            <h1 class="text-2xl font-serif font-bold text-rotie-brown mt-1">Daftar Pengelola Toko</h1>
        </div>

        <!-- Kartu Formulir Registrasi -->
        <div class="bg-rotie-warm border border-rotie-beige/80 rounded-2xl shadow-xl shadow-rotie-brown/5 p-6 sm:p-8">
            
            <?php if (!empty($error)): ?>
                <div class="mb-5 p-3.5 rounded-xl text-sm flex items-start gap-2.5 bg-rose-50 text-rose-800 border border-rose-200">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><?= e($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="registrasi.php" method="POST" class="space-y-4">
                <?= csrf_field(); ?>

                <div>
                    <label for="nama_lengkap" class="block text-xs font-semibold uppercase tracking-wider text-rotie-brown mb-1.5">
                        Nama Lengkap
                    </label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?= e($form['nama_lengkap']); ?>" required
                        placeholder="Contoh: Muhammad Al Farel"
                        class="w-full px-4 py-2.5 bg-white border border-rotie-beige rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rotie-caramel focus:border-transparent transition-all">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-rotie-brown mb-1.5">
                            Nama Pengguna
                        </label>
                        <input type="text" id="username" name="username" value="<?= e($form['username']); ?>" required
                            placeholder="alfarel_admin"
                            class="w-full px-4 py-2.5 bg-white border border-rotie-beige rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rotie-caramel focus:border-transparent transition-all">
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-rotie-brown mb-1.5">
                            Alamat Email
                        </label>
                        <input type="email" id="email" name="email" value="<?= e($form['email']); ?>" required
                            placeholder="admin@rotie.com"
                            class="w-full px-4 py-2.5 bg-white border border-rotie-beige rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rotie-caramel focus:border-transparent transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-rotie-brown mb-1.5">
                            Kata Sandi (Min. 6 Karakter)
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                placeholder="••••••••"
                                class="w-full pl-4 pr-11 py-2.5 bg-white border border-rotie-beige rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rotie-caramel focus:border-transparent transition-all">
                            <button type="button" onclick="togglePasswordVisibility('password', 'toggle-eye-icon-1')"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-rotie-brown/60 hover:text-rotie-caramel focus:outline-none transition-colors"
                                aria-label="Tampilkan / sembunyikan kata sandi">
                                <svg id="toggle-eye-icon-1" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="konfirmasi_password" class="block text-xs font-semibold uppercase tracking-wider text-rotie-brown mb-1.5">
                            Ulangi Kata Sandi
                        </label>
                        <div class="relative">
                            <input type="password" id="konfirmasi_password" name="konfirmasi_password" required
                                placeholder="••••••••"
                                class="w-full pl-4 pr-11 py-2.5 bg-white border border-rotie-beige rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rotie-caramel focus:border-transparent transition-all">
                            <button type="button" onclick="togglePasswordVisibility('konfirmasi_password', 'toggle-eye-icon-2')"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-rotie-brown/60 hover:text-rotie-caramel focus:outline-none transition-colors"
                                aria-label="Tampilkan / sembunyikan konfirmasi kata sandi">
                                <svg id="toggle-eye-icon-2" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="register_key" class="block text-xs font-semibold uppercase tracking-wider text-rotie-brown mb-1.5">
                        Kunci Otorisasi Pengelola
                    </label>
                    <input type="text" id="register_key" name="register_key" required value="ROTIE_IKMI_2026"
                        placeholder="Masukkan kunci otorisasi"
                        class="w-full px-4 py-2.5 bg-white border border-rotie-beige rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rotie-caramel focus:border-transparent transition-all">
                    <p class="text-[11px] text-gray-500 mt-1">Kunci verifikasi default penguji: <span class="font-mono font-bold text-rotie-caramel">ROTIE_IKMI_2026</span></p>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full py-3 px-4 bg-rotie-brown hover:bg-rotie-caramel text-white font-medium text-sm rounded-xl shadow-md hover:shadow-lg transition-all duration-200 transform active:scale-[0.99] flex items-center justify-center gap-2">
                        <span>Daftarkan Akun Pengelola</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-rotie-beige/60 text-center text-xs text-rotie-brown/70">
                Sudah memiliki akun pengelola?
                <a href="login.php" class="text-rotie-caramel font-semibold hover:underline ml-1">
                    Masuk di sini
                </a>
            </div>

        </div>
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

