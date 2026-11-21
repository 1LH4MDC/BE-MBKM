<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pengajuan_semhas')) {
            Schema::create('pengajuan_semhas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pendaftaran_mbkm_id')->unique()->constrained('pendaftaran_mbkms')->cascadeOnDelete();
                $table->foreignId('periode_akademik_id')->nullable()->constrained('periode_akademiks')->nullOnDelete();

                // 3 Dokumen PDF Semhas
                $table->string('laporan_akhir_path', 500)->nullable();
                $table->string('laporan_akhir_name', 255)->nullable();
                $table->string('logbook_fb07_path', 500)->nullable();
                $table->string('logbook_fb07_name', 255)->nullable();
                $table->string('nilai_supervisor_path', 500)->nullable();
                $table->string('nilai_supervisor_name', 255)->nullable();

                // Keputusan Verifikasi PIC
                $table->string('status_kelayakan', 50)->default('Menunggu Verifikasi');
                $table->text('catatan_pic')->nullable();
                $table->foreignId('verified_by_pic_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at_pic')->nullable();

                // Mekanisme Hak Override Ketua Tim MBKM
                $table->boolean('is_override_ketua')->default(false);
                $table->text('catatan_override_ketua')->nullable();
                $table->foreignId('override_by_ketua_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('override_at')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_semhas');
    }
};
