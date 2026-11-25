<?php
/**
 * pages/profil.php — Profil pengguna, statistik, menu pengaturan, dan tombol keluar.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

$stmtR = $db->prepare('SELECT COUNT(*) FROM resep WHERE pengguna_id = ?');
$stmtR->execute([$userId]);
$totalResep = (int) $stmtR->fetchColumn();

$stmtP = $db->prepare('
    SELECT COUNT(*) FROM produksi p
    JOIN resep r ON r.id = p.resep_id
    WHERE r.pengguna_id = ?
');
$stmtP->execute([$userId]);
$totalPerhitungan = (int) $stmtP->fetchColumn();

$showBack  = true;
$pageTitle = 'Profil';
$backUrl   = '/bakecost/pages/beranda.php';
$activeNav = 'beranda';
$htmlTitle = 'Profil — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content max-w-2xl mx-auto space-y-5">

        <!-- Header Profil -->
        <section class="editorial-hero flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-[var(--brand-espresso)] text-[var(--ink-inverse)] flex items-center justify-center font-editorial text-2xl font-semibold flex-shrink-0">
                <?= strtoupper(substr(currentUserName(), 0, 1)) ?>
            </div>
            <div class="min-w-0">
                <p class="eyebrow">Pengguna BakeCost</p>
                <h2 class="font-editorial text-2xl font-semibold text-ink truncate"><?= currentUserName() ?></h2>
                <p class="text-xs text-muted truncate"><?= currentUserEmail() ?></p>
            </div>
        </section>

        <!-- 2 Stat Cards -->
        <section class="grid grid-cols-2 gap-3.5">
            <div class="card">
                <p class="eyebrow">Resep Disimpan</p>
                <p class="font-editorial text-3xl font-semibold text-ink tabular-nums mt-1"><?= $totalResep ?></p>
            </div>
            <div class="card">
                <p class="eyebrow">Perhitungan</p>
                <p class="font-editorial text-3xl font-semibold text-accent tabular-nums mt-1"><?= $totalPerhitungan ?></p>
            </div>
        </section>

        <!-- Menu Pengaturan -->
        <section class="card p-0 overflow-hidden divide-y divide-[var(--border-hairline)]">
            <a href="/bakecost/pages/pengaturan_akun.php" class="flex items-center justify-between px-5 py-4 hover:bg-[var(--bg-accent-soft)] transition-colors">
                <span class="text-sm font-medium text-ink">Pengaturan Akun</span>
                <span class="text-muted">→</span>
            </a>

            <a href="/bakecost/pages/pengaturan_akun.php#password" class="flex items-center justify-between px-5 py-4 hover:bg-[var(--bg-accent-soft)] transition-colors">
                <span class="text-sm font-medium text-ink">Ubah Password</span>
                <span class="text-muted">→</span>
            </a>

            <div class="flex items-center justify-between px-5 py-4">
                <span class="text-sm font-medium text-secondary">Notifikasi</span>
                <span class="badge-biaya">Aktif</span>
            </div>

            <button type="button"
                    onclick="alert('BakeCost v1.0.0\nAplikasi Konverter Takaran Baking & Kalkulator HPP.')"
                    class="w-full text-left flex items-center justify-between px-5 py-4 hover:bg-[var(--bg-accent-soft)] transition-colors">
                <span class="text-sm font-medium text-ink">Bantuan</span>
                <span class="text-muted">→</span>
            </button>

            <div class="flex items-center justify-between px-5 py-4">
                <span class="text-sm font-medium text-secondary">Tentang Aplikasi</span>
                <span class="text-xs text-muted tabular-nums">v1.0.0</span>
            </div>
        </section>

        <a href="/bakecost/auth/logout.php"
           onclick="return confirm('Yakin ingin keluar dari akun?')"
           class="btn-danger-outline">
            Keluar dari Akun
        </a>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>
</body>
</html>
