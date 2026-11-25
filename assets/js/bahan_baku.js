/**
 * bahan_baku.js
 * Logika interaktif halaman Bahan Baku:
 * - Load & tampilkan list (dengan search dan filter kategori)
 * - Buka bottom sheet tambah / edit
 * - Submit form (POST/PUT ke API)
 * - Hapus dengan konfirmasi
 */

// ── State ──
let editingId   = null;   // null = mode tambah, angka = mode edit
let allBahan    = [];     // cache list dari API
let activeFilter = '';    // filter kategori aktif

// ── Inisialisasi saat DOM siap ──
document.addEventListener('DOMContentLoaded', () => {
    loadBahan();

    // Search dengan debounce
    document.getElementById('searchInput').addEventListener(
        'input',
        debounce((e) => renderList(e.target.value.trim()), 300)
    );
});

// ────────────────────────────────────────────────────
// LOAD & RENDER
// ────────────────────────────────────────────────────

/** Ambil data dari API lalu render */
async function loadBahan() {
    showSkeleton();
    try {
        allBahan = await apiFetch('/bakecost/api/bahan_baku.php');
        renderList();
    } catch (err) {
        document.getElementById('listContainer').innerHTML =
            `<div class="alert-error mt-4">⚠ Gagal memuat data: ${err.message}</div>`;
    }
}

/** Render list ke DOM berdasarkan keyword & filter kategori */
function renderList(keyword = '') {
    const container = document.getElementById('listContainer');
    const emptyState = document.getElementById('emptyState');

    let data = allBahan;

    // Filter kategori
    if (activeFilter) {
        data = data.filter(b => b.kategori === activeFilter);
    }

    // Filter keyword
    if (keyword) {
        const kw = keyword.toLowerCase();
        data = data.filter(b => b.nama.toLowerCase().includes(kw));
    }

    // Update counter
    document.getElementById('itemCount').textContent = `${data.length} bahan`;

    if (data.length === 0) {
        container.innerHTML = '';
        emptyState.classList.remove('hidden');
        return;
    }

    emptyState.classList.add('hidden');

    container.innerHTML = data.map(b => `
        <div class="table-row" id="row-${b.id}">
            <div class="flex-1 min-w-0 pr-3">
                <div class="flex items-center gap-2">
                    <p class="font-semibold text-[#3D1F0D] text-sm truncate">${escHtml(b.nama)}</p>
                    ${b.kategori ? `<span class="chip text-[10px] py-0.5 px-2 border-[#DDD0C4]">${escHtml(b.kategori)}</span>` : ''}
                </div>
                <p class="text-xs text-[#9E7A5A] mt-0.5">
                    ${formatNumber(b.jumlah_beli)} ${b.satuan} — Rp ${formatRupiah2(b.harga_beli)}
                </p>
            </div>
            <div class="flex-shrink-0 text-right">
                <p class="font-bold text-[#7C4A2D] text-sm">${formatRupiah2(b.harga_per_satuan)}/${b.satuan}</p>
                <div class="flex gap-2 justify-end mt-1">
                    <button onclick="openEdit(${b.id})"
                        class="text-xs text-[#7C4A2D] hover:underline font-medium">Edit</button>
                    <button onclick="deleteBahan(${b.id}, '${escHtml(b.nama)}')"
                        class="text-xs text-red-500 hover:underline font-medium">Hapus</button>
                </div>
            </div>
        </div>
    `).join('');
}

/** Tampilkan skeleton saat loading */
function showSkeleton() {
    const container = document.getElementById('listContainer');
    container.innerHTML = Array(4).fill(0).map(() => `
        <div class="table-row">
            <div class="flex-1 space-y-2">
                <div class="skeleton h-4 w-3/4"></div>
                <div class="skeleton h-3 w-1/2"></div>
            </div>
            <div class="skeleton h-5 w-20 rounded-full"></div>
        </div>
    `).join('');
}

// ────────────────────────────────────────────────────
// FILTER KATEGORI
// ────────────────────────────────────────────────────

function setFilter(kategori) {
    activeFilter = kategori;

    // Update tampilan chip aktif
    document.querySelectorAll('.filter-chip').forEach(chip => {
        chip.classList.toggle('chip-active', chip.dataset.value === kategori);
        chip.classList.toggle('chip', true);
    });

    renderList(document.getElementById('searchInput').value.trim());
}

// ────────────────────────────────────────────────────
// BOTTOM SHEET (Modal)
// ────────────────────────────────────────────────────

/** Buka sheet untuk tambah bahan baru */
function openAdd() {
    editingId = null;
    document.getElementById('sheetTitle').textContent = 'Tambah Bahan Baku';
    document.getElementById('bahanForm').reset();
    document.getElementById('formError').classList.add('hidden');
    openSheet();
}

/** Buka sheet untuk edit — ambil data dari cache */
function openEdit(id) {
    const bahan = allBahan.find(b => b.id === id);
    if (!bahan) return;

    editingId = id;
    document.getElementById('sheetTitle').textContent = 'Edit Bahan Baku';
    document.getElementById('formError').classList.add('hidden');

    // Isi form dengan data yang ada
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

// Tutup sheet jika klik overlay
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('sheetOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeSheet();
    });
});

// ────────────────────────────────────────────────────
// PREVIEW HARGA PER SATUAN (realtime)
// ────────────────────────────────────────────────────

function updatePreview() {
    const jumlah = parseFloat(document.getElementById('fJumlahBeli').value) || 0;
    const harga  = parseFloat(document.getElementById('fHargaBeli').value)  || 0;
    const satuan = document.getElementById('fSatuan').value;
    const preview = document.getElementById('hargaPreview');

    if (jumlah > 0 && harga > 0) {
        const perSatuan = harga / jumlah;
        preview.textContent = `Harga per ${satuan}: Rp ${formatRupiah2(perSatuan)}`;
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
}

// ────────────────────────────────────────────────────
// SUBMIT FORM
// ────────────────────────────────────────────────────

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
            // Mode edit — PUT
            await apiFetch(`/bakecost/api/bahan_baku.php?id=${editingId}`, {
                method: 'PUT', body: payload,
            });
            showToast('Bahan baku berhasil diperbarui ✓');
        } else {
            // Mode tambah — POST
            await apiFetch('/bakecost/api/bahan_baku.php', {
                method: 'POST', body: payload,
            });
            showToast('Bahan baku berhasil ditambahkan ✓');
        }

        closeSheet();
        await loadBahan();  // Refresh list
    } catch (err) {
        const errEl = document.getElementById('formError');
        errEl.textContent = err.message;
        errEl.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan';
    }
}

// ────────────────────────────────────────────────────
// HAPUS
// ────────────────────────────────────────────────────

async function deleteBahan(id, nama) {
    if (!confirmDelete(`Hapus bahan "${nama}"? Tindakan ini tidak bisa dibatalkan.`)) return;

    try {
        await apiFetch(`/bakecost/api/bahan_baku.php?id=${id}`, { method: 'DELETE' });

        // Hapus row dari DOM langsung (tanpa reload penuh)
        const row = document.getElementById(`row-${id}`);
        if (row) row.remove();

        // Update cache
        allBahan = allBahan.filter(b => b.id !== id);
        document.getElementById('itemCount').textContent = `${allBahan.length} bahan`;

        if (allBahan.length === 0) {
            document.getElementById('emptyState').classList.remove('hidden');
        }

        showToast('Bahan baku dihapus ✓');
    } catch (err) {
        showToast('⚠ ' + err.message);
    }
}

// ────────────────────────────────────────────────────
// UTIL
// ────────────────────────────────────────────────────

/** Escape HTML untuk keamanan (cegah XSS di innerHTML) */
function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/** Format angka ke Rupiah tanpa prefix "Rp " (dipakai dalam tabel) */
function formatRupiah2(num) {
    const val = parseFloat(num);
    if (isNaN(val)) return '0';
    // Jika ada desimal berarti harga_per_satuan, tampilkan 2 desimal jika perlu
    if (val % 1 !== 0) return val.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    return val.toLocaleString('id-ID');
}

