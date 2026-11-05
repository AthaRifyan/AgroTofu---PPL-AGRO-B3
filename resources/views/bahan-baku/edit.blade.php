<x-app-layout>
    <x-page-header title="Ubah bahan baku" eyebrow="Gudang" subtitle="Stok tidak diubah di sini. Pakai Stok masuk atau Opname." :back="route('bahan-baku.show', $bahanBaku)" />
    <x-card>
        @include('bahan-baku._form', ['action' => route('bahan-baku.update', $bahanBaku), 'method' => 'PUT'])
    </x-card>
</x-app-layout>
