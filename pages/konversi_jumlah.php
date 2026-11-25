<?php
/**
 * pages/konversi_jumlah.php — Step 2: Input Jumlah Produksi Target.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db      = getDB();
$userId  = currentUserId();
$resepId = (int) ($_GET['resep_id'] ?? 0);

if (!$resepId) {
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

$showBack    = true;
$pageTitle   = 'Konversi Bahan';
$backUrl     = '/bakecost/pages/konversi.php';
$activeNav   = 'konversi';
$steps       = ['Resep', 'Jumlah Produksi', 'Hasil'];
$currentStep = 2;
$htmlTitle   = 'Jumlah Produksi — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content page-content--with-fixed-cta max-w-2xl mx-auto space-y-5">
        <?php require __DIR__ . '/../components/step_indicator.php'; ?>

        <section class="card-accent flex items-center justify-between gap-3">
            <div>
                <p class="eyebrow">Resep Terpilih</p>
                <h2 class="font-editorial text-xl font-semibold text-ink mt-0.5"><?= htmlspecialchars($resep['nama']) ?></h2>
                <p class="text-xs text-secondary tabular-nums mt-0.5">
                    Resep asli untuk <?= $resep['jumlah_hasil'] ?> <?= htmlspecialchars($resep['satuan_hasil']) ?>
                </p>
            </div>
        </section>

        <section class="card space-y-4">
            <label class="form-label" for="jumlahInput">Target Jumlah Produksi</label>

            <div class="flex gap-2.5 items-center">
                <input
                    type="number"
                    id="jumlahInput"
                    class="input-field flex-1 text-2xl font-semibold tabular-nums"
                    placeholder="0"
                    min="0.1"
                    step="any"
                    value="<?= $resep['jumlah_hasil'] ?>"
                    oninput="updateInfo()"
                >
                <span class="px-4 py-3 rounded-2xl bg-[var(--bg-subtle)] border border-[var(--border-hairline)] text-sm font-medium text-secondary">
                    <?= htmlspecialchars($resep['satuan_hasil']) ?>
                </span>
            </div>

            <div class="flex gap-2 flex-wrap">
                <?php foreach ([10, 20, 30, 50, 100] as $preset): ?>
                    <button type="button"
                            class="preset-chip chip <?= $preset == $resep['jumlah_hasil'] ? 'chip-active' : '' ?>"
                            onclick="setPreset(<?= $preset ?>)">
                        <?= $preset ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="alert-info space-y-1" id="infoBox">
                <p>Resep asli: <strong class="tabular-nums"><?= $resep['jumlah_hasil'] ?> <?= htmlspecialchars($resep['satuan_hasil']) ?></strong></p>
                <p>Dikonversi menjadi: <strong id="infoTarget" class="tabular-nums"><?= $resep['jumlah_hasil'] ?> <?= htmlspecialchars($resep['satuan_hasil']) ?></strong></p>
                <span id="infoBadge" class="badge-biaya mt-1">Pengali 1× lipat</span>
            </div>
        </section>

        <div class="btn-fixed-bottom">
            <button type="button" onclick="lihatHasil()" class="btn-primary">Lihat Hasil Konversi</button>
        </div>
    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
const RESEP_ID    = <?= $resepId ?>;
const JUMLAH_ASLI = <?= $resep['jumlah_hasil'] ?>;
const SATUAN      = <?= json_encode($resep['satuan_hasil']) ?>;

function updateInfo() {
    const val    = parseFloat(document.getElementById('jumlahInput').value) || 0;
    const faktor = val > 0 ? (val / JUMLAH_ASLI) : 0;

    document.getElementById('infoTarget').textContent = `${val} ${SATUAN}`;

    const badge = document.getElementById('infoBadge');
    if (faktor > 0) {
        const faktorStr = Number.isInteger(faktor) ? `${faktor}×` : faktor.toFixed(2) + '×';
        badge.textContent = `Pengali ${faktorStr} lipat`;
    }

    document.querySelectorAll('.preset-chip').forEach(chip => {
        chip.classList.toggle('chip-active', parseFloat(chip.textContent) === val);
    });
}

function setPreset(val) {
    document.getElementById('jumlahInput').value = val;
    updateInfo();
}

function lihatHasil() {
    const val = parseFloat(document.getElementById('jumlahInput').value);
    if (!val || val <= 0) {
        showToast('Masukkan jumlah produksi yang valid.');
        return;
    }
    window.location = `/bakecost/pages/konversi_hasil.php?resep_id=${RESEP_ID}&jumlah=${val}`;
}

updateInfo();
</script>
</body>
</html>
