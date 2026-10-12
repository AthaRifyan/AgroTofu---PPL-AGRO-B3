<?php
// database/migrations/2026_10_08_000007_create_produksi_bahan_baku_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produksi_bahan_baku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produksi_id')->constrained('produksi')->cascadeOnDelete();
            $table->foreignId('bahan_baku_id')->constrained('bahan_baku')->restrictOnDelete();
            $table->decimal('jumlah_dipakai', 14, 2);
            $table->decimal('harga_satuan', 14, 2)->default(0);      // snapshot harga saat batch dimulai
            $table->timestamps();

            $table->unique(['produksi_id', 'bahan_baku_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produksi_bahan_baku');
    }
};