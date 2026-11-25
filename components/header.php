<?php
/**
 * components/header.php
 * Full-width sticky top navbar dengan konten sejajar max-width 1280px.
 */

$pageTitle = $pageTitle ?? 'BakeCost';
$showBack  = $showBack  ?? false;
$backUrl   = $backUrl   ?? 'javascript:history.back()';
$activeNav = $activeNav ?? 'beranda';

$desktopNavItems = [
    'beranda'  => ['label' => 'Beranda',     'url' => '/bakecost/pages/beranda.php'],
    'bahan'    => ['label' => 'Bahan Baku',  'url' => '/bakecost/pages/bahan_baku.php'],
    'resep'    => ['label' => 'Resep',       'url' => '/bakecost/pages/resep.php'],
    'konversi' => ['label' => 'Konversi',    'url' => '/bakecost/pages/konversi.php'],
    'biaya'    => ['label' => 'Biaya & HPP', 'url' => '/bakecost/pages/hpp_kalkulator.php'],
    'riwayat'  => ['label' => 'Riwayat',     'url' => '/bakecost/pages/riwayat.php'],
];
?>
<header class="site-header">
    <div class="site-header__inner">
        <?php if ($showBack): ?>
            <div class="flex items-center gap-3 min-w-0">
                <a href="<?= htmlspecialchars($backUrl) ?>" class="header-back-btn flex-shrink-0" aria-label="Kembali">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="font-editorial text-xl font-semibold text-ink truncate">
                    <?= htmlspecialchars($pageTitle) ?>
                </h1>
            </div>
        <?php else: ?>
            <a href="/bakecost/pages/beranda.php" class="brand-wordmark">BakeCost</a>
        <?php endif; ?>

        <nav class="desktop-nav" aria-label="Navigasi utama">
            <?php foreach ($desktopNavItems as $key => $item): ?>
                <a href="<?= $item['url'] ?>"
                   class="desktop-nav__link <?= $activeNav === $key ? 'desktop-nav__link--active' : '' ?>">
                    <?= $item['label'] ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <a href="/bakecost/pages/profil.php" class="header-profile-pill flex-shrink-0" aria-label="Profil pengguna">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="<?= $showBack ? 'hidden sm:inline' : '' ?>">Profil</span>
        </a>
    </div>
</header>
