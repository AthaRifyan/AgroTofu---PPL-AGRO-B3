<?php
// app/Http/Controllers/DetailProduksiController.php

namespace App\Http\Controllers;

use App\Enums\StatusTahap;
use App\Enums\TahapProduksi;
use App\Models\Produksi;
use App\Services\ProduksiService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DetailProduksiController extends Controller
{
    public function update(Request $request, Produksi $produksi, TahapProduksi $tahap, ProduksiService $service): RedirectResponse
    {
        $v = $request->validate(['status' => ['required', Rule::enum(StatusTahap::class)]]);

        try {
            $service->updateTahap($produksi, $tahap, StatusTahap::from($v['status']), $request->user()->id);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Tahap {$tahap->label()} diperbarui.");
    }
}