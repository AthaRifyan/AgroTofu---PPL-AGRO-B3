<?php
// app/Http/Controllers/SupplierController.php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->when($request->q, fn ($q, $s) => $q->where(
                fn ($w) => $w->where('nama', 'like', "%{$s}%")->orWhere('kode', 'like', "%{$s}%")
            ))
            ->orderBy('nama')->paginate(15)->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::create($this->validasi($request));

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validasi($request, $supplier));

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->mutasiBahan()->exists()) {
            return back()->with('error', 'Supplier sudah punya riwayat stok masuk. Nonaktifkan saja, jangan dihapus.');
        }

        $supplier->delete();

        return back()->with('success', 'Supplier dihapus.');
    }

    private function validasi(Request $request, ?Supplier $supplier = null): array
    {
        $data = $request->validate([
            'kode'          => ['required', 'string', 'max:20', Rule::unique('suppliers', 'kode')->ignore($supplier)],
            'nama'          => ['required', 'string', 'max:100'],
            'kontak_person' => ['nullable', 'string', 'max:100'],
            'no_hp'         => ['nullable', 'string', 'max:20'],
            'alamat'        => ['nullable', 'string'],
            'catatan'       => ['nullable', 'string'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
