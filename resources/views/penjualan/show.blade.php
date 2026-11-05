@php $batal = $penjualan->status === 'batal'; @endphp

<x-app-layout>
    <x-page-header :title="$penjualan->no_nota" eyebrow="Kasir" :subtitle="$penjualan->tanggal->translatedFormat('l, d F Y, H:i')" :back="route('penjualan.index')">
        <x-slot:actions>
            <x-status-badge :status="$penjualan->status" />
            <x-btn type="button" variant="secondary" icon="receipt" onclick="window.print()" class="no-print">Cetak</x-btn>
            @unless ($batal)
                <form method="POST" action="{{ route('penjualan.batal', $penjualan) }}" class="no-print" onsubmit="return confirm('Batalkan nota ini? Stok produk akan dikembalikan.')">
                    @csrf
                    <x-btn variant="danger" icon="cancel">Batalkan nota</x-btn>
                </form>
            @endunless
        </x-slot:actions>
    </x-page-header>

    @if ($batal)
        <div class="mb-4 flex items-center gap-2 rounded-tm-md bg-error-container px-4 py-3 text-label-md text-on-error-container" role="status">
            <x-icon name="info" class="text-[18px]" /> Nota ini sudah dibatalkan dan stok produk sudah dikembalikan.
        </div>
    @endif

    <x-card>
        <dl class="mb-4 grid gap-3 text-body-md sm:grid-cols-3">
            <div><dt class="text-body-sm text-on-surface-variant">Kasir</dt><dd class="text-label-lg text-on-surface">{{ $penjualan->kasir?->name ?? '-' }}</dd></div>
            <div><dt class="text-body-sm text-on-surface-variant">Pembeli</dt><dd class="text-label-lg text-on-surface">{{ $penjualan->nama_pembeli ?: '-' }}</dd></div>
            <div><dt class="text-body-sm text-on-surface-variant">Metode</dt><dd class="text-label-lg text-on-surface">{{ ucfirst($penjualan->metode_pembayaran) }}</dd></div>
        </dl>

        <div class="border-t border-outline-variant/50">
            @foreach ($penjualan->detail as $d)
                <div class="flex items-start justify-between gap-3 border-b border-outline-variant/40 py-3">
                    <div class="min-w-0">
                        <div class="text-label-lg text-on-surface">{{ $d->produk?->nama }}</div>
                        <div class="text-body-sm text-on-surface-variant"><x-qty :value="$d->jumlah" :unit="$d->produk?->satuan" /> × <x-money :value="$d->harga_satuan" /></div>
                    </div>
                    <div class="shrink-0 text-label-lg text-on-surface"><x-money :value="$d->subtotal" /></div>
                </div>
            @endforeach
        </div>

        <dl class="mt-4 ml-auto max-w-xs space-y-1.5">
            <div class="flex justify-between text-body-md"><dt class="text-on-surface-variant">Total</dt><dd class="text-headline-sm text-on-surface"><x-money :value="$penjualan->total" /></dd></div>
            <div class="flex justify-between text-body-md"><dt class="text-on-surface-variant">Dibayar</dt><dd class="text-on-surface"><x-money :value="$penjualan->bayar" /></dd></div>
            <div class="flex justify-between text-body-md"><dt class="text-on-surface-variant">Kembalian</dt><dd class="text-on-surface"><x-money :value="$penjualan->kembalian" /></dd></div>
        </dl>

        @if ($penjualan->catatan)<p class="mt-4 text-body-md text-on-surface-variant">{{ $penjualan->catatan }}</p>@endif
    </x-card>
</x-app-layout>
