<?php
/**
 * pages/konversi.php — Step 1: Pilih Resep untuk Konversi Bahan.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

$stmt = $db->prepare('SELECT id, nama, jumlah_hasil, satuan_hasil FROM resep WHERE pengguna_id = ? ORDER BY nama ASC');
$stmt->execute([$userId]);
$resepList   = $stmt->fetchAll();
$preselected = (int) ($_GET['resep_id'] ?? 0);

$showBack    = true;
$pageTitle   = 'Konversi Bahan';
$backUrl     = '/bakecost/pages/beranda.php';
$activeNav   = 'konversi';
$steps       = ['Resep', 'Jumlah Produksi', 'Hasil'];
$currentStep = 1;
$htmlTitle   = 'Pilih Resep — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content page-content--with-fixed-cta max-w-2xl mx-auto">
        <?php require __DIR__ . '/../components/step_indicator.php'; ?>

        <section class="mb-5">
            <p class="eyebrow">Langkah 01</p>
            <h2 class="font-editorial text-2xl font-semibold text-ink mt-0.5">Pilih Resep</h2>
        </section>

        <div class="relative mb-5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-muted absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
            </svg>
            <input type="text" id="searchInput" class="input-field pl-11" placeholder="Cari resep..." autocomplete="off">
        </div>

        <?php if (empty($resepList)): ?>
            <section class="card text-center py-12">
                <p class="font-editorial text-lg font-semibold text-ink">Belum ada resep</p>
                <p class="text-muted text-sm mt-1">Tambahkan resep terlebih dahulu sebelum melakukan konversi.</p>
                <a href="/bakecost/pages/resep_tambah.php" class="btn-primary btn-sm mt-5">+ Tambah Resep</a>
            </section>
        <?php else: ?>
            <section class="space-y-2.5" id="resepList">
                <?php foreach ($resepList as $idx => $r):
                    $isSelected = ($r['id'] === $preselected);
                ?>
                    <button type="button"
                            class="resep-card card w-full text-left flex items-center justify-between gap-3 p-4 transition-all <?= $isSelected ? 'card-selected' : 'hover:border-[var(--border-strong)]' ?>"
                            data-id="<?= $r['id'] ?>"
                            data-nama="<?= strtolower(htmlspecialchars($r['nama'])) ?>"
                            onclick="selectResep(<?= $r['id'] ?>)">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <span class="text-xs font-semibold text-muted tabular-nums flex-shrink-0">
                                (<?= str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT) ?>)
                            </span>
                            <div class="min-w-0">
                                <p class="font-editorial text-base font-semibold text-ink truncate"><?= htmlspecialchars($r['nama']) ?></p>
                                <p class="text-xs text-muted tabular-nums">Yield: <?= $r['jumlah_hasil'] ?> <?= htmlspecialchars($r['satuan_hasil']) ?></p>
                            </div>
                        </div>
                        <span class="check-icon w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 <?= $isSelected ? 'bg-[var(--brand-espresso)] border-[var(--brand-espresso)]' : 'border-[var(--border-strong)]' ?>">
                            <?php if ($isSelected): ?>
                                <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            <?php endif; ?>
                        </span>
                    </button>
                <?php endforeach; ?>
            </section>

            <div id="searchEmpty" class="hidden card text-center py-8 text-muted text-sm mt-3">Resep tidak ditemukan.</div>

            <div class="btn-fixed-bottom">
                <button type="button" id="btnLanjut" onclick="lanjut()" class="btn-primary">Lanjut</button>
            </div>
        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
let selectedId = <?= $preselected ?: 'null' ?>;

function selectResep(id) {
    document.querySelectorAll('.resep-card').forEach(card => {
        card.classList.remove('card-selected');
        const icon = card.querySelector('.check-icon');
        icon.classList.remove('bg-[var(--brand-espresso)]', 'border-[var(--brand-espresso)]');
        icon.classList.add('border-[var(--border-strong)]');
        icon.innerHTML = '';
    });

    const chosen = document.querySelector(`.resep-card[data-id="${id}"]`);
    if (chosen) {
        chosen.classList.add('card-selected');
        const icon = chosen.querySelector('.check-icon');
        icon.classList.add('bg-[var(--brand-espresso)]', 'border-[var(--brand-espresso)]');
        icon.classList.remove('border-[var(--border-strong)]');
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

document.getElementById('searchInput')?.addEventListener('input', debounce(function (e) {
    const kw    = e.target.value.trim().toLowerCase();
    let visible = 0;
    document.querySelectorAll('.resep-card').forEach(card => {
        const show = !kw || card.dataset.nama.includes(kw);
        card.classList.toggle('hidden', !show);
        if (show) visible++;
    });
    const empty = document.getElementById('searchEmpty');
    if (empty) empty.classList.toggle('hidden', visible > 0 || !kw);
}, 220));
</script>
</body>
</html>
