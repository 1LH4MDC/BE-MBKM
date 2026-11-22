<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom jadwal ke pengajuan_semhas jika belum ada
        if (Schema::hasTable('pengajuan_semhas')) {
            Schema::table('pengajuan_semhas', function (Blueprint $table) {
                if (! Schema::hasColumn('pengajuan_semhas', 'jadwal_tanggal')) {
                    $table->date('jadwal_tanggal')->nullable()->after('override_at');
                }
                if (! Schema::hasColumn('pengajuan_semhas', 'jadwal_waktu_mulai')) {
                    $table->string('jadwal_waktu_mulai', 20)->nullable()->after('jadwal_tanggal');
                }
                if (! Schema::hasColumn('pengajuan_semhas', 'jadwal_waktu_selesai')) {
                    $table->string('jadwal_waktu_selesai', 20)->nullable()->after('jadwal_waktu_mulai');
                }
                if (! Schema::hasColumn('pengajuan_semhas', 'jadwal_lokasi')) {
                    $table->string('jadwal_lokasi', 255)->nullable()->after('jadwal_waktu_selesai');
                }
            });
        }

        // 2. Buat tabel penilaian_semhas (1-to-1 per pendaftaran_mbkm)
        if (! Schema::hasTable('penilaian_semhas')) {
            Schema::create('penilaian_semhas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pendaftaran_mbkm_id')->unique()->constrained('pendaftaran_mbkms')->cascadeOnDelete();
                $table->foreignId('pengajuan_semhas_id')->nullable()->constrained('pengajuan_semhas')->nullOnDelete();
                $table->foreignId('periode_akademik_id')->constrained('periode_akademiks')->cascadeOnDelete();
                $table->foreignId('penguji_id')->constrained('users')->cascadeOnDelete();

                // 4 Komponen Nilai Presentasi (0..100)
                $table->decimal('nilai_kelengkapan_laporan', 5, 2)->default(0);
                $table->text('deskripsi_kelengkapan_laporan')->nullable();

                $table->decimal('nilai_slide', 5, 2)->default(0);
                $table->text('deskripsi_slide')->nullable();

                $table->decimal('nilai_cara_presentasi', 5, 2)->default(0);
                $table->text('deskripsi_cara_presentasi')->nullable();

                $table->decimal('nilai_tanya_jawab', 5, 2)->default(0);
                $table->text('deskripsi_tanya_jawab')->nullable();

                // Hasil hitung resmi di server
                $table->decimal('nilai_presentasi', 5, 2)->default(0);
                $table->decimal('rata_rata_nilai_total', 5, 2)->default(0);

                $table->timestamps();
            });
        }

        // 3. Buat tabel penilaian_semhas_mata_kuliahs
        if (! Schema::hasTable('penilaian_semhas_mata_kuliahs')) {
            Schema::create('penilaian_semhas_mata_kuliahs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('penilaian_semhas_id')->constrained('penilaian_semhas')->cascadeOnDelete();
                $table->foreignId('mata_kuliah_id')->nullable()->constrained('mata_kuliahs')->nullOnDelete();

                $table->string('kode_mk', 50);
                $table->string('nama_mk', 255);
                $table->unsignedTinyInteger('sks')->default(0);
                $table->text('kompetensi')->nullable();
                $table->text('aktivitas_pembelajaran')->nullable();

                $table->decimal('nilai_mata_kuliah', 5, 2)->default(0);
                $table->decimal('nilai_presentasi', 5, 2)->default(0);
                $table->decimal('nilai_total', 5, 2)->default(0);

                $table->timestamps();

                $table->unique(['penilaian_semhas_id', 'kode_mk'], 'uniq_penilaian_mk_kode');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian_semhas_mata_kuliahs');
        Schema::dropIfExists('penilaian_semhas');

        if (Schema::hasTable('pengajuan_semhas')) {
            Schema::table('pengajuan_semhas', function (Blueprint $table) {
                $cols = ['jadwal_tanggal', 'jadwal_waktu_mulai', 'jadwal_waktu_selesai', 'jadwal_lokasi'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('pengajuan_semhas', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
