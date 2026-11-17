
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prediksi_ai', function (Blueprint $table) {
            $table->id();

            $table->foreignId('produksi_id')
                ->constrained('produksi')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->decimal('berat_kedelai_kg', 10, 2);
            $table->unsignedInteger('jumlah_gambar');

            $table->decimal('good_percent', 5, 2);
            $table->decimal('bad_percent', 5, 2);

            $table->unsignedInteger('uncertain_count')->default(0);
            $table->decimal('coverage_percent', 5, 2)->default(0);

            $table->decimal('hasil_tahu_kg', 10, 2);
            $table->unsignedInteger('waktu_memasak_menit');

            $table->unsignedInteger('durasi_sebelumnya_menit')
                ->nullable();

            $table->boolean('diterapkan')->default(false);
            $table->json('response_json')->nullable();

            $table->timestamps();

            $table->index(['produksi_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediksi_ai');
    }
};
