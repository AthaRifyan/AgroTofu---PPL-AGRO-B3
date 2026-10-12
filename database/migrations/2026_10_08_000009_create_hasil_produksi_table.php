<?php
// database/migrations/2026_10_08_000009_create_hasil_produksi_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_produksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produksi_id')->constrained('produksi')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $table->foreignId('dicatat_oleh')->constrained('users')->restrictOnDelete();
            $table->decimal('jumlah_baik', 12, 2);                   // masuk ke stok produk
            $table->decimal('jumlah_reject', 12, 2)->default(0);     // rusak/hancur, tidak masuk stok
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['produksi_id', 'produk_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_produksi');
    }
};