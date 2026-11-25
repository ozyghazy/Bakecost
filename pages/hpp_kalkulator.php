<?php
/**
 * pages/hpp_kalkulator.php
 * Kalkulator HPP lengkap: biaya bahan (otomatis), tenaga kerja, overhead,
 * packaging, margin, dan harga jual yang direkomendasikan.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

// Ambil semua resep milik user
$resepStmt = $db->prepare('SELECT id, nama, jumlah_hasil, satuan_hasil FROM resep WHERE pengguna_id = ? ORDER BY nama ASC');
$resepStmt->execute([$userId]);
$resepList = $resepStmt->fetchAll();

// Ambil semua packaging milik user
$pkgStmt = $db->prepare('SELECT id, nama, harga_per_unit FROM packaging WHERE pengguna_id = ? ORDER BY nama ASC');
$pkgStmt->execute([$userId]);
$packagingList = $pkgStmt->fetchAll();

// Pre-select dari query string (dari hitung_biaya atau beranda)
$preResepId = (int) ($_GET['resep_id'] ?? 0);
$preJumlah  = (float) ($_GET['jumlah'] ?? 0);

$showBack  = true;
$pageTitle = 'Kalkulator HPP';
$backUrl   = 'javascript:history.back()';
$activeNav = 'biaya';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator HPP — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">

        <!-- Alert -->
        <div id="alertMsg" class="hidden mb-4"></div>

        <!-- Hasil HPP (tersembunyi, muncul setelah hitung) -->
        <div id="hasilCard" class="card bg-[#7C4A2D] text-white mb-4 hidden">
            <p class="text-sm opacity-80 mb-1">HPP Total</p>
            <p id="hppTotalLabel" class="text-2xl font-bold">Rp 0</p>
            <div class="flex gap-6 mt-3">
                <div>
                    <p class="text-xs opacity-70">HPP per unit</p>
                    <p id="hppPerUnitLabel" class="font-semibold">Rp 0</p>
                </div>
                <div>
                    <p class="text-xs opacity-70">Harga jual rekomendasi</p>
                    <p id="hargaJualLabel" class="font-semibold">—</p>
                </div>
            </div>
        </div>

        <!-- ── Form Kalkulasi ── -->

        <!-- 1. Pilih Resep -->
        <div class="card mb-3">
            <label class="form-label mb-2">Resep <span class="text-red-400">*</span></label>
            <select id="resepSelect" class="input-field" onchange="onResepChange()">
                <option value="">— Pilih resep —</option>
                <?php foreach ($resepList as $r): ?>
                <option value="<?= $r['id'] ?>"
                        data-yield="<?= $r['jumlah_hasil'] ?>"
                        data-satuan="<?= htmlspecialchars($r['satuan_hasil']) ?>"
                        <?= $r['id'] === $preResepId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['nama']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- 2. Jumlah produksi target -->
        <div class="card mb-3">
            <label class="form-label mb-2">Jumlah Produksi Target <span class="text-red-400">*</span></label>
            <div class="flex gap-2 items-center">
                <input type="number" id="jumlahProd" class="input-field flex-1"
                       placeholder="cth: 20" min="0.01" step="any"
                       value="<?= $preJumlah ?: '' ?>"
                       oninput="hitungOtomatis()">
                <span id="satuanLabel" class="text-[#9E7A5A] text-sm w-12 flex-shrink-0">unit</span>
            </div>
            <p id="faktorInfo" class="text-xs text-[#9E7A5A] mt-1 hidden"></p>
        </div>

        <!-- 3. Biaya Bahan (otomatis dari DB, bisa di-override) -->
        <div class="card mb-3">
            <div class="flex items-center justify-between mb-2">
                <label class="form-label mb-0">Biaya Bahan Baku</label>
                <button onclick="toggleOverrideBahan()" id="btnOverride" class="text-xs text-[#C17A3E] font-semibold">Input Manual</button>
            </div>
            <div id="biayaBahanOtomatis" class="alert-info text-sm">
                Pilih resep dan jumlah produksi terlebih dahulu.
            </div>
            <input type="number" id="biayaBahanInput" class="input-field hidden mt-2"
                   placeholder="cth: 50000" min="0" step="any" oninput="hitungOtomatis()">
        </div>

        <!-- 4. Biaya Tenaga Kerja -->
        <div class="card mb-3">
            <label class="form-label mb-2">Biaya Tenaga Kerja (Rp)</label>
            <input type="number" id="biayaTenaga" class="input-field" placeholder="cth: 30000" min="0" step="any" value="0" oninput="hitungOtomatis()">
            <p class="text-xs text-[#9E7A5A] mt-1">Total upah untuk satu batch produksi ini</p>
        </div>

        <!-- 5. Biaya Overhead -->
        <div class="card mb-3">
            <label class="form-label mb-2">Biaya Overhead (Rp)</label>
            <input type="number" id="biayaOverhead" class="input-field" placeholder="cth: 10000" min="0" step="any" value="0" oninput="hitungOtomatis()">
            <p class="text-xs text-[#9E7A5A] mt-1">Listrik, gas, air, dll. untuk batch ini</p>
        </div>

        <!-- 6. Packaging (opsional) -->
        <div class="card mb-3">
            <div class="flex items-center justify-between mb-3">
                <label class="form-label mb-0">Packaging</label>
                <a href="/bakecost/pages/packaging.php" class="text-xs text-[#7C4A2D] font-semibold hover:underline">Kelola Packaging →</a>
            </div>

            <?php if (empty($packagingList)): ?>
            <p class="text-sm text-[#9E7A5A]">Belum ada packaging. <a href="/bakecost/pages/packaging.php" class="underline text-[#7C4A2D]">Tambah sekarang</a>.</p>
            <?php else: ?>
            <div id="packagingRows" class="space-y-2">
                <!-- Baris packaging ditambah via JS -->
            </div>
            <button type="button" onclick="addPackagingRow()" class="mt-2 text-sm text-[#7C4A2D] font-semibold hover:underline">
                + Tambah Packaging
            </button>
            <script>
                const PKG_OPTIONS = <?= json_encode($packagingList, JSON_UNESCAPED_UNICODE) ?>;
            </script>
            <?php endif; ?>
        </div>

        <!-- 7. Margin & Harga Jual -->
        <div class="card mb-4">
            <label class="form-label mb-2">Margin Keuntungan (%)</label>
            <div class="flex gap-2 items-center mb-3">
                <input type="number" id="marginInput" class="input-field flex-1"
                       placeholder="cth: 30" min="0" max="1000" step="0.1" oninput="hitungOtomatis()">
                <span class="text-[#9E7A5A] text-sm flex-shrink-0">%</span>
            </div>
            <div class="flex gap-2 flex-wrap">
                <?php foreach ([20, 30, 40, 50] as $m): ?>
                <button class="chip text-sm" onclick="setMargin(<?= $m ?>)"><?= $m ?>%</button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tombol Hitung -->
        <button onclick="simpanHPP()" class="btn-primary mb-4">Simpan & Lihat HPP</button>

        <!-- Link perbandingan -->
        <a href="/bakecost/pages/hpp_perbandingan.php" class="flex items-center justify-center gap-2 text-sm text-[#7C4A2D] font-semibold hover:underline mb-4">
            📊 Lihat Perbandingan HPP Antar Resep
        </a>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
let biayaBahanOtomatis = 0;
let isOverrideBahan    = false;
let pkgRowIndex        = 0;

// ── Event saat resep berubah ──
async function onResepChange() {
    const sel    = document.getElementById('resepSelect');
    const opt    = sel.options[sel.selectedIndex];
    const satuan = opt.dataset.satuan || 'unit';
    document.getElementById('satuanLabel').textContent = satuan;

    const faktorInfo = document.getElementById('faktorInfo');
    const jumlah = parseFloat(document.getElementById('jumlahProd').value) || 0;
    const yield_ = parseFloat(opt.dataset.yield) || 0;

    if (yield_ > 0 && jumlah > 0) {
        faktorInfo.textContent = `Resep asli: ${yield_} ${satuan} · Faktor: ×${(jumlah/yield_).toFixed(2)}`;
        faktorInfo.classList.remove('hidden');
    }

    await hitungBiayaBahanOtomatis();
}

// Hitung biaya bahan dari API konversi
async function hitungBiayaBahanOtomatis() {
    const resepId = parseInt(document.getElementById('resepSelect').value);
    const jumlah  = parseFloat(document.getElementById('jumlahProd').value);
    const infoEl  = document.getElementById('biayaBahanOtomatis');

    if (!resepId || !jumlah || jumlah <= 0) {
        infoEl.textContent = 'Pilih resep dan jumlah produksi terlebih dahulu.';
        biayaBahanOtomatis = 0;
        hitungOtomatis();
        return;
    }

    infoEl.textContent = 'Menghitung...';

    try {
        const data = await apiFetch('/bakecost/api/konversi.php', {
            method: 'POST',
            body: { resep_id: resepId, jumlah_produksi: jumlah }
        });
        biayaBahanOtomatis = data.biaya_total;
        infoEl.innerHTML = `<strong>Rp ${Math.round(biayaBahanOtomatis).toLocaleString('id-ID')}</strong>
            <span class="text-[#9E7A5A] ml-1 text-xs">(dari ${data.bahan.length} bahan)</span>`;
    } catch {
        infoEl.textContent = 'Resep belum memiliki bahan.';
        biayaBahanOtomatis = 0;
    }

    hitungOtomatis();
}

// Toggle input manual biaya bahan
function toggleOverrideBahan() {
    isOverrideBahan = !isOverrideBahan;
    document.getElementById('biayaBahanOtomatis').classList.toggle('hidden', isOverrideBahan);
    document.getElementById('biayaBahanInput').classList.toggle('hidden', !isOverrideBahan);
    document.getElementById('btnOverride').textContent = isOverrideBahan ? 'Gunakan Otomatis' : 'Input Manual';
    hitungOtomatis();
}

// ── Hitung HPP real-time ──
function hitungOtomatis() {
    const jumlah  = parseFloat(document.getElementById('jumlahProd').value) || 0;
    const bahan   = isOverrideBahan
        ? (parseFloat(document.getElementById('biayaBahanInput').value) || 0)
        : biayaBahanOtomatis;
    const tenaga  = parseFloat(document.getElementById('biayaTenaga').value)  || 0;
    const overhead= parseFloat(document.getElementById('biayaOverhead').value) || 0;
    const margin  = parseFloat(document.getElementById('marginInput').value)   || null;

    // Hitung biaya packaging
    let biayaPkg = 0;
    document.querySelectorAll('.pkg-select').forEach(sel => {
        const idx    = sel.dataset.row;
        const jumlahP= parseInt(document.querySelector(`.pkg-jumlah[data-row="${idx}"]`)?.value) || 0;
        const harga  = parseFloat(sel.options[sel.selectedIndex]?.dataset.harga || 0);
        biayaPkg    += jumlahP * harga;
    });

    const hppTotal   = bahan + biayaPkg + tenaga + overhead;
    const hppPerUnit = jumlah > 0 ? hppTotal / jumlah : 0;
    const hargaJual  = margin ? hppPerUnit * (1 + margin / 100) : null;

    const hasilCard = document.getElementById('hasilCard');
    if (hppTotal > 0) {
        hasilCard.classList.remove('hidden');
        document.getElementById('hppTotalLabel').textContent    = 'Rp ' + Math.round(hppTotal).toLocaleString('id-ID');
        document.getElementById('hppPerUnitLabel').textContent  = 'Rp ' + Math.round(hppPerUnit).toLocaleString('id-ID');
        document.getElementById('hargaJualLabel').textContent   = hargaJual
            ? 'Rp ' + Math.round(hargaJual).toLocaleString('id-ID')
            : '—';
    }
}

// ── Packaging row ──
function addPackagingRow() {
    pkgRowIndex++;
    const idx = pkgRowIndex;
    const optHtml = (typeof PKG_OPTIONS !== 'undefined' ? PKG_OPTIONS : []).map(p =>
        `<option value="${p.id}" data-harga="${p.harga_per_unit}">${escH(p.nama)} - Rp ${Math.round(p.harga_per_unit).toLocaleString('id-ID')}/pcs</option>`
    ).join('');

    const row = document.createElement('div');
    row.className = 'flex gap-2 items-center';
    row.id = `pkgrow-${idx}`;
    row.innerHTML = `
        <select class="input-field text-sm flex-1 pkg-select" data-row="${idx}" onchange="hitungOtomatis()">
            <option value="">— Pilih packaging —</option>
            ${optHtml}
        </select>
        <input type="number" class="input-field text-sm w-20 pkg-jumlah" data-row="${idx}"
               placeholder="Qty" min="1" step="1" value="1" oninput="hitungOtomatis()">
        <button onclick="document.getElementById('pkgrow-${idx}').remove(); hitungOtomatis()"
            class="w-8 h-8 flex-shrink-0 rounded-full bg-red-50 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `;
    document.getElementById('packagingRows').appendChild(row);
}

function setMargin(val) {
    document.getElementById('marginInput').value = val;
    hitungOtomatis();
}

// ── Simpan ke DB ──
async function simpanHPP() {
    const resepId = parseInt(document.getElementById('resepSelect').value);
    const jumlah  = parseFloat(document.getElementById('jumlahProd').value);

    if (!resepId) return showAlert('Pilih resep terlebih dahulu.', 'error');
    if (!jumlah || jumlah <= 0) return showAlert('Masukkan jumlah produksi.', 'error');

    const biayaBahan   = isOverrideBahan
        ? (parseFloat(document.getElementById('biayaBahanInput').value) || 0)
        : biayaBahanOtomatis;
    const biayaTenaga  = parseFloat(document.getElementById('biayaTenaga').value)  || 0;
    const biayaOverhead= parseFloat(document.getElementById('biayaOverhead').value) || 0;
    const margin       = parseFloat(document.getElementById('marginInput').value)   || null;

    const packagingIds = [];
    document.querySelectorAll('.pkg-select').forEach(sel => {
        const idx    = sel.dataset.row;
        const pkgId  = parseInt(sel.value);
        const jumlahP= parseInt(document.querySelector(`.pkg-jumlah[data-row="${idx}"]`)?.value) || 0;
        if (pkgId && jumlahP > 0) packagingIds.push({ id: pkgId, jumlah: jumlahP });
    });

    const btn = event.target;
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    try {
        const res = await apiFetch('/bakecost/api/hpp.php', {
            method: 'POST',
            body: {
                resep_id: resepId, jumlah_produksi: jumlah,
                biaya_bahan: biayaBahan,
                biaya_tenaga_kerja: biayaTenaga,
                biaya_overhead: biayaOverhead,
                margin_persen: margin,
                packaging_ids: packagingIds,
                tipe: 'biaya'
            }
        });

        showToast(`HPP disimpan! HPP/unit: Rp ${Math.round(res.hpp_per_unit).toLocaleString('id-ID')} ✓`);
        setTimeout(() => window.location = `/bakecost/pages/riwayat.php`, 1500);
    } catch (err) {
        showAlert(err.message, 'error');
        btn.disabled = false; btn.textContent = 'Simpan & Lihat HPP';
    }
}

function showAlert(msg, type) {
    const el = document.getElementById('alertMsg');
    el.className = type === 'error' ? 'alert-error mb-4' : 'alert-success mb-4';
    el.textContent = msg;
    el.classList.remove('hidden');
}

function escH(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Init: jika ada pre-select dari query string ──
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('resepSelect');
    if (sel.value) onResepChange();
    document.getElementById('jumlahProd').addEventListener('input', debounce(async () => {
        const opt = sel.options[sel.selectedIndex];
        const yield_ = parseFloat(opt?.dataset.yield) || 0;
        const jumlah = parseFloat(document.getElementById('jumlahProd').value) || 0;
        const satuanLabel = document.getElementById('satuanLabel');
        if (yield_ > 0 && jumlah > 0) {
            const faktorInfo = document.getElementById('faktorInfo');
            faktorInfo.textContent = `Resep asli: ${yield_} ${satuanLabel.textContent} · Faktor: ×${(jumlah/yield_).toFixed(2)}`;
            faktorInfo.classList.remove('hidden');
        }
        await hitungBiayaBahanOtomatis();
    }, 400));
});
</script>
</body>
</html>

