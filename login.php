<?php
/**
 * login.php — Halaman login BakeCost.
 */

session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: /bakecost/pages/beranda.php');
    exit;
}

$error   = $_SESSION['auth_error']   ?? '';
$success = $_SESSION['auth_success'] ?? '';
unset($_SESSION['auth_error'], $_SESSION['auth_success']);

$htmlTitle = 'Masuk — BakeCost';
require __DIR__ . '/components/head.php';
?>
<body>
<div class="app-container">

    <header class="site-header">
        <div class="site-header__inner">
            <a href="/bakecost/index.php" class="brand-wordmark">BakeCost</a>
            <a href="/bakecost/register.php" class="btn-secondary btn-sm">Daftar</a>
        </div>
    </header>

    <main class="page-content flex-1 flex flex-col justify-center">
        <div class="max-w-sm w-full mx-auto">

            <div class="mb-7">
                <p class="eyebrow mb-2">Selamat Datang Kembali</p>
                <h1 class="editorial-title text-3xl font-semibold text-ink">Masuk ke Akun</h1>
                <p class="text-sm text-muted mt-1.5">Kelola bahan baku, resep, dan kalkulasi HPP usahamu.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error mb-4" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-success mb-4" role="status"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="card">
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
                                class="input-field pr-11"
                                placeholder="Masukkan password"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" onclick="togglePassword('password')" aria-label="Tampilkan atau sembunyikan password"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-ink transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn-primary">Masuk</button>
                    </div>
                </form>
            </div>

            <p class="text-center text-sm text-muted mt-6">
                Belum punya akun?
                <a href="/bakecost/register.php" class="text-ink font-semibold underline underline-offset-4">Daftar sekarang</a>
            </p>
        </div>
    </main>

</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
