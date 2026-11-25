<?php
/**
 * register.php — Halaman daftar akun baru
 */

session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: /bakecost/pages/beranda.php');
    exit;
}

$error = $_SESSION['auth_error'] ?? '';
unset($_SESSION['auth_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container flex flex-col min-h-[100dvh] relative overflow-hidden">

    <div class="absolute top-0 right-0 w-24 h-24 rounded-bl-full bg-[#EAD9C8] opacity-50"></div>

    <!-- Tombol kembali -->
    <div class="px-4 pt-4">
        <a href="/bakecost/login.php" class="inline-flex items-center text-[#7C4A2D] text-sm font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali
        </a>
    </div>

    <div class="flex-1 flex flex-col justify-center px-6 py-8">

        <!-- Judul -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-[#3D1F0D]">Buat Akun Baru</h1>
            <p class="text-sm text-[#9E7A5A] mt-1">Isi data di bawah untuk mendaftar</p>
        </div>

        <!-- Alert error -->
        <?php if ($error): ?>
        <div class="alert-error mb-4">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form register -->
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
                        class="input-field pr-12"
                        placeholder="Minimal 6 karakter"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" onclick="togglePassword('password', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[#9E7A5A] hover:text-[#7C4A2D]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div>
                <label class="form-label" for="konfirmasi">Konfirmasi Password</label>
                <div class="relative">
                    <input
                        type="password"
                        id="konfirmasi"
                        name="konfirmasi"
                        class="input-field pr-12"
                        placeholder="Ulangi password"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" onclick="togglePassword('konfirmasi', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[#9E7A5A] hover:text-[#7C4A2D]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <!-- Indikator kekuatan password -->
                <div id="passwordStrength" class="mt-2 h-1.5 rounded-full bg-[#EAD9C8] overflow-hidden hidden">
                    <div id="strengthBar" class="h-full transition-all duration-300 rounded-full"></div>
                </div>
                <p id="strengthText" class="text-xs mt-1 text-[#9E7A5A] hidden"></p>
            </div>

            <button type="submit" class="btn-primary mt-2">Buat Akun</button>
        </form>

        <p class="text-center text-sm text-[#9E7A5A] mt-6">
            Sudah punya akun?
            <a href="/bakecost/login.php" class="text-[#7C4A2D] font-semibold hover:underline">Masuk di sini</a>
        </p>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Indikator kekuatan password
document.getElementById('password').addEventListener('input', function () {
    const val = this.value;
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
    if (val.length >= 6)  strength++;
    if (val.length >= 10) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    const levels = [
        { pct: '20%', color: '#EF4444', label: 'Sangat lemah' },
        { pct: '40%', color: '#F97316', label: 'Lemah' },
        { pct: '60%', color: '#EAB308', label: 'Cukup' },
        { pct: '80%', color: '#22C55E', label: 'Kuat' },
        { pct: '100%', color: '#16A34A', label: 'Sangat kuat' },
    ];
    const level = levels[Math.min(strength - 1, 4)] || levels[0];
    bar.style.width = level.pct;
    bar.style.backgroundColor = level.color;
    text.textContent = level.label;
    text.style.color = level.color;
});
</script>
</body>
</html>

