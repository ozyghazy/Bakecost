<?php
/**
 * login.php — Halaman login
 */

session_start();

// Sudah login? Langsung ke beranda
if (!empty($_SESSION['user_id'])) {
    header('Location: /bakecost/pages/beranda.php');
    exit;
}

// Ambil pesan dari session (error / success dari register)
$error   = $_SESSION['auth_error']   ?? '';
$success = $_SESSION['auth_success'] ?? '';
unset($_SESSION['auth_error'], $_SESSION['auth_success']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container flex flex-col min-h-[100dvh] relative overflow-hidden">

    <!-- Dekorasi pojok -->
    <div class="absolute top-0 right-0 w-24 h-24 rounded-bl-full bg-[#EAD9C8] opacity-50"></div>

    <div class="flex-1 flex flex-col justify-center px-6 py-12">

        <!-- Logo -->
        <div class="text-center mb-10">
            <div class="w-14 h-14 rounded-2xl bg-[#7C4A2D] flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5A3.5 3.5 0 018.5 3 3.5 3.5 0 005 6.5c0 1.2.61 2.27 1.54 2.9L6 18h12l-.54-8.6A3.5 3.5 0 0019 6.5 3.5 3.5 0 0015.5 3 3.5 3.5 0 0012 6.5z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-[#3D1F0D]">Selamat Datang!</h1>
            <p class="text-sm text-[#9E7A5A] mt-1">Masuk ke akun BAKECOST kamu</p>
        </div>

        <!-- Alert error -->
        <?php if ($error): ?>
        <div class="alert-error mb-4 flex items-start gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Alert sukses dari register -->
        <?php if ($success): ?>
        <div class="alert-success mb-4">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Form login -->
        <form action="/bakecost/auth/login_process.php" method="POST" class="space-y-4">

            <div>
                <label class="form-label" for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="input-field"
                    placeholder="nama@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autocomplete="email"
                >
            </div>

            <div>
                <label class="form-label" for="password">Password</label>
                <div class="relative">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="input-field pr-12"
                        placeholder="Minimal 6 karakter"
                        required
                        autocomplete="current-password"
                    >
                    <!-- Toggle show/hide password -->
                    <button type="button" onclick="togglePassword('password', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[#9E7A5A] hover:text-[#7C4A2D]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary mt-2">Masuk</button>
        </form>

        <!-- Link ke register -->
        <p class="text-center text-sm text-[#9E7A5A] mt-6">
            Belum punya akun?
            <a href="/bakecost/register.php" class="text-[#7C4A2D] font-semibold hover:underline">Daftar sekarang</a>
        </p>

    </div>
</div>

<script>
// Toggle visibilitas password
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>

