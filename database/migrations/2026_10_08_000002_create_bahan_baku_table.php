<?php
// database/migrations/2026_10_08_000002_create_bahan_baku_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bahan_baku', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 100);
            $table->string('kategori', 30)->default('pendukung');  // utama | penggumpal | pendukung
            $table->string('satuan', 20);                           // kg | gram | liter | ml | pcs
            $table->decimal('stok_saat_ini', 14, 2)->default(0);
            $table->decimal('stok_minimum', 14, 2)->default(0);
            $table->decimal('harga_terakhir', 14, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bahan_baku');
    }
};