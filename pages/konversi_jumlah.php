<?php
/**
 * pages/konversi_jumlah.php — Step 2: Input Jumlah Produksi
 * Sesuai desain "Jumlah Produksi.png": info resep terpilih, input angka,
 * chip preset 10/20/30/50, info pengali, tombol Lihat Hasil.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

$resepId = (int) ($_GET['resep_id'] ?? 0);
if (!$resepId) {
    header('Location: /bakecost/pages/konversi.php');
    exit;
}

// Ambil resep (pastikan milik user)
$stmt = $db->prepare('SELECT id, nama, jumlah_hasil, satuan_hasil FROM resep WHERE id = ? AND pengguna_id = ?');
$stmt->execute([$resepId, $userId]);
$resep = $stmt->fetch();

if (!$resep) {
    header('Location: /bakecost/pages/konversi.php');
    exit;
}

$showBack  = true;
$pageTitle = 'Konversi Bahan';
$backUrl   = '/bakecost/pages/konversi.php';
$activeNav = 'konversi';

$steps       = ['Resep', 'Jumlah Produksi', 'Hasil'];
$currentStep = 2;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jumlah Produksi — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">
        <?php require __DIR__ . '/../components/step_indicator.php'; ?>

        <!-- Kartu resep terpilih -->
        <div class="card flex items-center gap-3 mb-5">
            <div class="w-14 h-14 rounded-xl bg-[#EAD9C8] flex items-center justify-center text-2xl flex-shrink-0">🧁</div>
            <div>
                <p class="font-bold text-[#3D1F0D]"><?= htmlspecialchars($resep['nama']) ?></p>
                <p class="text-[#9E7A5A] text-sm">Resep asli untuk <?= $resep['jumlah_hasil'] ?> <?= htmlspecialchars($resep['satuan_hasil']) ?></p>
            </div>
        </div>

        <!-- Input jumlah produksi -->
        <div class="card mb-4">
            <label class="form-label mb-3 block">Jumlah Produksi</label>

            <!-- Input + satuan -->
            <div class="flex gap-2 mb-3">
                <input
                    type="number"
                    id="jumlahInput"
                    class="input-field flex-1 text-xl font-semibold"
                    placeholder="0"
                    min="0.1"
                    step="any"
                    value="<?= $resep['jumlah_hasil'] ?>"
                    oninput="updateInfo()"
                >
                <div class="input-field w-24 flex items-center justify-between cursor-pointer bg-[#F9F4EF] text-sm font-medium text-[#3D1F0D]">
                    <span><?= htmlspecialchars($resep['satuan_hasil']) ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-[#9E7A5A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>

            <!-- Chip preset -->
            <div class="flex gap-2 flex-wrap mb-4">
                <?php foreach ([10, 20, 30, 50, 100] as $preset): ?>
                <button
                    class="preset-chip chip <?= $preset == $resep['jumlah_hasil'] ? 'chip-active' : '' ?>"
                    onclick="setPreset(<?= $preset ?>)">
                    <?= $preset ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Info konversi -->
            <div class="alert-info" id="infoBox">
                <p class="text-sm">Resep asli: <strong><?= $resep['jumlah_hasil'] ?> <?= htmlspecialchars($resep['satuan_hasil']) ?></strong></p>
                <p class="text-sm">Dikonversi menjadi: <strong id="infoTarget"><?= $resep['jumlah_hasil'] ?> <?= htmlspecialchars($resep['satuan_hasil']) ?></strong></p>
                <span id="infoBadge" class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-white text-[#7C4A2D]">
                    Pengali 1× lipat
                </span>
            </div>
        </div>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<!-- Tombol Lihat Hasil -->
<div class="btn-fixed-bottom">
    <button onclick="lihatHasil()" class="btn-primary">Lihat Hasil</button>
</div>

<script src="/bakecost/assets/js/app.js"></script>
<script>
const RESEP_ID    = <?= $resepId ?>;
const JUMLAH_ASLI = <?= $resep['jumlah_hasil'] ?>;
const SATUAN      = '<?= htmlspecialchars($resep['satuan_hasil']) ?>';

function updateInfo() {
    const val   = parseFloat(document.getElementById('jumlahInput').value) || 0;
    const faktor = val > 0 ? (val / JUMLAH_ASLI) : 0;

    document.getElementById('infoTarget').textContent = `${val} ${SATUAN}`;

    const badge = document.getElementById('infoBadge');
    if (faktor > 0) {
        const faktorStr = Number.isInteger(faktor)
            ? `${faktor}×`
            : faktor.toFixed(2) + '×';
        badge.textContent = `Pengali ${faktorStr} lipat`;
    }

    // Update chip aktif
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

// Init
updateInfo();
</script>
</body>
</html>

