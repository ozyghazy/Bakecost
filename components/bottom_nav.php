<?php
/**
 * bottom_nav.php
 * Bottom navigation bar — 4 tab: Beranda, Konversi, Biaya, Riwayat.
 * Variabel yang harus di-set sebelum include:
 *   $activeNav - salah satu dari: 'beranda', 'konversi', 'biaya', 'riwayat'
 */

$activeNav = $activeNav ?? 'beranda';

$navItems = [
    'beranda'  => ['label' => 'Beranda',  'url' => '/bakecost/pages/beranda.php',  'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    'konversi' => ['label' => 'Konversi', 'url' => '/bakecost/pages/konversi.php', 'icon' => 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z'],
    'biaya'    => ['label' => 'Biaya',    'url' => '/bakecost/pages/hpp_kalkulator.php', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    'riwayat'  => ['label' => 'Riwayat', 'url' => '/bakecost/pages/riwayat.php',  'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
];
?>
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-[#EAD9C8] flex max-w-lg mx-auto" style="max-width:100%">
    <?php foreach ($navItems as $key => $item):
        $isActive = ($activeNav === $key);
        $color = $isActive ? 'text-[#7C4A2D]' : 'text-[#B09080]';
    ?>
    <a href="<?= $item['url'] ?>" class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 <?= $color ?> hover:text-[#7C4A2D] transition-colors">
        <?php if ($isActive): ?>
            <!-- Latar aktif oval -->
            <span class="bg-[#F5E0CE] rounded-full px-3 py-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/>
                </svg>
            </span>
        <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/>
            </svg>
        <?php endif; ?>
        <span class="text-[10px] font-medium"><?= $item['label'] ?></span>
    </a>
    <?php endforeach; ?>
</nav>

