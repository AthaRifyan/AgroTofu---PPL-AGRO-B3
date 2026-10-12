<?php
// database/migrations/2026_10_08_000008_create_detail_produksi_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_produksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produksi_id')->constrained('produksi')->cascadeOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('urutan');
            $table->string('tahap', 30);                             // lihat enum TahapProduksi
            $table->string('status', 20)->default('menunggu');       // menunggu | berjalan | selesai
            $table->dateTime('waktu_mulai')->nullable();
            $table->dateTime('waktu_selesai')->nullable();
            $table->unsignedInteger('durasi_menit')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['produksi_id', 'tahap']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_produksi');
    }
};