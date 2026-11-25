<?php
/**
 * pages/riwayat.php
 * Riwayat konversi bahan dan perhitungan biaya/HPP pengguna.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$showBack  = false;
$activeNav = 'riwayat';
$htmlTitle = 'Riwayat — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content space-y-5">

        <section class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="eyebrow">Arsip Aktivitas</p>
                <h1 class="editorial-title text-2xl sm:text-3xl font-semibold text-ink mt-0.5">Riwayat</h1>
            </div>

            <div class="flex gap-2" role="tablist" aria-label="Filter tipe riwayat">
                <button type="button" class="filter-tab chip chip-active" data-tipe="semua" onclick="setTipe('semua')">Semua</button>
                <button type="button" class="filter-tab chip" data-tipe="konversi" onclick="setTipe('konversi')">Konversi</button>
                <button type="button" class="filter-tab chip" data-tipe="biaya" onclick="setTipe('biaya')">Biaya</button>
            </div>
        </section>

        <section class="list-card">
            <div class="list-card__header">
                <span>Aktivitas Produksi</span>
                <span>Hasil / Total</span>
            </div>
            <div id="riwayatContainer">
                <div class="list-row"><div class="skeleton h-5 w-1/2"></div><div class="skeleton h-5 w-24"></div></div>
                <div class="list-row"><div class="skeleton h-5 w-1/2"></div><div class="skeleton h-5 w-24"></div></div>
            </div>
        </section>

        <section id="emptyRiwayat" class="hidden card text-center py-12">
            <p class="font-editorial text-lg font-semibold text-ink">Belum ada riwayat</p>
            <p class="text-xs text-muted mt-1">Simpan hasil konversi atau perhitungan HPP untuk melihatnya di sini.</p>
        </section>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
let allHistory = [];
let activeTipe = 'semua';

document.addEventListener('DOMContentLoaded', loadHistory);

async function loadHistory() {
    try {
        allHistory = await apiFetch('/bakecost/api/hpp.php');
        renderHistory();
    } catch (err) {
        showToast('Gagal memuat riwayat: ' + err.message);
    }
}

function setTipe(tipe) {
    activeTipe = tipe;
    document.querySelectorAll('.filter-tab').forEach(btn => {
        btn.classList.toggle('chip-active', btn.dataset.tipe === tipe);
    });
    renderHistory();
}

function renderHistory() {
    const container = document.getElementById('riwayatContainer');
    const emptyEl   = document.getElementById('emptyRiwayat');

    const filtered = activeTipe === 'semua'
        ? allHistory
        : allHistory.filter(item => item.tipe === activeTipe);

    if (!filtered.length) {
        container.innerHTML = '';
        emptyEl.classList.remove('hidden');
        return;
    }

    emptyEl.classList.add('hidden');
    container.innerHTML = filtered.map((item, idx) => {
        const isBiaya       = item.tipe === 'biaya';
        const dateFormatted = formatDateIndo(item.created_at);
        const numLabel      = '(' + String(idx + 1).padStart(2, '0') + ')';

        return `
            <article class="list-row list-row--clickable"
                     onclick="viewDetail(${item.id}, ${item.resep_id}, ${item.jumlah_produksi}, '${item.tipe}')">
                <div class="list-row__main">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span class="text-xs font-semibold text-muted tabular-nums">${numLabel}</span>
                        <p class="font-editorial text-base font-semibold text-ink truncate">
                            ${escHtml(item.resep_nama)}
                        </p>
                        <span class="${isBiaya ? 'badge-biaya' : 'badge-konversi'}">
                            ${isBiaya ? 'Biaya' : 'Konversi'}
                        </span>
                    </div>
                    <p class="list-row__meta">
                        ${formatNumber(item.jumlah_produksi)} ${escHtml(item.satuan_hasil || 'porsi')} · ${dateFormatted}
                    </p>
                </div>

                <div class="list-row__aside">
                    <div class="list-row__stack">
                        <p class="list-row__price ${isBiaya ? '' : 'list-row__price--ink'}">
                            ${isBiaya ? formatRupiah(item.hpp_total) : (formatNumber(item.jumlah_produksi) + ' ' + escHtml(item.satuan_hasil || 'porsi'))}
                        </p>
                        <button type="button" onclick="deleteHistory(event, ${item.id})" class="btn-action btn-action--danger">
                            Hapus
                        </button>
                    </div>
                    <span class="bento-card__arrow hidden sm:inline-flex" aria-hidden="true">→</span>
                </div>
            </article>
        `;
    }).join('');
}

function viewDetail(id, resepId, jumlah, tipe) {
    if (tipe === 'konversi') {
        window.location = `/bakecost/pages/konversi_hasil.php?resep_id=${resepId}&jumlah=${jumlah}`;
    } else {
        window.location = `/bakecost/pages/hitung_biaya.php?resep_id=${resepId}&jumlah=${jumlah}`;
    }
}

async function deleteHistory(e, id) {
    e.stopPropagation();
    if (!confirmDelete('Hapus catatan riwayat ini?')) return;
    try {
        await apiFetch(`/bakecost/api/hpp.php?id=${id}`, { method: 'DELETE' });
        showToast('Riwayat dihapus');
        await loadHistory();
    } catch (err) {
        showToast(err.message);
    }
}

function formatDateIndo(dtStr) {
    if (!dtStr) return '';
    const date   = new Date(dtStr.replace(' ', 'T'));
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}
</script>
</body>
</html>
