<?php
/**
 * pages/hpp_kalkulator.php
 * Kalkulator HPP lengkap: bahan baku, tenaga kerja, overhead, packaging, dan margin.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

$resepStmt = $db->prepare('SELECT id, nama, jumlah_hasil, satuan_hasil FROM resep WHERE pengguna_id = ? ORDER BY nama ASC');
$resepStmt->execute([$userId]);
$resepList = $resepStmt->fetchAll();

$pkgStmt = $db->prepare('SELECT id, nama, harga_per_unit FROM packaging WHERE pengguna_id = ? ORDER BY nama ASC');
$pkgStmt->execute([$userId]);
$packagingList = $pkgStmt->fetchAll();

$preResepId = (int) ($_GET['resep_id'] ?? 0);
$preJumlah  = (float) ($_GET['jumlah'] ?? 0);

$showBack  = false;
$activeNav = 'biaya';
$htmlTitle = 'Kalkulator HPP — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content space-y-5">

        <section class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="eyebrow">Kalkulasi Produksi</p>
                <h1 class="editorial-title text-2xl sm:text-3xl font-semibold text-ink mt-0.5">Kalkulator HPP</h1>
            </div>
            <a href="/bakecost/pages/hpp_perbandingan.php" class="btn-secondary btn-sm">
                Bandingkan HPP →
            </a>
        </section>

        <div id="alertMsg" class="hidden" role="alert"></div>

        <div class="editorial-split">
            <!-- Kiri: Form Input -->
            <div class="space-y-5">
                <section class="card space-y-4">
                    <p class="eyebrow">01 · Resep &amp; Target Batch</p>

                    <div>
                        <label class="form-label" for="resepSelect">Resep <span class="text-red-500">*</span></label>
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

                    <div>
                        <label class="form-label" for="jumlahProd">Jumlah Produksi Target <span class="text-red-500">*</span></label>
                        <div class="flex gap-2.5 items-center">
                            <input type="number" id="jumlahProd" class="input-field flex-1 tabular-nums"
                                   placeholder="cth: 20" min="0.01" step="any"
                                   value="<?= $preJumlah ?: '' ?>"
                                   oninput="hitungOtomatis()">
                            <span id="satuanLabel" class="text-muted text-sm w-14 flex-shrink-0">unit</span>
                        </div>
                        <p id="faktorInfo" class="text-xs text-muted mt-1.5 hidden tabular-nums"></p>
                    </div>

                    <div class="pt-2 border-t border-[var(--border-hairline)]">
                        <div class="flex items-center justify-between mb-2">
                            <label class="form-label mb-0" for="biayaBahanInput">Biaya Bahan Baku</label>
                            <button type="button" onclick="toggleOverrideBahan()" id="btnOverride" class="btn-action">
                                Input Manual
                            </button>
                        </div>
                        <div id="biayaBahanOtomatis" class="alert-info">
                            Pilih resep dan jumlah produksi terlebih dahulu.
                        </div>
                        <input type="number" id="biayaBahanInput" class="input-field hidden mt-2 tabular-nums"
                               placeholder="cth: 50000" min="0" step="any" oninput="hitungOtomatis()">
                    </div>
                </section>

                <section class="card space-y-4">
                    <p class="eyebrow">02 · Biaya Operasional &amp; Kemasan</p>

                    <div class="grid sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="form-label" for="biayaTenaga">Biaya Tenaga Kerja (Rp)</label>
                            <input type="number" id="biayaTenaga" class="input-field tabular-nums" placeholder="cth: 30000" min="0" step="any" value="0" oninput="hitungOtomatis()">
                            <p class="text-[11px] text-muted mt-1">Total upah untuk satu batch ini</p>
                        </div>
                        <div>
                            <label class="form-label" for="biayaOverhead">Biaya Overhead (Rp)</label>
                            <input type="number" id="biayaOverhead" class="input-field tabular-nums" placeholder="cth: 10000" min="0" step="any" value="0" oninput="hitungOtomatis()">
                            <p class="text-[11px] text-muted mt-1">Listrik, gas oven, air, dll.</p>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-[var(--border-hairline)] space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="form-label mb-0">Packaging / Kemasan</span>
                            <a href="/bakecost/pages/packaging.php" class="text-xs font-semibold text-accent hover:underline">Kelola Packaging →</a>
                        </div>

                        <?php if (empty($packagingList)): ?>
                            <p class="text-xs text-muted">
                                Belum ada kemasan. <a href="/bakecost/pages/packaging.php" class="text-ink font-semibold underline">Tambah sekarang</a>.
                            </p>
                        <?php else: ?>
                            <div id="packagingRows" class="space-y-2"></div>
                            <button type="button" onclick="addPackagingRow()" class="btn-action">
                                + Tambah Kemasan
                            </button>
                            <script>const PKG_OPTIONS = <?= json_encode($packagingList, JSON_UNESCAPED_UNICODE) ?>;</script>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="card space-y-3">
                    <p class="eyebrow">03 · Target Margin</p>
                    <label class="form-label" for="marginInput">Margin Keuntungan (%)</label>
                    <div class="flex gap-2 items-center">
                        <input type="number" id="marginInput" class="input-field flex-1 tabular-nums"
                               placeholder="cth: 30" min="0" max="1000" step="0.1" oninput="hitungOtomatis()">
                        <span class="text-muted text-sm font-medium">%</span>
                    </div>
                    <div class="flex gap-2 flex-wrap pt-1">
                        <?php foreach ([20, 30, 40, 50] as $m): ?>
                            <button type="button" class="chip" onclick="setMargin(<?= $m ?>)"><?= $m ?>%</button>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <!-- Kanan: Ringkasan HPP Live Sticky -->
            <aside class="editorial-split__sticky space-y-4">
                <section id="hasilCard" class="card-espresso space-y-4">
                    <div>
                        <p class="text-xs uppercase tracking-wider opacity-70">Estimasi HPP Total</p>
                        <p id="hppTotalLabel" class="font-editorial text-3xl font-semibold text-[var(--accent-crust)] tabular-nums mt-0.5">Rp 0</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-3 border-t border-white/15">
                        <div>
                            <p class="text-xs opacity-70">HPP per Unit</p>
                            <p id="hppPerUnitLabel" class="font-semibold text-base tabular-nums mt-0.5">Rp 0</p>
                        </div>
                        <div>
                            <p class="text-xs opacity-70">Rekomendasi Jual</p>
                            <p id="hargaJualLabel" class="font-semibold text-base text-[var(--accent-crust)] tabular-nums mt-0.5">—</p>
                        </div>
                    </div>
                </section>

                <button type="button" onclick="simpanHPP(this)" class="btn-primary">Simpan Perhitungan HPP</button>
            </aside>
        </div>
    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
let biayaBahanOtomatis = 0;
let isOverrideBahan    = false;
let pkgRowIndex        = 0;

async function onResepChange() {
    const sel    = document.getElementById('resepSelect');
    const opt    = sel.options[sel.selectedIndex];
    const satuan = opt.dataset.satuan || 'unit';
    document.getElementById('satuanLabel').textContent = satuan;

    const faktorInfo = document.getElementById('faktorInfo');
    const jumlah     = parseFloat(document.getElementById('jumlahProd').value) || 0;
    const yieldVal   = parseFloat(opt.dataset.yield) || 0;

    if (yieldVal > 0 && jumlah > 0) {
        faktorInfo.textContent = `Resep asli: ${yieldVal} ${satuan} · Pengali: ×${(jumlah / yieldVal).toFixed(2)}`;
        faktorInfo.classList.remove('hidden');
    }

    await hitungBiayaBahanOtomatis();
}

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

    infoEl.textContent = 'Menghitung biaya bahan...';

    try {
        const data = await apiFetch('/bakecost/api/konversi.php', {
            method: 'POST',
            body: { resep_id: resepId, jumlah_produksi: jumlah },
        });
        biayaBahanOtomatis = data.biaya_total;
        infoEl.innerHTML = `<strong class="tabular-nums">${formatRupiah(biayaBahanOtomatis)}</strong>
            <span class="text-muted ml-1 text-xs">(dari ${data.bahan.length} bahan)</span>`;
    } catch {
        infoEl.textContent = 'Resep belum memiliki bahan.';
        biayaBahanOtomatis = 0;
    }

    hitungOtomatis();
}

function toggleOverrideBahan() {
    isOverrideBahan = !isOverrideBahan;
    document.getElementById('biayaBahanOtomatis').classList.toggle('hidden', isOverrideBahan);
    document.getElementById('biayaBahanInput').classList.toggle('hidden', !isOverrideBahan);
    document.getElementById('btnOverride').textContent = isOverrideBahan ? 'Gunakan Otomatis' : 'Input Manual';
    hitungOtomatis();
}

function hitungOtomatis() {
    const jumlah   = parseFloat(document.getElementById('jumlahProd').value) || 0;
    const bahan    = isOverrideBahan
        ? (parseFloat(document.getElementById('biayaBahanInput').value) || 0)
        : biayaBahanOtomatis;
    const tenaga   = parseFloat(document.getElementById('biayaTenaga').value)   || 0;
    const overhead = parseFloat(document.getElementById('biayaOverhead').value) || 0;
    const margin   = parseFloat(document.getElementById('marginInput').value)   || null;

    let biayaPkg = 0;
    document.querySelectorAll('.pkg-select').forEach(sel => {
        const idx     = sel.dataset.row;
        const jumlahP = parseInt(document.querySelector(`.pkg-jumlah[data-row="${idx}"]`)?.value) || 0;
        const harga   = parseFloat(sel.options[sel.selectedIndex]?.dataset.harga || 0);
        biayaPkg     += jumlahP * harga;
    });

    const hppTotal   = bahan + biayaPkg + tenaga + overhead;
    const hppPerUnit = jumlah > 0 ? hppTotal / jumlah : 0;
    const hargaJual  = margin ? hppPerUnit * (1 + margin / 100) : null;

    const hasilCard = document.getElementById('hasilCard');
    if (hppTotal > 0) {
        hasilCard.classList.remove('hidden');
        document.getElementById('hppTotalLabel').textContent   = formatRupiah(hppTotal);
        document.getElementById('hppPerUnitLabel').textContent = formatRupiah(hppPerUnit);
        document.getElementById('hargaJualLabel').textContent  = hargaJual ? formatRupiah(hargaJual) : '—';
    }
}

function addPackagingRow() {
    pkgRowIndex++;
    const idx = pkgRowIndex;
    const optHtml = (typeof PKG_OPTIONS !== 'undefined' ? PKG_OPTIONS : []).map(p =>
        `<option value="${p.id}" data-harga="${p.harga_per_unit}">${escHtml(p.nama)} — ${formatRupiah(p.harga_per_unit)}/pcs</option>`
    ).join('');

    const row = document.createElement('div');
    row.className = 'flex gap-2 items-center';
    row.id = `pkgrow-${idx}`;
    row.innerHTML = `
        <select class="input-field text-sm flex-1 pkg-select" data-row="${idx}" onchange="hitungOtomatis()">
            <option value="">— Pilih kemasan —</option>
            ${optHtml}
        </select>
        <input type="number" class="input-field text-sm w-20 tabular-nums pkg-jumlah" data-row="${idx}"
               placeholder="Qty" min="1" step="1" value="1" oninput="hitungOtomatis()">
        <button type="button" onclick="document.getElementById('pkgrow-${idx}').remove(); hitungOtomatis()" aria-label="Hapus kemasan"
            class="w-8 h-8 flex-shrink-0 rounded-full border border-[var(--border-hairline)] bg-white text-muted hover:text-[var(--state-error-text)] flex items-center justify-center">
            ×
        </button>
    `;
    document.getElementById('packagingRows').appendChild(row);
}

function setMargin(val) {
    document.getElementById('marginInput').value = val;
    hitungOtomatis();
}

async function simpanHPP(btn) {
    const resepId = parseInt(document.getElementById('resepSelect').value);
    const jumlah  = parseFloat(document.getElementById('jumlahProd').value);

    if (!resepId) return showAlert('Pilih resep terlebih dahulu.', 'error');
    if (!jumlah || jumlah <= 0) return showAlert('Masukkan jumlah produksi.', 'error');

    const biayaBahan    = isOverrideBahan
        ? (parseFloat(document.getElementById('biayaBahanInput').value) || 0)
        : biayaBahanOtomatis;
    const biayaTenaga   = parseFloat(document.getElementById('biayaTenaga').value)   || 0;
    const biayaOverhead = parseFloat(document.getElementById('biayaOverhead').value) || 0;
    const margin        = parseFloat(document.getElementById('marginInput').value)   || null;

    const packagingIds = [];
    document.querySelectorAll('.pkg-select').forEach(sel => {
        const idx     = sel.dataset.row;
        const pkgId   = parseInt(sel.value);
        const jumlahP = parseInt(document.querySelector(`.pkg-jumlah[data-row="${idx}"]`)?.value) || 0;
        if (pkgId && jumlahP > 0) packagingIds.push({ id: pkgId, jumlah: jumlahP });
    });

    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        const res = await apiFetch('/bakecost/api/hpp.php', {
            method: 'POST',
            body: {
                resep_id:           resepId,
                jumlah_produksi:    jumlah,
                biaya_bahan:        biayaBahan,
                biaya_tenaga_kerja: biayaTenaga,
                biaya_overhead:     biayaOverhead,
                margin_persen:      margin,
                packaging_ids:      packagingIds,
                tipe:               'biaya',
            },
        });
        showToast(`HPP disimpan (${formatRupiah(res.hpp_per_unit)}/unit)`);
        setTimeout(() => window.location = '/bakecost/pages/riwayat.php', 1200);
    } catch (err) {
        showAlert(err.message, 'error');
        btn.disabled = false;
        btn.textContent = 'Simpan Perhitungan HPP';
    }
}

function showAlert(msg, type) {
    const el = document.getElementById('alertMsg');
    el.className = type === 'error' ? 'alert-error' : 'alert-success';
    el.textContent = msg;
    el.classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('resepSelect');
    if (sel.value) onResepChange();

    document.getElementById('jumlahProd').addEventListener('input', debounce(async () => {
        const opt         = sel.options[sel.selectedIndex];
        const yieldVal    = parseFloat(opt?.dataset.yield) || 0;
        const jumlah      = parseFloat(document.getElementById('jumlahProd').value) || 0;
        const satuanLabel = document.getElementById('satuanLabel');
        if (yieldVal > 0 && jumlah > 0) {
            const faktorInfo = document.getElementById('faktorInfo');
            faktorInfo.textContent = `Resep asli: ${yieldVal} ${satuanLabel.textContent} · Pengali: ×${(jumlah / yieldVal).toFixed(2)}`;
            faktorInfo.classList.remove('hidden');
        }
        await hitungBiayaBahanOtomatis();
    }, 380));
});
</script>
</body>
</html>
