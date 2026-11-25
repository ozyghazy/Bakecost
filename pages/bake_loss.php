<?php
/**
 * pages/bake_loss.php
 * Halaman kalkulator & catatan Bake Loss (penyusutan adonan saat dipanggang).
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bake Loss — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">
        <!-- Card Penjelasan -->
        <div class="card bg-[#FDE8D5] mb-4">
            <h2 class="text-sm font-bold text-[#5A2D0C]">Apa itu Bake Loss?</h2>
            <p class="text-xs text-[#7C4A2D] mt-1 leading-relaxed">
                Penyusutan kadar air saat pemanggangan. Formula: <br>
                <code>((Berat Sebelum - Berat Sesudah) / Berat Sebelum) × 100%</code>.
            </p>
        </div>

        <!-- Form Hitung & Simpan -->
        <div class="card mb-5">
            <h3 class="text-sm font-bold text-[#7C4A2D] uppercase tracking-wide mb-3">Hitung Penyusutan</h3>
            <form id="lossForm" onsubmit="saveLoss(event)" class="space-y-3">
                <div>
                    <label class="form-label" for="resepId">Pilih Resep <span class="text-red-400">*</span></label>
                    <select id="resepId" class="input-field text-sm" required>
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
                        <label class="form-label" for="beratSebelum">Sebelum Panggang (g) <span class="text-red-400">*</span></label>
                        <input type="number" id="beratSebelum" class="input-field text-sm" placeholder="cth: 550" min="0.1" step="any" required oninput="calcLoss()">
                    </div>
                    <div>
                        <label class="form-label" for="beratSesudah">Sesudah Panggang (g) <span class="text-red-400">*</span></label>
                        <input type="number" id="beratSesudah" class="input-field text-sm" placeholder="cth: 480" min="0.1" step="any" required oninput="calcLoss()">
                    </div>
                </div>

                <!-- Preview Real-time -->
                <div id="lossPreviewBox" class="p-3 rounded-xl bg-[#F5E0CE] text-[#3D1F0D] hidden text-center">
                    <span class="text-xs text-[#9E7A5A] block">Persentase Penyusutan</span>
                    <span id="lossPersenText" class="text-xl font-bold text-[#7C4A2D]">0%</span>
                    <span id="lossGramText" class="text-xs block text-[#9E7A5A] mt-0.5">Berkurang 0 g</span>
                </div>

                <div>
                    <label class="form-label" for="catatan">Catatan / Suhu Oven (opsional)</label>
                    <input type="text" id="catatan" class="input-field text-sm" placeholder="cth: 175°C selama 35 menit">
                </div>

                <button type="submit" id="btnSubmitLoss" class="btn-primary py-2.5 text-sm mt-1">
                    Simpan Catatan Bake Loss
                </button>
            </form>
        </div>

        <!-- Riwayat Bake Loss -->
        <div class="mb-4">
            <h3 class="text-sm font-bold text-[#3D1F0D] mb-2">Riwayat Uji Coba Bake Loss</h3>
            <div id="lossList" class="space-y-2">
                <div class="skeleton h-14 w-full"></div>
            </div>
            <div id="emptyLoss" class="hidden text-center py-6 card text-xs text-[#9E7A5A]">
                Belum ada data riwayat bake loss.
            </div>
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
    const box = document.getElementById('lossPreviewBox');

    if (sblm > 0 && ssdh > 0 && sblm >= ssdh) {
        const lossGram = sblm - ssdh;
        const lossPct = (lossGram / sblm) * 100;
        document.getElementById('lossPersenText').textContent = lossPct.toFixed(2) + '%';
        document.getElementById('lossGramText').textContent = `Berkurang ${lossGram.toFixed(1)} g dari berat awal`;
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
    const listEl = document.getElementById('lossList');
    const emptyEl = document.getElementById('emptyLoss');

    if (!bakeLossData.length) {
        listEl.innerHTML = '';
        emptyEl.classList.remove('hidden');
        return;
    }

    emptyEl.classList.add('hidden');
    listEl.innerHTML = bakeLossData.map(item => `
        <div class="card p-3 flex items-center justify-between">
            <div>
                <p class="font-semibold text-xs text-[#3D1F0D]">${escHtml(item.resep_nama)}</p>
                <p class="text-[11px] text-[#9E7A5A] mt-0.5">
                    ${item.berat_sebelum}g ➔ ${item.berat_sesudah}g
                    ${item.catatan ? `· <span class="italic">${escHtml(item.catatan)}</span>` : ''}
                </p>
                <p class="text-[10px] text-[#B09080] mt-0.5">${item.tanggal}</p>
            </div>
            <div class="text-right">
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-[#FDE8D5] text-[#7C4A2D]">
                    -${item.persen_loss}%
                </span>
                <button onclick="deleteLoss(${item.id})" class="block mt-1 text-[10px] text-red-400 hover:text-red-600">Hapus</button>
            </div>
        </div>
    `).join('');
}

async function saveLoss(e) {
    e.preventDefault();
    const resepId = parseInt(document.getElementById('resepId').value);
    const sblm = parseFloat(document.getElementById('beratSebelum').value);
    const ssdh = parseFloat(document.getElementById('beratSesudah').value);
    const catatan = document.getElementById('catatan').value.trim();

    if (ssdh > sblm) {
        showToast('⚠ Berat sesudah tidak boleh lebih besar dari berat sebelum!');
        return;
    }

    const btn = document.getElementById('btnSubmitLoss');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        await apiFetch('/bakecost/api/bake_loss.php', {
            method: 'POST',
            body: { resep_id: resepId, berat_sebelum: sblm, berat_sesudah: ssdh, catatan: catatan }
        });
        showToast('Catatan bake loss berhasil disimpan ✓');
        document.getElementById('lossForm').reset();
        document.getElementById('lossPreviewBox').classList.add('hidden');
        await loadBakeLoss();
    } catch (err) {
        showToast('⚠ ' + err.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan Catatan Bake Loss';
    }
}

async function deleteLoss(id) {
    if (!confirmDelete('Hapus catatan bake loss ini?')) return;
    try {
        await apiFetch(`/bakecost/api/bake_loss.php?id=${id}`, { method: 'DELETE' });
        showToast('Catatan dihapus ✓');
        await loadBakeLoss();
    } catch (err) {
        showToast('⚠ ' + err.message);
    }
}

function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>

