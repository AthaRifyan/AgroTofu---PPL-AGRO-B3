<?php
// database/migrations/2026_10_08_000012_create_pengeluaran_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('kategori', 30);              // bahan_baku | operasional | lainnya
            $table->string('keterangan');
            $table->decimal('jumlah', 14, 2);
            $table->date('tanggal');
            $table->nullableMorphs('referensi');
            $table->timestamps();

            $table->index(['tanggal', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran');
    }
};