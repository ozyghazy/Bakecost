<?php
/**
 * pages/pengaturan_akun.php — Ubah nama profil dan ganti password akun.
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
$htmlTitle = 'Pengaturan Akun — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content max-w-2xl mx-auto space-y-5">

        <?php if ($msg): ?>
            <div class="alert-success font-medium" role="status"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert-error font-medium" role="alert"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <section class="card space-y-4">
            <p class="eyebrow">Informasi Akun</p>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_profile">
                <div>
                    <label class="form-label" for="akunEmail">Email</label>
                    <input type="text" id="akunEmail" class="input-field" value="<?= currentUserEmail() ?>" disabled>
                    <p class="text-[11px] text-muted mt-1">Email akun tidak dapat diubah.</p>
                </div>
                <div>
                    <label class="form-label" for="akunNama">Nama Lengkap</label>
                    <input type="text" id="akunNama" name="nama" class="input-field" value="<?= currentUserName() ?>" required>
                </div>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </form>
        </section>

        <section class="card space-y-4" id="password">
            <p class="eyebrow">Keamanan</p>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="change_password">
                <div>
                    <label class="form-label" for="passLama">Password Saat Ini</label>
                    <input type="password" id="passLama" name="pass_lama" class="input-field" placeholder="••••••••" required>
                </div>
                <div>
                    <label class="form-label" for="passBaru">Password Baru</label>
                    <input type="password" id="passBaru" name="pass_baru" class="input-field" placeholder="Minimal 6 karakter" required>
                </div>
                <div>
                    <label class="form-label" for="passKonf">Konfirmasi Password Baru</label>
                    <input type="password" id="passKonf" name="pass_konf" class="input-field" placeholder="Ulangi password baru" required>
                </div>
                <button type="submit" class="btn-secondary">Ubah Password</button>
            </form>
        </section>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>
</body>
</html>
