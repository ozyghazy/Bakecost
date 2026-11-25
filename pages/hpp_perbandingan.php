<?php
/**
 * pages/hpp_perbandingan.php
 * Perbandingan HPP dan margin antar resep produksi milik user.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

// Query perbandingan HPP terbaru per resep
$stmt = $db->prepare('
    SELECT r.id AS resep_id, r.nama, r.satuan_hasil,
           p.hpp_total, p.hpp_per_unit, p.margin_persen, p.harga_jual,
           p.jumlah_produksi, p.created_at
    FROM resep r
    JOIN (
        SELECT p1.*
        FROM produksi p1
        INNER JOIN (
            SELECT resep_id, MAX(created_at) as max_created
            FROM produksi
            WHERE tipe = "biaya"
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbandingan HPP — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">
        <div class="mb-4">
            <h2 class="text-base font-bold text-[#3D1F0D]">Komparasi HPP Resep</h2>
            <p class="text-xs text-[#9E7A5A]">Diurutkan dari HPP per unit terendah ke tertinggi.</p>
        </div>

        <?php if (empty($perbandinganList)): ?>
        <div class="card text-center py-10">
            <div class="text-4xl mb-2">📊</div>
            <p class="font-semibold text-sm text-[#7C4A2D]">Belum ada perhitungan HPP</p>
            <p class="text-xs text-[#9E7A5A] mt-1">Lakukan perhitungan di Kalkulator HPP terlebih dahulu.</p>
            <a href="/bakecost/pages/hpp_kalkulator.php" class="btn-primary mt-4 py-2.5 text-xs max-w-xs mx-auto">
                Buka Kalkulator HPP
            </a>
        </div>
        <?php else: ?>
        <div class="space-y-3 mb-6">
            <?php foreach ($perbandinganList as $idx => $item): ?>
            <div class="card p-4">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#F5E0CE] text-[#7C4A2D] text-xs font-bold flex items-center justify-center">
                            #<?= $idx + 1 ?>
                        </span>
                        <h3 class="font-bold text-sm text-[#3D1F0D]"><?= htmlspecialchars($item['nama']) ?></h3>
                    </div>
                    <span class="text-xs bg-[#FDE8D5] text-[#7C4A2D] px-2 py-0.5 rounded-full font-semibold">
                        Batch <?= $item['jumlah_produksi'] ?> <?= htmlspecialchars($item['satuan_hasil']) ?>
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-[#F0E8DF] text-xs">
                    <div>
                        <span class="text-[#9E7A5A] block">HPP / Unit</span>
                        <span class="font-bold text-[#7C4A2D] text-sm">
                            Rp <?= number_format($item['hpp_per_unit'], 0, ',', '.') ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-[#9E7A5A] block">HPP Total Batch</span>
                        <span class="font-semibold text-[#3D1F0D]">
                            Rp <?= number_format($item['hpp_total'], 0, ',', '.') ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-[#9E7A5A] block">Margin Keuntungan</span>
                        <span class="font-semibold text-[#3D1F0D]">
                            <?= $item['margin_persen'] ? $item['margin_persen'] . '%' : '—' ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-[#9E7A5A] block">Rekomendasi Jual</span>
                        <span class="font-semibold text-emerald-700">
                            <?= $item['harga_jual'] ? 'Rp ' . number_format($item['harga_jual'], 0, ',', '.') : '—' ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>
</body>
</html>

