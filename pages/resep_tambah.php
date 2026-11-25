<?php
/**
 * pages/resep_tambah.php
 * Form tambah resep manual beserta daftar bahan dari bahan_baku user.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

$stmt = $db->prepare('SELECT id, nama, satuan, harga_per_satuan FROM bahan_baku WHERE pengguna_id = ? ORDER BY nama ASC');
$stmt->execute([$userId]);
$bahanOptions = $stmt->fetchAll();

$showBack  = true;
$pageTitle = 'Tambah Resep';
$backUrl   = '/bakecost/pages/resep.php';
$activeNav = 'resep';
$htmlTitle = 'Tambah Resep — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content space-y-5 max-w-2xl mx-auto">

        <div id="alertMsg" class="hidden" role="alert"></div>

        <!-- Info Resep -->
        <section class="card space-y-4">
            <p class="eyebrow">Informasi Resep</p>

            <div>
                <label class="form-label" for="rNama">Nama Resep <span class="text-red-500">*</span></label>
                <input type="text" id="rNama" class="input-field" placeholder="cth: Brownies Cokelat Fudgy" maxlength="150" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="rJumlah">Jumlah Hasil <span class="text-red-500">*</span></label>
                    <input type="number" id="rJumlah" class="input-field tabular-nums" placeholder="cth: 10" min="0.01" step="any" required>
                </div>
                <div>
                    <label class="form-label" for="rSatuan">Satuan Hasil</label>
                    <select id="rSatuan" class="input-field">
                        <option value="pcs">pcs</option>
                        <option value="porsi">porsi</option>
                        <option value="loyang">loyang</option>
                        <option value="g">gram</option>
                        <option value="kg">kg</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="form-label" for="rDeskripsi">Deskripsi (opsional)</label>
                <textarea id="rDeskripsi" class="input-field" rows="2" placeholder="Catatan singkat tentang resep ini..."></textarea>
            </div>
        </section>

        <!-- Daftar Bahan -->
        <section class="card space-y-4">
            <div class="flex items-center justify-between">
                <p class="eyebrow">Komposisi Bahan</p>
                <button type="button" onclick="addBahanRow()" class="text-xs font-semibold text-accent hover:underline">
                    + Tambah Baris
                </button>
            </div>

            <?php if (empty($bahanOptions)): ?>
                <div class="alert-info text-center">
                    Belum ada bahan baku. <a href="/bakecost/pages/bahan_baku.php" class="font-semibold underline">Tambah bahan baku</a> terlebih dahulu.
                </div>
            <?php else: ?>
                <script>const BAHAN_OPTIONS = <?= json_encode($bahanOptions, JSON_UNESCAPED_UNICODE) ?>;</script>

                <div id="bahanContainer" class="space-y-3"></div>

                <div class="pt-4 border-t border-[var(--border-hairline)] flex justify-between items-center">
                    <span class="text-sm text-muted">Estimasi biaya bahan</span>
                    <span id="totalBiaya" class="font-bold text-accent text-base tabular-nums">Rp 0</span>
                </div>
            <?php endif; ?>
        </section>

        <button type="button" id="submitBtn" onclick="submitResep()"
            class="btn-primary"
            <?= empty($bahanOptions) ? 'disabled' : '' ?>>
            Simpan Resep
        </button>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
let rowIndex = 0;

function addBahanRow(bahanId = '', jumlah = '', catatan = '') {
    rowIndex++;
    const idx = rowIndex;

    const optionsHtml = BAHAN_OPTIONS.map(b =>
        `<option value="${b.id}" data-satuan="${escHtml(b.satuan)}" data-harga="${b.harga_per_satuan}"
                 ${String(b.id) === String(bahanId) ? 'selected' : ''}>
            ${escHtml(b.nama)} (${escHtml(b.satuan)})
         </option>`
    ).join('');

    const row = document.createElement('div');
    row.className = 'flex gap-2 items-start p-3 rounded-2xl bg-[var(--bg-surface)] border border-[var(--border-hairline)]';
    row.id = `brow-${idx}`;
    row.innerHTML = `
        <div class="flex-1 space-y-2">
            <select onchange="updateSatuanLabel(${idx}); updateTotal()" class="input-field text-sm bahan-select" data-row="${idx}">
                <option value="">— Pilih bahan —</option>
                ${optionsHtml}
            </select>
            <div class="grid grid-cols-12 gap-2 items-center">
                <input type="number" value="${jumlah}" placeholder="Jumlah"
                       class="input-field text-sm col-span-5 tabular-nums bahan-jumlah" data-row="${idx}"
                       min="0.001" step="any" oninput="updateTotal()">
                <span class="bahan-satuan text-muted text-xs col-span-2" data-row="${idx}"></span>
                <input type="text" value="${escHtml(catatan)}" placeholder="Catatan (opsional)"
                       class="input-field text-sm col-span-5 bahan-catatan" data-row="${idx}" maxlength="150">
            </div>
        </div>
        <button type="button" onclick="removeBahanRow(${idx})" aria-label="Hapus baris bahan"
            class="mt-1 w-8 h-8 flex-shrink-0 rounded-full border border-[var(--border-hairline)] bg-white text-muted hover:text-[var(--state-error-text)] flex items-center justify-center">
            ×
        </button>
    `;
    document.getElementById('bahanContainer').appendChild(row);
    if (bahanId) updateSatuanLabel(idx);
}

function removeBahanRow(idx) {
    document.getElementById(`brow-${idx}`)?.remove();
    updateTotal();
}

function updateSatuanLabel(idx) {
    const select = document.querySelector(`.bahan-select[data-row="${idx}"]`);
    const label  = document.querySelector(`.bahan-satuan[data-row="${idx}"]`);
    if (!select || !label) return;
    const opt = select.options[select.selectedIndex];
    label.textContent = opt ? (opt.dataset.satuan || '') : '';
}

function updateTotal() {
    let total = 0;
    document.querySelectorAll('.bahan-select').forEach(select => {
        const idx    = select.dataset.row;
        const jumlah = parseFloat(document.querySelector(`.bahan-jumlah[data-row="${idx}"]`)?.value) || 0;
        const opt    = select.options[select.selectedIndex];
        const harga  = parseFloat(opt?.dataset.harga || 0);
        total += jumlah * harga;
    });
    document.getElementById('totalBiaya').textContent = formatRupiah(total);
}

async function submitResep() {
    const nama     = document.getElementById('rNama').value.trim();
    const jumlah   = parseFloat(document.getElementById('rJumlah').value);
    const satuan   = document.getElementById('rSatuan').value;
    const deskrips = document.getElementById('rDeskripsi').value.trim();

    if (!nama) return showAlert('Nama resep wajib diisi.', 'error');
    if (!jumlah || jumlah <= 0) return showAlert('Jumlah hasil harus lebih dari 0.', 'error');

    const bahan = [];
    document.querySelectorAll('.bahan-select').forEach(select => {
        const idx     = select.dataset.row;
        const bahanId = parseInt(select.value);
        const jumlahB = parseFloat(document.querySelector(`.bahan-jumlah[data-row="${idx}"]`)?.value);
        const catatan = document.querySelector(`.bahan-catatan[data-row="${idx}"]`)?.value.trim();
        if (bahanId && jumlahB > 0) {
            bahan.push({ bahan_id: bahanId, jumlah: jumlahB, catatan: catatan || '' });
        }
    });

    if (bahan.length === 0) return showAlert('Tambahkan minimal 1 bahan ke resep.', 'error');

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        const res = await apiFetch('/bakecost/api/resep.php', {
            method: 'POST',
            body: { nama, jumlah_hasil: jumlah, satuan_hasil: satuan, deskripsi: deskrips, bahan, dibuat_via_ai: false },
        });
        showToast('Resep berhasil disimpan');
        setTimeout(() => window.location = `/bakecost/pages/resep_detail.php?id=${res.id}`, 900);
    } catch (err) {
        showAlert(err.message, 'error');
        btn.disabled = false;
        btn.textContent = 'Simpan Resep';
    }
}

function showAlert(msg, type = 'error') {
    const el = document.getElementById('alertMsg');
    el.className = type === 'error' ? 'alert-error' : 'alert-success';
    el.textContent = msg;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof BAHAN_OPTIONS !== 'undefined') addBahanRow();
});
</script>
</body>
</html>
