
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_produksi', function (Blueprint $table) {
            if (!Schema::hasColumn('detail_produksi', 'durasi_awal_menit')) {
                $table->unsignedInteger('durasi_awal_menit')
                    ->nullable()
                    ->after('durasi_rencana_menit');
            }

            if (!Schema::hasColumn('detail_produksi', 'disesuaikan_ai')) {
                $table->boolean('disesuaikan_ai')
                    ->default(false)
                    ->after('durasi_awal_menit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('detail_produksi', function (Blueprint $table) {
            if (Schema::hasColumn('detail_produksi', 'disesuaikan_ai')) {
                $table->dropColumn('disesuaikan_ai');
            }

            if (Schema::hasColumn('detail_produksi', 'durasi_awal_menit')) {
                $table->dropColumn('durasi_awal_menit');
            }
        });
    }
};
