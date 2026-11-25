<?php
/**
 * pages/packaging.php — Manajemen data kemasan / packaging milik user.
 */

require_once __DIR__ . '/../components/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$showBack  = true;
$pageTitle = 'Kelola Packaging';
$backUrl   = '/bakecost/pages/hpp_kalkulator.php';
$activeNav = 'biaya';
$htmlTitle = 'Packaging — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content space-y-5">

        <section class="flex items-end justify-between gap-3">
            <div>
                <p class="eyebrow">Kemasan Produk</p>
                <h2 class="font-editorial text-2xl sm:text-3xl font-semibold text-ink mt-0.5">Daftar Packaging</h2>
                <p class="text-xs text-muted mt-0.5">Box, plastik, mika, pita, atau stiker kemasan.</p>
            </div>
            <button type="button" onclick="openAddPackaging()" class="btn-primary btn-sm flex-shrink-0">
                + Tambah Packaging
            </button>
        </section>

        <section class="list-card">
            <div class="list-card__header">
                <span>Nama Kemasan</span>
                <span>Harga / Unit</span>
            </div>
            <div id="packagingList">
                <div class="list-row"><div class="skeleton h-5 w-1/2"></div><div class="skeleton h-5 w-24"></div></div>
            </div>
        </section>

        <section id="emptyPackaging" class="hidden card text-center py-12">
            <p class="font-editorial text-lg font-semibold text-ink">Belum ada kemasan</p>
            <p class="text-xs text-muted mt-1">Tambahkan kemasan agar perhitungan HPP makin akurat.</p>
        </section>
    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<!-- Modal Form Packaging -->
<div id="pkgModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-sheet">
        <div class="w-10 h-1 bg-[var(--border-strong)] rounded-full mx-auto mb-5 md:hidden"></div>
        <h3 id="modalTitle" class="font-editorial text-xl font-semibold text-ink mb-5">Tambah Kemasan</h3>

        <form id="pkgForm" onsubmit="savePackaging(event)" class="space-y-4">
            <input type="hidden" id="pkgId" value="">
            <div>
                <label class="form-label" for="pkgNama">Nama Kemasan <span class="text-red-500">*</span></label>
                <input type="text" id="pkgNama" class="input-field" placeholder="cth: Box Kardus 20×20 cm" required>
            </div>
            <div>
                <label class="form-label" for="pkgHarga">Harga per Unit (Rp) <span class="text-red-500">*</span></label>
                <input type="number" id="pkgHarga" class="input-field tabular-nums" placeholder="cth: 2500" min="1" step="any" required>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal()" class="btn-secondary flex-1">Batal</button>
                <button type="submit" id="btnSavePkg" class="btn-primary flex-1">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script>
let packagingData = [];

document.addEventListener('DOMContentLoaded', () => {
    loadPackaging();
    document.getElementById('pkgModal').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });
});

async function loadPackaging() {
    try {
        packagingData = await apiFetch('/bakecost/api/packaging.php');
        renderPackaging();
    } catch (err) {
        showToast('Gagal memuat: ' + err.message);
    }
}

function renderPackaging() {
    const listEl  = document.getElementById('packagingList');
    const emptyEl = document.getElementById('emptyPackaging');

    if (!packagingData.length) {
        listEl.innerHTML = '';
        emptyEl.classList.remove('hidden');
        return;
    }

    emptyEl.classList.add('hidden');
    listEl.innerHTML = packagingData.map(p => `
        <div class="list-row">
            <div class="list-row__main">
                <p class="list-row__title">${escHtml(p.nama)}</p>
            </div>
            <div class="list-row__stack">
                <p class="list-row__price">${formatRupiah(p.harga_per_unit)} / pcs</p>
                <div class="list-row__actions">
                    <button type="button" onclick="openEditPackaging(${p.id})" class="btn-action">Edit</button>
                    <button type="button" onclick="deletePackaging(${p.id})" class="btn-action btn-action--danger">Hapus</button>
                </div>
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
    document.getElementById('pkgId').value    = item.id;
    document.getElementById('modalTitle').textContent = 'Edit Kemasan';
    document.getElementById('pkgNama').value  = item.nama;
    document.getElementById('pkgHarga').value = item.harga_per_unit;
    document.getElementById('pkgModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('pkgModal').classList.add('hidden');
}

async function savePackaging(e) {
    e.preventDefault();
    const id    = document.getElementById('pkgId').value;
    const nama  = document.getElementById('pkgNama').value.trim();
    const harga = parseFloat(document.getElementById('pkgHarga').value);

    const btn = document.getElementById('btnSavePkg');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        if (id) {
            await apiFetch(`/bakecost/api/packaging.php?id=${id}`, {
                method: 'PUT',
                body: { nama, harga_per_unit: harga },
            });
            showToast('Kemasan diperbarui');
        } else {
            await apiFetch('/bakecost/api/packaging.php', {
                method: 'POST',
                body: { nama, harga_per_unit: harga },
            });
            showToast('Kemasan ditambahkan');
        }
        closeModal();
        await loadPackaging();
    } catch (err) {
        showToast(err.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan';
    }
}

async function deletePackaging(id) {
    if (!confirmDelete('Hapus kemasan ini?')) return;
    try {
        await apiFetch(`/bakecost/api/packaging.php?id=${id}`, { method: 'DELETE' });
        showToast('Kemasan dihapus');
        await loadPackaging();
    } catch (err) {
        showToast(err.message);
    }
}
</script>
</body>
</html>
