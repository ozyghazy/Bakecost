<?php
/**
 * pages/hitung_biaya.php — Rincian harga per bahan + total estimasi
 * Sesuai desain "Hitung Biaya.png": stepper 3-step (Resep ✓ · Jumlah Produksi ✓ · Biaya),
 * list bahan dengan harga/satuan yang bisa diedit, dan tombol Simpan (ke produksi).
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

// Ambil bahan + konversi
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

$faktor = $jumlahTarget / $resep['jumlah_hasil'];

function fmtJml(float $n): string {
    if ($n == floor($n)) return number_format($n, 0, ',', '.');
    return rtrim(rtrim(number_format($n, 3, ',', '.'), '0'), ',');
}

$showBack  = true;
$pageTitle = 'Hitung Biaya';
$backUrl   = "/bakecost/pages/konversi_hasil.php?resep_id={$resepId}&jumlah={$jumlahTarget}";
$activeNav = 'biaya';

$steps       = ['Resep', 'Jumlah Produksi', 'Biaya'];
$currentStep = 3;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hitung Biaya — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">
        <?php require __DIR__ . '/../components/step_indicator.php'; ?>

        <!-- Info resep terpilih -->
        <div class="card flex items-center gap-3 mb-5">
            <div class="w-14 h-14 rounded-xl bg-[#EAD9C8] flex items-center justify-center text-2xl flex-shrink-0">🧁</div>
            <div>
                <p class="font-bold text-[#3D1F0D]"><?= htmlspecialchars($resep['nama']) ?></p>
                <p class="text-[#9E7A5A] text-sm"><?= fmtJml($jumlahTarget) ?> <?= htmlspecialchars($resep['satuan_hasil']) ?></p>
            </div>
        </div>

        <!-- Header tabel harga -->
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-[15px] font-bold text-[#3D1F0D]">Harga Bahan Baku</h2>
            <button onclick="toggleEditHarga()" id="btnUbahHarga" class="text-sm text-[#C17A3E] font-semibold hover:underline">
                Ubah Harga
            </button>
        </div>

        <!-- Tabel rincian bahan -->
        <div class="card mb-4 p-0 overflow-hidden">
            <?php if (empty($bahanList)): ?>
            <div class="p-4 text-center text-[#9E7A5A] text-sm">Resep belum memiliki bahan.</div>
            <?php else: ?>
            <div id="bahanRows" class="divide-y divide-[#F0E8DF]">
                <?php foreach ($bahanList as $i => $b):
                    $jumlahK = $b['jumlah_asli'] * $faktor;
                    $hargaPs = (float) $b['harga_per_satuan'];
                    $subtotal = $jumlahK * $hargaPs;
                ?>
                <div class="px-4 py-3.5 bahan-row"
                     data-jumlah="<?= $jumlahK ?>"
                     data-idx="<?= $i ?>">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 pr-3">
                            <p class="font-medium text-[#3D1F0D] text-sm"><?= htmlspecialchars($b['bahan_nama']) ?></p>
                            <p class="text-[#9E7A5A] text-xs"><?= fmtJml($jumlahK) ?> <?= $b['satuan'] ?> ×</p>
                        </div>
                        <!-- Harga/satuan: mode view vs edit -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <!-- View mode -->
                            <span class="view-harga text-sm text-[#9E7A5A]">
                                Rp <?= number_format($hargaPs, 0, ',', '.') ?>/<?= $b['satuan'] ?>
                            </span>
                            <!-- Edit mode (tersembunyi) -->
                            <input type="number"
                                   class="edit-harga input-field w-28 text-sm hidden"
                                   value="<?= $hargaPs ?>"
                                   min="0"
                                   step="any"
                                   data-satuan="<?= $b['satuan'] ?>"
                                   oninput="updateSubtotal(this, <?= $jumlahK ?>)">
                            <!-- Subtotal -->
                            <p class="font-bold text-[#3D1F0D] text-sm w-20 text-right subtotal"
                               data-value="<?= $subtotal ?>">
                                Rp <?= number_format($subtotal, 0, ',', '.') ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Total biaya bahan -->
        <div class="card bg-[#FDE8D5] mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-[#5A2D0C] text-sm">Total Estimasi Biaya Bahan Baku</p>
                    <p class="text-xs text-[#9E7A5A]">
                        Untuk <?= fmtJml($jumlahTarget) ?> <?= htmlspecialchars($resep['satuan_hasil']) ?> ·
                        <span id="perUnitLabel">
                            Rp <?= number_format(
                                $jumlahTarget > 0
                                    ? array_sum(array_map(fn($b) => ($b['jumlah_asli'] * $faktor) * $b['harga_per_satuan'], $bahanList)) / $jumlahTarget
                                    : 0,
                                0, ',', '.'
                            ) ?>/<?= htmlspecialchars($resep['satuan_hasil']) ?>
                        </span>
                    </p>
                </div>
                <p id="totalLabel" class="text-xl font-bold text-[#7C4A2D]">
                    Rp <?= number_format(
                        array_sum(array_map(fn($b) => ($b['jumlah_asli'] * $faktor) * $b['harga_per_satuan'], $bahanList)),
                        0, ',', '.'
                    ) ?>
                </p>
            </div>
        </div>

        <!-- Link ke HPP Kalkulator lengkap -->
        <div class="card mb-4 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-[#3D1F0D]">Hitung HPP Lengkap</p>
                <p class="text-xs text-[#9E7A5A]">Tambah biaya tenaga kerja, overhead & packaging</p>
            </div>
            <a href="/bakecost/pages/hpp_kalkulator.php?resep_id=<?= $resepId ?>&jumlah=<?= $jumlahTarget ?>"
               class="flex items-center gap-1 text-sm text-[#7C4A2D] font-semibold hover:underline flex-shrink-0 ml-3">
                Lanjut →
            </a>
        </div>

        <!-- Tombol simpan -->
        <button onclick="simpanBiaya()" class="btn-primary">Simpan ke Riwayat</button>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
const RESEP_ID       = <?= $resepId ?>;
const JUMLAH_TARGET  = <?= $jumlahTarget ?>;
let isEditingHarga   = false;

// ── Toggle mode ubah harga ──
function toggleEditHarga() {
    isEditingHarga = !isEditingHarga;
    document.querySelectorAll('.view-harga').forEach(el => el.classList.toggle('hidden', isEditingHarga));
    document.querySelectorAll('.edit-harga').forEach(el => el.classList.toggle('hidden', !isEditingHarga));
    document.getElementById('btnUbahHarga').textContent = isEditingHarga ? 'Selesai' : 'Ubah Harga';
}

// ── Update subtotal satu baris saat harga diubah ──
function updateSubtotal(input, jumlah) {
    const harga   = parseFloat(input.value) || 0;
    const subtotal = jumlah * harga;
    const row     = input.closest('.bahan-row');
    const subEl   = row.querySelector('.subtotal');
    subEl.dataset.value = subtotal;
    subEl.textContent   = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');

    // Update view-harga label
    const viewHarga = row.querySelector('.view-harga');
    if (viewHarga) viewHarga.textContent = `Rp ${Math.round(harga).toLocaleString('id-ID')}/${input.dataset.satuan}`;

    updateTotal();
}

// ── Hitung ulang total dari semua subtotal ──
function updateTotal() {
    let total = 0;
    document.querySelectorAll('.subtotal').forEach(el => {
        total += parseFloat(el.dataset.value) || 0;
    });
    document.getElementById('totalLabel').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');

    const perUnit = JUMLAH_TARGET > 0 ? total / JUMLAH_TARGET : 0;
    document.getElementById('perUnitLabel').textContent =
        `Rp ${Math.round(perUnit).toLocaleString('id-ID')}/${<?= json_encode($resep['satuan_hasil']) ?>}`;
}

// ── Simpan ke DB via API HPP ──
async function simpanBiaya() {
    // Kumpulkan total dari DOM
    let biayaBahan = 0;
    document.querySelectorAll('.subtotal').forEach(el => {
        biayaBahan += parseFloat(el.dataset.value) || 0;
    });

    const btn = event.target;
    btn.disabled = true; btn.textContent = 'Menyimpan...';

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
                tipe:               'biaya'
            }
        });
        showToast('Perhitungan disimpan ke Riwayat ✓');
        setTimeout(() => window.location = '/bakecost/pages/riwayat.php', 1200);
    } catch (err) {
        showToast('⚠ ' + err.message);
        btn.disabled = false; btn.textContent = 'Simpan ke Riwayat';
    }
}
</script>
</body>
</html>

