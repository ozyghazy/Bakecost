<?php
/**
 * pages/hpp_perbandingan.php
 * Perbandingan HPP dan margin terbaru antar resep milik user.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

$stmt = $db->prepare('
    SELECT r.id AS resep_id, r.nama, r.satuan_hasil,
           p.hpp_total, p.hpp_per_unit, p.margin_persen, p.harga_jual,
           p.jumlah_produksi, p.created_at
    FROM resep r
    JOIN (
        SELECT p1.*
        FROM produksi p1
        INNER JOIN (
            SELECT resep_id, MAX(created_at) AS max_created
            FROM produksi
            WHERE tipe = \'biaya\'
            GROUP BY resep_id
        ) p2 ON p1.resep_id = p2.resep_id AND p1.created_at = p2.max_created
    ) p ON r.id = p.resep_id
    WHERE r.pengguna_id = ?
    ORDER BY p.hpp_per_unit ASC
');
$stmt->execute([$userId]);
$perbandinganList = $stmt->fetchAll();

$showBack  = true;
$pageTitle = 'Perbandingan HPP';
$backUrl   = '/bakecost/pages/hpp_kalkulator.php';
$activeNav = 'biaya';
$htmlTitle = 'Perbandingan HPP — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content space-y-5">

        <section>
            <p class="eyebrow">Analisis Biaya</p>
            <h2 class="font-editorial text-2xl font-semibold text-ink mt-0.5">Komparasi HPP Antar Resep</h2>
            <p class="text-xs text-muted mt-1">Diurutkan dari HPP per unit terendah ke tertinggi berdasarkan perhitungan terakhir.</p>
        </section>

        <?php if (empty($perbandinganList)): ?>
            <section class="card text-center py-12">
                <p class="font-editorial text-lg font-semibold text-ink">Belum ada perhitungan HPP</p>
                <p class="text-xs text-muted mt-1">Simpan minimal satu perhitungan di Kalkulator HPP untuk melihat perbandingan.</p>
                <a href="/bakecost/pages/hpp_kalkulator.php" class="btn-primary btn-sm mt-5">
                    Buka Kalkulator HPP
                </a>
            </section>
        <?php else: ?>
            <section class="grid sm:grid-cols-2 gap-4">
                <?php foreach ($perbandinganList as $idx => $item): ?>
                    <article class="card space-y-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs font-semibold text-muted tabular-nums">
                                    (<?= str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT) ?>)
                                </span>
                                <h3 class="font-editorial text-lg font-semibold text-ink mt-0.5">
                                    <?= htmlspecialchars($item['nama']) ?>
                                </h3>
                            </div>
                            <span class="badge-biaya tabular-nums">
                                <?= $item['jumlah_produksi'] ?> <?= htmlspecialchars($item['satuan_hasil']) ?>
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-[var(--border-hairline)] text-xs tabular-nums">
                            <div>
                                <span class="text-muted block">HPP / Unit</span>
                                <span class="font-bold text-accent text-base">
                                    Rp <?= number_format($item['hpp_per_unit'], 0, ',', '.') ?>
                                </span>
                            </div>
                            <div>
                                <span class="text-muted block">HPP Total Batch</span>
                                <span class="font-semibold text-ink text-sm">
                                    Rp <?= number_format($item['hpp_total'], 0, ',', '.') ?>
                                </span>
                            </div>
                            <div>
                                <span class="text-muted block">Margin</span>
                                <span class="font-semibold text-ink">
                                    <?= $item['margin_persen'] ? $item['margin_persen'] . '%' : '—' ?>
                                </span>
                            </div>
                            <div>
                                <span class="text-muted block">Rekomendasi Jual</span>
                                <span class="font-semibold text-[var(--state-success-text)]">
                                    <?= $item['harga_jual'] ? 'Rp ' . number_format($item['harga_jual'], 0, ',', '.') : '—' ?>
                                </span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>
</body>
</html>
