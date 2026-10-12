<?php
// database/migrations/2026_10_08_000003_create_stok_bahan_baku_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_bahan_baku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_baku_id')->constrained('bahan_baku')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('tipe', 20);                  // masuk | keluar | penyesuaian (opname)
            $table->decimal('jumlah', 14, 2);            // untuk penyesuaian = stok fisik hasil hitung
            $table->decimal('harga_satuan', 14, 2)->default(0);
            $table->decimal('stok_sebelum', 14, 2);
            $table->decimal('stok_sesudah', 14, 2);
            $table->nullableMorphs('referensi');         // mis. batch produksi yang memakai bahan
            $table->date('tanggal');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['bahan_baku_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_bahan_baku');
    }
};