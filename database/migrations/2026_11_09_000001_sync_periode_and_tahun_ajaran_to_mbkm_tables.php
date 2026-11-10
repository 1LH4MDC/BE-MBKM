<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pendaftaran_mbkms')) {
            Schema::table('pendaftaran_mbkms', function (Blueprint $table) {
                if (! Schema::hasColumn('pendaftaran_mbkms', 'tahun_ajaran')) {
                    $table->string('tahun_ajaran', 50)->nullable()->after('periode_akademik_id');
                }
            });

            $pendaftarans = DB::table('pendaftaran_mbkms')->whereNull('tahun_ajaran')->get();
            foreach ($pendaftarans as $p) {
                $periode = DB::table('periode_akademiks')->where('id', $p->periode_akademik_id)->first();
                if ($periode) {
                    $label = trim("{$periode->tahun_ajaran} {$periode->semester}");
                    DB::table('pendaftaran_mbkms')->where('id', $p->id)->update([
                        'tahun_ajaran' => $label,
                    ]);
                }
            }
        }

        if (Schema::hasTable('rkms')) {
            Schema::table('rkms', function (Blueprint $table) {
                if (! Schema::hasColumn('rkms', 'periode_akademik_id')) {
                    $table->foreignId('periode_akademik_id')->nullable()->after('pendaftaran_mbkm_id')->constrained('periode_akademiks')->nullOnDelete();
                }
                if (! Schema::hasColumn('rkms', 'tahun_ajaran')) {
                    $table->string('tahun_ajaran', 50)->nullable()->after('periode_akademik_id');
                }
            });

            $rkms = DB::table('rkms')->whereNull('periode_akademik_id')->get();
            foreach ($rkms as $r) {
                $pendaftaran = DB::table('pendaftaran_mbkms')->where('id', $r->pendaftaran_mbkm_id)->first();
                if ($pendaftaran) {
                    $periode = DB::table('periode_akademiks')->where('id', $pendaftaran->periode_akademik_id)->first();
                    $label = $pendaftaran->tahun_ajaran ?? ($periode ? trim("{$periode->tahun_ajaran} {$periode->semester}") : null);
                    DB::table('rkms')->where('id', $r->id)->update([
                        'periode_akademik_id' => $pendaftaran->periode_akademik_id,
                        'tahun_ajaran' => $label,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rkms')) {
            Schema::table('rkms', function (Blueprint $table) {
                if (Schema::hasColumn('rkms', 'periode_akademik_id')) {
                    $table->dropForeign(['periode_akademik_id']);
                    $table->dropColumn('periode_akademik_id');
                }
                if (Schema::hasColumn('rkms', 'tahun_ajaran')) {
                    $table->dropColumn('tahun_ajaran');
                }
            });
        }
    }
};

