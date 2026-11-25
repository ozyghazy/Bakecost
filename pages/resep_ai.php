<?php
/**
 * pages/resep_ai.php
 * Halaman Generate Resep otomatis via AI.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$showBack  = true;
$pageTitle = 'Generate Resep AI';
$backUrl   = '/bakecost/pages/resep.php';
$activeNav = 'beranda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Resep AI — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">
        <!-- Banner AI -->
        <div class="card bg-purple-50 border border-purple-200 mb-4">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xl">✨</span>
                <h2 class="text-sm font-bold text-purple-900">Asisten Resep AI</h2>
            </div>
            <p class="text-xs text-purple-700 leading-relaxed">
                Tuliskan ide kue yang ingin dibuat (misal: "Bolu kukus pandan keju 1 loyang"), AI akan meracik komposisi bahan dan takarannya.
            </p>
        </div>

        <!-- Form Prompt -->
        <div class="card mb-4">
            <label class="form-label text-xs">Ide Kue / Baking</label>
            <div class="flex gap-2">
                <input type="text" id="aiPrompt" class="input-field text-sm flex-1"
                       placeholder="cth: Brownies Fudgy Cokelat Lumer"
                       value="Brownies Panggang Cokelat">
                <button onclick="generateRecipe()" id="btnGenerate" class="btn-primary text-xs w-auto px-4 py-2.5">
                    Generate
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div id="aiLoading" class="hidden text-center py-8">
            <div class="inline-block animate-spin text-3xl mb-2">🔄</div>
            <p class="text-xs text-[#7C4A2D] font-semibold">Sedang meracik takaran bahan kue...</p>
        </div>

        <!-- Hasil Generate Resep -->
        <div id="aiResultCard" class="hidden">
            <div class="card mb-4 border border-[#C17A3E]">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <span class="text-[10px] bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-bold">Resep AI</span>
                        <h3 id="resNama" class="text-base font-bold text-[#3D1F0D] mt-1"></h3>
                        <p id="resYield" class="text-xs text-[#9E7A5A]"></p>
                    </div>
                </div>

                <p id="resDeskripsi" class="text-xs text-[#5A2D0C] bg-[#FDE8D5] p-2.5 rounded-xl my-3 italic"></p>

                <h4 class="text-xs font-bold text-[#7C4A2D] uppercase tracking-wide mb-2">Komposisi Bahan:</h4>
                <div id="resBahanList" class="divide-y divide-[#F0E8DF] text-xs mb-3"></div>

                <div id="aiInfoNote" class="text-[11px] text-[#9E7A5A] mb-3"></div>

                <button onclick="saveAiRecipe()" id="btnSaveAi" class="btn-primary text-sm py-2.5">
                    Simpan Resep Ini ke Akun
                </button>
            </div>
        </div>
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

    const btn = document.getElementById('btnGenerate');
    const loading = document.getElementById('aiLoading');
    const result = document.getElementById('aiResultCard');

    btn.disabled = true;
    loading.classList.remove('hidden');
    result.classList.add('hidden');

    try {
        const res = await apiFetch('/bakecost/api/ai_resep.php', {
            method: 'POST',
            body: { prompt }
        });

        currentAiRecipe = res.resep;
        displayRecipe(res);
    } catch (err) {
        showToast('⚠ ' + err.message);
    } finally {
        btn.disabled = false;
        loading.classList.add('hidden');
    }
}

function displayRecipe(res) {
    const r = res.resep;
    document.getElementById('resNama').textContent = r.nama;
    document.getElementById('resYield').textContent = `Hasil: ${r.jumlah_hasil} ${r.satuan_hasil}`;
    document.getElementById('resDeskripsi').textContent = r.deskripsi || '';

    const bahanEl = document.getElementById('resBahanList');
    bahanEl.innerHTML = (r.bahan || []).map(b => `
        <div class="py-2 flex items-center justify-between">
            <div>
                <span class="font-medium text-[#3D1F0D]">${escHtml(b.nama)}</span>
                ${b.catatan ? `<span class="text-[#9E7A5A] text-[11px] block">(${escHtml(b.catatan)})</span>` : ''}
            </div>
            <span class="font-bold text-[#7C4A2D]">${b.jumlah} ${escHtml(b.satuan)}</span>
        </div>
    `).join('');

    const infoEl = document.getElementById('aiInfoNote');
    infoEl.textContent = res.info || 'Generated via Google Gemini AI.';

    document.getElementById('aiResultCard').classList.remove('hidden');
}

async function saveAiRecipe() {
    if (!currentAiRecipe) return;

    const btn = document.getElementById('btnSaveAi');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        // Ambil daftar bahan baku milik user untuk mencocokkan atau membuat baru
        const userBahan = await apiFetch('/bakecost/api/bahan_baku.php');
        const bahanMap = {};
        userBahan.forEach(b => bahanMap[b.nama.toLowerCase()] = b.id);

        const preparedBahan = [];

        for (const b of (currentAiRecipe.bahan || [])) {
            const key = b.nama.toLowerCase();
            let bahanId = bahanMap[key];

            // Jika bahan belum ada di DB user, auto-buat dengan estimasi harga wajar
            if (!bahanId) {
                const autoCreated = await apiFetch('/bakecost/api/bahan_baku.php', {
                    method: 'POST',
                    body: {
                        nama: b.nama,
                        satuan: ['g','kg','ml','l','pcs','butir'].includes(b.satuan) ? b.satuan : 'g',
                        jumlah_beli: b.satuan === 'butir' ? 10 : 1000,
                        harga_beli: b.satuan === 'butir' ? 20000 : 15000,
                        kategori: 'Lainnya'
                    }
                });
                bahanId = autoCreated.data.id;
                bahanMap[key] = bahanId;
            }

            preparedBahan.push({
                bahan_id: bahanId,
                jumlah: b.jumlah,
                catatan: b.catatan || 'Takaran AI'
            });
        }

        const savedResep = await apiFetch('/bakecost/api/resep.php', {
            method: 'POST',
            body: {
                nama: currentAiRecipe.nama,
                jumlah_hasil: currentAiRecipe.jumlah_hasil,
                satuan_hasil: currentAiRecipe.satuan_hasil,
                deskripsi: currentAiRecipe.deskripsi,
                dibuat_via_ai: true,
                bahan: preparedBahan
            }
        });

        showToast('Resep AI berhasil disimpan ke akun! ✓');
        setTimeout(() => {
            window.location = `/bakecost/pages/resep_detail.php?id=${savedResep.id}`;
        }, 1200);

    } catch (err) {
        showToast('⚠ Gagal menyimpan: ' + err.message);
        btn.disabled = false;
        btn.textContent = 'Simpan Resep Ini ke Akun';
    }
}

function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>

