<?php
/**
 * pages/resep_ai.php — Halaman Generate Resep otomatis via AI.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$showBack  = true;
$pageTitle = 'Generate Resep AI';
$backUrl   = '/bakecost/pages/resep.php';
$activeNav = 'resep';
$htmlTitle = 'Generate Resep AI — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content space-y-5">

        <section class="editorial-hero space-y-2">
            <p class="eyebrow">Asisten Resep AI</p>
            <h2 class="font-editorial text-2xl font-semibold text-ink">Racik Ide Kue Otomatis</h2>
            <p class="text-sm text-secondary">
                Tuliskan ide kue yang ingin dibuat (misal: "Bolu kukus pandan keju 1 loyang"), AI akan menyusun komposisi bahan dan takarannya.
            </p>
        </section>

        <section class="card space-y-3">
            <label class="form-label" for="aiPrompt">Ide Kue / Produk Baking</label>
            <div class="flex flex-col sm:flex-row gap-2.5">
                <input type="text" id="aiPrompt" class="input-field flex-1"
                       placeholder="cth: Brownies Fudgy Cokelat Lumer"
                       value="Brownies Panggang Cokelat">
                <button type="button" onclick="generateRecipe()" id="btnGenerate" class="btn-primary sm:w-auto px-6">
                    Generate
                </button>
            </div>
        </section>

        <section id="aiLoading" class="hidden card text-center py-10 space-y-3" role="status" aria-live="polite">
            <div class="skeleton h-5 w-48 mx-auto"></div>
            <p class="text-xs text-muted">Sedang menyusun takaran bahan kue...</p>
        </section>

        <section id="aiResultCard" class="hidden card space-y-4">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="badge-ai mb-1.5">Hasil AI</span>
                    <h3 id="resNama" class="font-editorial text-2xl font-semibold text-ink"></h3>
                    <p id="resYield" class="text-xs text-muted mt-0.5 tabular-nums"></p>
                </div>
            </div>

            <p id="resDeskripsi" class="alert-info italic"></p>

            <div class="list-card">
                <div class="list-card__header">
                    <span>Komposisi Bahan</span>
                    <span>Takaran</span>
                </div>
                <div id="resBahanList"></div>
            </div>

            <p id="aiInfoNote" class="text-xs text-muted"></p>

            <button type="button" onclick="saveAiRecipe()" id="btnSaveAi" class="btn-primary">
                Simpan Resep ke Akun
            </button>
        </section>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
let currentAiRecipe = null;

async function generateRecipe() {
    const prompt = document.getElementById('aiPrompt').value.trim();
    if (!prompt) {
        showToast('Tuliskan ide kue terlebih dahulu.');
        return;
    }

    const btn     = document.getElementById('btnGenerate');
    const loading = document.getElementById('aiLoading');
    const result  = document.getElementById('aiResultCard');

    btn.disabled = true;
    loading.classList.remove('hidden');
    result.classList.add('hidden');

    try {
        const res = await apiFetch('/bakecost/api/ai_resep.php', {
            method: 'POST',
            body: { prompt },
        });
        currentAiRecipe = res.resep;
        displayRecipe(res);
    } catch (err) {
        showToast(err.message);
    } finally {
        btn.disabled = false;
        loading.classList.add('hidden');
    }
}

function displayRecipe(res) {
    const r = res.resep;
    document.getElementById('resNama').textContent      = r.nama;
    document.getElementById('resYield').textContent     = `Hasil: ${r.jumlah_hasil} ${r.satuan_hasil}`;
    document.getElementById('resDeskripsi').textContent = r.deskripsi || '';

    document.getElementById('resBahanList').innerHTML = (r.bahan || []).map(b => `
        <div class="list-row">
            <div class="list-row__main">
                <p class="list-row__title">${escHtml(b.nama)}</p>
                ${b.catatan ? `<p class="list-row__meta">${escHtml(b.catatan)}</p>` : ''}
            </div>
            <div class="list-row__aside">
                <span class="list-row__price">${b.jumlah} ${escHtml(b.satuan)}</span>
            </div>
        </div>
    `).join('');

    document.getElementById('aiInfoNote').textContent = res.info || 'Generated via Google Gemini AI.';
    document.getElementById('aiResultCard').classList.remove('hidden');
}

async function saveAiRecipe() {
    if (!currentAiRecipe) return;

    const btn = document.getElementById('btnSaveAi');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        const userBahan = await apiFetch('/bakecost/api/bahan_baku.php');
        const bahanMap  = {};
        userBahan.forEach(b => bahanMap[b.nama.toLowerCase()] = b.id);

        const preparedBahan = [];

        for (const b of (currentAiRecipe.bahan || [])) {
            const key   = b.nama.toLowerCase();
            let bahanId = bahanMap[key];

            if (!bahanId) {
                const autoCreated = await apiFetch('/bakecost/api/bahan_baku.php', {
                    method: 'POST',
                    body: {
                        nama:        b.nama,
                        satuan:      ['g', 'kg', 'ml', 'l', 'pcs', 'butir'].includes(b.satuan) ? b.satuan : 'g',
                        jumlah_beli: b.satuan === 'butir' ? 10 : 1000,
                        harga_beli:  b.satuan === 'butir' ? 20000 : 15000,
                        kategori:    'Lainnya',
                    },
                });
                bahanId = autoCreated.data.id;
                bahanMap[key] = bahanId;
            }

            preparedBahan.push({
                bahan_id: bahanId,
                jumlah:   b.jumlah,
                catatan:  b.catatan || 'Takaran AI',
            });
        }

        const savedResep = await apiFetch('/bakecost/api/resep.php', {
            method: 'POST',
            body: {
                nama:          currentAiRecipe.nama,
                jumlah_hasil:  currentAiRecipe.jumlah_hasil,
                satuan_hasil:  currentAiRecipe.satuan_hasil,
                deskripsi:     currentAiRecipe.deskripsi,
                dibuat_via_ai: true,
                bahan:         preparedBahan,
            },
        });

        showToast('Resep AI berhasil disimpan');
        setTimeout(() => {
            window.location = `/bakecost/pages/resep_detail.php?id=${savedResep.id}`;
        }, 1000);
    } catch (err) {
        showToast('Gagal menyimpan: ' + err.message);
        btn.disabled = false;
        btn.textContent = 'Simpan Resep ke Akun';
    }
}
</script>
</body>
</html>
