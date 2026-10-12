<?php
// database/migrations/2026_10_08_000010_create_penjualan_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('no_nota', 30)->unique();
            $table->foreignId('kasir_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('tanggal');
            $table->string('nama_pembeli', 100)->nullable();
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('bayar', 14, 2)->default(0);
            $table->decimal('kembalian', 14, 2)->default(0);
            $table->string('metode_pembayaran', 20)->default('tunai'); // tunai | transfer | qris | lainnya
            $table->string('status', 20)->default('selesai');          // selesai | batal
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan');
    }
};