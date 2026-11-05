<x-app-layout>
    <x-page-header title="Tambah produk" eyebrow="Gudang" :back="route('produk.index')" />
    <x-card>
        @include('produk._form', ['produk' => new \App\Models\Produk(['satuan' => 'kg']), 'action' => route('produk.store'), 'method' => 'POST'])
    </x-card>
</x-app-layout>
