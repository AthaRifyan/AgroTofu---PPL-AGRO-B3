{{-- resources/views/produksi/_prediksi-ai.blade.php
     Dipasang di produksi/show.blade.php, tepat setelah kartu "Alur tahapan produksi":
         @include('produksi._prediksi-ai')
     Memakai variabel $produksi (relasi prediksiAi.pengguna sudah dimuat controller). --}}

@php
    $bisaJalankan = $produksi->status === \App\Enums\StatusProduksi::Berjalan
        && auth()->user()->hasRole('karyawan');
    $terakhir = $produksi->prediksiAi->first();
    $kolom = 'md:grid-cols-[1.4fr_1fr_1fr_1fr_1.4fr_0.8fr]';
@endphp

<x-card class="mt-4" title="Prediksi AI kualitas kedelai"
        description="Upload foto biji kedelai. AI menghitung persentase Good/Bad, estimasi hasil tahu, dan durasi pemasakan, lalu mengisi durasi tahap pemasakan secara otomatis.">

    @if ($bisaJalankan)
        <form method="POST" enctype="multipart/form-data" action="{{ route('produksi.prediksi-ai', $produksi) }}"
              class="flex flex-col gap-4"
              x-data="{ loading: false, n: 0 }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
            @csrf

            <x-field name="berat_kedelai_kg" label="Berat kedelai (kg)">
                <x-text-input id="berat_kedelai_kg" name="berat_kedelai_kg" type="number" step="0.01" min="0.01"
                              inputmode="decimal" required class="sm:w-64"
                              :value="old('berat_kedelai_kg', $produksi->berat_kedelai_kg)" />
            </x-field>

            <div>
                <label for="images" class="mb-1 block text-label-md text-on-surface">
                    Foto biji kedelai
                    <span class="text-on-surface-variant">(maks. {{ config('services.soybean_ai.max_images') }} foto, 10 MB per foto)</span>
                </label>
                <input type="file" id="images" name="images[]" accept="image/png,image/jpeg,image/webp" multiple required
                       x-on:change="n = $event.target.files.length"
                       class="block w-full text-body-sm text-on-surface-variant file:mr-3 file:rounded-tm-md file:border-0 file:bg-primary-container file:px-3.5 file:py-1.5 file:text-label-md file:font-bold file:text-on-primary-container">
                <p class="mt-1 text-body-sm text-on-surface-variant" x-show="n > 0" x-text="n + ' foto dipilih'"></p>
                @error('images') <p class="mt-1 text-body-sm text-error">{{ $message }}</p> @enderror
                @error('images.*') <p class="mt-1 text-body-sm text-error">{{ $message }}</p> @enderror
            </div>

            <x-submit-button class="w-auto self-start" icon="psychology" loading-text="Memproses… jangan tutup halaman">
                Jalankan prediksi AI
            </x-submit-button>
        </form>
    @else
        <p class="text-body-sm text-on-surface-variant">
            Prediksi AI hanya tersedia untuk batch yang sedang berjalan dan khusus karyawan pada batch yang sedang berjalan.
        </p>
    @endif

    @if ($terakhir)
        <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-tm-md bg-surface-container-low p-3.5">
                <div class="text-body-sm text-on-surface-variant">Good</div>
                <div class="text-label-lg font-bold text-on-surface">{{ number_format($terakhir->good_percent, 1) }}%</div>
            </div>
            <div class="rounded-tm-md bg-surface-container-low p-3.5">
                <div class="text-body-sm text-on-surface-variant">Bad</div>
                <div class="text-label-lg font-bold text-on-surface">{{ number_format($terakhir->bad_percent, 1) }}%</div>
            </div>
            <div class="rounded-tm-md bg-surface-container-low p-3.5">
                <div class="text-body-sm text-on-surface-variant">Estimasi hasil tahu</div>
                <div class="text-label-lg font-bold text-on-surface">{{ number_format($terakhir->hasil_tahu_kg, 2) }} kg</div>
            </div>
            <div class="rounded-tm-md bg-surface-container-low p-3.5">
                <div class="text-body-sm text-on-surface-variant">Durasi pemasakan</div>
                <div class="text-label-lg font-bold text-on-surface">{{ \App\Models\PrediksiAi::label($terakhir->waktu_memasak_menit) }}</div>
                @if ($terakhir->diterapkan)
                    <div class="mt-0.5 text-body-sm text-secondary">
                        @if ($terakhir->durasi_sebelumnya_menit !== null)
                            Disesuaikan AI dari {{ \App\Models\PrediksiAi::label($terakhir->durasi_sebelumnya_menit) }}
                        @else
                            Sudah diisi ke tahap pemasakan
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-card>

@if ($terakhir)
    <x-card class="mt-4 !p-0 overflow-hidden">
        <x-table-head :cols="$kolom">
            <span>Waktu / oleh</span><span>Berat / foto</span><span>Good / Bad</span><span>Hasil tahu</span><span>Durasi masak</span><span>Diterapkan</span>
        </x-table-head>

        <div class="divide-y divide-surface-container-low">
            @foreach ($produksi->prediksiAi as $riwayat)
                <div class="grid grid-cols-2 items-center gap-x-3 gap-y-1 px-5 py-3.5 text-body-md text-on-surface sm:px-6 {{ $kolom }}">
                    <div>
                        <div class="text-label-lg">{{ $riwayat->created_at->translatedFormat('d M Y H:i') }}</div>
                        <div class="text-body-sm text-on-surface-variant">{{ $riwayat->pengguna?->name ?? '-' }}</div>
                    </div>
                    <div>
                        {{ number_format($riwayat->berat_kedelai_kg, 2) }} kg
                        <div class="text-body-sm text-on-surface-variant">{{ $riwayat->jumlah_gambar }} foto</div>
                    </div>
                    <div>{{ number_format($riwayat->good_percent, 1) }}% / {{ number_format($riwayat->bad_percent, 1) }}%</div>
                    <div>{{ number_format($riwayat->hasil_tahu_kg, 2) }} kg</div>
                    <div>
                        @if ($riwayat->durasi_sebelumnya_menit !== null && $riwayat->durasi_sebelumnya_menit !== $riwayat->waktu_memasak_menit)
                            {{ \App\Models\PrediksiAi::label($riwayat->durasi_sebelumnya_menit) }} → {{ \App\Models\PrediksiAi::label($riwayat->waktu_memasak_menit) }}
                        @else
                            {{ \App\Models\PrediksiAi::label($riwayat->waktu_memasak_menit) }}
                        @endif
                    </div>
                    <div class="text-body-sm text-on-surface-variant">{{ $riwayat->diterapkan ? 'Ya' : 'Tidak' }}</div>
                </div>
            @endforeach
        </div>
    </x-card>
@endif