
<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BahanBakuController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PrediksiAiController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StokBahanBakuController;
use App\Http\Controllers\StokProdukController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'aktif'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::redirect('/', '/dashboard');

    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | PROFIL
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');


    /*
    |--------------------------------------------------------------------------
    | ADMIN: REGISTRASI AKUN
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::get('/register', [RegisteredUserController::class, 'create'])
                ->name('register.create');

            Route::post('/register', [RegisteredUserController::class, 'store'])
                ->name('register.store');
        });


    /*
    |--------------------------------------------------------------------------
    | ADMIN DAN MANAJER: MASTER DATA DAN STOK
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin|manajer')->group(function () {

        // Supplier
        Route::resource('suppliers', SupplierController::class)
            ->except(['show']);


        // Bahan baku
        Route::resource('bahan-baku', BahanBakuController::class)
            ->parameters([
                'bahan-baku' => 'bahanBaku',
            ]);

        Route::post(
            'bahan-baku/{bahanBaku}/stok-masuk',
            [StokBahanBakuController::class, 'masuk']
        )
            ->whereNumber('bahanBaku')
            ->name('bahan-baku.stok-masuk');

        Route::post(
            'bahan-baku/{bahanBaku}/opname',
            [StokBahanBakuController::class, 'opname']
        )
            ->whereNumber('bahanBaku')
            ->name('bahan-baku.opname');


        // Produk
        Route::resource('produk', ProdukController::class)
            ->except(['show']);

        Route::post(
            'stok-produk/{produk}/opname',
            [StokProdukController::class, 'opname']
        )
            ->whereNumber('produk')
            ->name('stok-produk.opname');
    });


    /*
    |--------------------------------------------------------------------------
    | PRODUKSI: LAPORAN BATCH BARU
    |--------------------------------------------------------------------------
    |
    | Alur:
    | 1. Karyawan membuat laporan batch.
    | 2. Sistem memotong stok bahan baku.
    | 3. Karyawan menjalankan prediksi AI.
    | 4. Karyawan mengisi hasil produksi aktual.
    | 5. Karyawan menyelesaikan laporan atau membatalkan batch.
    |
    */


    /*
    |--------------------------------------------------------------------------
    | KARYAWAN: MEMBUAT DAN MENGELOLA LAPORAN BATCH
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:karyawan')->group(function () {

        // Form membuat laporan batch baru
        Route::get(
            'produksi/create',
            [ProduksiController::class, 'create']
        )
            ->name('produksi.create');


        // Menyimpan laporan batch baru
        Route::post(
            'produksi',
            [ProduksiController::class, 'store']
        )
            ->name('produksi.store');


        // Menjalankan prediksi AI untuk batch
        Route::post(
            'produksi/{produksi}/prediksi-ai',
            [ProduksiController::class, 'prediksiAi']
        )
            ->whereNumber('produksi')
            ->name('produksi.prediksi-ai');


        // Menyelesaikan laporan batch
        Route::post(
            'produksi/{produksi}/selesaikan',
            [ProduksiController::class, 'selesaikanLaporan']
        )
            ->whereNumber('produksi')
            ->name('produksi.selesaikan');


        // Membatalkan laporan batch yang belum selesai
        Route::post(
            'produksi/{produksi}/batal-laporan',
            [ProduksiController::class, 'batalLaporan']
        )
            ->whereNumber('produksi')
            ->name('produksi.batal-laporan');
    });


    /*
    |--------------------------------------------------------------------------
    | ADMIN, MANAJER, DAN KARYAWAN: MELIHAT LAPORAN PRODUKSI
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin|manajer|karyawan')->group(function () {

        // Daftar laporan produksi
        Route::get(
            'produksi',
            [ProduksiController::class, 'index']
        )
            ->name('produksi.index');


        // Riwayat prediksi AI
        Route::get(
            'prediksi-ai',
            [PrediksiAiController::class, 'index']
        )
            ->name('prediksi-ai.index');


        /*
         * DETAIL PRODUKSI HARUS DI BAWAH ROUTE PRODUKSI LAINNYA.
         * whereNumber() mencegah kata seperti "create" dianggap sebagai ID.
         */

        Route::get(
            'produksi/{produksi}',
            [ProduksiController::class, 'show']
        )
            ->whereNumber('produksi')
            ->name('produksi.show');
    });


    /*
    |--------------------------------------------------------------------------
    | ADMIN, MANAJER, DAN KASIR: PENJUALAN DAN STOK PRODUK
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin|manajer|kasir')->group(function () {

        // Penjualan
        Route::resource('penjualan', PenjualanController::class)
            ->only([
                'index',
                'create',
                'store',
                'show',
            ]);

        Route::post(
            'penjualan/{penjualan}/batal',
            [PenjualanController::class, 'batal']
        )
            ->whereNumber('penjualan')
            ->name('penjualan.batal');


        // Stok produk
        Route::get(
            'stok-produk',
            [StokProdukController::class, 'index']
        )
            ->name('stok-produk.index');
    });

});
