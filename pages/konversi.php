<?php
/**
 * pages/konversi.php — Step 1: Pilih Resep
 * Sesuai desain "Pilih Resep.png": stepper 1-2-3, search, list resep, tombol Lanjut.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

// Ambil semua resep milik user
$stmt = $db->prepare('SELECT id, nama, jumlah_hasil, satuan_hasil FROM resep WHERE pengguna_id = ? ORDER BY nama ASC');
$stmt->execute([$userId]);
$resepList = $stmt->fetchAll();

// Jika ada resep_id dari query string (shortcut dari halaman lain)
$preselected = (int) ($_GET['resep_id'] ?? 0);

$showBack  = true;
$pageTitle = 'Konversi Bahan';
$backUrl   = '/bakecost/pages/beranda.php';
$activeNav = 'konversi';

$steps       = ['Resep', 'Jumlah Produksi', 'Hasil'];
$currentStep = 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Resep — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">
        <?php require __DIR__ . '/../components/step_indicator.php'; ?>

        <h2 class="text-lg font-bold text-[#3D1F0D] mb-4">Pilih Resep</h2>

        <!-- Search -->
        <div class="relative mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#B09080] absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
            </svg>
            <input type="text" id="searchInput" class="input-field pl-10" placeholder="Cari resep..." autocomplete="off">
        </div>

        <?php if (empty($resepList)): ?>
        <!-- Tidak ada resep -->
        <div class="text-center py-12">
            <p class="text-4xl mb-3">📋</p>
            <p class="font-semibold text-[#7C4A2D]">Belum ada resep</p>
            <p class="text-[#9E7A5A] text-sm mt-1">Tambahkan resep terlebih dahulu sebelum konversi.</p>
            <a href="/bakecost/pages/resep_tambah.php" class="inline-block mt-4 px-5 py-2 bg-[#7C4A2D] text-white text-sm rounded-xl font-semibold">+ Tambah Resep</a>
        </div>
        <?php else: ?>

        <!-- List resep — bisa diklik untuk pilih -->
        <div class="space-y-2 mb-24" id="resepList">
            <?php foreach ($resepList as $r):
                $isSelected = ($r['id'] === $preselected);
            ?>
            <div class="resep-card card flex items-center gap-3 p-3 cursor-pointer transition-all
                        <?= $isSelected ? 'card-selected' : 'hover:shadow-md' ?>"
                 data-id="<?= $r['id'] ?>"
                 data-nama="<?= strtolower(htmlspecialchars($r['nama'])) ?>"
                 onclick="selectResep(<?= $r['id'] ?>)">

                <div class="w-12 h-12 rounded-xl bg-[#EAD9C8] flex items-center justify-center text-xl flex-shrink-0">🧁</div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-[#3D1F0D] truncate"><?= htmlspecialchars($r['nama']) ?></p>
                    <p class="text-[#9E7A5A] text-xs"><?= $r['jumlah_hasil'] ?> <?= htmlspecialchars($r['satuan_hasil']) ?></p>
                </div>
                <!-- Indikator pilih -->
                <div class="check-icon w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0
                            <?= $isSelected ? 'bg-[#7C4A2D] border-[#7C4A2D]' : 'border-[#DDD0C4]' ?>">
                    <?php if ($isSelected): ?>
                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Tidak ada hasil search -->
        <div id="searchEmpty" class="hidden text-center py-8 text-[#9E7A5A] text-sm">Resep tidak ditemukan.</div>

        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<!-- Tombol Lanjut fixed -->
<div class="btn-fixed-bottom">
    <button id="btnLanjut" onclick="lanjut()"
        class="btn-primary <?= empty($resepList) ? 'opacity-50 cursor-not-allowed' : '' ?>"
        <?= empty($resepList) ? 'disabled' : '' ?>>
        Lanjut
    </button>
</div>

<script src="/bakecost/assets/js/app.js"></script>
<script>
let selectedId = <?= $preselected ?: 'null' ?>;

function selectResep(id) {
    // Reset semua card
    document.querySelectorAll('.resep-card').forEach(card => {
        card.classList.remove('card-selected');
        card.classList.add('hover:shadow-md');
        const icon = card.querySelector('.check-icon');
        icon.className = icon.className.replace('bg-[#7C4A2D] border-[#7C4A2D]', 'border-[#DDD0C4]');
        icon.innerHTML = '';
    });

    // Aktifkan yang dipilih
    const chosen = document.querySelector(`.resep-card[data-id="${id}"]`);
    if (chosen) {
        chosen.classList.add('card-selected');
        chosen.classList.remove('hover:shadow-md');
        const icon = chosen.querySelector('.check-icon');
        icon.classList.add('bg-[#7C4A2D]', 'border-[#7C4A2D]');
        icon.classList.remove('border-[#DDD0C4]');
        icon.innerHTML = `<svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>`;
    }
    selectedId = id;
}

function lanjut() {
    if (!selectedId) {
        showToast('Pilih salah satu resep terlebih dahulu.');
        return;
    }
    window.location = `/bakecost/pages/konversi_jumlah.php?resep_id=${selectedId}`;
}

// Search filter
document.getElementById('searchInput')?.addEventListener('input', debounce(function (e) {
    const kw = e.target.value.trim().toLowerCase();
    let visible = 0;
    document.querySelectorAll('.resep-card').forEach(card => {
        const show = !kw || card.dataset.nama.includes(kw);
        card.classList.toggle('hidden', !show);
        if (show) visible++;
    });
    const empty = document.getElementById('searchEmpty');
    if (empty) empty.classList.toggle('hidden', visible > 0 || !kw);
}, 250));
</script>
</body>
</html>

