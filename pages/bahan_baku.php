<?php
/**
 * pages/bahan_baku.php
 * Manajemen katalog bahan baku milik user (list, cari, filter, tambah, edit, hapus).
 */

require_once __DIR__ . '/../components/auth_guard.php';

$showBack  = false;
$activeNav = 'bahan';
$htmlTitle = 'Bahan Baku — BakeCost';
require __DIR__ . '/../components/head.php';
?>
<body>
<div class="app-container">

    <?php require __DIR__ . '/../components/header.php'; ?>

    <main class="page-content space-y-5">

        <!-- Header halaman + tombol tambah -->
        <section class="flex items-end justify-between gap-3">
            <div>
                <p class="eyebrow">Katalog Bahan</p>
                <h1 class="editorial-title text-2xl sm:text-3xl font-semibold text-ink mt-0.5">Bahan Baku</h1>
                <p class="text-xs text-muted mt-0.5" id="itemCount">Memuat data...</p>
            </div>
            <button type="button" onclick="openAdd()" class="btn-primary btn-sm flex-shrink-0">
                + Tambah Bahan
            </button>
        </section>

        <!-- Pencarian & Filter Kategori -->
        <section class="space-y-3">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-muted absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                </svg>
                <input
                    type="text"
                    id="searchInput"
                    class="input-field pl-11"
                    placeholder="Cari nama bahan baku..."
                    autocomplete="off"
                >
            </div>

            <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar" role="tablist" aria-label="Filter kategori bahan">
                <button type="button" class="filter-chip chip chip-active flex-shrink-0" data-value="" onclick="setFilter('')">Semua</button>
                <button type="button" class="filter-chip chip flex-shrink-0" data-value="Tepung" onclick="setFilter('Tepung')">Tepung</button>
                <button type="button" class="filter-chip chip flex-shrink-0" data-value="Gula & Pemanis" onclick="setFilter('Gula & Pemanis')">Gula &amp; Pemanis</button>
                <button type="button" class="filter-chip chip flex-shrink-0" data-value="Lemak & Minyak" onclick="setFilter('Lemak & Minyak')">Lemak &amp; Minyak</button>
                <button type="button" class="filter-chip chip flex-shrink-0" data-value="Telur & Susu" onclick="setFilter('Telur & Susu')">Telur &amp; Susu</button>
                <button type="button" class="filter-chip chip flex-shrink-0" data-value="Cokelat" onclick="setFilter('Cokelat')">Cokelat</button>
                <button type="button" class="filter-chip chip flex-shrink-0" data-value="Pengembang" onclick="setFilter('Pengembang')">Pengembang</button>
                <button type="button" class="filter-chip chip flex-shrink-0" data-value="Perisa" onclick="setFilter('Perisa')">Perisa</button>
                <button type="button" class="filter-chip chip flex-shrink-0" data-value="Lainnya" onclick="setFilter('Lainnya')">Lainnya</button>
            </div>
        </section>

        <!-- Daftar Bahan -->
        <section>
            <div class="list-card">
                <div class="list-card__header">
                    <span>Nama Bahan</span>
                    <span>Harga / Satuan</span>
                </div>
                <div id="listContainer"></div>
            </div>

            <div id="emptyState" class="hidden card text-center py-12 mt-3">
                <p class="font-editorial text-lg font-semibold text-ink">Belum ada bahan baku</p>
                <p class="text-muted text-sm mt-1">Tambahkan bahan pertamamu untuk menghitung biaya resep otomatis.</p>
                <button type="button" onclick="openAdd()" class="btn-primary btn-sm mt-4">
                    + Tambah Sekarang
                </button>
            </div>
        </section>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<!-- Modal Form Tambah / Edit Bahan Baku -->
<div id="sheetOverlay" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="sheetTitle">
    <div id="bottomSheet" class="modal-sheet">
        <div class="w-10 h-1 bg-[var(--border-strong)] rounded-full mx-auto mb-5 md:hidden"></div>

        <h2 id="sheetTitle" class="font-editorial text-xl font-semibold text-ink mb-5">Tambah Bahan Baku</h2>

        <div id="formError" class="alert-error mb-4 hidden" role="alert"></div>

        <form id="bahanForm" onsubmit="submitForm(event)" class="space-y-4">
            <div>
                <label class="form-label" for="fNama">Nama Bahan <span class="text-red-500">*</span></label>
                <input type="text" id="fNama" class="input-field" placeholder="cth: Tepung terigu protein sedang" required maxlength="100">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="fKategori">Kategori</label>
                    <select id="fKategori" class="input-field">
                        <option value="">— Pilih —</option>
                        <option value="Tepung">Tepung</option>
                        <option value="Gula & Pemanis">Gula &amp; Pemanis</option>
                        <option value="Lemak & Minyak">Lemak &amp; Minyak</option>
                        <option value="Telur & Susu">Telur &amp; Susu</option>
                        <option value="Cokelat">Cokelat</option>
                        <option value="Pengembang">Pengembang</option>
                        <option value="Perisa">Perisa</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="form-label" for="fSatuan">Satuan <span class="text-red-500">*</span></label>
                    <select id="fSatuan" class="input-field" onchange="updatePreview()">
                        <option value="g">Gram (g)</option>
                        <option value="kg">Kilogram (kg)</option>
                        <option value="ml">Mililiter (ml)</option>
                        <option value="l">Liter (l)</option>
                        <option value="pcs">Pieces (pcs)</option>
                        <option value="butir">Butir</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="fJumlahBeli">Jumlah Beli <span class="text-red-500">*</span></label>
                    <input type="number" id="fJumlahBeli" class="input-field tabular-nums" placeholder="cth: 1000" min="0.001" step="any" required oninput="updatePreview()">
                </div>
                <div>
                    <label class="form-label" for="fHargaBeli">Harga Beli (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" id="fHargaBeli" class="input-field tabular-nums" placeholder="cth: 12000" min="1" step="any" required oninput="updatePreview()">
                </div>
            </div>

            <div id="hargaPreview" class="alert-info font-semibold tabular-nums hidden"></div>

            <div class="pt-2 flex gap-3">
                <button type="button" onclick="closeSheet()" class="btn-secondary flex-1">Batal</button>
                <button type="submit" id="submitBtn" class="btn-primary flex-1">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="toast"></div>
<script src="/bakecost/assets/js/app.js"></script>
<script src="/bakecost/assets/js/bahan_baku.js"></script>
</body>
</html>
