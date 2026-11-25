<?php
/**
 * pages/packaging.php
 * Manajemen data kemasan / packaging milik user.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$showBack  = true;
$pageTitle = 'Kelola Packaging';
$backUrl   = '/bakecost/pages/hpp_kalkulator.php';
$activeNav = 'biaya';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packaging — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-[#3D1F0D]">Daftar Kemasan</h2>
                <p class="text-xs text-[#9E7A5A]">Box, plastik, stiker, mika, dll.</p>
            </div>
            <button onclick="openAddPackaging()" class="btn-primary text-xs py-2 px-3 w-auto">
                + Tambah
            </button>
        </div>

        <div id="packagingList" class="space-y-2 mb-6">
            <div class="skeleton h-14 w-full"></div>
            <div class="skeleton h-14 w-full"></div>
        </div>

        <div id="emptyPackaging" class="hidden text-center py-10 card">
            <div class="text-4xl mb-2">📦</div>
            <p class="font-semibold text-[#7C4A2D] text-sm">Belum ada kemasan</p>
            <p class="text-xs text-[#9E7A5A] mt-1">Tambahkan kemasan untuk menghitung HPP secara presisi.</p>
        </div>
    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<!-- Modal Form Packaging -->
<div id="pkgModal" class="modal-overlay hidden">
    <div class="modal-sheet">
        <div class="w-10 h-1 bg-[#DDD0C4] rounded-full mx-auto mb-4"></div>
        <h3 id="modalTitle" class="text-base font-bold text-[#3D1F0D] mb-4">Tambah Kemasan</h3>

        <form id="pkgForm" onsubmit="savePackaging(event)" class="space-y-3">
            <input type="hidden" id="pkgId" value="">
            <div>
                <label class="form-label" for="pkgNama">Nama Kemasan <span class="text-red-400">*</span></label>
                <input type="text" id="pkgNama" class="input-field text-sm" placeholder="cth: Box Kardus 20x20 cm" required>
            </div>
            <div>
                <label class="form-label" for="pkgHarga">Harga per Unit (Rp) <span class="text-red-400">*</span></label>
                <input type="number" id="pkgHarga" class="input-field text-sm" placeholder="cth: 2500" min="1" step="any" required>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="button" onclick="closeModal()" class="btn-secondary flex-1 text-sm py-2.5">Batal</button>
                <button type="submit" id="btnSavePkg" class="btn-primary flex-1 text-sm py-2.5">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
let packagingData = [];

document.addEventListener('DOMContentLoaded', loadPackaging);

async function loadPackaging() {
    try {
        packagingData = await apiFetch('/bakecost/api/packaging.php');
        renderPackaging();
    } catch (err) {
        showToast('Gagal memuat: ' + err.message);
    }
}

function renderPackaging() {
    const listEl = document.getElementById('packagingList');
    const emptyEl = document.getElementById('emptyPackaging');

    if (!packagingData.length) {
        listEl.innerHTML = '';
        emptyEl.classList.remove('hidden');
        return;
    }

    emptyEl.classList.add('hidden');
    listEl.innerHTML = packagingData.map(p => `
        <div class="card flex items-center justify-between p-3.5">
            <div>
                <p class="font-semibold text-sm text-[#3D1F0D]">${escHtml(p.nama)}</p>
                <p class="text-xs text-[#9E7A5A]">Rp ${Math.round(p.harga_per_unit).toLocaleString('id-ID')} / pcs</p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="openEditPackaging(${p.id})" class="text-xs font-semibold text-[#7C4A2D] hover:underline">Edit</button>
                <button onclick="deletePackaging(${p.id})" class="text-xs font-semibold text-red-500 hover:underline">Hapus</button>
            </div>
        </div>
    `).join('');
}

function openAddPackaging() {
    document.getElementById('pkgId').value = '';
    document.getElementById('modalTitle').textContent = 'Tambah Kemasan';
    document.getElementById('pkgForm').reset();
    document.getElementById('pkgModal').classList.remove('hidden');
}

function openEditPackaging(id) {
    const item = packagingData.find(p => p.id === id);
    if (!item) return;
    document.getElementById('pkgId').value = item.id;
    document.getElementById('modalTitle').textContent = 'Edit Kemasan';
    document.getElementById('pkgNama').value = item.nama;
    document.getElementById('pkgHarga').value = item.harga_per_unit;
    document.getElementById('pkgModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('pkgModal').classList.add('hidden');
}

async function savePackaging(e) {
    e.preventDefault();
    const id = document.getElementById('pkgId').value;
    const nama = document.getElementById('pkgNama').value.trim();
    const harga = parseFloat(document.getElementById('pkgHarga').value);

    const btn = document.getElementById('btnSavePkg');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        if (id) {
            await apiFetch(`/bakecost/api/packaging.php?id=${id}`, {
                method: 'PUT',
                body: { nama, harga_per_unit: harga }
            });
            showToast('Kemasan diperbarui ✓');
        } else {
            await apiFetch('/bakecost/api/packaging.php', {
                method: 'POST',
                body: { nama, harga_per_unit: harga }
            });
            showToast('Kemasan ditambahkan ✓');
        }
        closeModal();
        await loadPackaging();
    } catch (err) {
        showToast('⚠ ' + err.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan';
    }
}

async function deletePackaging(id) {
    if (!confirmDelete('Hapus kemasan ini?')) return;
    try {
        await apiFetch(`/bakecost/api/packaging.php?id=${id}`, { method: 'DELETE' });
        showToast('Kemasan dihapus ✓');
        await loadPackaging();
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

