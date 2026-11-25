<?php
/**
 * pages/resep.php — List semua resep milik user
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

// Ambil semua resep untuk initial render (SSR)
$stmt = $db->prepare(
    'SELECT r.id, r.nama, r.jumlah_hasil, r.satuan_hasil, r.dibuat_via_ai,
            COUNT(rb.id) AS jumlah_bahan
     FROM resep r
     LEFT JOIN resep_bahan rb ON rb.resep_id = r.id
     WHERE r.pengguna_id = ?
     GROUP BY r.id
     ORDER BY r.created_at DESC'
);
$stmt->execute([$userId]);
$resepList = $stmt->fetchAll();

$activeNav = 'beranda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resep — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">

    <header class="sticky top-0 z-40 bg-[#FDF5EE] border-b border-[#EAD9C8] px-4 py-3 flex items-center justify-between">
        <div>
            <p class="text-[17px] font-bold text-[#3D1F0D]">Resep Saya</p>
            <p class="text-[11px] text-[#9E7A5A]"><?= count($resepList) ?> resep tersimpan</p>
        </div>
        <div class="flex gap-2">
            <!-- Tombol AI Generate -->
            <a href="/bakecost/pages/resep_ai.php"
               class="flex items-center gap-1 border border-[#7C4A2D] text-[#7C4A2D] text-sm font-semibold px-3 py-2 rounded-xl hover:bg-[#FDE8D5] transition-colors">
                ✨ AI
            </a>
            <!-- Tombol Tambah Manual -->
            <a href="/bakecost/pages/resep_tambah.php"
               class="flex items-center gap-1 bg-[#7C4A2D] text-white text-sm font-semibold px-3 py-2 rounded-xl hover:opacity-90 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah
            </a>
        </div>
    </header>

    <main class="page-content">

        <!-- Search bar -->
        <div class="relative mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#B09080] absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
            </svg>
            <input type="text" id="searchInput" class="input-field pl-10" placeholder="Cari resep..." autocomplete="off">
        </div>

        <?php if (empty($resepList)): ?>
        <!-- Empty state -->
        <div class="text-center py-16">
            <div class="text-6xl mb-4">📋</div>
            <p class="font-bold text-[#7C4A2D] text-lg">Belum ada resep</p>
            <p class="text-[#9E7A5A] text-sm mt-2 mb-6">Tambahkan resep pertamamu secara manual<br>atau gunakan AI untuk generate otomatis.</p>
            <div class="flex gap-3 justify-center">
                <a href="/bakecost/pages/resep_tambah.php" class="px-5 py-2.5 bg-[#7C4A2D] text-white text-sm rounded-xl font-semibold">+ Tambah Manual</a>
                <a href="/bakecost/pages/resep_ai.php" class="px-5 py-2.5 border border-[#7C4A2D] text-[#7C4A2D] text-sm rounded-xl font-semibold">✨ Generate AI</a>
            </div>
        </div>
        <?php else: ?>

        <!-- List resep -->
        <div id="resepList" class="space-y-3">
            <?php foreach ($resepList as $r): ?>
            <div class="resep-item card flex items-center gap-3 p-3 cursor-pointer hover:shadow-md transition-shadow"
                 data-nama="<?= strtolower(htmlspecialchars($r['nama'])) ?>"
                 onclick="window.location='/bakecost/pages/resep_detail.php?id=<?= $r['id'] ?>'">

                <!-- Avatar emoji -->
                <div class="w-14 h-14 rounded-xl bg-[#EAD9C8] flex items-center justify-center text-2xl flex-shrink-0">🧁</div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-[#3D1F0D] truncate"><?= htmlspecialchars($r['nama']) ?></p>
                        <?php if ($r['dibuat_via_ai']): ?>
                        <span class="text-[10px] bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-semibold flex-shrink-0">✨ AI</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-[#9E7A5A] text-xs mt-0.5"><?= $r['jumlah_hasil'] ?> <?= htmlspecialchars($r['satuan_hasil']) ?> · <?= $r['jumlah_bahan'] ?> bahan</p>
                </div>

                <!-- Aksi -->
                <div class="flex items-center gap-2 flex-shrink-0" onclick="event.stopPropagation()">
                    <!-- Tombol langsung konversi -->
                    <a href="/bakecost/pages/konversi.php?resep_id=<?= $r['id'] ?>"
                       class="w-8 h-8 rounded-xl bg-[#FDE8D5] flex items-center justify-center hover:bg-[#F5D0A8] transition-colors"
                       title="Konversi resep ini">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#7C4A2D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </a>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#C4A882]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Empty setelah filter search -->
        <div id="searchEmpty" class="hidden text-center py-10">
            <p class="text-[#9E7A5A]">Resep "<span id="searchKeyword"></span>" tidak ditemukan.</p>
        </div>

        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<script src="/bakecost/assets/js/app.js"></script>
<script>
// Filter search client-side (data sudah di DOM via SSR)
document.getElementById('searchInput')?.addEventListener('input', debounce(function (e) {
    const keyword = e.target.value.trim().toLowerCase();
    const items   = document.querySelectorAll('.resep-item');
    let visible   = 0;

    items.forEach(item => {
        const nama = item.dataset.nama || '';
        const show = !keyword || nama.includes(keyword);
        item.classList.toggle('hidden', !show);
        if (show) visible++;
    });

    const emptyEl  = document.getElementById('searchEmpty');
    const kwEl     = document.getElementById('searchKeyword');
    if (emptyEl) {
        emptyEl.classList.toggle('hidden', visible > 0 || !keyword);
        if (kwEl) kwEl.textContent = e.target.value;
    }
}, 250));
</script>
</body>
</html>

