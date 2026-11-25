<?php
/**
 * pages/beranda.php — Dashboard utama BakeCost.
 * Menampilkan hero greeting, bento grid fitur bernomor (01)-(04), dan resep terbaru.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

// Ambil 6 resep terbaru milik user
$stmt = $db->prepare('SELECT id, nama, jumlah_hasil, satuan_hasil, dibuat_via_ai FROM resep WHERE pengguna_id = ? ORDER BY created_at DESC LIMIT 6');
$stmt->execute([$userId]);
$resepList = $stmt->fetchAll();

// Hitung ringkasan statistik untuk frosted glass panel
$stmtResep = $db->prepare('SELECT COUNT(*) FROM resep WHERE pengguna_id = ?');
$stmtResep->execute([$userId]);
$jumlahResep = (int) $stmtResep->fetchColumn();

$stmtBahan = $db->prepare('SELECT COUNT(*) FROM bahan_baku WHERE pengguna_id = ?');
$stmtBahan->execute([$userId]);
$jumlahBahan = (int) $stmtBahan->fetchColumn();

$activeNav = 'beranda';
$htmlTitle = 'Beranda — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content space-y-8">

        <!-- Hero Editorial Banner + Frosted Glass Stats -->
        <section class="editorial-hero">
            <div class="grid md:grid-cols-12 gap-5 items-center">
                <div class="md:col-span-7 space-y-2">
                    <p class="eyebrow">Ruang Kerja Baking</p>
                    <h1 class="editorial-title text-2xl sm:text-3xl font-semibold text-ink leading-tight">
                        Halo, <?= currentUserName() ?>.
                    </h1>
                    <p class="text-sm text-secondary max-w-md">
                        Konversi takaran adonan sesuai target produksi dan pantau estimasi HPP setiap resep dengan akurat.
                    </p>
                </div>

                <div class="md:col-span-5">
                    <div class="glass-panel p-4 grid grid-cols-2 gap-3">
                        <div>
                            <p class="eyebrow">Resep</p>
                            <p class="font-editorial text-2xl font-semibold text-ink tabular-nums mt-0.5"><?= $jumlahResep ?></p>
                            <p class="text-xs text-muted">Tersimpan</p>
                        </div>
                        <div class="border-l border-[var(--border-hairline)] pl-3">
                            <p class="eyebrow">Bahan Baku</p>
                            <p class="font-editorial text-2xl font-semibold text-accent tabular-nums mt-0.5"><?= $jumlahBahan ?></p>
                            <p class="text-xs text-muted">Terdaftar</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Bento Grid Fitur Bernomor (01) - (04) -->
        <section>
            <div class="flex items-center justify-between mb-3">
                <p class="eyebrow">Alat Produksi</p>
                <a href="/bakecost/pages/hpp_perbandingan.php" class="text-xs font-semibold text-accent hover:underline">
                    Perbandingan HPP →
                </a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
                <a href="/bakecost/pages/konversi.php" class="bento-card">
                    <div class="bento-card__top">
                        <span class="bento-card__num">(01)</span>
                        <span class="bento-card__arrow" aria-hidden="true">→</span>
                    </div>
                    <div>
                        <h2 class="bento-card__title">Konversi Bahan</h2>
                        <p class="bento-card__desc">Sesuaikan takaran resep dengan jumlah produksi.</p>
                    </div>
                </a>

                <a href="/bakecost/pages/hpp_kalkulator.php" class="bento-card">
                    <div class="bento-card__top">
                        <span class="bento-card__num">(02)</span>
                        <span class="bento-card__arrow" aria-hidden="true">→</span>
                    </div>
                    <div>
                        <h2 class="bento-card__title">Hitung HPP</h2>
                        <p class="bento-card__desc">Estimasi biaya bahan, kemasan &amp; harga jual.</p>
                    </div>
                </a>

                <a href="/bakecost/pages/bahan_baku.php" class="bento-card">
                    <div class="bento-card__top">
                        <span class="bento-card__num">(03)</span>
                        <span class="bento-card__arrow" aria-hidden="true">→</span>
                    </div>
                    <div>
                        <h2 class="bento-card__title">Bahan Baku</h2>
                        <p class="bento-card__desc">Kelola katalog bahan &amp; harga per satuan.</p>
                    </div>
                </a>

                <a href="/bakecost/pages/bake_loss.php" class="bento-card">
                    <div class="bento-card__top">
                        <span class="bento-card__num">(04)</span>
                        <span class="bento-card__arrow" aria-hidden="true">→</span>
                    </div>
                    <div>
                        <h2 class="bento-card__title">Bake Loss</h2>
                        <p class="bento-card__desc">Hitung persen penyusutan berat pasca panggang.</p>
                    </div>
                </a>
            </div>
        </section>

        <!-- Resep Terbaru / Populer -->
        <section>
            <div class="flex items-end justify-between mb-4">
                <div>
                    <p class="eyebrow">Katalog Resep</p>
                    <h2 class="font-editorial text-xl font-semibold text-ink mt-0.5">Resep Terbaru</h2>
                </div>
                <a href="/bakecost/pages/resep.php" class="text-xs font-semibold text-ink hover:text-accent transition-colors">
                    Lihat Semua →
                </a>
            </div>

            <?php if (empty($resepList)): ?>
                <div class="card text-center py-10">
                    <p class="font-editorial text-lg font-semibold text-ink">Belum ada resep</p>
                    <p class="text-muted text-sm mt-1">Tambahkan resep pertamamu untuk mulai konversi dan hitung HPP.</p>
                    <div class="mt-5 flex justify-center gap-2">
                        <a href="/bakecost/pages/resep_tambah.php" class="btn-primary btn-sm">+ Tambah Resep</a>
                        <a href="/bakecost/pages/resep_ai.php" class="btn-secondary btn-sm">Generate AI</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                    <?php foreach ($resepList as $idx => $resep): ?>
                        <a href="/bakecost/pages/resep_detail.php?id=<?= $resep['id'] ?>" class="card card-interactive p-4 flex flex-col justify-between gap-4">
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-xs font-semibold text-muted tabular-nums">
                                    (<?= str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT) ?>)
                                </span>
                                <?php if ($resep['dibuat_via_ai']): ?>
                                    <span class="badge-ai">AI</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="font-editorial text-base font-semibold text-ink truncate">
                                    <?= htmlspecialchars($resep['nama']) ?>
                                </h3>
                                <p class="text-xs text-muted mt-1 tabular-nums">
                                    Yield: <?= $resep['jumlah_hasil'] ?> <?= htmlspecialchars($resep['satuan_hasil']) ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<script src="/bakecost/assets/js/app.js"></script>
</body>
</html>
