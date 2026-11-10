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
    kat(r) { return this.info(r.bahan_baku_id)?.kategori ?? ''; },
    dipakaiBaris(id, idx) { return this.rows.some((r, i) => i !== idx && String(r.bahan_baku_id) === String(id)); },
    melebihiStok(r) { const b = this.info(r.bahan_baku_id); return !!b && (Number(r.jumlah) || 0) > b.stok; },
    get beratUtama() {
        return this.rows.reduce((s, r) => {
            const b = this.info(r.bahan_baku_id);
            return s + (b && b.kategori === 'utama' ? (Number(r.jumlah) || 0) : 0);
        }, 0);
    },
}));

/**
 * Input rupiah: tampilan 12.000 (pemisah ribuan), nilai yang dikirim 12000 (angka polos).
 * Dipakai oleh <x-rupiah-input>. Koma = pemisah desimal (maks. 2 digit).
 */
Alpine.data('rupiahInput', (awal = '', nama = '') => ({
    nama,
    tampil: '',
    mentah: '',

    init() { this.isiDariMentah(awal); },

    kelompok(bulat) { return bulat.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); },

    isiDariMentah(v) {
        const s = String(v ?? '').trim();
        if (s === '' || Number.isNaN(Number(s))) { this.tampil = ''; this.mentah = ''; return; }
        const [i, d0 = ''] = s.split('.');
        const d = d0.replace(/0+$/, '').slice(0, 2);          // "12000.00" -> "12000", "1500.50" -> "1500.5"
        const bulat = String(parseInt(i || '0', 10));
        this.mentah = d ? `${bulat}.${d}` : bulat;
        this.tampil = this.kelompok(bulat) + (d ? ',' + d : '');
    },

    // Dipanggil dari komponen lain lewat $dispatch('isi-rupiah', { nama, nilai })
    isi(detail) { if (detail && detail.nama === this.nama) this.isiDariMentah(detail.nilai); },

    ketik(e) {
        const el = e.target;
        const kiri = el.value.slice(0, el.selectionStart ?? el.value.length).replace(/[^\d,]/g, '').length;
        const bersih = el.value.replace(/[^\d,]/g, '');
        const k = bersih.indexOf(',');
        const bulat = (k === -1 ? bersih : bersih.slice(0, k)).replace(/^0+(?=\d)/, '');
        const desimal = k === -1 ? null : bersih.slice(k + 1).replace(/,/g, '').slice(0, 2);

        this.mentah = (bulat === '' && desimal === null) ? '' : (bulat === '' ? '0' : bulat) + (desimal ? '.' + desimal : '');
        this.tampil = this.kelompok(bulat) + (desimal === null ? '' : ',' + desimal);

        // pertahankan posisi kursor setelah format ulang
        this.$nextTick(() => {
            let hitung = 0;
            let p = 0;
            while (p < this.tampil.length && hitung < kiri) {
                if (/[\d,]/.test(this.tampil[p])) hitung++;
                p++;
            }
            el.setSelectionRange(p, p);
        });
    },
}));

/**
 * Form "Tambah produk" (produk.create): pilih produk dari katalog, tampilkan stok dan harga saat ini.
 * ada = { 'Tahu Putih': { stok, harga, satuan }, ... } dari ProdukController::create ($produkAda)
 */
Alpine.data('produkBaru', (ada = {}, awalNama = '') => ({
    ada,
    nama: awalNama,
    tambah: '',
    loading: false,

    get info() { return this.nama ? (this.ada[this.nama] ?? null) : null; },
    get stokAkhir() { return this.info ? this.info.stok + (Number(this.tambah) || 0) : null; },
    angka(v) { return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(v) || 0); },
    rupiah,

    // Saat produk dipilih: isi harga jual dengan harga saat ini (bisa diubah lagi)
    pilih() {
        if (this.info) this.$dispatch('isi-rupiah', { nama: 'harga_jual', nilai: String(this.info.harga) });
    },
}));

/**
 * Countdown tahapan produksi (produksi.show).
 * Sumber data: GET produksi/{id}/tahapan -> { server_time, status_batch, tahapan: [{ tahap, status, sisa_detik, bisa_dimulai, ... }] }
 * Sisa waktu = sisa_detik dari server dikurangi waktu yang berlalu sejak data diambil (diukur dengan
 * performance.now(), jam monoton), jadi tidak bergantung pada jam perangkat.
 * Data diambil ulang saat hitung mundur mencapai nol dan tiap 30 detik. Jika status tahap berubah
 * dibanding tampilan awal, halaman dimuat ulang supaya kartu mengikuti data terbaru.
 */
Alpine.data('tahapanLive', (url, awalStatus = {}, awalBisa = {}, awalBatch = '') => ({
    url,
    awalStatus,
    awalBisa,
    awalBatch,
    data: {},
    statusBatch: awalBatch,
    ambilPada: 0,
    sekarang: 0,
    terakhirMinta: 0,

    init() {
        this.ambil();
        setInterval(() => {
            this.sekarang = performance.now();
            this.cekHabis();
        }, 1000);
        setInterval(() => {
            if (!document.hidden && this.statusBatch === 'berjalan') this.ambil();
        }, 30000);
    },

    async ambil() {
        this.terakhirMinta = performance.now();
        try {
            const res = await fetch(this.url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const json = await res.json();

            this.data = Object.fromEntries((json.tahapan || []).map((t) => [t.tahap, t]));
            this.statusBatch = json.status_batch;
            this.ambilPada = performance.now();
            this.sekarang = this.ambilPada;

            const berubah = json.status_batch !== this.awalBatch
                || (json.tahapan || []).some((t) => this.awalStatus[t.tahap] !== t.status);
            if (berubah) window.location.reload();
        } catch (e) {
            // jaringan terputus: biarkan hitung mundur berjalan, coba lagi pada siklus berikutnya
        }
    },

    sisa(tahap) {
        const t = this.data[tahap];
        if (!t || t.sisa_detik === null || t.sisa_detik === undefined) return null;
        const berlalu = (this.sekarang - this.ambilPada) / 1000;
        return Math.max(0, Math.ceil(t.sisa_detik - berlalu));
    },

    label(tahap) {
        const s = this.sisa(tahap);
        if (s === null) return '';
        const mm = String(Math.floor(s / 60)).padStart(2, '0');
        const ss = String(s % 60).padStart(2, '0');
        return `${mm}:${ss}`;
    },

    bisa(tahap) {
        const t = this.data[tahap];
        return t ? !!t.bisa_dimulai : !!this.awalBisa[tahap];
    },

    // Hitung mundur habis -> minta data terbaru (server menyelesaikan tahap otomatis)
    cekHabis() {
        if (performance.now() - this.terakhirMinta < 4000) return;
        const habis = Object.keys(this.data).some((k) => this.data[k].status === 'berjalan' && this.sisa(k) === 0);
        if (habis) this.ambil();
    },
}));

Alpine.start();
