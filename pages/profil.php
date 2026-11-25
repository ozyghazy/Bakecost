<?php
/**
 * pages/profil.php
 * Sesuai desain "Profil.png": avatar, info user, 2 stats card,
 * menu navigasi pengaturan, dan tombol Keluar.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

// Hitung resep tersimpan
$stmtR = $db->prepare('SELECT COUNT(*) FROM resep WHERE pengguna_id = ?');
$stmtR->execute([$userId]);
$totalResep = (int) $stmtR->fetchColumn();

// Hitung total perhitungan produksi
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">
        <!-- User Info Header (Sesuai Profil.png) -->
        <div class="flex items-center gap-3.5 mb-5 mt-1">
            <div class="w-16 h-16 rounded-full bg-[#EAD9C8] flex items-center justify-center text-[#7C4A2D] flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-[#3D1F0D] leading-snug"><?= currentUserName() ?></h2>
                <p class="text-xs text-[#9E7A5A]">Pengguna BAKECOST</p>
                <p class="text-xs text-[#B09080]"><?= currentUserEmail() ?></p>
            </div>
        </div>

        <!-- 2 Stat Cards (Sesuai Profil.png) -->
        <div class="grid grid-cols-2 gap-3 mb-5">
            <div class="card p-4">
                <span class="text-2xl font-bold text-[#7C4A2D] block"><?= $totalResep ?></span>
                <span class="text-xs text-[#9E7A5A] mt-0.5 block">Resep Disimpan</span>
            </div>
            <div class="card p-4">
                <span class="text-2xl font-bold text-[#7C4A2D] block"><?= $totalPerhitungan ?></span>
                <span class="text-xs text-[#9E7A5A] mt-0.5 block">Perhitungan</span>
            </div>
        </div>

        <!-- Menu List Card (Sesuai Profil.png) -->
        <div class="card p-0 divide-y divide-[#F0E8DF] mb-5 overflow-hidden">
            <a href="/bakecost/pages/pengaturan_akun.php" class="flex items-center justify-between p-3.5 hover:bg-[#FDE8D5] transition-colors">
                <div class="flex items-center gap-3">
                    <span class="text-base">⚙</span>
                    <span class="text-sm font-medium text-[#3D1F0D]">Pengaturan Akun</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#C4A882]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <a href="/bakecost/pages/pengaturan_akun.php#password" class="flex items-center justify-between p-3.5 hover:bg-[#FDE8D5] transition-colors">
                <div class="flex items-center gap-3">
                    <span class="text-base">🔒</span>
                    <span class="text-sm font-medium text-[#3D1F0D]">Ubah Password</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#C4A882]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <div class="flex items-center justify-between p-3.5 opacity-60">
                <div class="flex items-center gap-3">
                    <span class="text-base">🔔</span>
                    <span class="text-sm font-medium text-[#3D1F0D]">Notifikasi</span>
                </div>
                <span class="text-[10px] bg-[#EAD9C8] text-[#7C4A2D] px-2 py-0.5 rounded-full font-semibold">Aktif</span>
            </div>

            <div class="flex items-center justify-between p-3.5 cursor-pointer hover:bg-[#FDE8D5]" onclick="alert('BAKECOST v1.0.0\nAplikasi Konverter Takaran Baking & Kalkulator HPP Kuliah Informatika.')">
                <div class="flex items-center gap-3">
                    <span class="text-base">❓</span>
                    <span class="text-sm font-medium text-[#3D1F0D]">Bantuan</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#C4A882]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </div>

            <div class="flex items-center justify-between p-3.5">
                <div class="flex items-center gap-3">
                    <span class="text-base">ℹ</span>
                    <span class="text-sm font-medium text-[#3D1F0D]">Tentang Aplikasi</span>
                </div>
                <span class="text-xs text-[#9E7A5A]">v1.0.0</span>
            </div>
        </div>

        <!-- Tombol Keluar (Sesuai Profil.png) -->
        <a href="/bakecost/auth/logout.php" onclick="return confirm('Yakin ingin keluar dari akun?')"
           class="w-full flex items-center justify-center gap-2 py-3 rounded-xl border border-red-300 text-red-600 font-semibold text-sm hover:bg-red-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Keluar
        </a>
    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>
</body>
</html>

