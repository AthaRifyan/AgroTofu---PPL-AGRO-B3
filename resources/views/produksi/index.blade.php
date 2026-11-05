@php
    $kelola = Auth::user()->hasAnyRole(['admin', 'manajer']);
    $filter = request('status');
    $tab = ['' => 'Semua', 'terjadwal' => 'Terjadwal', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai', 'batal' => 'Batal'];
@endphp

<x-app-layout>
    <x-page-header title="Manajemen produksi" eyebrow="Produksi" subtitle="Jadwal dan batch produksi tahu.">
        @if ($kelola)
            <x-slot:actions>
                <x-btn :href="route('produksi.create')" icon="add">Jadwalkan produksi</x-btn>
            </x-slot:actions>
        @endif
    </x-page-header>

    <div class="-mx-4 mb-4 flex gap-1.5 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0">
        @foreach ($tab as $nilai => $label)
            <a href="{{ route('produksi.index', $nilai === '' ? [] : ['status' => $nilai]) }}"
               @if ((string) $filter === (string) $nilai) aria-current="true" @endif
               class="shrink-0 rounded-tm-md px-3.5 py-1.5 text-label-md transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary {{ (string) $filter === (string) $nilai ? 'bg-primary-container font-bold text-on-primary-container shadow-sm' : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' }}">{{ $label }}</a>
        @endforeach
    </div>

    <x-card class="!p-0 overflow-hidden">
        <x-table-head cols="md:grid-cols-[minmax(0,1.6fr)_1.4fr_1fr_1.2fr_1fr_1.5rem]">
            <span>Batch</span><span>Jadwal</span><span>Kedelai</span><span>Penanggung jawab</span><span>Status</span><span></span>
        </x-table-head>

        <div class="divide-y divide-surface-container-low">
            @forelse ($produksi as $p)
                <a href="{{ route('produksi.show', $p) }}"
                   class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-5 py-3.5 transition-colors hover:bg-surface-container-low/60 focus:outline-none focus-visible:bg-surface-container-low sm:px-6 md:grid-cols-[minmax(0,1.6fr)_1.4fr_1fr_1.2fr_1fr_1.5rem] {{ $p->status->value === 'berjalan' ? 'bg-surface-container-low/70' : '' }}">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md {{ $p->status->value === 'berjalan' ? 'bg-primary-container text-on-primary-container' : 'bg-surface-container text-secondary' }}"><x-icon name="precision_manufacturing" class="text-[20px]" /></span>
                        <div class="min-w-0">
                            <div class="truncate text-label-lg text-on-surface">{{ $p->kode_batch }}</div>
                            <div class="text-body-sm text-on-surface-variant md:hidden">
                                {{ $p->tanggal_jadwal->translatedFormat('d M Y') }} · <x-qty :value="$p->berat_kedelai_kg" unit="kg" />
                            </div>
                        </div>
                    </div>
                    <div class="hidden text-body-md text-on-surface md:block">
                        {{ $p->tanggal_jadwal->translatedFormat('d M Y') }}
                        @if ($p->jam_rencana)<div class="text-body-sm text-on-surface-variant">{{ \Illuminate\Support\Str::of($p->jam_rencana)->substr(0, 5) }}</div>@endif
                    </div>
                    <div class="hidden text-label-lg text-on-surface md:block"><x-qty :value="$p->berat_kedelai_kg" unit="kg" /></div>
                    <div class="hidden text-body-md text-on-surface md:block">{{ $p->penanggungJawab?->name ?? '-' }}</div>
                    <div class="text-right md:text-left"><x-status-badge :status="$p->status" /></div>
                    <div class="hidden text-outline md:block"><x-icon name="chevron_right" class="text-[20px]" /></div>
                </a>
            @empty
                <div class="p-6"><x-empty-state icon="precision_manufacturing" title="Belum ada produksi" text="Tidak ada batch yang cocok dengan filter ini.">
                    @if ($kelola)<x-btn :href="route('produksi.create')" icon="add">Jadwalkan produksi</x-btn>@endif
                </x-empty-state></div>
            @endforelse
        </div>

        {{ $produksi->links() }}
    </x-card>
</x-app-layout>
