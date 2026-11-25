<?php
/**
 * pages/hitung_biaya.php — Rincian harga per bahan dan estimasi biaya total.
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
            rb.jumlah AS jumlah_asli
     FROM resep_bahan rb
     JOIN bahan_baku b ON b.id = rb.bahan_id
     WHERE rb.resep_id = ?
     ORDER BY b.nama ASC'
);
$bahanStmt->execute([$resepId]);
$bahanList = $bahanStmt->fetchAll();

$faktor       = $jumlahTarget / $resep['jumlah_hasil'];
$initialTotal = array_sum(array_map(fn($b) => ($b['jumlah_asli'] * $faktor) * $b['harga_per_satuan'], $bahanList));
$initialUnit  = $jumlahTarget > 0 ? $initialTotal / $jumlahTarget : 0;

function fmtJml(float $n): string
{
    if ($n == floor($n)) return number_format($n, 0, ',', '.');
    return rtrim(rtrim(number_format($n, 3, ',', '.'), '0'), ',');
}

$showBack    = true;
$pageTitle   = 'Hitung Biaya';
$backUrl     = "/bakecost/pages/konversi_hasil.php?resep_id={$resepId}&jumlah={$jumlahTarget}";
$activeNav   = 'biaya';
$steps       = ['Resep', 'Jumlah Produksi', 'Biaya'];
$currentStep = 3;
$htmlTitle   = 'Hitung Biaya — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content space-y-5">
        <?php require __DIR__ . '/../components/step_indicator.php'; ?>

        <div class="editorial-split">
            <!-- Kiri: Rincian Harga Bahan -->
            <section class="list-card">
                <div class="list-card__header">
                    <span>Rincian Harga Bahan</span>
                    <button type="button" onclick="toggleEditHarga()" id="btnUbahHarga" class="btn-action">
                        Ubah Harga
                    </button>
                </div>

                <?php if (empty($bahanList)): ?>
                    <div class="p-8 text-center text-muted text-sm">Resep belum memiliki bahan.</div>
                <?php else: ?>
                    <div id="bahanRows">
                        <?php foreach ($bahanList as $i => $b):
                            $jumlahK  = $b['jumlah_asli'] * $faktor;
                            $hargaPs  = (float) $b['harga_per_satuan'];
                            $subtotal = $jumlahK * $hargaPs;
                        ?>
                            <div class="list-row bahan-row" data-jumlah="<?= $jumlahK ?>" data-idx="<?= $i ?>">
                                <div class="list-row__main">
                                    <p class="list-row__title"><?= htmlspecialchars($b['bahan_nama']) ?></p>
                                    <p class="list-row__meta">
                                        <?= fmtJml($jumlahK) ?> <?= htmlspecialchars($b['satuan']) ?> ·
                                        <span class="view-harga">
                                            Rp <?= number_format($hargaPs, 0, ',', '.') ?>/<?= htmlspecialchars($b['satuan']) ?>
                                        </span>
                                    </p>
                                </div>

                                <div class="list-row__aside">
                                    <input type="number"
                                           class="edit-harga input-field w-28 text-xs py-1.5 hidden tabular-nums"
                                           value="<?= $hargaPs ?>"
                                           min="0"
                                           step="any"
                                           data-satuan="<?= htmlspecialchars($b['satuan']) ?>"
                                           oninput="updateSubtotal(this, <?= $jumlahK ?>)">
                                    <p class="list-row__price list-row__price--ink subtotal" data-value="<?= $subtotal ?>">
                                        Rp <?= number_format($subtotal, 0, ',', '.') ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Kanan: Ringkasan Total & Aksi (Sticky di Desktop) -->
            <aside class="editorial-split__sticky space-y-4">
                <section class="card-accent">
                    <p class="eyebrow">Estimasi Biaya</p>
                    <h2 class="font-editorial text-2xl font-semibold text-ink mt-0.5"><?= htmlspecialchars($resep['nama']) ?></h2>
                    <p class="text-sm text-secondary tabular-nums mt-1">
                        Target: <?= fmtJml($jumlahTarget) ?> <?= htmlspecialchars($resep['satuan_hasil']) ?>
                    </p>
                </section>

                <section class="card-espresso space-y-1">
                    <p class="text-xs uppercase tracking-wider opacity-75">Total Estimasi Bahan Baku</p>
                    <p id="totalLabel" class="font-editorial text-3xl font-semibold text-[var(--accent-crust)] tabular-nums">
                        Rp <?= number_format($initialTotal, 0, ',', '.') ?>
                    </p>
                    <p class="text-xs opacity-70 tabular-nums">
                        <?= fmtJml($jumlahTarget) ?> <?= htmlspecialchars($resep['satuan_hasil']) ?> ·
                        <span id="perUnitLabel">
                            Rp <?= number_format($initialUnit, 0, ',', '.') ?>/<?= htmlspecialchars($resep['satuan_hasil']) ?>
                        </span>
                    </p>
                </section>

                <div class="space-y-2.5">
                    <button type="button" onclick="simpanBiaya(this)" class="btn-primary">Simpan ke Riwayat</button>
                    <a href="/bakecost/pages/hpp_kalkulator.php?resep_id=<?= $resepId ?>&jumlah=<?= $jumlahTarget ?>" class="btn-secondary">
                        Hitung HPP Lengkap →
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
const RESEP_ID      = <?= $resepId ?>;
const JUMLAH_TARGET = <?= $jumlahTarget ?>;
const SATUAN_HASIL  = <?= json_encode($resep['satuan_hasil']) ?>;
let isEditingHarga  = false;

function toggleEditHarga() {
    isEditingHarga = !isEditingHarga;
    document.querySelectorAll('.view-harga').forEach(el => el.classList.toggle('hidden', isEditingHarga));
    document.querySelectorAll('.edit-harga').forEach(el => el.classList.toggle('hidden', !isEditingHarga));
    document.getElementById('btnUbahHarga').textContent = isEditingHarga ? 'Selesai' : 'Ubah Harga';
}

function updateSubtotal(input, jumlah) {
    const harga    = parseFloat(input.value) || 0;
    const subtotal = jumlah * harga;
    const row      = input.closest('.bahan-row');
    const subEl    = row.querySelector('.subtotal');
    subEl.dataset.value = subtotal;
    subEl.textContent   = formatRupiah(subtotal);

    const viewHarga = row.querySelector('.view-harga');
    if (viewHarga) {
        viewHarga.textContent = `${formatRupiah(harga)}/${input.dataset.satuan}`;
    }
    updateTotal();
}

function updateTotal() {
    let total = 0;
    document.querySelectorAll('.subtotal').forEach(el => {
        total += parseFloat(el.dataset.value) || 0;
    });
    document.getElementById('totalLabel').textContent = formatRupiah(total);

    const perUnit = JUMLAH_TARGET > 0 ? total / JUMLAH_TARGET : 0;
    document.getElementById('perUnitLabel').textContent = `${formatRupiah(perUnit)}/${SATUAN_HASIL}`;
}

async function simpanBiaya(btn) {
    let biayaBahan = 0;
    document.querySelectorAll('.subtotal').forEach(el => {
        biayaBahan += parseFloat(el.dataset.value) || 0;
    });

    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        await apiFetch('/bakecost/api/hpp.php', {
            method: 'POST',
            body: {
                resep_id:           RESEP_ID,
                jumlah_produksi:    JUMLAH_TARGET,
                biaya_bahan:        biayaBahan,
                biaya_tenaga_kerja: 0,
                biaya_overhead:     0,
                packaging_ids:      [],
                tipe:               'biaya',
            },
        });
        showToast('Perhitungan disimpan ke Riwayat');
        setTimeout(() => window.location = '/bakecost/pages/riwayat.php', 1000);
    } catch (err) {
        showToast(err.message);
        btn.disabled = false;
        btn.textContent = 'Simpan ke Riwayat';
    }
}
</script>
</body>
</html>
