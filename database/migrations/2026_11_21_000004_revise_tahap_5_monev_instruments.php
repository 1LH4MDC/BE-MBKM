<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Perbarui monev_mitras: nullable kolom lama & tambah proyeksi_magang_selanjutnya
        if (Schema::hasTable('monev_mitras')) {
            Schema::table('monev_mitras', function (Blueprint $table) {
                $table->string('metode_pelaksanaan', 50)->nullable()->change();
                $table->text('catatan_evaluasi')->nullable()->change();

                if (! Schema::hasColumn('monev_mitras', 'proyeksi_magang_selanjutnya')) {
                    $table->json('proyeksi_magang_selanjutnya')->nullable()->after('kendala_tindak_lanjut');
                }
            });
        }

        // 2. Perbarui periode_akademiks: tambah target_nilai_monev
        if (Schema::hasTable('periode_akademiks')) {
            Schema::table('periode_akademiks', function (Blueprint $table) {
                if (! Schema::hasColumn('periode_akademiks', 'target_nilai_monev')) {
                    $table->decimal('target_nilai_monev', 3, 2)->default(4.00)->after('is_aktif');
                }
            });
        }

        // 3. Tabel monev_proses_mahasiswas (Minggu ke-8: 12 Indikator)
        if (! Schema::hasTable('monev_proses_mahasiswas')) {
            Schema::create('monev_proses_mahasiswas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('monev_mitra_id')->constrained('monev_mitras')->cascadeOnDelete();
                $table->foreignId('pendaftaran_mbkm_id')->constrained('pendaftaran_mbkms')->cascadeOnDelete();

                // 12 Indikator (Nilai 1 - 5)
                $table->unsignedTinyInteger('kehadiran_ketepatan_waktu')->default(5);
                $table->unsignedTinyInteger('ketaatan_aturan')->default(5);
                $table->unsignedTinyInteger('pencapaian_tugas')->default(5);
                $table->unsignedTinyInteger('kualitas_hasil_kerja')->default(5);
                $table->unsignedTinyInteger('kemampuan_menyelesaikan_masalah')->default(5);
                $table->unsignedTinyInteger('penguasaan_tools')->default(5);
                $table->unsignedTinyInteger('kemampuan_analisis')->default(5);
                $table->unsignedTinyInteger('komunikasi_kolaborasi')->default(5);
                $table->unsignedTinyInteger('tanggung_jawab_inisiatif')->default(5);
                $table->unsignedTinyInteger('etika_profesional')->default(5);
                $table->unsignedTinyInteger('relevansi_ilmu_komputer')->default(5);
                $table->unsignedTinyInteger('kontribusi_kompetensi')->default(5);

                $table->text('catatan_pemonev')->nullable();
                $table->text('temuan_monev')->nullable();
                $table->string('status_monev', 50)->default('Tidak Ada Temuan');
                $table->timestamps();

                $table->unique(['monev_mitra_id', 'pendaftaran_mbkm_id'], 'uniq_monev_proses_mitra_mhs');
            });
        }

        // 4. Tabel monev_hasil_mahasiswas (Minggu ke-18: 7 Indikator)
        if (! Schema::hasTable('monev_hasil_mahasiswas')) {
            Schema::create('monev_hasil_mahasiswas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('monev_mitra_id')->constrained('monev_mitras')->cascadeOnDelete();
                $table->foreignId('pendaftaran_mbkm_id')->constrained('pendaftaran_mbkms')->cascadeOnDelete();

                // 7 Indikator (Nilai 1 - 5)
                $table->unsignedTinyInteger('kompleksitas_jobdesk')->default(3);
                $table->unsignedTinyInteger('kesesuaian_rkm')->default(4);
                $table->unsignedTinyInteger('relevansi_pl')->default(4);
                $table->unsignedTinyInteger('kualitas_supervisi')->default(4);
                $table->unsignedTinyInteger('ketercapaian_cpmk')->default(4);
                $table->unsignedTinyInteger('outcome_pekerjaan')->default(4);
                $table->unsignedTinyInteger('pengembangan_skill')->default(4);

                $table->timestamps();

                $table->unique(['monev_mitra_id', 'pendaftaran_mbkm_id'], 'uniq_monev_hasil_mitra_mhs');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monev_hasil_mahasiswas');
        Schema::dropIfExists('monev_proses_mahasiswas');

        if (Schema::hasTable('periode_akademiks') && Schema::hasColumn('periode_akademiks', 'target_nilai_monev')) {
            Schema::table('periode_akademiks', function (Blueprint $table) {
                $table->dropColumn('target_nilai_monev');
            });
        }

        if (Schema::hasTable('monev_mitras') && Schema::hasColumn('monev_mitras', 'proyeksi_magang_selanjutnya')) {
            Schema::table('monev_mitras', function (Blueprint $table) {
                $table->dropColumn('proyeksi_magang_selanjutnya');
            });
        }
    }
};
