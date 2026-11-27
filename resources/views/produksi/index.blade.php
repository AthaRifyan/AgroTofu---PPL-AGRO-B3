
@php
    $user = Auth::user();
    $karyawan = $user->hasRole('karyawan');
    $filter = request('status');

    $tab = [
        '' => 'Semua',
        'terjadwal' => 'Terjadwal',
        'berjalan' => 'Berjalan',
        'selesai' => 'Selesai',
        'batal' => 'Batal',
    ];
@endphp

<x-app-layout>
    <x-page-header
        title="Laporan produksi"
        eyebrow="Produksi"
        subtitle="{{ $karyawan ? 'Daftar batch produksi milikmu.' : 'Daftar seluruh batch produksi.' }}"
    >
        @if ($karyawan)
            <x-slot:actions>
                <x-btn
                    :href="route('produksi.create')"
                    icon="add"
                >
                    Buat laporan batch
                </x-btn>
            </x-slot:actions>
        @endif
    </x-page-header>

    <div class="-mx-4 mb-4 flex gap-1.5 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0">
        @foreach ($tab as $nilai => $label)
            <a
                href="{{ route('produksi.index', $nilai === '' ? [] : ['status' => $nilai]) }}"
                @if ((string) $filter === (string) $nilai)
                    aria-current="true"
                @endif
                class="shrink-0 rounded-tm-md px-3.5 py-1.5 text-label-md
                    {{ (string) $filter === (string) $nilai
                        ? 'bg-primary-container font-bold text-on-primary-container'
                        : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    <x-card class="!p-0 overflow-hidden">
        <x-table-head cols="md:grid-cols-[minmax(0,1.5fr)_1fr_1fr_1.2fr_1fr_1.5rem]">
            <span>Kode batch</span>
            <span>Tanggal</span>
            <span>Kedelai</span>
            <span>Penanggung jawab</span>
            <span>Status</span>
            <span></span>
        </x-table-head>

        <div class="divide-y divide-surface-container-low">
            @forelse ($produksi as $p)
                <a
                    href="{{ route('produksi.show', $p) }}"
                    class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-5 py-3.5 hover:bg-surface-container-low/60 sm:px-6 md:grid-cols-[minmax(0,1.5fr)_1fr_1fr_1.2fr_1fr_1.5rem]"
                >
                    <div class="min-w-0">
                        <div class="truncate text-label-lg text-on-surface">
                            {{ $p->kode_batch }}
                        </div>

                        <div class="text-body-sm text-on-surface-variant">
                            {{ $p->mode_laporan ? 'Laporan batch' : 'Produksi lama' }}
                        </div>
                    </div>

                    <div class="hidden text-body-md text-on-surface md:block">
                        {{ $p->tanggal_jadwal->translatedFormat('d M Y') }}
                    </div>

                    <div class="hidden text-body-md text-on-surface md:block">
                        {{ number_format((float) $p->berat_kedelai_kg, 2, ',', '.') }} kg
                    </div>

                    <div class="hidden text-body-md text-on-surface md:block">
                        {{ $p->penanggungJawab?->name ?? '-' }}
                    </div>

                    <div class="text-right md:text-left">
                        <x-status-badge :status="$p->status" />
                    </div>

                    <div class="hidden text-outline md:block">
                        <x-icon name="chevron_right" class="text-[20px]" />
                    </div>
                </a>
            @empty
                <div class="p-6">
                    <x-empty-state
                        icon="precision_manufacturing"
                        title="Belum ada laporan produksi"
                        text="Belum ada batch yang cocok dengan filter ini."
                    >
                        @if ($karyawan)
                            <x-btn
                                :href="route('produksi.create')"
                                icon="add"
                            >
                                Buat laporan batch
                            </x-btn>
                        @endif
                    </x-empty-state>
                </div>
            @endforelse
        </div>

        <div class="p-4">
            {{ $produksi->links() }}
        </div>
    </x-card>
</x-app-layout>
