<?php
/**
 * pages/konversi_hasil.php — Step 3: Tabel Hasil Konversi Bahan.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db           = getDB();
$userId       = currentUserId();
$resepId      = (int) ($_GET['resep_id'] ?? 0);
$jumlahTarget = (float) ($_GET['jumlah'] ?? 0);

if (!$resepId || $jumlahTarget <= 0) {
    header('Location: /bakecost/pages/konversi.php');
    exit;
}

$stmt = $db->prepare('SELECT id, nama, jumlah_hasil, satuan_hasil FROM resep WHERE id = ? AND pengguna_id = ?');
$stmt->execute([$resepId, $userId]);
$resep = $stmt->fetch();
if (!$resep) {
    header('Location: /bakecost/pages/konversi.php');
    exit;
}

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

$faktor        = $jumlahTarget / $resep['jumlah_hasil'];
$biayaTotal    = 0;
$bahanKonversi = [];

foreach ($bahanList as $b) {
    $jumlahK     = $b['jumlah_asli'] * $faktor;
    $biaya       = $jumlahK * $b['harga_per_satuan'];
    $biayaTotal += $biaya;
    $bahanKonversi[] = array_merge($b, [
        'jumlah_konversi' => $jumlahK,
        'biaya'           => $biaya,
    ]);
}

function fmtJml(float $n): string
{
    if ($n == floor($n)) return number_format($n, 0, ',', '.');
    return rtrim(rtrim(number_format($n, 3, ',', '.'), '0'), ',');
}

$showBack    = true;
$pageTitle   = 'Hasil Konversi';
$backUrl     = "/bakecost/pages/konversi_jumlah.php?resep_id={$resepId}";
$activeNav   = 'konversi';
$steps       = ['Resep', 'Jumlah Produksi', 'Hasil'];
$currentStep = 3;
$htmlTitle   = 'Hasil Konversi — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content space-y-5">
        <?php require __DIR__ . '/../components/step_indicator.php'; ?>

        <div class="editorial-split">
            <section class="list-card">
                <div class="list-card__header">
                    <span>Bahan</span>
                    <span>Takaran Baru</span>
                </div>

                <?php if (empty($bahanKonversi)): ?>
                    <div class="p-8 text-center text-muted text-sm">
                        Resep ini belum memiliki bahan.
                        <a href="/bakecost/pages/resep_detail.php?id=<?= $resepId ?>" class="text-ink font-semibold underline">Tambah bahan →</a>
                    </div>
                <?php else: ?>
                    <div>
                        <?php foreach ($bahanKonversi as $b): ?>
                            <div class="list-row">
                                <div class="list-row__main">
                                    <p class="list-row__title"><?= htmlspecialchars($b['bahan_nama']) ?></p>
                                    <?php if ($b['catatan']): ?>
                                        <p class="list-row__meta"><?= htmlspecialchars($b['catatan']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="list-row__stack">
                                    <p class="list-row__price list-row__price--ink">
                                        <?= fmtJml($b['jumlah_konversi']) ?> <?= htmlspecialchars($b['satuan']) ?>
                                    </p>
                                    <p class="list-row__meta">
                                        dari <?= fmtJml($b['jumlah_asli']) ?> <?= htmlspecialchars($b['satuan']) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="editorial-split__sticky space-y-4">
                <section class="card-accent">
                    <p class="eyebrow">Target Produksi</p>
                    <h2 class="font-editorial text-2xl font-semibold text-ink mt-0.5"><?= htmlspecialchars($resep['nama']) ?></h2>
                    <p class="text-sm text-secondary tabular-nums mt-1">
                        <?= fmtJml($jumlahTarget) ?> <?= htmlspecialchars($resep['satuan_hasil']) ?> (<?= fmtJml($faktor) ?>× resep asli)
                    </p>
                </section>

                <?php if ($biayaTotal > 0): ?>
                    <section class="card-espresso space-y-1">
                        <p class="text-xs uppercase tracking-wider opacity-75">Estimasi Biaya Bahan</p>
                        <p class="font-editorial text-3xl font-semibold text-[var(--accent-crust)] tabular-nums">
                            Rp <?= number_format($biayaTotal, 0, ',', '.') ?>
                        </p>
                        <p class="text-xs opacity-70 tabular-nums">
                            Untuk <?= fmtJml($jumlahTarget) ?> <?= htmlspecialchars($resep['satuan_hasil']) ?>
                        </p>
                    </section>
                <?php endif; ?>

                <div class="grid grid-cols-2 lg:grid-cols-1 gap-3">
                    <button type="button" onclick="simpanKonversi(this)" class="btn-primary">Simpan ke Riwayat</button>
                    <a href="/bakecost/pages/hitung_biaya.php?resep_id=<?= $resepId ?>&jumlah=<?= $jumlahTarget ?>" class="btn-secondary">
                        Hitung Biaya →
                    </a>
                </div>
            </aside>
        </div>
    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
async function simpanKonversi(btn) {
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';
    try {
        await apiFetch('/bakecost/api/hpp.php', {
            method: 'POST',
            body: {
                resep_id:           <?= $resepId ?>,
                jumlah_produksi:    <?= $jumlahTarget ?>,
                biaya_tenaga_kerja: 0,
                biaya_overhead:     0,
                packaging_ids:      [],
                tipe:               'konversi',
            },
        });
        showToast('Konversi disimpan ke Riwayat');
        setTimeout(() => window.location = '/bakecost/pages/riwayat.php', 1000);
    } catch (err) {
        showToast(err.message);
        btn.disabled = false;
        btn.textContent = 'Simpan';
    }
}
</script>
</body>
</html>
