<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_produksi', function (Blueprint $table) {
            $table->unsignedSmallInteger('durasi_rencana_menit')->nullable()->after('status');   // durasi yang diinput user
            $table->dateTime('batas_waktu')->nullable()->after('waktu_mulai');                   // waktu_mulai + durasi
            $table->unsignedInteger('durasi_aktual_detik')->nullable()->after('waktu_selesai');  // lama sebenarnya
            $table->string('jenis_selesai', 20)->nullable()->after('waktu_selesai');             // manual | otomatis
        });

        // pindahkan data lama (menit -> detik), lalu buang kolom lama
        DB::table('detail_produksi')->whereNotNull('durasi_menit')->update([
            'durasi_aktual_detik' => DB::raw('durasi_menit * 60'),
            'jenis_selesai'       => 'manual',
        ]);

        Schema::table('detail_produksi', function (Blueprint $table) {
            $table->dropColumn('durasi_menit');
        });
    }

    public function down(): void
    {
        Schema::table('detail_produksi', function (Blueprint $table) {
            $table->unsignedInteger('durasi_menit')->nullable();
        });

        DB::table('detail_produksi')->whereNotNull('durasi_aktual_detik')->update([
            'durasi_menit' => DB::raw('ROUND(durasi_aktual_detik / 60)'),
        ]);

        Schema::table('detail_produksi', function (Blueprint $table) {
            $table->dropColumn(['durasi_rencana_menit', 'batas_waktu', 'durasi_aktual_detik', 'jenis_selesai']);
        });
    }
};