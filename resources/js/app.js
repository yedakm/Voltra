import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Global toast store — mirrors the prototype's ToastProvider
Alpine.store('toasts', {
    items: [],
    push(msg, kind = 'info') {
        const id = Date.now() + Math.random();
        this.items.push({ id, msg, kind });
        setTimeout(() => {
            this.items = this.items.filter((t) => t.id !== id);
        }, 3200);
    },
});

// Indonesian Rupiah formatter — mirrors the prototype's fmtIDR helper.
window.fmtIDR = function (n) {
    if (n === null || n === undefined || isNaN(n)) return '—';
    return 'Rp ' + Math.round(Number(n)).toLocaleString('id-ID');
};

// Whole days between two ISO date strings.
window.dayCount = function (start, end) {
    if (!start || !end) return 0;
    return Math.max(0, Math.ceil((new Date(end) - new Date(start)) / 86400000));
};

// ===== Jembatan ke REST API Voltra (Sanctum stateful — pakai cookie sesi) =====
window.voltraApi = async function (method, url, body) {
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    const res = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': m ? decodeURIComponent(m[1]) : '',
        },
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });
    let data = {};
    try {
        data = await res.json();
    } catch (e) {
        /* respons kosong */
    }
    if (!res.ok) {
        const firstErr = data.errors ? Object.values(data.errors)[0][0] : null;
        throw new Error(data.message || firstErr || `Permintaan gagal (${res.status})`);
    }
    return data;
};

/**
 * Kirim aksi tulis ke API lalu tampilkan toast & muat ulang halaman.
 * okMsg boleh string atau fungsi yang menerima respons JSON.
 */
window.voltraSave = function (url, body, okMsg) {
    return window
        .voltraApi('POST', url, body)
        .then((r) => {
            const msg = typeof okMsg === 'function' ? okMsg(r) : okMsg;
            window.Alpine.store('toasts').push(msg, 'success');
            setTimeout(() => window.location.reload(), 1100);
            return r;
        })
        .catch((e) => {
            window.Alpine.store('toasts').push(e.message, 'error');
            throw e;
        });
};

Alpine.start();
