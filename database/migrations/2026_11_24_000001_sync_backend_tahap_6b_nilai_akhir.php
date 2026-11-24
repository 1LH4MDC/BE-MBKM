<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat tabel penilaian_supervisors (1-to-1 per pendaftaran_mbkm)
        if (! Schema::hasTable('penilaian_supervisors')) {
            Schema::create('penilaian_supervisors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pendaftaran_mbkm_id')->unique()->constrained('pendaftaran_mbkms')->cascadeOnDelete();
                $table->foreignId('periode_akademik_id')->constrained('periode_akademiks')->cascadeOnDelete();
                $table->foreignId('pic_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('nama_supervisor', 255);
                $table->decimal('rata_rata_nilai', 5, 2)->default(0);
                $table->timestamps();
            });
        }

        // 2. Buat tabel penilaian_supervisor_mata_kuliahs
        if (! Schema::hasTable('penilaian_supervisor_mata_kuliahs')) {
            Schema::create('penilaian_supervisor_mata_kuliahs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('penilaian_supervisor_id')->constrained('penilaian_supervisors')->cascadeOnDelete();
                $table->foreignId('mata_kuliah_id')->nullable()->constrained('mata_kuliahs')->nullOnDelete();
                $table->string('kode_mk', 50);
                $table->string('nama_mk', 255);
                $table->unsignedTinyInteger('sks')->default(0);
                $table->text('job_description')->nullable();
                $table->decimal('nilai', 5, 2)->default(0);
                $table->timestamps();

                $table->unique(['penilaian_supervisor_id', 'kode_mk'], 'uniq_penilaian_spv_mk_kode');
            });
        }

        // 3. Buat tabel penilaian_dpls (1-to-1 per pendaftaran_mbkm)
        if (! Schema::hasTable('penilaian_dpls')) {
            Schema::create('penilaian_dpls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pendaftaran_mbkm_id')->unique()->constrained('pendaftaran_mbkms')->cascadeOnDelete();
                $table->foreignId('periode_akademik_id')->constrained('periode_akademiks')->cascadeOnDelete();
                $table->foreignId('dpl_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedInteger('jumlah_bimbingan_saat_dinilai')->default(0);
                $table->decimal('rata_rata_nilai', 5, 2)->default(0);
                $table->timestamps();
            });
        }

        // 4. Buat tabel penilaian_dpl_mata_kuliahs
        if (! Schema::hasTable('penilaian_dpl_mata_kuliahs')) {
            Schema::create('penilaian_dpl_mata_kuliahs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('penilaian_dpl_id')->constrained('penilaian_dpls')->cascadeOnDelete();
                $table->foreignId('mata_kuliah_id')->nullable()->constrained('mata_kuliahs')->nullOnDelete();
                $table->string('kode_mk', 50);
                $table->string('nama_mk', 255);
                $table->unsignedTinyInteger('sks')->default(0);
                $table->text('job_description')->nullable();
                $table->decimal('nilai', 5, 2)->default(0);
                $table->timestamps();

                $table->unique(['penilaian_dpl_id', 'kode_mk'], 'uniq_penilaian_dpl_mk_kode');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian_dpl_mata_kuliahs');
        Schema::dropIfExists('penilaian_dpls');
        Schema::dropIfExists('penilaian_supervisor_mata_kuliahs');
        Schema::dropIfExists('penilaian_supervisors');
    }
};
