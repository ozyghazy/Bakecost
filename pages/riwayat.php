<?php
/**
 * pages/riwayat.php
 * Sesuai desain "Riwayat.png": filter Semua / Konversi / Biaya,
 * riwayat kalkulasi konversi dan biaya produksi pengguna.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$showBack  = false;
$pageTitle = 'Riwayat';
$activeNav = 'riwayat';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">

    <header class="sticky top-0 z-40 bg-[#FDF5EE] border-b border-[#EAD9C8] px-4 py-3 flex items-center justify-between">
        <div>
            <h1 class="text-[20px] font-bold text-[#3D1F0D]">Riwayat</h1>
        </div>
        <a href="/bakecost/pages/profil.php" class="w-9 h-9 rounded-full bg-[#EAD9C8] flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#7C4A2D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </a>
    </header>

    <main class="page-content">
        <!-- Filter Tabs (Sesuai UI: Semua, Konversi, Biaya) -->
        <div class="flex gap-2 mb-4">
            <button class="filter-tab chip chip-active" data-tipe="semua" onclick="setTipe('semua')">Semua</button>
            <button class="filter-tab chip" data-tipe="konversi" onclick="setTipe('konversi')">Konversi</button>
            <button class="filter-tab chip" data-tipe="biaya" onclick="setTipe('biaya')">Biaya</button>
        </div>

        <!-- List Riwayat -->
        <div id="riwayatContainer" class="space-y-3 mb-6">
            <div class="skeleton h-20 w-full"></div>
            <div class="skeleton h-20 w-full"></div>
        </div>

        <div id="emptyRiwayat" class="hidden text-center py-12 card">
            <div class="text-4xl mb-2">📜</div>
            <p class="font-semibold text-sm text-[#7C4A2D]">Belum ada riwayat</p>
            <p class="text-xs text-[#9E7A5A] mt-1">Lakukan konversi bahan atau hitung biaya untuk melihat riwayat.</p>
        </div>
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
    const emptyEl = document.getElementById('emptyRiwayat');

    let filtered = allHistory;
    if (activeTipe !== 'semua') {
        filtered = allHistory.filter(item => item.tipe === activeTipe);
    }

    if (!filtered.length) {
        container.innerHTML = '';
        emptyEl.classList.remove('hidden');
        return;
    }

    emptyEl.classList.add('hidden');
    container.innerHTML = filtered.map(item => {
        const isBiaya = item.tipe === 'biaya';
        const dateFormatted = formatDateIndo(item.created_at);

        return `
            <div class="card p-3 flex items-center gap-3 hover:shadow-md transition-shadow cursor-pointer"
                 onclick="viewDetail(${item.id}, ${item.resep_id}, ${item.jumlah_produksi}, '${item.tipe}')">
                <div class="w-14 h-14 rounded-xl bg-[#EAD9C8] flex items-center justify-center text-2xl flex-shrink-0">
                    🧁
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-sm text-[#3D1F0D] truncate">
                        ${escHtml(item.resep_nama)} (${formatNumber(item.jumlah_produksi)} ${escHtml(item.satuan_hasil || 'porsi')})
                    </p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="${isBiaya ? 'badge-biaya' : 'badge-konversi'}">
                            ${isBiaya ? 'Biaya' : 'Konversi'}
                        </span>
                        <span class="text-xs text-[#9E7A5A]">· ${dateFormatted}</span>
                    </div>
                    <p class="text-xs font-semibold ${isBiaya ? 'text-[#7C4A2D]' : 'text-[#3D1F0D]'} mt-1">
                        ${isBiaya ? ('Rp ' + Math.round(item.hpp_total).toLocaleString('id-ID')) : (formatNumber(item.jumlah_produksi) + ' ' + escHtml(item.satuan_hasil || 'porsi'))}
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button onclick="deleteHistory(event, ${item.id})" class="text-xs text-red-400 hover:text-red-600 p-1" title="Hapus riwayat">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#C4A882]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
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
        showToast('Riwayat dihapus ✓');
        await loadHistory();
    } catch (err) {
        showToast('⚠ ' + err.message);
    }
}

function formatDateIndo(dtStr) {
    if (!dtStr) return '';
    const date = new Date(dtStr);
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>

