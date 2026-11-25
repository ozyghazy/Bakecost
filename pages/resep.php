<?php
/**
 * pages/resep.php — Daftar semua resep milik pengguna.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

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

$activeNav = 'resep';
$htmlTitle = 'Resep Saya — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content space-y-5">

        <section class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="eyebrow">Koleksi Resep</p>
                <h1 class="editorial-title text-2xl sm:text-3xl font-semibold text-ink mt-0.5">Resep Saya</h1>
                <p class="text-xs text-muted mt-0.5 tabular-nums"><?= count($resepList) ?> resep tersimpan</p>
            </div>
            <div class="flex gap-2">
                <a href="/bakecost/pages/resep_ai.php" class="btn-secondary btn-sm">Generate AI</a>
                <a href="/bakecost/pages/resep_tambah.php" class="btn-primary btn-sm">+ Tambah Resep</a>
            </div>
        </section>

        <section class="relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-muted absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
            </svg>
            <input type="text" id="searchInput" class="input-field pl-11" placeholder="Cari resep..." autocomplete="off">
        </section>

        <?php if (empty($resepList)): ?>
            <section class="card text-center py-14">
                <p class="font-editorial text-xl font-semibold text-ink">Belum ada resep</p>
                <p class="text-muted text-sm mt-1.5 mb-6">Tambahkan resep pertamamu secara manual atau buat dengan asisten AI.</p>
                <div class="flex gap-2 justify-center">
                    <a href="/bakecost/pages/resep_tambah.php" class="btn-primary btn-sm">+ Tambah Manual</a>
                    <a href="/bakecost/pages/resep_ai.php" class="btn-secondary btn-sm">Generate AI</a>
                </div>
            </section>
        <?php else: ?>
            <section id="resepList" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($resepList as $idx => $r): ?>
                    <article class="resep-item bento-card cursor-pointer"
                             data-nama="<?= strtolower(htmlspecialchars($r['nama'])) ?>"
                             onclick="window.location='/bakecost/pages/resep_detail.php?id=<?= $r['id'] ?>'">
                        <div class="bento-card__top">
                            <div class="flex items-center gap-2">
                                <span class="bento-card__num">(<?= str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT) ?>)</span>
                                <?php if ($r['dibuat_via_ai']): ?>
                                    <span class="badge-ai">AI</span>
                                <?php endif; ?>
                            </div>
                            <a href="/bakecost/pages/konversi.php?resep_id=<?= $r['id'] ?>"
                               onclick="event.stopPropagation()"
                               class="bento-card__arrow"
                               title="Konversi resep ini"
                               aria-label="Konversi <?= htmlspecialchars($r['nama']) ?>">→</a>
                        </div>

                        <div>
                            <h2 class="bento-card__title truncate"><?= htmlspecialchars($r['nama']) ?></h2>
                            <p class="bento-card__desc tabular-nums">
                                <?= $r['jumlah_hasil'] ?> <?= htmlspecialchars($r['satuan_hasil']) ?> · <?= $r['jumlah_bahan'] ?> bahan
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <div id="searchEmpty" class="hidden card text-center py-10 text-muted text-sm">
                Resep "<span id="searchKeyword"></span>" tidak ditemukan.
            </div>
        <?php endif; ?>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<script src="/bakecost/assets/js/app.js"></script>
<script>
document.getElementById('searchInput')?.addEventListener('input', debounce(function (e) {
    const keyword = e.target.value.trim().toLowerCase();
    const items   = document.querySelectorAll('.resep-item');
    let visible   = 0;

    items.forEach(item => {
        const show = !keyword || (item.dataset.nama || '').includes(keyword);
        item.classList.toggle('hidden', !show);
        if (show) visible++;
    });

    const emptyEl = document.getElementById('searchEmpty');
    const kwEl    = document.getElementById('searchKeyword');
    if (emptyEl) {
        emptyEl.classList.toggle('hidden', visible > 0 || !keyword);
        if (kwEl) kwEl.textContent = e.target.value;
    }
}, 220));
</script>
</body>
</html>
