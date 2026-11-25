/**
 * assets/js/app.js
 * Fungsi utilitas global untuk aplikasi BakeCost.
 */

/** Escape HTML untuk mencegah XSS saat render ke innerHTML */
function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/** Format angka ke format Rupiah (dengan prefix Rp) */
function formatRupiah(amount) {
    const val = parseFloat(amount);
    if (isNaN(val)) return 'Rp 0';
    return 'Rp ' + Math.round(val).toLocaleString('id-ID');
}

/** Format angka dengan maksimal desimal tertentu tanpa trailing zero */
function formatNumber(num, decimals = 2) {
    const val = parseFloat(num);
    if (isNaN(val)) return '0';
    return val.toFixed(decimals).replace(/\.?0+$/, '');
}

/** Tampilkan toast notifikasi sementara di bagian bawah */
function showToast(message, duration = 2600) {
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

/** Wrapper fetch JSON dengan error handling seragam */
async function apiFetch(url, options = {}) {
    const defaults = {
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    };
    const config = { ...defaults, ...options };
    if (config.body && typeof config.body === 'object') {
        config.body = JSON.stringify(config.body);
    }
    const res = await fetch(url, config);
    const data = await res.json();
    if (!res.ok) {
        throw new Error(data.error || 'Terjadi kesalahan pada server.');
    }
    return data;
}

/** Dialog konfirmasi hapus */
function confirmDelete(message = 'Yakin ingin menghapus data ini?') {
    return confirm(message);
}

/** Debounce untuk input pencarian / kalkulasi */
function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}
