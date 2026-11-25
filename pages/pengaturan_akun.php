<?php
/**
 * pages/pengaturan_akun.php
 * Ubah profil (nama) dan ubah password akun.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();
$msg    = '';
$err    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $nama = trim($_POST['nama'] ?? '');
        if (!empty($nama)) {
            $stmt = $db->prepare('UPDATE pengguna SET nama = ? WHERE id = ?');
            $stmt->execute([$nama, $userId]);
            $_SESSION['user_name'] = $nama;
            $msg = 'Profil berhasil diperbarui.';
        } else {
            $err = 'Nama tidak boleh kosong.';
        }
    }

    if ($action === 'change_password') {
        $passLama = trim($_POST['pass_lama'] ?? '');
        $passBaru = trim($_POST['pass_baru'] ?? '');
        $passKonf = trim($_POST['pass_konf'] ?? '');

        if (empty($passLama) || empty($passBaru)) {
            $err = 'Semua field password wajib diisi.';
        } elseif (strlen($passBaru) < 6) {
            $err = 'Password baru minimal 6 karakter.';
        } elseif ($passBaru !== $passKonf) {
            $err = 'Konfirmasi password baru tidak cocok.';
        } else {
            $stmt = $db->prepare('SELECT password_hash FROM pengguna WHERE id = ?');
            $stmt->execute([$userId]);
            $currentHash = $stmt->fetchColumn();

            if (!password_verify($passLama, $currentHash)) {
                $err = 'Password lama salah.';
            } else {
                $newHash = password_hash($passBaru, PASSWORD_BCRYPT);
                $db->prepare('UPDATE pengguna SET password_hash = ? WHERE id = ?')->execute([$newHash, $userId]);
                $msg = 'Password berhasil diubah.';
            }
        }
    }
}

$showBack  = true;
$pageTitle = 'Pengaturan Akun';
$backUrl   = '/bakecost/pages/profil.php';
$activeNav = 'beranda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">
        <?php if ($msg): ?>
            <div class="alert-success mb-4 text-xs font-semibold">✓ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert-error mb-4 text-xs font-semibold">⚠ <?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <!-- Form Edit Nama -->
        <div class="card mb-4">
            <h2 class="text-sm font-bold text-[#7C4A2D] uppercase tracking-wide mb-3">Informasi Akun</h2>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="update_profile">
                <div>
                    <label class="form-label text-xs">Email</label>
                    <input type="text" class="input-field text-sm bg-gray-100 text-gray-500 cursor-not-allowed"
                           value="<?= currentUserEmail() ?>" disabled>
                    <p class="text-[10px] text-[#9E7A5A] mt-1">Email akun tidak dapat diubah.</p>
                </div>
                <div>
                    <label class="form-label text-xs">Nama Lengkap</label>
                    <input type="text" name="nama" class="input-field text-sm"
                           value="<?= currentUserName() ?>" required>
                </div>
                <button type="submit" class="btn-primary py-2.5 text-xs">Simpan Perubahan</button>
            </form>
        </div>

        <!-- Form Ganti Password -->
        <div class="card mb-4" id="password">
            <h2 class="text-sm font-bold text-[#7C4A2D] uppercase tracking-wide mb-3">Ubah Password</h2>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="change_password">
                <div>
                    <label class="form-label text-xs">Password Saat Ini</label>
                    <input type="password" name="pass_lama" class="input-field text-sm" placeholder="••••••••" required>
                </div>
                <div>
                    <label class="form-label text-xs">Password Baru</label>
                    <input type="password" name="pass_baru" class="input-field text-sm" placeholder="Minimal 6 karakter" required>
                </div>
                <div>
                    <label class="form-label text-xs">Konfirmasi Password Baru</label>
                    <input type="password" name="pass_konf" class="input-field text-sm" placeholder="Ulangi password baru" required>
                </div>
                <button type="submit" class="btn-secondary py-2.5 text-xs">Ubah Password</button>
            </form>
        </div>
    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>
</body>
</html>

