<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi', function (Blueprint $table) {
            $table->boolean('mode_laporan')
                ->default(false)
                ->after('status');

            $table->unsignedSmallInteger('durasi_non_masak_estimasi_menit')
                ->nullable()
                ->after('berat_kedelai_kg');

            $table->unsignedSmallInteger('durasi_masak_aktual_menit')
                ->nullable()
                ->after('waktu_selesai');

            $table->unsignedSmallInteger('durasi_non_masak_aktual_menit')
                ->nullable()
                ->after('durasi_masak_aktual_menit');

            $table->unsignedInteger('durasi_total_aktual_menit')
                ->nullable()
                ->after('durasi_non_masak_aktual_menit');

            $table->text('catatan_aktual')
                ->nullable()
                ->after('catatan');

            $table->dateTime('stok_dikembalikan_at')
                ->nullable()
                ->after('catatan_aktual');

            $table->foreignId('dibatalkan_oleh')
                ->nullable()
                ->after('stok_dikembalikan_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('alasan_batal')
                ->nullable()
                ->after('dibatalkan_oleh');

            $table->index(['mode_laporan', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('produksi', function (Blueprint $table) {
            $table->dropIndex(['mode_laporan', 'status']);
            $table->dropConstrainedForeignId('dibatalkan_oleh');

            $table->dropColumn([
                'mode_laporan',
                'durasi_non_masak_estimasi_menit',
                'durasi_masak_aktual_menit',
                'durasi_non_masak_aktual_menit',
                'durasi_total_aktual_menit',
                'catatan_aktual',
                'stok_dikembalikan_at',
                'alasan_batal',
            ]);
        });
    }
};
