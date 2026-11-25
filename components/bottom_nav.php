<?php
/**
 * components/bottom_nav.php
 * Bottom navigation bar untuk layar mobile (< 768px).
 * Desktop menggunakan navigasi di header.php.
 */

$activeNav = $activeNav ?? 'beranda';

$navItems = [
    'beranda'  => [
        'label' => 'Beranda',
        'url'   => '/bakecost/pages/beranda.php',
        'icon'  => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    ],
    'konversi' => [
        'label' => 'Konversi',
        'url'   => '/bakecost/pages/konversi.php',
        'icon'  => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
    ],
    'biaya'    => [
        'label' => 'Biaya',
        'url'   => '/bakecost/pages/hpp_kalkulator.php',
        'icon'  => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ],
    'riwayat'  => [
        'label' => 'Riwayat',
        'url'   => '/bakecost/pages/riwayat.php',
        'icon'  => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    ],
];
?>
<nav class="bottom-nav" aria-label="Navigasi bawah">
    <?php foreach ($navItems as $key => $item):
        $isActive = ($activeNav === $key);
    ?>
        <a href="<?= $item['url'] ?>"
           class="bottom-nav__item <?= $isActive ? 'bottom-nav__item--active' : '' ?>"
           <?= $isActive ? 'aria-current="page"' : '' ?>>
            <span class="bottom-nav__icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/>
                </svg>
            </span>
            <span><?= $item['label'] ?></span>
        </a>
    <?php endforeach; ?>
</nav>
