<?php
/**
 * pages/resep_detail.php
 * Detail resep, daftar bahan, aksi konversi/HPP, serta mode edit inline & hapus.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db      = getDB();
$userId  = currentUserId();
$resepId = (int) ($_GET['id'] ?? 0);

if (!$resepId) {
    header('Location: /bakecost/pages/resep.php');
    exit;
}

$stmt = $db->prepare('SELECT * FROM resep WHERE id = ? AND pengguna_id = ?');
$stmt->execute([$resepId, $userId]);
$resep = $stmt->fetch();

if (!$resep) {
    header('Location: /bakecost/pages/resep.php');
    exit;
}

$bahanStmt = $db->prepare(
    'SELECT rb.id AS rb_id, rb.bahan_id, b.nama AS bahan_nama, b.satuan, b.harga_per_satuan,
            rb.jumlah, rb.catatan
     FROM resep_bahan rb
     JOIN bahan_baku b ON b.id = rb.bahan_id
     WHERE rb.resep_id = ?
     ORDER BY b.nama ASC'
);
$bahanStmt->execute([$resepId]);
$bahanList = $bahanStmt->fetchAll();

$allBahan = $db->prepare('SELECT id, nama, satuan, harga_per_satuan FROM bahan_baku WHERE pengguna_id = ? ORDER BY nama ASC');
$allBahan->execute([$userId]);
$bahanOptions = $allBahan->fetchAll();

$totalBiaya = array_sum(array_map(fn($b) => $b['jumlah'] * $b['harga_per_satuan'], $bahanList));

$showBack  = true;
$pageTitle = $resep['nama'];
$backUrl   = '/bakecost/pages/resep.php';
$activeNav = 'resep';
$htmlTitle = $resep['nama'] . ' — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">

        <div id="alertMsg" class="hidden mb-4" role="alert"></div>

        <!-- MODE VIEW -->
        <div id="viewMode" class="editorial-split">

            <!-- Kiri: Daftar Bahan -->
            <section class="list-card">
                <div class="list-card__header">
                    <span>Komposisi Bahan (<?= count($bahanList) ?>)</span>
                    <button type="button" onclick="switchToEdit()" class="btn-action">Edit Resep</button>
                </div>

                <?php if (empty($bahanList)): ?>
                    <p class="text-muted text-sm p-6">Belum ada bahan. Klik "Edit Resep" untuk menambahkan.</p>
                <?php else: ?>
                    <div>
                        <?php foreach ($bahanList as $b): ?>
                            <div class="list-row">
                                <div class="list-row__main">
                                    <p class="list-row__title"><?= htmlspecialchars($b['bahan_nama']) ?></p>
                                    <?php if ($b['catatan']): ?>
                                        <p class="list-row__meta"><?= htmlspecialchars($b['catatan']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="list-row__stack">
                                    <p class="list-row__price list-row__price--ink">
                                        <?= rtrim(rtrim(number_format((float) $b['jumlah'], 3, '.', ''), '0'), '.') ?> <?= htmlspecialchars($b['satuan']) ?>
                                    </p>
                                    <p class="list-row__meta">
                                        Rp <?= number_format($b['jumlah'] * $b['harga_per_satuan'], 0, ',', '.') ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Kanan: Ringkasan & Aksi (Sticky di Desktop) -->
            <aside class="editorial-split__sticky space-y-4">
                <section class="editorial-hero space-y-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <p class="eyebrow">Detail Resep</p>
                            <?php if ($resep['dibuat_via_ai']): ?>
                                <span class="badge-ai">AI</span>
                            <?php endif; ?>
                        </div>
                        <h2 class="font-editorial text-2xl font-semibold text-ink">
                            <?= htmlspecialchars($resep['nama']) ?>
                        </h2>
                        <p class="text-sm text-secondary mt-1 tabular-nums">
                            Yield dasar: <?= $resep['jumlah_hasil'] ?> <?= htmlspecialchars($resep['satuan_hasil']) ?>
                        </p>
                        <?php if ($resep['deskripsi']): ?>
                            <p class="text-xs text-muted mt-2 leading-relaxed"><?= htmlspecialchars($resep['deskripsi']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="glass-panel p-4 flex items-center justify-between">
                        <span class="text-xs text-secondary">Estimasi Biaya Bahan</span>
                        <span class="font-editorial text-xl font-semibold text-accent tabular-nums">
                            Rp <?= number_format($totalBiaya, 0, ',', '.') ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-2.5">
                        <a href="/bakecost/pages/konversi.php?resep_id=<?= $resepId ?>" class="btn-primary">Konversi Resep</a>
                        <a href="/bakecost/pages/hpp_kalkulator.php?resep_id=<?= $resepId ?>" class="btn-secondary">Hitung HPP</a>
                        <a href="/bakecost/pages/bake_loss.php?resep_id=<?= $resepId ?>" class="btn-secondary">Catat Bake Loss</a>
                    </div>
                </section>

                <div class="flex items-center justify-between text-xs text-muted px-2">
                    <span>Dibuat <?= date('d M Y', strtotime($resep['created_at'])) ?></span>
                    <button type="button" onclick="hapusResep()" class="btn-action btn-action--danger">
                        Hapus Resep
                    </button>
                </div>
            </aside>
        </div>

        <!-- MODE EDIT -->
        <div id="editMode" class="hidden space-y-5">
            <script>
                const BAHAN_OPTIONS  = <?= json_encode($bahanOptions, JSON_UNESCAPED_UNICODE) ?>;
                const RESEP_ID       = <?= $resepId ?>;
                const EXISTING_BAHAN = <?= json_encode($bahanList, JSON_UNESCAPED_UNICODE) ?>;
            </script>

            <section class="card space-y-4">
                <p class="eyebrow">Edit Informasi Resep</p>
                <div>
                    <label class="form-label" for="eNama">Nama Resep</label>
                    <input type="text" id="eNama" class="input-field" value="<?= htmlspecialchars($resep['nama']) ?>" required>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label" for="eJumlah">Jumlah Hasil</label>
                        <input type="number" id="eJumlah" class="input-field tabular-nums" value="<?= $resep['jumlah_hasil'] ?>" min="0.01" step="any" required>
                    </div>
                    <div>
                        <label class="form-label" for="eSatuan">Satuan</label>
                        <select id="eSatuan" class="input-field">
                            <?php foreach (['pcs', 'porsi', 'loyang', 'g', 'kg'] as $s): ?>
                                <option value="<?= $s ?>" <?= $resep['satuan_hasil'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="form-label" for="eDeskripsi">Deskripsi</label>
                    <textarea id="eDeskripsi" class="input-field" rows="2"><?= htmlspecialchars($resep['deskripsi'] ?? '') ?></textarea>
                </div>
            </section>

            <section class="card space-y-4">
                <div class="flex items-center justify-between">
                    <p class="eyebrow">Daftar Bahan</p>
                    <button type="button" onclick="addBahanRow()" class="text-xs font-semibold text-accent hover:underline">+ Tambah Baris</button>
                </div>
                <div id="bahanContainer" class="space-y-3"></div>
                <div class="pt-3 border-t border-[var(--border-hairline)] flex justify-between items-center">
                    <span class="text-sm text-muted">Estimasi biaya bahan</span>
                    <span id="totalBiaya" class="font-bold text-accent tabular-nums">Rp 0</span>
                </div>
            </section>

            <div class="flex gap-3">
                <button type="button" onclick="switchToView()" class="btn-secondary flex-1">Batal</button>
                <button type="button" onclick="updateResep()" id="updateBtn" class="btn-primary flex-1">Simpan Perubahan</button>
            </div>
        </div>

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
    const optHtml = BAHAN_OPTIONS.map(b =>
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
            <select class="input-field text-sm bahan-select" data-row="${idx}" onchange="updateSatuanLabel(${idx}); updateTotal()">
                <option value="">— Pilih bahan —</option>
                ${optHtml}
            </select>
            <div class="grid grid-cols-12 gap-2 items-center">
                <input type="number" value="${jumlah}" placeholder="Jumlah" class="input-field text-sm col-span-5 tabular-nums bahan-jumlah" data-row="${idx}" min="0.001" step="any" oninput="updateTotal()">
                <span class="bahan-satuan text-muted text-xs col-span-2" data-row="${idx}"></span>
                <input type="text" value="${escHtml(catatan)}" placeholder="Catatan (opsional)" class="input-field text-sm col-span-5 bahan-catatan" data-row="${idx}" maxlength="150">
            </div>
        </div>
        <button type="button" onclick="document.getElementById('brow-${idx}').remove(); updateTotal()" aria-label="Hapus baris"
            class="mt-1 w-8 h-8 flex-shrink-0 rounded-full border border-[var(--border-hairline)] bg-white text-muted hover:text-[var(--state-error-text)] flex items-center justify-center">
            ×
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
    document.getElementById('totalBiaya').textContent = formatRupiah(total);
}

function switchToEdit() {
    document.getElementById('viewMode').classList.add('hidden');
    document.getElementById('editMode').classList.remove('hidden');
    document.getElementById('bahanContainer').innerHTML = '';
    rowIndex = 0;
    EXISTING_BAHAN.forEach(b => addBahanRow(b.bahan_id, b.jumlah, b.catatan || ''));
    updateTotal();
}

function switchToView() {
    document.getElementById('editMode').classList.add('hidden');
    document.getElementById('viewMode').classList.remove('hidden');
}

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
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        await apiFetch(`/bakecost/api/resep.php?id=${RESEP_ID}`, {
            method: 'PUT',
            body: { nama, jumlah_hasil: jumlah, satuan_hasil: satuan, deskripsi: deskrip, bahan },
        });
        showToast('Resep diperbarui');
        setTimeout(() => location.reload(), 800);
    } catch (err) {
        showAlert(err.message, 'error');
        btn.disabled = false;
        btn.textContent = 'Simpan Perubahan';
    }
}

async function hapusResep() {
    if (!confirmDelete('Hapus resep ini? Semua data bahan dan riwayat produksi terkait akan ikut terhapus.')) return;
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
</script>
</body>
</html>
