<?php
// database/migrations/2026_10_08_000006_create_produksi_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produksi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_batch', 30)->unique();
            $table->date('tanggal_jadwal');
            $table->time('jam_rencana')->nullable();
            $table->decimal('berat_kedelai_kg', 10, 2);              // dihitung dari bahan kategori "utama"
            $table->string('status', 20)->default('terjadwal');      // terjadwal | berjalan | selesai | batal
            $table->dateTime('waktu_mulai')->nullable();
            $table->dateTime('waktu_selesai')->nullable();
            $table->decimal('biaya_bahan', 14, 2)->default(0);       // HPP informatif, bukan pengeluaran kas
            $table->foreignId('penanggung_jawab_id')->constrained('users')->restrictOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['tanggal_jadwal', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produksi');
    }
};