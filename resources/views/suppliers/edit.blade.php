<x-app-layout>
    <x-page-header title="Ubah supplier" eyebrow="Gudang" :subtitle="$supplier->nama" :back="route('suppliers.index')" />
    <x-card>
        @include('suppliers._form', ['action' => route('suppliers.update', $supplier), 'method' => 'PUT'])
    </x-card>
</x-app-layout>
