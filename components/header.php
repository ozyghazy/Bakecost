<?php
/**
 * header.php
 * Top bar aplikasi: logo BAKECOST di kiri, ikon profil di kanan.
 * Variabel yang bisa di-override sebelum include:
 *   $pageTitle  - judul halaman (default: 'BAKECOST')
 *   $showBack   - tampilkan tombol back (default: false)
 *   $backUrl    - URL tombol back (default: javascript:history.back())
 */

$pageTitle = $pageTitle ?? 'BAKECOST';
$showBack  = $showBack  ?? false;
$backUrl   = $backUrl   ?? 'javascript:history.back()';
?>
<header class="sticky top-0 z-40 bg-[#FDF5EE] border-b border-[#EAD9C8] px-4 py-3 flex items-center justify-between">
    <?php if ($showBack): ?>
        <!-- Tombol kembali -->
        <a href="<?= $backUrl ?>" class="flex items-center text-[#3D1F0D] hover:opacity-70 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-[17px] font-semibold text-[#3D1F0D] absolute left-1/2 -translate-x-1/2"><?= htmlspecialchars($pageTitle) ?></h1>
        <div class="w-6"></div><!-- spacer agar judul tetap center -->
    <?php else: ?>
        <!-- Logo kiri -->
        <div>
            <p class="text-[17px] font-bold text-[#3D1F0D] leading-tight">BAKECOST</p>
            <p class="text-[11px] text-[#9E7A5A] leading-tight">Baking Converter &amp; Cost Calculator</p>
        </div>
        <!-- Ikon profil kanan -->
        <a href="/bakecost/pages/profil.php" class="w-9 h-9 rounded-full bg-[#EAD9C8] flex items-center justify-center hover:opacity-80 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#7C4A2D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </a>
    <?php endif; ?>
</header>

