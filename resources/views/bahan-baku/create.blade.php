<x-app-layout>
    <x-page-header title="Tambah bahan baku" eyebrow="Gudang" :back="route('bahan-baku.index')" />
    <x-card>
        @include('bahan-baku._form', ['action' => route('bahan-baku.store'), 'method' => 'POST'])
    </x-card>
</x-app-layout>
