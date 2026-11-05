import Alpine from 'alpinejs';

window.Alpine = Alpine;

const rupiah = (v) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(Number(v) || 0));

/**
 * Form kasir (penjualan.create).
 * produk = [{id, nama, harga, stok, satuan}] dari PenjualanController::create
 * Field yang dikirim ke backend: items[n][produk_id], items[n][jumlah], metode_pembayaran, bayar, nama_pembeli
 */
Alpine.data('posForm', (produk = [], oldItems = [], oldBayar = '') => ({
    produk,
    qty: Object.fromEntries(produk.map((p) => {
        const lama = (oldItems || []).find((i) => String(i.produk_id) === String(p.id));
        return [p.id, lama ? Number(lama.jumlah) || 0 : 0];
    })),
    bayar: oldBayar,
    loading: false,
    q: '',
    rupiah,

    get cart() {
        return this.produk
            .filter((p) => (Number(this.qty[p.id]) || 0) > 0)
            .map((p) => ({ ...p, jumlah: Number(this.qty[p.id]), subtotal: p.harga * Number(this.qty[p.id]) }));
    },
    get total() { return this.cart.reduce((s, i) => s + i.subtotal, 0); },
    get kembalian() { return Math.max(0, (Number(this.bayar) || 0) - this.total); },
    get kurang() { return this.total > 0 && (Number(this.bayar) || 0) < this.total; },
    get bisaKirim() { return this.cart.length > 0 && !this.kurang; },

    tambah(p) { this.qty[p.id] = (Number(this.qty[p.id]) || 0) + 1; },
    kurangi(p) { this.qty[p.id] = Math.max(0, (Number(this.qty[p.id]) || 0) - 1); },
    melebihiStok(p) { return (Number(this.qty[p.id]) || 0) > p.stok; },
    hapus(p) { this.qty[p.id] = 0; },
    kosongkan() { Object.keys(this.qty).forEach((k) => { this.qty[k] = 0; }); },
    qtyOf(p) { return Number(this.qty[p.id]) || 0; },
    uangPas() { this.bayar = this.total; },
}));

/**
 * Form jadwal produksi (produksi.create).
 * bahan = [{id, nama, kategori, satuan, stok}] dari ProduksiController::create
 * Field: bahan[n][bahan_baku_id], bahan[n][jumlah], tanggal_jadwal, jam_rencana, catatan, langsung_mulai
 */
Alpine.data('produksiForm', (bahan = [], oldRows = []) => ({
    bahan,
    loading: false,
    rows: (oldRows && oldRows.length)
        ? oldRows.map((r) => ({ bahan_baku_id: String(r.bahan_baku_id ?? ''), jumlah: r.jumlah ?? '' }))
        : [{ bahan_baku_id: '', jumlah: '' }],

    tambah() { this.rows.push({ bahan_baku_id: '', jumlah: '' }); },
    hapus(i) { if (this.rows.length > 1) this.rows.splice(i, 1); },
    info(id) { return this.bahan.find((b) => String(b.id) === String(id)); },
    dipakaiBaris(id, idx) { return this.rows.some((r, i) => i !== idx && String(r.bahan_baku_id) === String(id)); },
    melebihiStok(r) { const b = this.info(r.bahan_baku_id); return !!b && (Number(r.jumlah) || 0) > b.stok; },
    get beratUtama() {
        return this.rows.reduce((s, r) => {
            const b = this.info(r.bahan_baku_id);
            return s + (b && b.kategori === 'utama' ? (Number(r.jumlah) || 0) : 0);
        }, 0);
    },
}));

Alpine.start();
