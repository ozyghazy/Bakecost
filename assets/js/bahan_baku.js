/**
 * assets/js/bahan_baku.js
 * Logika interaktif halaman Bahan Baku (load, filter, tambah, edit, hapus).
 */

let editingId    = null;
let allBahan     = [];
let activeFilter = '';

document.addEventListener('DOMContentLoaded', () => {
    loadBahan();

    document.getElementById('searchInput').addEventListener(
        'input',
        debounce((e) => renderList(e.target.value.trim()), 250)
    );

    document.getElementById('sheetOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeSheet();
    });
});

async function loadBahan() {
    showSkeleton();
    try {
        allBahan = await apiFetch('/bakecost/api/bahan_baku.php');
        renderList();
    } catch (err) {
        document.getElementById('listContainer').innerHTML =
            `<div class="alert-error my-4">Gagal memuat data: ${escHtml(err.message)}</div>`;
    }
}

function renderList(keyword = '') {
    const container  = document.getElementById('listContainer');
    const emptyState = document.getElementById('emptyState');

    let data = allBahan;
    if (activeFilter) {
        data = data.filter(b => b.kategori === activeFilter);
    }
    if (keyword) {
        const kw = keyword.toLowerCase();
        data = data.filter(b => b.nama.toLowerCase().includes(kw));
    }

    document.getElementById('itemCount').textContent = `${data.length} bahan terdaftar`;

    if (data.length === 0) {
        container.innerHTML = '';
        emptyState.classList.remove('hidden');
        return;
    }

    emptyState.classList.add('hidden');

    container.innerHTML = data.map(b => `
        <div class="list-row" id="row-${b.id}">
            <div class="list-row__main">
                <div class="flex items-center gap-2 flex-wrap">
                    <p class="list-row__title truncate">${escHtml(b.nama)}</p>
                    ${b.kategori ? `<span class="badge-biaya">${escHtml(b.kategori)}</span>` : ''}
                </div>
                <p class="list-row__meta">
                    ${formatNumber(b.jumlah_beli)} ${escHtml(b.satuan)} · Rp ${formatHarga(b.harga_beli)}
                </p>
            </div>
            <div class="list-row__stack">
                <p class="list-row__price">Rp ${formatHarga(b.harga_per_satuan)}/${escHtml(b.satuan)}</p>
                <div class="list-row__actions">
                    <button type="button" onclick="openEdit(${b.id})" class="btn-action">Edit</button>
                    <button type="button" onclick="deleteBahan(${b.id}, '${escHtml(b.nama)}')" class="btn-action btn-action--danger">Hapus</button>
                </div>
            </div>
        </div>
    `).join('');
}

function showSkeleton() {
    document.getElementById('listContainer').innerHTML = Array(4).fill(0).map(() => `
        <div class="list-row">
            <div class="list-row__main space-y-2">
                <div class="skeleton h-4 w-2/3"></div>
                <div class="skeleton h-3 w-1/3"></div>
            </div>
            <div class="skeleton h-6 w-24"></div>
        </div>
    `).join('');
}

function setFilter(kategori) {
    activeFilter = kategori;
    document.querySelectorAll('.filter-chip').forEach(chip => {
        chip.classList.toggle('chip-active', chip.dataset.value === kategori);
    });
    renderList(document.getElementById('searchInput').value.trim());
}

function openAdd() {
    editingId = null;
    document.getElementById('sheetTitle').textContent = 'Tambah Bahan Baku';
    document.getElementById('bahanForm').reset();
    document.getElementById('formError').classList.add('hidden');
    document.getElementById('hargaPreview').classList.add('hidden');
    openSheet();
}

function openEdit(id) {
    const bahan = allBahan.find(b => b.id === id);
    if (!bahan) return;

    editingId = id;
    document.getElementById('sheetTitle').textContent = 'Edit Bahan Baku';
    document.getElementById('formError').classList.add('hidden');

    document.getElementById('fNama').value       = bahan.nama;
    document.getElementById('fKategori').value   = bahan.kategori || '';
    document.getElementById('fSatuan').value     = bahan.satuan;
    document.getElementById('fJumlahBeli').value = bahan.jumlah_beli;
    document.getElementById('fHargaBeli').value  = bahan.harga_beli;

    updatePreview();
    openSheet();
}

function openSheet() {
    document.getElementById('sheetOverlay').classList.remove('hidden');
    document.getElementById('bottomSheet').scrollTop = 0;
}

function closeSheet() {
    document.getElementById('sheetOverlay').classList.add('hidden');
    editingId = null;
}

function updatePreview() {
    const jumlah  = parseFloat(document.getElementById('fJumlahBeli').value) || 0;
    const harga   = parseFloat(document.getElementById('fHargaBeli').value)  || 0;
    const satuan  = document.getElementById('fSatuan').value;
    const preview = document.getElementById('hargaPreview');

    if (jumlah > 0 && harga > 0) {
        preview.textContent = `Harga otomatis per ${satuan}: Rp ${formatHarga(harga / jumlah)}`;
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
}

async function submitForm(e) {
    e.preventDefault();

    const payload = {
        nama:        document.getElementById('fNama').value.trim(),
        kategori:    document.getElementById('fKategori').value.trim(),
        satuan:      document.getElementById('fSatuan').value,
        jumlah_beli: parseFloat(document.getElementById('fJumlahBeli').value),
        harga_beli:  parseFloat(document.getElementById('fHargaBeli').value),
    };

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        if (editingId) {
            await apiFetch(`/bakecost/api/bahan_baku.php?id=${editingId}`, {
                method: 'PUT',
                body: payload,
            });
            showToast('Bahan baku diperbarui');
        } else {
            await apiFetch('/bakecost/api/bahan_baku.php', {
                method: 'POST',
                body: payload,
            });
            showToast('Bahan baku ditambahkan');
        }
        closeSheet();
        await loadBahan();
    } catch (err) {
        const errEl = document.getElementById('formError');
        errEl.textContent = err.message;
        errEl.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan';
    }
}

async function deleteBahan(id, nama) {
    if (!confirmDelete(`Hapus bahan "${nama}"? Tindakan ini tidak bisa dibatalkan.`)) return;

    try {
        await apiFetch(`/bakecost/api/bahan_baku.php?id=${id}`, { method: 'DELETE' });
        allBahan = allBahan.filter(b => b.id !== id);
        renderList(document.getElementById('searchInput').value.trim());
        showToast('Bahan baku dihapus');
    } catch (err) {
        showToast(err.message);
    }
}

function formatHarga(num) {
    const val = parseFloat(num);
    if (isNaN(val)) return '0';
    if (val % 1 !== 0) {
        return val.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }
    return val.toLocaleString('id-ID');
}
