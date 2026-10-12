<?php
// routes/web.php

use App\Http\Controllers\BahanBakuController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DetailProduksiController;
use App\Http\Controllers\HasilProduksiController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StokBahanBakuController;
use App\Http\Controllers\StokProdukController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'aktif'])->group(function () {

    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Profil (bawaan Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── ADMIN + MANAJER: master data, stok, jadwal produksi ──────────────
    Route::middleware('role:admin|manajer')->group(function () {
        Route::resource('suppliers', SupplierController::class)->except('show');

        Route::resource('bahan-baku', BahanBakuController::class)->parameters(['bahan-baku' => 'bahanBaku']);
        Route::post('bahan-baku/{bahanBaku}/stok-masuk', [StokBahanBakuController::class, 'masuk'])->name('bahan-baku.stok-masuk');
        Route::post('bahan-baku/{bahanBaku}/opname', [StokBahanBakuController::class, 'opname'])->name('bahan-baku.opname');

        Route::resource('produk', ProdukController::class)->except('show');
        Route::post('stok-produk/{produk}/opname', [StokProdukController::class, 'opname'])->name('stok-produk.opname');

        // didaftarkan SEBELUM produksi.show agar /produksi/create tidak tertangkap {produksi}
        Route::resource('produksi', ProduksiController::class)->only(['create', 'store']);
        Route::post('produksi/{produksi}/mulai', [ProduksiController::class, 'mulai'])->name('produksi.mulai');
        Route::post('produksi/{produksi}/batal', [ProduksiController::class, 'batal'])->name('produksi.batal');
    });

    // ── ADMIN + MANAJER + KARYAWAN: pengerjaan produksi ──────────────────
    Route::middleware('role:admin|manajer|karyawan')->group(function () {
        Route::patch('produksi/{produksi}/tahap/{tahap}', [DetailProduksiController::class, 'update'])->name('produksi.tahap.update');
        Route::post('produksi/{produksi}/hasil', [HasilProduksiController::class, 'store'])->name('produksi.hasil.store');
    });

    // ── ADMIN + MANAJER + KASIR: penjualan & stok produk ─────────────────
    Route::middleware('role:admin|manajer|kasir')->group(function () {
        Route::resource('penjualan', PenjualanController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('penjualan/{penjualan}/batal', [PenjualanController::class, 'batal'])->name('penjualan.batal');
        Route::get('stok-produk', [StokProdukController::class, 'index'])->name('stok-produk.index');
    });

    // ── SEMUA ROLE KERJA: lihat produksi ─────────────────────────────────
    Route::middleware('role:admin|manajer|karyawan|kasir')->group(function () {
        Route::resource('produksi', ProduksiController::class)->only(['index', 'show']);
    });
});