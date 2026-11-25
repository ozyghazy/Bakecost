<?php
/**
 * pages/bake_loss.php
 * Kalkulator & riwayat catatan Bake Loss (penyusutan berat pasca panggang).
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$userId = currentUserId();

$resepStmt = $db->prepare('SELECT id, nama FROM resep WHERE pengguna_id = ? ORDER BY nama ASC');
$resepStmt->execute([$userId]);
$resepList = $resepStmt->fetchAll();

$preResepId = (int) ($_GET['resep_id'] ?? 0);

$showBack  = true;
$pageTitle = 'Kalkulator Bake Loss';
$backUrl   = '/bakecost/pages/beranda.php';
$activeNav = 'beranda';
$htmlTitle = 'Bake Loss — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content space-y-5">

        <section class="editorial-hero space-y-1.5">
            <p class="eyebrow">Penyusutan Oven</p>
            <h2 class="font-editorial text-2xl sm:text-3xl font-semibold text-ink">Bake Loss Calculator</h2>
            <p class="text-xs text-secondary leading-relaxed">
                Hitung persentase penyusutan kadar air saat pemanggangan dengan formula
                <code class="font-mono text-ink">((Berat Sebelum − Berat Sesudah) / Berat Sebelum) × 100%</code>.
            </p>
        </section>

        <div class="editorial-split">
            <section class="list-card">
                <div class="list-card__header">
                    <span>Riwayat Uji Bake Loss</span>
                    <span>Penyusutan</span>
                </div>
                <div id="lossList">
                    <div class="list-row"><div class="skeleton h-5 w-1/2"></div><div class="skeleton h-5 w-20"></div></div>
                </div>
                <div id="emptyLoss" class="hidden text-center py-10 px-4 text-xs text-muted">
                    Belum ada data riwayat bake loss.
                </div>
            </section>

            <aside class="editorial-split__sticky">
                <section class="card space-y-4">
                    <p class="eyebrow">Catat Uji Panggang</p>

                    <form id="lossForm" onsubmit="saveLoss(event)" class="space-y-4">
                        <div>
                            <label class="form-label" for="resepId">Pilih Resep <span class="text-red-500">*</span></label>
                            <select id="resepId" class="input-field" required>
                                <option value="">— Pilih Resep —</option>
                                <?php foreach ($resepList as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $r['id'] === $preResepId ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label" for="beratSebelum">Sebelum (g) <span class="text-red-500">*</span></label>
                                <input type="number" id="beratSebelum" class="input-field tabular-nums" placeholder="cth: 550" min="0.1" step="any" required oninput="calcLoss()">
                            </div>
                            <div>
                                <label class="form-label" for="beratSesudah">Sesudah (g) <span class="text-red-500">*</span></label>
                                <input type="number" id="beratSesudah" class="input-field tabular-nums" placeholder="cth: 480" min="0.1" step="any" required oninput="calcLoss()">
                            </div>
                        </div>

                        <div id="lossPreviewBox" class="card-accent text-center hidden space-y-0.5">
                            <span class="eyebrow block">Persentase Penyusutan</span>
                            <span id="lossPersenText" class="font-editorial text-3xl font-semibold text-accent tabular-nums block">0%</span>
                            <span id="lossGramText" class="text-xs text-muted tabular-nums block">Berkurang 0 g</span>
                        </div>

                        <div>
                            <label class="form-label" for="catatan">Catatan / Suhu Oven (opsional)</label>
                            <input type="text" id="catatan" class="input-field" placeholder="cth: 175°C selama 35 menit">
                        </div>

                        <button type="submit" id="btnSubmitLoss" class="btn-primary">
                            Simpan Catatan Bake Loss
                        </button>
                    </form>
                </section>
            </aside>
        </div>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
let bakeLossData = [];

document.addEventListener('DOMContentLoaded', loadBakeLoss);

function calcLoss() {
    const sblm = parseFloat(document.getElementById('beratSebelum').value) || 0;
    const ssdh = parseFloat(document.getElementById('beratSesudah').value) || 0;
    const box  = document.getElementById('lossPreviewBox');

    if (sblm > 0 && ssdh > 0 && sblm >= ssdh) {
        const lossGram = sblm - ssdh;
        const lossPct  = (lossGram / sblm) * 100;
        document.getElementById('lossPersenText').textContent = lossPct.toFixed(2) + '%';
        document.getElementById('lossGramText').textContent   = `Berkurang ${lossGram.toFixed(1)} g dari berat awal`;
        box.classList.remove('hidden');
    } else {
        box.classList.add('hidden');
    }
}

async function loadBakeLoss() {
    try {
        bakeLossData = await apiFetch('/bakecost/api/bake_loss.php');
        renderBakeLoss();
    } catch (err) {
        showToast('Gagal memuat riwayat: ' + err.message);
    }
}

function renderBakeLoss() {
    const listEl  = document.getElementById('lossList');
    const emptyEl = document.getElementById('emptyLoss');

    if (!bakeLossData.length) {
        listEl.innerHTML = '';
        emptyEl.classList.remove('hidden');
        return;
    }

    emptyEl.classList.add('hidden');
    listEl.innerHTML = bakeLossData.map(item => `
        <article class="list-row">
            <div class="list-row__main">
                <p class="list-row__title truncate">${escHtml(item.resep_nama)}</p>
                <p class="list-row__meta">
                    ${item.berat_sebelum} g → ${item.berat_sesudah} g · ${escHtml(item.tanggal)}
                    ${item.catatan ? `· <span class="italic">${escHtml(item.catatan)}</span>` : ''}
                </p>
            </div>
            <div class="list-row__stack">
                <span class="badge-biaya tabular-nums">−${item.persen_loss}%</span>
                <button type="button" onclick="deleteLoss(${item.id})" class="btn-action btn-action--danger">Hapus</button>
            </div>
        </article>
    `).join('');
}

async function saveLoss(e) {
    e.preventDefault();
    const resepId = parseInt(document.getElementById('resepId').value);
    const sblm    = parseFloat(document.getElementById('beratSebelum').value);
    const ssdh    = parseFloat(document.getElementById('beratSesudah').value);
    const catatan = document.getElementById('catatan').value.trim();

    if (ssdh > sblm) {
        showToast('Berat sesudah tidak boleh lebih besar dari berat sebelum.');
        return;
    }

    const btn = document.getElementById('btnSubmitLoss');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        await apiFetch('/bakecost/api/bake_loss.php', {
            method: 'POST',
            body: { resep_id: resepId, berat_sebelum: sblm, berat_sesudah: ssdh, catatan },
        });
        showToast('Catatan bake loss disimpan');
        document.getElementById('lossForm').reset();
        document.getElementById('lossPreviewBox').classList.add('hidden');
        await loadBakeLoss();
    } catch (err) {
        showToast(err.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan Catatan Bake Loss';
    }
}

async function deleteLoss(id) {
    if (!confirmDelete('Hapus catatan bake loss ini?')) return;
    try {
        await apiFetch(`/bakecost/api/bake_loss.php?id=${id}`, { method: 'DELETE' });
        showToast('Catatan dihapus');
        await loadBakeLoss();
    } catch (err) {
        showToast(err.message);
    }
}
</script>
</body>
</html>
