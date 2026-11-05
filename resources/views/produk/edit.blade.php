<x-app-layout>
    <x-page-header title="Ubah produk" eyebrow="Gudang" :subtitle="$produk->nama" :back="route('produk.index')" />
    <x-card>
        @include('produk._form', ['action' => route('produk.update', $produk), 'method' => 'PUT'])
    </x-card>
</x-app-layout>
