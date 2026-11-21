<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel dpl_periode_links
        if (! Schema::hasTable('dpl_periode_links')) {
            Schema::create('dpl_periode_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dpl_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('periode_akademik_id')->constrained('periode_akademiks')->cascadeOnDelete();
                $table->string('link_wa', 500)->nullable();
                $table->timestamps();

                $table->unique(['dpl_id', 'periode_akademik_id'], 'unique_dpl_periode');
            });
        }

        // 2. Tambah kolom foto_uploaded_by_role pada bimbingan_magangs
        if (Schema::hasTable('bimbingan_magangs')) {
            Schema::table('bimbingan_magangs', function (Blueprint $table) {
                if (! Schema::hasColumn('bimbingan_magangs', 'foto_uploaded_by_role')) {
                    $table->string('foto_uploaded_by_role', 50)->nullable()->after('foto_bukti_path');
                }
            });
        }

        // 3. Tabel kuisioner_sesis
        if (! Schema::hasTable('kuisioner_sesis')) {
            Schema::create('kuisioner_sesis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('periode_akademik_id')->unique()->constrained('periode_akademiks')->cascadeOnDelete();
                $table->string('status', 50)->default('draft');
                $table->dateTime('published_at')->nullable();
                $table->dateTime('closed_at')->nullable();
                $table->timestamps();
            });
        }

        // 4. Tambah kolom sudah_isi_kuisioner dan kuisioner_submitted_at pada pendaftaran_mbkms
        if (Schema::hasTable('pendaftaran_mbkms')) {
            Schema::table('pendaftaran_mbkms', function (Blueprint $table) {
                if (! Schema::hasColumn('pendaftaran_mbkms', 'link_logbook')) {
                    $table->string('link_logbook', 500)->nullable()->after('status');
                }
                if (! Schema::hasColumn('pendaftaran_mbkms', 'link_wa_dpl')) {
                    $table->string('link_wa_dpl', 500)->nullable()->after('link_logbook');
                }
                if (! Schema::hasColumn('pendaftaran_mbkms', 'sudah_isi_kuisioner')) {
                    $table->boolean('sudah_isi_kuisioner')->default(false)->after('link_wa_dpl');
                }
                if (! Schema::hasColumn('pendaftaran_mbkms', 'kuisioner_submitted_at')) {
                    $table->dateTime('kuisioner_submitted_at')->nullable()->after('sudah_isi_kuisioner');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pendaftaran_mbkms')) {
            Schema::table('pendaftaran_mbkms', function (Blueprint $table) {
                $cols = array_filter(
                    ['sudah_isi_kuisioner', 'kuisioner_submitted_at'],
                    fn ($c) => Schema::hasColumn('pendaftaran_mbkms', $c)
                );
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        Schema::dropIfExists('kuisioner_sesis');

        if (Schema::hasTable('bimbingan_magangs')) {
            Schema::table('bimbingan_magangs', function (Blueprint $table) {
                if (Schema::hasColumn('bimbingan_magangs', 'foto_uploaded_by_role')) {
                    $table->dropColumn('foto_uploaded_by_role');
                }
            });
        }

        Schema::dropIfExists('dpl_periode_links');
    }
};
