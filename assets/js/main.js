// ===== CART =====
function updateCartBadge() {
    fetch('/keranjang.php?action=count')
        .then(r => r.json())
        .then(data => {
            document.querySelectorAll('.cart-badge').forEach(b => {
                b.textContent = data.count;
                b.style.display = data.count > 0 ? 'flex' : 'none';
            });
        }).catch(() => {});
}

function tambahKeKeranjang(produkId) {
    const formData = new FormData();
    formData.append('action', 'tambah');
    formData.append('produk_id', produkId);
    formData.append('jumlah', 1);
    fetch('/keranjang.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) updateCartBadge();
        });
}

function updateQty(keranjangId, delta) {
    const input = document.getElementById('qty-' + keranjangId);
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    input.value = val;
    updateKeranjang(keranjangId, val);
}

function updateKeranjang(keranjangId, jumlah) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', keranjangId);
    formData.append('jumlah', jumlah);
    fetch('/keranjang.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
}

function hapusKeranjang(id) {
    if (!confirm('Hapus item ini?')) return;
    const formData = new FormData();
    formData.append('action', 'hapus');
    formData.append('id', id);
    fetch('/keranjang.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
}

// ===== VOUCHER =====
function cekVoucher() {
    const kode = document.getElementById('voucherInput')?.value?.trim();
    const info = document.getElementById('voucherInfo');
    if (!kode || !info) return;

    const formData = new FormData();
    formData.append('action', 'cek_voucher');
    formData.append('kode', kode);
    fetch('/checkout.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                info.innerHTML = `<span style="color:var(--success);">✅ ${data.message}</span>`;
            } else {
                info.innerHTML = `<span style="color:var(--danger);">❌ ${data.message}</span>`;
            }
        });
}

// ===== NOTIFIKASI =====
function toggleNotif(e) {
    e.preventDefault();
    e.stopPropagation();
    const dd = document.getElementById('notifDropdown');
    dd?.classList.toggle('show');
}

document.addEventListener('click', () => {
    document.getElementById('notifDropdown')?.classList.remove('show');
});

// ===== TOAST =====
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icons = { success: '✅', error: '❌', warning: '⚠️' };
    toast.innerHTML = `<span>${icons[type] || '🔔'}</span><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ===== HAPUS KONFIRMASI =====
function konfirmasiHapus(url, pesan = 'Yakin ingin menghapus?') {
    if (confirm(pesan)) window.location.href = url;
}

// ===== AUTO HIDE ALERT =====
document.addEventListener('DOMContentLoaded', () => {
    updateCartBadge();
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 4000);
    });
});