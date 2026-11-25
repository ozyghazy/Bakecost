<?php
/**
 * pages/bahan_baku.php
 * Halaman manajemen bahan baku.
 * Fitur: list dengan search & filter kategori, tambah, edit, hapus via modal bottom sheet.
 */

require_once __DIR__ . '/../components/auth_guard.php';

$showBack  = false;
$activeNav = 'beranda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bahan Baku — BAKECOST</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/bakecost/assets/css/style.css">
</head>
<body>
<div class="app-container">

    <!-- ── Header dengan judul kustom ── -->
    <header class="sticky top-0 z-40 bg-[#FDF5EE] border-b border-[#EAD9C8] px-4 py-3 flex items-center justify-between">
        <div>
            <p class="text-[17px] font-bold text-[#3D1F0D] leading-tight">Bahan Baku</p>
            <p class="text-[11px] text-[#9E7A5A] leading-tight" id="itemCount">Memuat...</p>
        </div>
        <!-- Tombol tambah di kanan atas -->
        <button onclick="openAdd()"
            class="flex items-center gap-1.5 bg-[#7C4A2D] text-white text-sm font-semibold px-4 py-2 rounded-xl hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah
        </button>
    </header>

    <main class="page-content">

        <!-- ── Search bar ── -->
        <div class="relative mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#B09080] absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
            </svg>
            <input
                type="text"
                id="searchInput"
                class="input-field pl-10"
                placeholder="Cari bahan baku..."
                autocomplete="off"
            >
        </div>

        <!-- ── Filter chip kategori ── -->
        <div class="flex gap-2 overflow-x-auto pb-1 mb-4 no-scrollbar">
            <button class="filter-chip chip chip-active flex-shrink-0" data-value="" onclick="setFilter('')">Semua</button>
            <button class="filter-chip chip flex-shrink-0" data-value="Tepung" onclick="setFilter('Tepung')">Tepung</button>
            <button class="filter-chip chip flex-shrink-0" data-value="Gula & Pemanis" onclick="setFilter('Gula & Pemanis')">Gula & Pemanis</button>
            <button class="filter-chip chip flex-shrink-0" data-value="Lemak & Minyak" onclick="setFilter('Lemak & Minyak')">Lemak & Minyak</button>
            <button class="filter-chip chip flex-shrink-0" data-value="Telur & Susu" onclick="setFilter('Telur & Susu')">Telur & Susu</button>
            <button class="filter-chip chip flex-shrink-0" data-value="Cokelat" onclick="setFilter('Cokelat')">Cokelat</button>
            <button class="filter-chip chip flex-shrink-0" data-value="Pengembang" onclick="setFilter('Pengembang')">Pengembang</button>
            <button class="filter-chip chip flex-shrink-0" data-value="Perisa" onclick="setFilter('Perisa')">Perisa</button>
            <button class="filter-chip chip flex-shrink-0" data-value="Lainnya" onclick="setFilter('Lainnya')">Lainnya</button>
        </div>

        <!-- ── Header tabel ── -->
        <div class="flex justify-between text-[11px] font-semibold text-[#9E7A5A] uppercase tracking-wide px-0 mb-1">
            <span>Nama Bahan</span>
            <span>Harga/Satuan</span>
        </div>

        <!-- ── List container (diisi JS) ── -->
        <div class="card p-0 divide-y divide-[#F0E8DF] overflow-hidden">
            <div id="listContainer" class="px-4"></div>
        </div>

        <!-- ── Empty state ── -->
        <div id="emptyState" class="hidden text-center py-12">
            <div class="text-5xl mb-3">🧂</div>
            <p class="font-semibold text-[#7C4A2D]">Belum ada bahan baku</p>
            <p class="text-[#9E7A5A] text-sm mt-1">Tambahkan bahan pertamamu untuk mulai membuat resep.</p>
            <button onclick="openAdd()" class="inline-block mt-4 px-5 py-2 bg-[#7C4A2D] text-white text-sm rounded-xl font-semibold">
                + Tambah Sekarang
            </button>
        </div>

    </main>

    <?php require __DIR__ . '/../components/bottom_nav.php'; ?>
</div>

<!-- ════════════════════════════════════════════════════
     BOTTOM SHEET — Form Tambah / Edit Bahan Baku
     ════════════════════════════════════════════════════ -->
<div id="sheetOverlay" class="modal-overlay hidden">
    <div id="bottomSheet" class="modal-sheet overflow-y-auto max-h-[90dvh]">

        <!-- Handle tarik -->
        <div class="w-10 h-1 bg-[#DDD0C4] rounded-full mx-auto mb-5"></div>

        <h2 id="sheetTitle" class="text-[17px] font-bold text-[#3D1F0D] mb-5">Tambah Bahan Baku</h2>

        <!-- Alert error form -->
        <div id="formError" class="alert-error mb-4 hidden"></div>

        <form id="bahanForm" onsubmit="submitForm(event)" class="space-y-4">

            <!-- Nama bahan -->
            <div>
                <label class="form-label" for="fNama">Nama Bahan <span class="text-red-400">*</span></label>
                <input type="text" id="fNama" class="input-field" placeholder="cth: Tepung terigu" required maxlength="100">
            </div>

            <!-- Kategori -->
            <div>
                <label class="form-label" for="fKategori">Kategori</label>
                <select id="fKategori" class="input-field">
                    <option value="">— Pilih kategori —</option>
                    <option value="Tepung">Tepung</option>
                    <option value="Gula & Pemanis">Gula & Pemanis</option>
                    <option value="Lemak & Minyak">Lemak & Minyak</option>
                    <option value="Telur & Susu">Telur & Susu</option>
                    <option value="Cokelat">Cokelat</option>
                    <option value="Pengembang">Pengembang</option>
                    <option value="Perisa">Perisa</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <!-- Satuan -->
            <div>
                <label class="form-label" for="fSatuan">Satuan <span class="text-red-400">*</span></label>
                <select id="fSatuan" class="input-field" onchange="updatePreview()">
                    <option value="g">Gram (g)</option>
                    <option value="kg">Kilogram (kg)</option>
                    <option value="ml">Mililiter (ml)</option>
                    <option value="l">Liter (l)</option>
                    <option value="pcs">Pieces (pcs)</option>
                    <option value="butir">Butir</option>
                </select>
            </div>

            <!-- Jumlah beli & Harga beli — 2 kolom -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label" for="fJumlahBeli">Jumlah Beli <span class="text-red-400">*</span></label>
                    <input type="number" id="fJumlahBeli" class="input-field" placeholder="cth: 1000" min="0.001" step="any" required oninput="updatePreview()">
                    <p class="text-[11px] text-[#9E7A5A] mt-1">Berapa banyak yang dibeli</p>
                </div>
                <div>
                    <label class="form-label" for="fHargaBeli">Harga Beli (Rp) <span class="text-red-400">*</span></label>
                    <input type="number" id="fHargaBeli" class="input-field" placeholder="cth: 12000" min="1" step="any" required oninput="updatePreview()">
                    <p class="text-[11px] text-[#9E7A5A] mt-1">Total harga pembelian</p>
                </div>
            </div>

            <!-- Preview harga per satuan (muncul otomatis) -->
            <div id="hargaPreview" class="alert-info font-semibold hidden"></div>

            <!-- Tombol submit -->
            <div class="pt-2 flex gap-3">
                <button type="button" onclick="closeSheet()" class="btn-secondary flex-1">Batal</button>
                <button type="submit" id="submitBtn" class="btn-primary flex-1">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast -->
<div id="toast"></div>

<script src="/bakecost/assets/js/app.js"></script>
<script src="/bakecost/assets/js/bahan_baku.js"></script>
</body>
</html>

