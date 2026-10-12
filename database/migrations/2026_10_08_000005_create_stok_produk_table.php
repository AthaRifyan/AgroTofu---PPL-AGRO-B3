<?php
// database/migrations/2026_10_08_000005_create_stok_produk_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('tipe', 20);                  // masuk | keluar | penyesuaian
            $table->decimal('jumlah', 14, 2);
            $table->decimal('stok_sebelum', 14, 2);
            $table->decimal('stok_sesudah', 14, 2);
            $table->nullableMorphs('referensi');         // batch produksi / nota penjualan
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['produk_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_produk');
    }
};