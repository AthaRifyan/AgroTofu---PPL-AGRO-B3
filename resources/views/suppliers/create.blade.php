<x-app-layout>
    <x-page-header title="Tambah supplier" eyebrow="Gudang" :back="route('suppliers.index')" />
    <x-card>
        @include('suppliers._form', ['supplier' => new \App\Models\Supplier(), 'action' => route('suppliers.store'), 'method' => 'POST'])
    </x-card>
</x-app-layout>
