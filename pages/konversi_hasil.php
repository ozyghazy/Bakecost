<?php
/**
 * pages/konversi_hasil.php — Step 3: Hasil Konversi Bahan
 * Sesuai desain "Hasil Konversi.png": tabel bahan dengan jumlah asli & konversi,
 * tombol "Hitung Biaya" dan "Simpan".
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

$resepId      = (int) ($_GET['resep_id'] ?? 0);
$jumlahTarget = (float) ($_GET['jumlah'] ?? 0);

if (!$resepId || $jumlahTarget <= 0) {
    header('Location: /bakecost/pages/konversi.php');
    exit;
}

// Ambil resep
$stmt = $db->prepare('SELECT id, nama, jumlah_hasil, satuan_hasil FROM resep WHERE id = ? AND pengguna_id = ?');
$stmt->execute([$resepId, $userId]);
$resep = $stmt->fetch();
if (!$resep) { header('Location: /bakecost/pages/konversi.php'); exit; }

// Ambil bahan dan hitung konversi di sisi server (konsisten dengan API)
$bahanStmt = $db->prepare(
    'SELECT rb.bahan_id, b.nama AS bahan_nama, b.satuan, b.harga_per_satuan,
            rb.jumlah AS jumlah_asli, rb.catatan
     FROM resep_bahan rb
     JOIN bahan_baku b ON b.id = rb.bahan_id
     WHERE rb.resep_id = ?
     ORDER BY b.nama ASC'
);
$bahanStmt->execute([$resepId]);
$bahanList = $bahanStmt->fetchAll();

$faktor     = $jumlahTarget / $resep['jumlah_hasil'];
$biayaTotal = 0;

// Hitung setiap bahan
$bahanKonversi = [];
foreach ($bahanList as $b) {
    $jumlahK  = $b['jumlah_asli'] * $faktor;
    $biaya    = $jumlahK * $b['harga_per_satuan'];
    $biayaTotal += $biaya;
    $bahanKonversi[] = array_merge($b, [
        'jumlah_konversi' => $jumlahK,
        'biaya'           => $biaya,
    ]);
}

/** Format angka bahan: hapus trailing zero */
function fmtJml(float $n): string {
    if ($n == floor($n)) return number_format($n, 0, ',', '.');
    return rtrim(rtrim(number_format($n, 3, ',', '.'), '0'), ',');
}

$showBack  = true;
$pageTitle = 'Hasil Konversi Bahan';
$backUrl   = "/bakecost/pages/konversi_jumlah.php?resep_id={$resepId}";
$activeNav = 'konversi';

$steps       = ['Resep', 'Jumlah Produksi', 'Hasil'];
$currentStep = 3;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Konversi — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">
        <?php require __DIR__ . '/../components/step_indicator.php'; ?>

        <!-- Info resep -->
        <div class="card flex items-center gap-3 mb-4">
            <div class="w-14 h-14 rounded-xl bg-[#EAD9C8] flex items-center justify-center text-2xl flex-shrink-0">🧁</div>
            <div>
                <p class="font-bold text-[#3D1F0D]"><?= htmlspecialchars($resep['nama']) ?></p>
                <p class="text-[#9E7A5A] text-sm">
                    <?= fmtJml($jumlahTarget) ?> <?= htmlspecialchars($resep['satuan_hasil']) ?>
                    (<?= fmtJml($faktor) ?>× resep asli)
                </p>
            </div>
        </div>

        <!-- Tabel hasil konversi -->
        <div class="card mb-4 p-0">
            <!-- Header tabel -->
            <div class="flex justify-between px-4 py-3 bg-[#F9F4EF] rounded-t-2xl">
                <span class="text-[11px] font-bold text-[#9E7A5A] uppercase tracking-wide">Bahan</span>
                <span class="text-[11px] font-bold text-[#9E7A5A] uppercase tracking-wide">Jumlah</span>
            </div>

            <?php if (empty($bahanKonversi)): ?>
            <div class="px-4 py-6 text-center text-[#9E7A5A] text-sm">
                Resep ini belum memiliki bahan. <a href="/bakecost/pages/resep_detail.php?id=<?= $resepId ?>" class="text-[#7C4A2D] underline">Tambah bahan →</a>
            </div>
            <?php else: ?>
            <div class="divide-y divide-[#F0E8DF]">
                <?php foreach ($bahanKonversi as $b): ?>
                <div class="flex items-start justify-between px-4 py-3.5">
                    <div class="flex-1 pr-4">
                        <p class="font-medium text-[#3D1F0D] text-sm"><?= htmlspecialchars($b['bahan_nama']) ?></p>
                        <?php if ($b['catatan']): ?>
                        <p class="text-[#9E7A5A] text-xs"><?= htmlspecialchars($b['catatan']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-bold text-[#3D1F0D] text-sm">
                            <?= fmtJml($b['jumlah_konversi']) ?> <?= $b['satuan'] ?>
                        </p>
                        <p class="text-[#9E7A5A] text-xs">dari <?= fmtJml($b['jumlah_asli']) ?> <?= $b['satuan'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Estimasi total biaya bahan -->
        <?php if ($biayaTotal > 0): ?>
        <div class="card bg-[#FDE8D5] mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-semibold text-[#5A2D0C]">Estimasi Biaya Bahan</p>
                    <p class="text-xs text-[#9E7A5A]">Untuk <?= fmtJml($jumlahTarget) ?> <?= htmlspecialchars($resep['satuan_hasil']) ?></p>
                </div>
                <p class="text-xl font-bold text-[#7C4A2D]">Rp <?= number_format($biayaTotal, 0, ',', '.') ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tombol aksi bawah (2 tombol sesuai desain) -->
        <div class="grid grid-cols-2 gap-3 mb-4">
            <a href="/bakecost/pages/hitung_biaya.php?resep_id=<?= $resepId ?>&jumlah=<?= $jumlahTarget ?>"
               class="btn-secondary text-center">
                Hitung Biaya
            </a>
            <button onclick="simpanKonversi()" class="btn-primary">Simpan</button>
        </div>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
// Simpan konversi ke riwayat (memanggil endpoint HPP dengan hanya biaya_bahan)
async function simpanKonversi() {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';
    try {
        await apiFetch('/bakecost/api/hpp.php', {
            method: 'POST',
            body: {
                resep_id:          <?= $resepId ?>,
                jumlah_produksi:   <?= $jumlahTarget ?>,
                biaya_tenaga_kerja: 0,
                biaya_overhead:    0,
                packaging_ids:     [],
                tipe:              'konversi'  // tandai sebagai simpan konversi saja
            }
        });
        showToast('Konversi disimpan ke Riwayat ✓');
        setTimeout(() => window.location = '/bakecost/pages/riwayat.php', 1200);
    } catch (err) {
        showToast('⚠ ' + err.message);
        btn.disabled = false;
        btn.textContent = 'Simpan';
    }
}
</script>
</body>
</html>

