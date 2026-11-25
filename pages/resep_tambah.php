<?php
/**
 * pages/resep_tambah.php
 * Form tambah resep manual: nama, yield, deskripsi, dan daftar bahan.
 * Bahan dipilih dari bahan_baku milik user (dropdown dinamis).
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

// Ambil daftar bahan baku milik user untuk dropdown
$stmt = $db->prepare('SELECT id, nama, satuan, harga_per_satuan FROM bahan_baku WHERE pengguna_id = ? ORDER BY nama ASC');
$stmt->execute([$userId]);
$bahanOptions = $stmt->fetchAll();

$showBack  = true;
$pageTitle = 'Tambah Resep';
$backUrl   = '/bakecost/pages/resep.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Resep — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">

        <!-- Alert pesan error / sukses -->
        <div id="alertMsg" class="hidden mb-4"></div>

        <!-- ── Form header resep ── -->
        <div class="card mb-4">
            <h2 class="text-sm font-bold text-[#7C4A2D] uppercase tracking-wide mb-4">Info Resep</h2>

            <div class="space-y-4">
                <div>
                    <label class="form-label" for="rNama">Nama Resep <span class="text-red-400">*</span></label>
                    <input type="text" id="rNama" class="input-field" placeholder="cth: Brownies Cokelat" maxlength="150" required>
                </div>

                <!-- Jumlah hasil & satuan — 2 kolom -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label" for="rJumlah">Jumlah Hasil <span class="text-red-400">*</span></label>
                        <input type="number" id="rJumlah" class="input-field" placeholder="cth: 10" min="0.01" step="any" required>
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
            </div>
        </div>

        <!-- ── Daftar bahan ── -->
        <div class="card mb-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-[#7C4A2D] uppercase tracking-wide">Daftar Bahan</h2>
                <button type="button" onclick="addBahanRow()"
                    class="flex items-center gap-1 text-sm text-[#7C4A2D] font-semibold hover:opacity-70 transition-opacity">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Bahan
                </button>
            </div>

            <?php if (empty($bahanOptions)): ?>
            <div class="alert-info text-center">
                <p>Belum ada bahan baku. <a href="/bakecost/pages/bahan_baku.php" class="font-semibold underline">Tambah bahan baku</a> terlebih dahulu.</p>
            </div>
            <?php else: ?>

            <!-- Bahan options sebagai JSON untuk JS -->
            <script>
                const BAHAN_OPTIONS = <?= json_encode($bahanOptions, JSON_UNESCAPED_UNICODE) ?>;
            </script>

            <!-- Container baris bahan -->
            <div id="bahanContainer" class="space-y-3">
                <!-- Baris pertama default -->
            </div>

            <!-- Total estimasi biaya bahan -->
            <div class="mt-4 pt-4 border-t border-[#F0E8DF] flex justify-between items-center">
                <p class="text-sm text-[#9E7A5A]">Estimasi biaya bahan</p>
                <p id="totalBiaya" class="font-bold text-[#7C4A2D]">Rp 0</p>
            </div>

            <?php endif; ?>
        </div>

        <!-- Tombol simpan -->
        <button id="submitBtn" onclick="submitResep()"
            class="btn-primary <?= empty($bahanOptions) ? 'opacity-50 cursor-not-allowed' : '' ?>"
            <?= empty($bahanOptions) ? 'disabled' : '' ?>>
            Simpan Resep
        </button>

    </main>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
// ── Tambah baris bahan ke form ──
let rowIndex = 0;

function addBahanRow(bahanId = '', jumlah = '', catatan = '') {
    rowIndex++;
    const idx = rowIndex;

    const optionsHtml = BAHAN_OPTIONS.map(b =>
        `<option value="${b.id}" data-satuan="${b.satuan}" data-harga="${b.harga_per_satuan}"
                 ${String(b.id) === String(bahanId) ? 'selected' : ''}>
            ${escHtml(b.nama)} (${b.satuan})
         </option>`
    ).join('');

    const row = document.createElement('div');
    row.className = 'flex gap-2 items-start';
    row.id = `brow-${idx}`;
    row.innerHTML = `
        <div class="flex-1 space-y-2">
            <select onchange="updateTotal()" class="input-field text-sm bahan-select" data-row="${idx}">
                <option value="">— Pilih bahan —</option>
                ${optionsHtml}
            </select>
            <div class="flex gap-2">
                <input type="number" value="${jumlah}" placeholder="Jumlah"
                       class="input-field text-sm flex-1 bahan-jumlah" data-row="${idx}"
                       min="0.001" step="any" oninput="updateTotal()">
                <span class="bahan-satuan text-[#9E7A5A] text-sm self-center w-8 text-right flex-shrink-0" data-row="${idx}"></span>
            </div>
            <input type="text" value="${escHtml(catatan)}" placeholder="Catatan (opsional)"
                   class="input-field text-sm bahan-catatan" data-row="${idx}" maxlength="150">
        </div>
        <button type="button" onclick="removeBahanRow(${idx})"
            class="mt-2 w-8 h-8 flex-shrink-0 rounded-full bg-red-50 flex items-center justify-center hover:bg-red-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `;
    document.getElementById('bahanContainer').appendChild(row);

    // Inisialisasi satuan jika bahan sudah dipilih
    const select = row.querySelector('.bahan-select');
    if (select.value) updateSatuanLabel(idx);
    select.addEventListener('change', () => updateSatuanLabel(idx));
}

function removeBahanRow(idx) {
    const row = document.getElementById(`brow-${idx}`);
    if (row) row.remove();
    updateTotal();
}

function updateSatuanLabel(idx) {
    const select = document.querySelector(`.bahan-select[data-row="${idx}"]`);
    const label  = document.querySelector(`.bahan-satuan[data-row="${idx}"]`);
    if (!select || !label) return;
    const opt = select.options[select.selectedIndex];
    label.textContent = opt ? (opt.dataset.satuan || '') : '';
}

// Hitung total estimasi biaya real-time
function updateTotal() {
    let total = 0;
    document.querySelectorAll('.bahan-select').forEach(select => {
        const idx    = select.dataset.row;
        const jumlah = parseFloat(document.querySelector(`.bahan-jumlah[data-row="${idx}"]`)?.value) || 0;
        const opt    = select.options[select.selectedIndex];
        const harga  = parseFloat(opt?.dataset.harga || 0);
        total += jumlah * harga;
    });
    document.getElementById('totalBiaya').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
}

// ── Submit resep ke API ──
async function submitResep() {
    const nama     = document.getElementById('rNama').value.trim();
    const jumlah   = parseFloat(document.getElementById('rJumlah').value);
    const satuan   = document.getElementById('rSatuan').value;
    const deskrips = document.getElementById('rDeskripsi').value.trim();

    if (!nama) return showAlert('Nama resep wajib diisi.', 'error');
    if (!jumlah || jumlah <= 0) return showAlert('Jumlah hasil harus lebih dari 0.', 'error');

    // Kumpulkan bahan dari form
    const bahan = [];
    document.querySelectorAll('.bahan-select').forEach(select => {
        const idx    = select.dataset.row;
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
        showToast('Resep berhasil disimpan ✓');
        setTimeout(() => window.location = `/bakecost/pages/resep_detail.php?id=${res.id}`, 1000);
    } catch (err) {
        showAlert(err.message, 'error');
        btn.disabled = false;
        btn.textContent = 'Simpan Resep';
    }
}

function showAlert(msg, type = 'error') {
    const el = document.getElementById('alertMsg');
    el.className = type === 'error' ? 'alert-error mb-4' : 'alert-success mb-4';
    el.textContent = msg;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Tambah satu baris default saat load
document.addEventListener('DOMContentLoaded', () => {
    if (typeof BAHAN_OPTIONS !== 'undefined') addBahanRow();
});
</script>
</body>
</html>

