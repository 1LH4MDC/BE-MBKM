<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plotting_pemonev_mitras')) {
            Schema::create('plotting_pemonev_mitras', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mitra_id')->constrained('mitras')->cascadeOnDelete();
                $table->foreignId('periode_akademik_id')->constrained('periode_akademiks')->cascadeOnDelete();
                $table->foreignId('dosen_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['mitra_id', 'periode_akademik_id', 'dosen_id'], 'uniq_pemonev_mitra_periode_dosen');
            });
        }

        if (! Schema::hasTable('monev_mitras')) {
            Schema::create('monev_mitras', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mitra_id')->constrained('mitras')->cascadeOnDelete();
                $table->foreignId('periode_akademik_id')->constrained('periode_akademiks')->cascadeOnDelete();
                $table->string('periode_monev', 50)->comment('Minggu ke-0, Minggu ke-8, Minggu ke-18');
                $table->date('tanggal_pelaksanaan');
                $table->string('metode_pelaksanaan', 50)->comment('Luring atau Daring');
                $table->text('catatan_evaluasi');
                $table->text('kendala_tindak_lanjut')->nullable();
                $table->string('dokumentasi_path', 500)->nullable();
                $table->string('dokumentasi_name', 255)->nullable();
                $table->foreignId('filled_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['mitra_id', 'periode_akademik_id', 'periode_monev'], 'uniq_monev_mitra_periode_tahap');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monev_mitras');
        Schema::dropIfExists('plotting_pemonev_mitras');
    }
};
