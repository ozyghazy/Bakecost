<?php
/**
 * pages/resep_detail.php
 * Detail resep: tampilkan info & daftar bahan, bisa edit atau hapus.
 * Mode edit: form inline yang sama dengan resep_tambah.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();
$resepId = (int) ($_GET['id'] ?? 0);

if (!$resepId) {
    header('Location: /bakecost/pages/resep.php');
    exit;
}

// Ambil resep
$stmt = $db->prepare('SELECT * FROM resep WHERE id = ? AND pengguna_id = ?');
$stmt->execute([$resepId, $userId]);
$resep = $stmt->fetch();

if (!$resep) {
    header('Location: /bakecost/pages/resep.php');
    exit;
}

// Ambil bahan resep
$bahanStmt = $db->prepare(
    'SELECT rb.id as rb_id, rb.bahan_id, b.nama AS bahan_nama, b.satuan, b.harga_per_satuan,
            rb.jumlah, rb.catatan
     FROM resep_bahan rb
     JOIN bahan_baku b ON b.id = rb.bahan_id
     WHERE rb.resep_id = ?
     ORDER BY b.nama ASC'
);
$bahanStmt->execute([$resepId]);
$bahanList = $bahanStmt->fetchAll();

// Semua bahan baku user (untuk dropdown edit)
$allBahan = $db->prepare('SELECT id, nama, satuan, harga_per_satuan FROM bahan_baku WHERE pengguna_id = ? ORDER BY nama ASC');
$allBahan->execute([$userId]);
$bahanOptions = $allBahan->fetchAll();

// Hitung total estimasi biaya bahan
$totalBiaya = array_sum(array_map(fn($b) => $b['jumlah'] * $b['harga_per_satuan'], $bahanList));

$showBack  = true;
$pageTitle = htmlspecialchars($resep['nama']);
$backUrl   = '/bakecost/pages/resep.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($resep['nama']) ?> — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">

        <!-- Alert -->
        <div id="alertMsg" class="hidden mb-4"></div>

        <!-- ── Mode VIEW (default) ── -->
        <div id="viewMode">

            <!-- Header resep -->
            <div class="card mb-4">
                <div class="flex items-start gap-3">
                    <div class="w-14 h-14 rounded-xl bg-[#EAD9C8] flex items-center justify-center text-3xl flex-shrink-0">🧁</div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="font-bold text-[#3D1F0D] text-lg"><?= htmlspecialchars($resep['nama']) ?></h1>
                            <?php if ($resep['dibuat_via_ai']): ?>
                            <span class="text-[10px] bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-semibold">✨ AI</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-[#9E7A5A] text-sm">Resep untuk <?= $resep['jumlah_hasil'] ?> <?= htmlspecialchars($resep['satuan_hasil']) ?></p>
                        <?php if ($resep['deskripsi']): ?>
                        <p class="text-[#5A2D0C] text-xs mt-1"><?= htmlspecialchars($resep['deskripsi']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tombol aksi -->
                <div class="flex gap-2 mt-4">
                    <a href="/bakecost/pages/konversi.php?resep_id=<?= $resepId ?>"
                       class="btn-primary flex-1 text-center text-sm py-2.5">⚖ Konversi</a>
                    <a href="/bakecost/pages/hpp_kalkulator.php?resep_id=<?= $resepId ?>"
                       class="btn-secondary flex-1 text-center text-sm py-2.5">💰 Hitung HPP</a>
                </div>
            </div>

            <!-- Daftar bahan -->
            <div class="card mb-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-bold text-[#7C4A2D] uppercase tracking-wide">
                        Bahan (<?= count($bahanList) ?>)
                    </h2>
                    <button onclick="switchToEdit()" class="text-sm text-[#7C4A2D] font-semibold hover:underline">
                        ✏ Edit Resep
                    </button>
                </div>

                <?php if (empty($bahanList)): ?>
                <p class="text-[#9E7A5A] text-sm">Belum ada bahan. Klik "Edit Resep" untuk menambahkan.</p>
                <?php else: ?>

                <!-- Header tabel -->
                <div class="flex justify-between text-[11px] font-semibold text-[#9E7A5A] uppercase tracking-wide mb-1">
                    <span>Bahan</span>
                    <span>Jumlah · Estimasi</span>
                </div>

                <div class="divide-y divide-[#F0E8DF]">
                    <?php foreach ($bahanList as $b): ?>
                    <div class="table-row">
                        <div>
                            <p class="font-medium text-[#3D1F0D] text-sm"><?= htmlspecialchars($b['bahan_nama']) ?></p>
                            <?php if ($b['catatan']): ?>
                            <p class="text-[#9E7A5A] text-xs"><?= htmlspecialchars($b['catatan']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-[#3D1F0D] text-sm">
                                <?= rtrim(rtrim(number_format($b['jumlah'], 3, '.', ''), '0'), '.') ?> <?= $b['satuan'] ?>
                            </p>
                            <p class="text-[#9E7A5A] text-xs">
                                Rp <?= number_format($b['jumlah'] * $b['harga_per_satuan'], 0, ',', '.') ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Total -->
                <div class="flex justify-between items-center mt-3 pt-3 border-t border-[#EAD9C8]">
                    <p class="text-sm text-[#9E7A5A]">Total estimasi bahan</p>
                    <p class="font-bold text-[#7C4A2D]">Rp <?= number_format($totalBiaya, 0, ',', '.') ?></p>
                </div>

                <?php endif; ?>
            </div>

            <!-- Info tambahan -->
            <div class="card mb-4">
                <div class="flex justify-between">
                    <div>
                        <p class="text-xs text-[#9E7A5A]">Dibuat</p>
                        <p class="text-sm font-medium text-[#3D1F0D]">
                            <?= date('d M Y', strtotime($resep['created_at'])) ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-[#9E7A5A]">Bake Loss</p>
                        <a href="/bakecost/pages/bake_loss.php?resep_id=<?= $resepId ?>"
                           class="text-sm font-semibold text-[#7C4A2D] hover:underline">Lihat / Tambah →</a>
                    </div>
                </div>
            </div>

            <!-- Tombol hapus -->
            <button onclick="hapusResep()" class="w-full py-3 text-red-500 text-sm font-semibold hover:text-red-700 transition-colors">
                🗑 Hapus Resep
            </button>
        </div>

        <!-- ── Mode EDIT (tersembunyi default) ── -->
        <div id="editMode" class="hidden">

            <script>
                const BAHAN_OPTIONS = <?= json_encode($bahanOptions, JSON_UNESCAPED_UNICODE) ?>;
                const RESEP_ID      = <?= $resepId ?>;
                const EXISTING_BAHAN = <?= json_encode($bahanList, JSON_UNESCAPED_UNICODE) ?>;
            </script>

            <div class="card mb-4">
                <h2 class="text-sm font-bold text-[#7C4A2D] uppercase tracking-wide mb-4">Info Resep</h2>
                <div class="space-y-3">
                    <div>
                        <label class="form-label">Nama Resep</label>
                        <input type="text" id="eNama" class="input-field" value="<?= htmlspecialchars($resep['nama']) ?>" required>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Jumlah Hasil</label>
                            <input type="number" id="eJumlah" class="input-field" value="<?= $resep['jumlah_hasil'] ?>" min="0.01" step="any" required>
                        </div>
                        <div>
                            <label class="form-label">Satuan</label>
                            <select id="eSatuan" class="input-field">
                                <?php foreach (['pcs','porsi','loyang','g','kg'] as $s): ?>
                                <option value="<?= $s ?>" <?= $resep['satuan_hasil'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Deskripsi</label>
                        <textarea id="eDeskripsi" class="input-field" rows="2"><?= htmlspecialchars($resep['deskripsi'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold text-[#7C4A2D] uppercase tracking-wide">Daftar Bahan</h2>
                    <button type="button" onclick="addBahanRow()" class="text-sm text-[#7C4A2D] font-semibold hover:underline">+ Tambah</button>
                </div>
                <div id="bahanContainer" class="space-y-3"></div>
                <div class="mt-4 pt-4 border-t border-[#F0E8DF] flex justify-between items-center">
                    <p class="text-sm text-[#9E7A5A]">Estimasi biaya bahan</p>
                    <p id="totalBiaya" class="font-bold text-[#7C4A2D]">Rp 0</p>
                </div>
            </div>

            <div class="flex gap-3">
                <button onclick="switchToView()" class="btn-secondary flex-1">Batal</button>
                <button onclick="updateResep()" id="updateBtn" class="btn-primary flex-1">Simpan Perubahan</button>
            </div>
        </div>

    </main>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
let rowIndex = 0;

// ── Tambah baris bahan ──
function addBahanRow(bahanId = '', jumlah = '', catatan = '') {
    rowIndex++;
    const idx = rowIndex;
    const optHtml = BAHAN_OPTIONS.map(b =>
        `<option value="${b.id}" data-satuan="${b.satuan}" data-harga="${b.harga_per_satuan}"
                 ${String(b.id) === String(bahanId) ? 'selected' : ''}>
            ${escH(b.nama)} (${b.satuan})
         </option>`
    ).join('');

    const row = document.createElement('div');
    row.className = 'flex gap-2 items-start';
    row.id = `brow-${idx}`;
    row.innerHTML = `
        <div class="flex-1 space-y-2">
            <select class="input-field text-sm bahan-select" data-row="${idx}" onchange="updateSatuanLabel(${idx}); updateTotal()">
                <option value="">— Pilih bahan —</option>
                ${optHtml}
            </select>
            <div class="flex gap-2">
                <input type="number" value="${jumlah}" placeholder="Jumlah" class="input-field text-sm flex-1 bahan-jumlah" data-row="${idx}" min="0.001" step="any" oninput="updateTotal()">
                <span class="bahan-satuan text-[#9E7A5A] text-sm self-center w-8 text-right flex-shrink-0" data-row="${idx}"></span>
            </div>
            <input type="text" value="${escH(catatan)}" placeholder="Catatan (opsional)" class="input-field text-sm bahan-catatan" data-row="${idx}" maxlength="150">
        </div>
        <button type="button" onclick="document.getElementById('brow-${idx}').remove(); updateTotal()"
            class="mt-2 w-8 h-8 flex-shrink-0 rounded-full bg-red-50 flex items-center justify-center hover:bg-red-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `;
    document.getElementById('bahanContainer').appendChild(row);
    if (bahanId) { updateSatuanLabel(idx); updateTotal(); }
}

function updateSatuanLabel(idx) {
    const sel = document.querySelector(`.bahan-select[data-row="${idx}"]`);
    const lbl = document.querySelector(`.bahan-satuan[data-row="${idx}"]`);
    if (sel && lbl) lbl.textContent = sel.options[sel.selectedIndex]?.dataset.satuan || '';
}

function updateTotal() {
    let total = 0;
    document.querySelectorAll('.bahan-select').forEach(sel => {
        const idx    = sel.dataset.row;
        const jumlah = parseFloat(document.querySelector(`.bahan-jumlah[data-row="${idx}"]`)?.value) || 0;
        const harga  = parseFloat(sel.options[sel.selectedIndex]?.dataset.harga || 0);
        total += jumlah * harga;
    });
    document.getElementById('totalBiaya').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
}

// ── Switch antara view dan edit mode ──
function switchToEdit() {
    document.getElementById('viewMode').classList.add('hidden');
    document.getElementById('editMode').classList.remove('hidden');

    // Isi ulang bahan
    document.getElementById('bahanContainer').innerHTML = '';
    rowIndex = 0;
    EXISTING_BAHAN.forEach(b => addBahanRow(b.bahan_id, b.jumlah, b.catatan || ''));
    updateTotal();
}

function switchToView() {
    document.getElementById('editMode').classList.add('hidden');
    document.getElementById('viewMode').classList.remove('hidden');
}

// ── Update resep ──
async function updateResep() {
    const nama    = document.getElementById('eNama').value.trim();
    const jumlah  = parseFloat(document.getElementById('eJumlah').value);
    const satuan  = document.getElementById('eSatuan').value;
    const deskrip = document.getElementById('eDeskripsi').value.trim();

    if (!nama || !jumlah) return showAlert('Nama dan jumlah hasil wajib diisi.', 'error');

    const bahan = [];
    document.querySelectorAll('.bahan-select').forEach(sel => {
        const idx     = sel.dataset.row;
        const bahanId = parseInt(sel.value);
        const jumlahB = parseFloat(document.querySelector(`.bahan-jumlah[data-row="${idx}"]`)?.value);
        const catatan = document.querySelector(`.bahan-catatan[data-row="${idx}"]`)?.value.trim();
        if (bahanId && jumlahB > 0) bahan.push({ bahan_id: bahanId, jumlah: jumlahB, catatan: catatan || '' });
    });

    if (!bahan.length) return showAlert('Tambahkan minimal 1 bahan.', 'error');

    const btn = document.getElementById('updateBtn');
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    try {
        await apiFetch(`/bakecost/api/resep.php?id=${RESEP_ID}`, {
            method: 'PUT',
            body: { nama, jumlah_hasil: jumlah, satuan_hasil: satuan, deskripsi: deskrip, bahan },
        });
        showToast('Resep diperbarui ✓');
        setTimeout(() => location.reload(), 900);
    } catch (err) {
        showAlert(err.message, 'error');
        btn.disabled = false; btn.textContent = 'Simpan Perubahan';
    }
}

// ── Hapus resep ──
async function hapusResep() {
    if (!confirmDelete('Hapus resep ini? Semua data bahan dan riwayat produksi akan ikut terhapus.')) return;
    try {
        await apiFetch(`/bakecost/api/resep.php?id=${RESEP_ID}`, { method: 'DELETE' });
        showToast('Resep dihapus');
        setTimeout(() => window.location = '/bakecost/pages/resep.php', 800);
    } catch (err) {
        showAlert(err.message, 'error');
    }
}

function showAlert(msg, type) {
    const el = document.getElementById('alertMsg');
    el.className = type === 'error' ? 'alert-error mb-4' : 'alert-success mb-4';
    el.textContent = msg;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function escH(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>

