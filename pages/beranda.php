<?php
/**
 * beranda.php — Dashboard utama
 * Menampilkan shortcut Konversi & Biaya, serta daftar resep milik user.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

// Ambil 5 resep terbaru milik user (untuk "Resep Populer")
$stmt = $db->prepare('SELECT id, nama, jumlah_hasil, satuan_hasil FROM resep WHERE pengguna_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$userId]);
$resepList = $stmt->fetchAll();

// Hitung stats untuk greeting
$stmtJumlah = $db->prepare('SELECT COUNT(*) FROM resep WHERE pengguna_id = ?');
$stmtJumlah->execute([$userId]);
$jumlahResep = (int) $stmtJumlah->fetchColumn();

$activeNav = 'beranda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">

        <!-- Banner greeting -->
        <div class="card flex items-center justify-between mb-4 bg-[#FDE8D5] border border-[#EAD9C8]">
            <div>
                <h2 class="text-[18px] font-bold text-[#3D1F0D]">Halo, <?= currentUserName() ?>!</h2>
                <p class="text-sm text-[#7C4A2D] mt-0.5">Yuk, hitung kebutuhan bahan dan estimasi biaya untuk resep favoritmu.</p>
            </div>
            <!-- Ilustrasi kue kecil -->
            <div class="w-16 h-16 flex-shrink-0 ml-3 bg-[#EAD9C8] rounded-full flex items-center justify-center text-3xl">🎂</div>
        </div>

        <!-- 2 Shortcut card -->
        <div class="grid grid-cols-2 gap-3 mb-6">

            <!-- Konversi Bahan -->
            <a href="/bakecost/pages/konversi.php" class="card hover:shadow-md transition-shadow cursor-pointer">
                <div class="w-10 h-10 rounded-xl bg-[#FDE8D5] flex items-center justify-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#7C4A2D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-[#3D1F0D] text-sm">Konversi Bahan</h3>
                <p class="text-[#9E7A5A] text-xs mt-1 leading-snug">Sesuaikan jumlah bahan berdasarkan jumlah produksi.</p>
                <div class="mt-3 flex justify-end">
                    <div class="w-7 h-7 rounded-full bg-[#7C4A2D] flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Hitung Biaya -->
            <a href="/bakecost/pages/hpp_kalkulator.php" class="card hover:shadow-md transition-shadow cursor-pointer">
                <div class="w-10 h-10 rounded-xl bg-[#FDE8D5] flex items-center justify-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#7C4A2D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-[#3D1F0D] text-sm">Hitung Biaya</h3>
                <p class="text-[#9E7A5A] text-xs mt-1 leading-snug">Estimasi biaya bahan baku dari resep yang kamu pilih.</p>
                <div class="mt-3 flex justify-end">
                    <div class="w-7 h-7 rounded-full bg-[#7C4A2D] flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>
        </div>

        <!-- Shortcut tambahan (baris ke-2) -->
        <div class="grid grid-cols-2 gap-3 mb-6">
            <a href="/bakecost/pages/bahan_baku.php" class="card flex items-center gap-3 hover:shadow-md transition-shadow">
                <div class="w-9 h-9 rounded-xl bg-[#FDE8D5] flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#7C4A2D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold text-[#3D1F0D]">Bahan Baku</span>
            </a>
            <a href="/bakecost/pages/bake_loss.php" class="card flex items-center gap-3 hover:shadow-md transition-shadow">
                <div class="w-9 h-9 rounded-xl bg-[#FDE8D5] flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#7C4A2D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold text-[#3D1F0D]">Bake Loss</span>
            </a>
        </div>

        <!-- Section: Resep Populer -->
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-[15px] font-bold text-[#3D1F0D]">Resep Populer</h3>
            <a href="/bakecost/pages/resep.php" class="text-sm text-[#C17A3E] font-semibold hover:underline">Lihat Semua ›</a>
        </div>

        <?php if (empty($resepList)): ?>
            <!-- State kosong -->
            <div class="card text-center py-8">
                <p class="text-4xl mb-3">📋</p>
                <p class="text-[#7C4A2D] font-semibold text-sm">Belum ada resep</p>
                <p class="text-[#9E7A5A] text-xs mt-1">Tambahkan resep pertamamu untuk mulai kalkulasi.</p>
                <a href="/bakecost/pages/resep_tambah.php" class="inline-block mt-4 px-5 py-2 bg-[#7C4A2D] text-white text-sm rounded-xl font-semibold">+ Tambah Resep</a>
            </div>
        <?php else: ?>
            <div class="card p-0 overflow-hidden divide-y divide-[#F0E8DF]">
                <?php foreach ($resepList as $resep): ?>
                <a href="/bakecost/pages/resep_detail.php?id=<?= $resep['id'] ?>"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-[#FDE8D5] transition-colors">
                    <!-- Avatar resep (emoji sebagai placeholder) -->
                    <div class="w-12 h-12 rounded-xl bg-[#EAD9C8] flex items-center justify-center text-xl flex-shrink-0">🧁</div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-[#3D1F0D] text-sm truncate"><?= htmlspecialchars($resep['nama']) ?></p>
                        <p class="text-[#9E7A5A] text-xs"><?= $resep['jumlah_hasil'] ?> <?= htmlspecialchars($resep['satuan_hasil']) ?></p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#C4A882] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>
<script src="/bakecost/assets/js/app.js"></script>
</body>
</html>

