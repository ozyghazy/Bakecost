/**
 * app.js
 * Fungsi utilitas global untuk BAKECOST.
 */

/* ── Format angka ke Rupiah ── */
function formatRupiah(amount) {
    return 'Rp ' + Math.round(amount).toLocaleString('id-ID');
}

/* ── Format angka dengan 2 desimal ── */
function formatNumber(num, decimals = 2) {
    return parseFloat(num).toFixed(decimals).replace(/\.?0+$/, '');
}

/* ── Tampilkan toast notifikasi sementara ── */
function showToast(message, duration = 2500) {
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), duration);
}

/* ── Fetch helper (JSON) ── */
async function apiFetch(url, options = {}) {
    const defaults = {
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    };
    const config = { ...defaults, ...options };
    if (config.body && typeof config.body === 'object') {
        config.body = JSON.stringify(config.body);
    }
    const res = await fetch(url, config);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Terjadi kesalahan');
    return data;
}

/* ── Konfirmasi hapus dengan dialog sederhana ── */
function confirmDelete(message = 'Yakin ingin menghapus data ini?') {
    return confirm(message);
}

/* ── Debounce untuk search input ── */
function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

