<?php
/**
 * register.php — Halaman pendaftaran akun baru BakeCost.
 */

session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: /bakecost/pages/beranda.php');
    exit;
}

$error = $_SESSION['auth_error'] ?? '';
unset($_SESSION['auth_error']);

$htmlTitle = 'Daftar Akun — BakeCost';
require __DIR__ . '/components/head.php';
?>
<body>
<div class="app-container">

    <header class="site-header">
        <div class="site-header__inner">
            <a href="/bakecost/login.php" class="header-back-btn" aria-label="Kembali ke halaman login">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <a href="/bakecost/index.php" class="brand-wordmark">BakeCost</a>
            <div class="w-10" aria-hidden="true"></div>
        </div>
    </header>

    <main class="page-content flex-1 flex flex-col justify-center">
        <div class="max-w-sm w-full mx-auto">

            <div class="mb-7">
                <p class="eyebrow mb-2">Akun Baru</p>
                <h1 class="editorial-title text-3xl font-semibold text-ink">Mulai dengan BakeCost</h1>
                <p class="text-sm text-muted mt-1.5">Data bahan baku dan resepmu tersimpan privat per akun.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error mb-4" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <form action="/bakecost/auth/register_process.php" method="POST" class="space-y-4">
                    <div>
                        <label class="form-label" for="nama">Nama Lengkap</label>
                        <input
                            type="text"
                            id="nama"
                            name="nama"
                            class="input-field"
                            placeholder="Nama kamu"
                            value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                            required
                            autocomplete="name"
                        >
                    </div>

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
                                placeholder="Minimal 6 karakter"
                                required
                                autocomplete="new-password"
                            >
                            <button type="button" onclick="togglePassword('password')" aria-label="Tampilkan atau sembunyikan password"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-ink transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        <div id="passwordStrength" class="mt-2 h-1.5 rounded-full bg-[var(--bg-subtle)] overflow-hidden hidden">
                            <div id="strengthBar" class="h-full transition-all duration-300 rounded-full"></div>
                        </div>
                        <p id="strengthText" class="text-xs mt-1 text-muted hidden"></p>
                    </div>

                    <div>
                        <label class="form-label" for="konfirmasi">Konfirmasi Password</label>
                        <div class="relative">
                            <input
                                type="password"
                                id="konfirmasi"
                                name="konfirmasi"
                                class="input-field pr-11"
                                placeholder="Ulangi password"
                                required
                                autocomplete="new-password"
                            >
                            <button type="button" onclick="togglePassword('konfirmasi')" aria-label="Tampilkan atau sembunyikan konfirmasi password"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-ink transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn-primary">Buat Akun</button>
                    </div>
                </form>
            </div>

            <p class="text-center text-sm text-muted mt-6">
                Sudah punya akun?
                <a href="/bakecost/login.php" class="text-ink font-semibold underline underline-offset-4">Masuk di sini</a>
            </p>
        </div>
    </main>

</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}

document.getElementById('password').addEventListener('input', function () {
    const val  = this.value;
    const bar  = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    const wrap = document.getElementById('passwordStrength');

    if (val.length === 0) {
        wrap.classList.add('hidden');
        text.classList.add('hidden');
        return;
    }

    wrap.classList.remove('hidden');
    text.classList.remove('hidden');

    let strength = 0;
    if (val.length >= 6)           strength++;
    if (val.length >= 10)          strength++;
    if (/[A-Z]/.test(val))         strength++;
    if (/[0-9]/.test(val))         strength++;
    if (/[^A-Za-z0-9]/.test(val))  strength++;

    const levels = [
        { pct: '20%',  color: '#B84A39', label: 'Sangat lemah' },
        { pct: '40%',  color: '#D47B38', label: 'Lemah' },
        { pct: '60%',  color: '#C58940', label: 'Cukup' },
        { pct: '80%',  color: '#3B7A57', label: 'Kuat' },
        { pct: '100%', color: '#1F5E3B', label: 'Sangat kuat' },
    ];
    const level = levels[Math.min(Math.max(strength - 1, 0), 4)];
    bar.style.width           = level.pct;
    bar.style.backgroundColor = level.color;
    text.textContent          = level.label;
    text.style.color          = level.color;
});
</script>
</body>
</html>
