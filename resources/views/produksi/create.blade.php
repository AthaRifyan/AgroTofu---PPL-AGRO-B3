@php
    $bahanErrors = collect($errors->getMessages())->filter(fn ($m, $k) => str_starts_with($k, 'bahan'))->flatten()->all();
@endphp

<x-app-layout>
    <x-page-header title="Jadwalkan produksi" eyebrow="Produksi" subtitle="Stok bahan baru berkurang saat batch dimulai." :back="route('produksi.index')" />

    <form method="POST" action="{{ route('produksi.store') }}" class="flex flex-col gap-4"
          x-data="produksiForm(@js($bahanJson), @js(old('bahan', [])))"
          x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf

        <x-card title="Jadwal">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="tanggal_jadwal" label="Tanggal produksi">
                    <x-text-input id="tanggal_jadwal" name="tanggal_jadwal" type="date" :value="old('tanggal_jadwal', now()->toDateString())" required />
                </x-field>
                <x-field name="jam_rencana" label="Jam rencana">
                    <x-text-input id="jam_rencana" name="jam_rencana" type="time" :value="old('jam_rencana')" required />
                </x-field>
            </div>
            <x-field class="mt-5" name="catatan" label="Catatan (opsional)">
                <x-textarea-input id="catatan" name="catatan">{{ old('catatan') }}</x-textarea-input>
            </x-field>
        </x-card>

        <x-card title="Bahan baku yang dipakai" description="Wajib ada minimal satu bahan berkategori utama (kedelai).">
            {{-- Legenda warna kategori --}}
            <div class="mb-3 flex flex-wrap gap-2 text-label-sm">
                <span class="inline-flex items-center gap-1.5 rounded-tm-lg bg-primary-container px-2.5 py-1 text-on-primary-container"><span class="h-2 w-2 rounded-full bg-current opacity-70"></span>Utama</span>
                <span class="inline-flex items-center gap-1.5 rounded-tm-lg bg-secondary-fixed px-2.5 py-1 text-on-secondary-fixed-variant"><span class="h-2 w-2 rounded-full bg-current opacity-70"></span>Penggumpal</span>
                <span class="inline-flex items-center gap-1.5 rounded-tm-lg bg-tertiary-fixed px-2.5 py-1 text-on-tertiary-fixed"><span class="h-2 w-2 rounded-full bg-current opacity-70"></span>Pendukung</span>
            </div>
            <div class="space-y-3">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="rounded-tm-md border-l-4 p-3 transition-colors"
                         x-bind:class="{
                             'border-primary-container bg-primary-fixed/30': kat(row) === 'utama',
                             'border-secondary bg-secondary-fixed/40': kat(row) === 'penggumpal',
                             'border-tertiary bg-tertiary-fixed/40': kat(row) === 'pendukung',
                             'border-outline-variant bg-surface-container-low': ! kat(row),
                         }">
                        <div class="flex items-start gap-2">
                            <div class="grid flex-1 gap-3 sm:grid-cols-[1fr_9rem]">
                                <div>
                                    <div class="mb-1 flex items-center justify-between gap-2">
                                        <label class="text-label-md text-on-surface" x-bind:for="'bahan_' + i">Pilih bahan</label>
                                        <span x-show="kat(row)" x-cloak class="rounded-tm-lg px-2 py-0.5 text-label-sm capitalize"
                                              x-bind:class="{
                                                  'bg-primary-container text-on-primary-container': kat(row) === 'utama',
                                                  'bg-secondary-fixed text-on-secondary-fixed-variant': kat(row) === 'penggumpal',
                                                  'bg-tertiary-fixed text-on-tertiary-fixed': kat(row) === 'pendukung',
                                              }" x-text="kat(row)"></span>
                                    </div>
                                    <select x-bind:id="'bahan_' + i" x-bind:name="`bahan[${i}][bahan_baku_id]`" x-model="row.bahan_baku_id" required
                                            class="w-full rounded-tm-md border-2 bg-surface-container-lowest py-3 pl-4 pr-10 text-body-md text-on-surface shadow-sm focus:outline-none focus:ring-2"
                                            x-bind:class="{
                                                'border-primary-container focus:border-primary-container focus:ring-primary-container/50': kat(row) === 'utama',
                                                'border-secondary focus:border-secondary focus:ring-secondary/40': kat(row) === 'penggumpal',
                                                'border-tertiary focus:border-tertiary focus:ring-tertiary/40': kat(row) === 'pendukung',
                                                'border-outline focus:border-primary focus:ring-primary/30': ! kat(row),
                                            }">
                                        <option value="">Pilih bahan…</option>
                                        @foreach (['utama' => 'Utama', 'penggumpal' => 'Penggumpal', 'pendukung' => 'Pendukung'] as $kat => $namaKat)
                                            @php $grup = collect($bahanJson)->where('kategori', $kat); @endphp
                                            @if ($grup->isNotEmpty())
                                                <optgroup label="{{ $namaKat }}">
                                                    @foreach ($grup as $b)
                                                        <option value="{{ $b['id'] }}" x-bind:disabled="dipakaiBaris({{ $b['id'] }}, i)">{{ $b['nama'] }} · stok {{ rtrim(rtrim(number_format($b['stok'], 2, ',', '.'), '0'), ',') }} {{ $b['satuan'] }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-label-md text-on-surface" x-bind:for="'jumlah_' + i">
                                        Jumlah <span x-show="info(row.bahan_baku_id)" x-text="'(' + (info(row.bahan_baku_id)?.satuan ?? '') + ')'"></span>
                                    </label>
                                    <input x-bind:id="'jumlah_' + i" x-bind:name="`bahan[${i}][jumlah]`" x-model="row.jumlah" type="number" step="any" min="0" inputmode="decimal" required
                                           class="w-full rounded-tm-md border border-transparent bg-surface-container-low px-4 py-3 text-body-md text-on-surface focus:bg-surface-bright focus:border-primary-container focus:outline-none focus:ring-2 focus:ring-primary-container">
                                </div>
                            </div>
                            <button type="button" x-on:click="hapus(i)" x-show="rows.length > 1" aria-label="Hapus baris"
                                    class="mt-6 flex h-10 w-10 shrink-0 items-center justify-center rounded-tm-md text-on-surface-variant hover:bg-surface-container-high focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary">
                                <x-icon name="delete" class="text-[22px]" />
                            </button>
                        </div>
                        <p x-show="melebihiStok(row)" x-cloak class="mt-2 flex items-center gap-1 text-body-sm text-error">
                            <x-icon name="warning" class="text-[16px]" />
                            <span>Melebihi stok saat ini. Batch tidak bisa dimulai sebelum stok cukup.</span>
                        </p>
                    </div>
                </template>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <x-btn type="button" variant="secondary" size="sm" icon="add" x-on:click="tambah()">Tambah bahan</x-btn>
                <p class="text-body-sm text-on-surface-variant">Berat kedelai (kategori utama): <span class="text-label-lg text-on-surface" x-text="new Intl.NumberFormat('id-ID').format(beratUtama) + ' kg'"></span></p>
            </div>

            <x-input-error class="mt-3" :messages="$bahanErrors" />
        </x-card>

        <x-card>
            <x-checkbox name="langsung_mulai" label="Langsung mulai batch sekarang (stok bahan langsung dikurangi)" :checked="false" />
        </x-card>

        <div class="flex flex-wrap gap-3">
            <x-submit-button class="w-auto" icon="save" loading-text="Menyimpan…">Simpan jadwal</x-submit-button>
            <x-btn :href="route('produksi.index')" variant="secondary">Batal</x-btn>
        </div>
    </form>
</x-app-layout>
