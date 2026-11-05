@php
    $user    = Auth::user();
    $kelola  = $user->hasAnyRole(['admin', 'manajer']);
    $kerja   = $user->hasAnyRole(['admin', 'manajer', 'karyawan']);
    $status  = $produksi->status->value;
    $hasilErrors = collect($errors->getMessages())->filter(fn ($m, $k) => str_starts_with($k, 'hasil'))->flatten()->all();
@endphp

<x-app-layout>
    <x-page-header :title="$produksi->kode_batch" eyebrow="Produksi" :back="route('produksi.index')"
                   :subtitle="$produksi->tanggal_jadwal->translatedFormat('l, d F Y') . ($produksi->jam_rencana ? ' · ' . \Illuminate\Support\Str::of($produksi->jam_rencana)->substr(0, 5) : '')">
        <x-slot:actions>
            <x-status-badge :status="$produksi->status" />
            @if ($kelola && $status === 'terjadwal')
                <form method="POST" action="{{ route('produksi.mulai', $produksi) }}" onsubmit="return confirm('Mulai batch ini? Stok bahan baku akan langsung dikurangi.')">
                    @csrf
                    <x-btn icon="play_arrow">Mulai batch</x-btn>
                </form>
                <form method="POST" action="{{ route('produksi.batal', $produksi) }}" onsubmit="return confirm('Batalkan jadwal produksi ini?')">
                    @csrf
                    <x-btn variant="secondary" icon="cancel">Batalkan</x-btn>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card icon="grain" label="Berat kedelai" tone="primary"><x-qty :value="$produksi->berat_kedelai_kg" unit="kg" /></x-stat-card>
        <x-stat-card icon="person" label="Penanggung jawab">{{ $produksi->penanggungJawab?->name ?? '-' }}</x-stat-card>
        @if ((float) $produksi->biaya_bahan > 0)
            <x-stat-card icon="payments" label="Biaya bahan"><x-money :value="$produksi->biaya_bahan" /></x-stat-card>
        @endif
        @if ($status === 'selesai' && $produksi->rendemen !== null)
            <x-stat-card icon="insights" label="Rendemen" tone="success">
                {{ rtrim(rtrim(number_format($produksi->rendemen, 2, ',', '.'), '0'), ',') }}×
                <x-slot:footer><span>Hasil dibanding kedelai</span></x-slot:footer>
            </x-stat-card>
        @endif
    </div>

    @if ($produksi->waktu_mulai || $produksi->waktu_selesai)
        <p class="mt-3 text-body-sm text-on-surface-variant">
            @if ($produksi->waktu_mulai)Mulai {{ $produksi->waktu_mulai->translatedFormat('d M Y, H:i') }}@endif
            @if ($produksi->waktu_selesai) · Selesai {{ $produksi->waktu_selesai->translatedFormat('d M Y, H:i') }}@endif
        </p>
    @endif
    @if ($produksi->catatan)<p class="mt-2 text-body-md text-on-surface-variant">{{ $produksi->catatan }}</p>@endif

    {{-- Alur tahapan --}}
    <x-card class="mt-6" title="Alur tahapan produksi" :description="$status === 'berjalan' ? 'Tandai tiap tahap saat dikerjakan.' : 'Urutan tahap dari pencucian sampai pengemasan.'">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($produksi->tahapan as $t)
                @php $st = $t->status->value; @endphp
                <div class="flex flex-col justify-between rounded-tm-md p-3 transition-all {{ $st === 'berjalan' ? 'bg-surface-container-high shadow-sm' : 'bg-surface-container-low hover:bg-surface-container' }}">
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <span class="rounded-tm-sm px-2 py-0.5 text-label-sm {{ $st === 'berjalan' ? 'bg-primary-container text-on-primary-container' : ($st === 'selesai' ? 'bg-tertiary-fixed text-on-tertiary-fixed' : 'bg-surface-container text-on-surface-variant') }}">TAHAP {{ str_pad((string) $t->urutan, 2, '0', STR_PAD_LEFT) }}</span>
                            @if ($st === 'selesai')<x-icon name="check_circle" class="text-[18px] text-primary" />
                            @elseif ($st === 'berjalan')<span class="h-2 w-2 animate-ping rounded-full bg-primary-container"></span>
                            @else<x-icon name="radio_button_unchecked" class="text-[18px] text-outline" />@endif
                        </div>
                        <div class="text-label-lg text-on-surface">{{ $t->tahap->label() }}</div>
                        <div class="mt-0.5 text-body-sm text-on-surface-variant">
                            @if ($st === 'menunggu') Menunggu
                            @elseif ($st === 'berjalan') Sedang dikerjakan
                            @else Selesai @if ($t->durasi_menit !== null)· {{ $t->durasi_menit }} menit @endif @endif
                        </div>
                        @if ($t->petugas)<div class="text-body-sm text-on-surface-variant">{{ $t->petugas->name }}</div>@endif
                    </div>

                    @if ($status === 'berjalan' && $kerja && $st !== 'selesai')
                        <form method="POST" action="{{ route('produksi.tahap.update', [$produksi, $t->tahap->value]) }}" class="mt-3 rounded-tm-sm bg-surface-container-lowest/70 p-1.5">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $st === 'menunggu' ? 'berjalan' : 'selesai' }}">
                            <x-btn size="sm" class="w-full" :variant="$st === 'menunggu' ? 'secondary' : 'primary'" :icon="$st === 'menunggu' ? 'play_arrow' : 'check'">{{ $st === 'menunggu' ? 'Mulai tahap' : 'Selesaikan tahap' }}</x-btn>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </x-card>

    {{-- Bahan baku --}}
    <x-card class="mt-4 !p-0 overflow-hidden">
        <div class="px-5 pb-3 pt-5 sm:px-6 sm:pt-6">
            <h2 class="text-headline-sm font-bold text-on-surface">Bahan baku yang dipakai</h2>
        </div>
        <div class="divide-y divide-surface-container-low">
            @forelse ($produksi->bahanBaku as $b)
                <div class="flex items-center justify-between gap-3 px-5 py-3.5 sm:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md bg-surface-container text-primary"><x-icon name="grain" class="text-[20px]" /></span>
                        <div class="min-w-0">
                            <div class="truncate text-label-lg text-on-surface">{{ $b->nama }}</div>
                            <div class="text-body-sm text-on-surface-variant">{{ ucfirst($b->kategori) }}</div>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <div class="text-label-lg font-bold text-on-surface"><x-qty :value="$b->pivot->jumlah_dipakai" :unit="$b->satuan" /></div>
                        @if ((float) $b->pivot->harga_satuan > 0)<div class="text-body-sm text-on-surface-variant"><x-money :value="$b->pivot->harga_satuan" /> / {{ $b->satuan }}</div>@endif
                    </div>
                </div>
            @empty
                <div class="p-6"><x-empty-state icon="grain" title="Tidak ada bahan tercatat" /></div>
            @endforelse
        </div>
    </x-card>

    {{-- Hasil produksi --}}
    @if ($status === 'berjalan' && $kerja)
        <x-card class="mt-4" title="Catat hasil produksi" description="Isi jumlah yang dihasilkan. Produk yang tidak dihasilkan boleh dikosongkan. Menyimpan hasil akan menyelesaikan batch.">
            <form method="POST" action="{{ route('produksi.hasil.store', $produksi) }}" class="flex flex-col gap-4"
                  onsubmit="return confirm('Simpan hasil dan selesaikan batch ini?')"
                  x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
                @csrf

                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($produkAktif as $i => $p)
                        <div class="rounded-tm-md bg-surface-container-low p-3">
                            <input type="hidden" name="hasil[{{ $i }}][produk_id]" value="{{ $p->id }}">
                            <div class="mb-2 text-label-lg text-on-surface">{{ $p->nama }} <span class="text-body-sm text-on-surface-variant">({{ $p->satuan }})</span></div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-label-md text-on-surface" for="baik_{{ $i }}">Jumlah baik</label>
                                    <x-text-input id="baik_{{ $i }}" name="hasil[{{ $i }}][jumlah_baik]" type="number" step="any" min="0" inputmode="decimal" :value="old('hasil.' . $i . '.jumlah_baik')" placeholder="0" class="!bg-surface-container-lowest" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-label-md text-on-surface" for="reject_{{ $i }}">Jumlah reject</label>
                                    <x-text-input id="reject_{{ $i }}" name="hasil[{{ $i }}][jumlah_reject]" type="number" step="any" min="0" inputmode="decimal" :value="old('hasil.' . $i . '.jumlah_reject')" placeholder="0" class="!bg-surface-container-lowest" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <x-input-error :messages="$hasilErrors" />

                <x-field name="catatan" label="Catatan hasil (opsional)">
                    <x-text-input id="catatan" name="catatan" type="text" maxlength="255" :value="old('catatan')" />
                </x-field>

                <x-submit-button class="w-auto self-start" icon="check_circle" loading-text="Menyimpan…">Simpan hasil dan selesaikan batch</x-submit-button>
            </form>
        </x-card>
    @elseif ($produksi->hasil->isNotEmpty())
        <x-card class="mt-4 !p-0 overflow-hidden">
            <div class="px-5 pb-3 pt-5 sm:px-6 sm:pt-6"><h2 class="text-headline-sm font-bold text-on-surface">Hasil produksi</h2></div>
            <div class="divide-y divide-surface-container-low">
                @foreach ($produksi->hasil as $h)
                    <div class="flex items-center justify-between gap-3 px-5 py-3.5 sm:px-6">
                        <div class="min-w-0">
                            <div class="truncate text-label-lg text-on-surface">{{ $h->produk?->nama }}</div>
                            <div class="text-body-sm text-on-surface-variant">@if ($h->pencatat) Dicatat {{ $h->pencatat->name }} @endif</div>
                        </div>
                        <div class="shrink-0 text-right">
                            <x-badge tone="success" :dot="true"><x-qty :value="$h->jumlah_baik" :unit="$h->produk?->satuan" /> baik</x-badge>
                            @if ((float) $h->jumlah_reject > 0)<div class="mt-0.5 text-body-sm text-error"><x-qty :value="$h->jumlah_reject" /> reject</div>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif
</x-app-layout>
