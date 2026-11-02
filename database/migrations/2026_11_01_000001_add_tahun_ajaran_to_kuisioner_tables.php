<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        if (Schema::hasTable('kuisioner_kategoris')) {
            Schema::table('kuisioner_kategoris', function (Blueprint $table) {
                if (! Schema::hasColumn('kuisioner_kategoris', 'tahun_ajaran_id')) {
                    $table->foreignId('tahun_ajaran_id')->nullable()->after('id')->constrained('periode_akademiks')->nullOnDelete();
                }
                if (! Schema::hasColumn('kuisioner_kategoris', 'tahun_ajaran')) {
                    $table->string('tahun_ajaran', 50)->nullable()->after('tahun_ajaran_id');
                }
            });

            try {
                Schema::table('kuisioner_kategoris', function (Blueprint $table) {
                    $table->dropUnique('kuisioner_kategoris_slug_unique');
                });
            } catch (Throwable $e) {

            }
        }

        if (Schema::hasTable('kuisioner_pertanyaans')) {
            Schema::table('kuisioner_pertanyaans', function (Blueprint $table) {
                if (! Schema::hasColumn('kuisioner_pertanyaans', 'tahun_ajaran_id')) {
                    $table->foreignId('tahun_ajaran_id')->nullable()->after('id')->constrained('periode_akademiks')->nullOnDelete();
                }
                if (! Schema::hasColumn('kuisioner_pertanyaans', 'tahun_ajaran')) {
                    $table->string('tahun_ajaran', 50)->nullable()->after('tahun_ajaran_id');
                }
            });
        }

        if (Schema::hasTable('kuisioner_jawabans')) {
            Schema::table('kuisioner_jawabans', function (Blueprint $table) {
                if (! Schema::hasColumn('kuisioner_jawabans', 'tahun_ajaran_id')) {
                    $table->foreignId('tahun_ajaran_id')->nullable()->after('id')->constrained('periode_akademiks')->nullOnDelete();
                }
                if (! Schema::hasColumn('kuisioner_jawabans', 'tahun_ajaran')) {
                    $table->string('tahun_ajaran', 50)->nullable()->after('tahun_ajaran_id');
                }
            });
        }

        $aktif = DB::table('periode_akademiks')->where('is_aktif', true)->first();
        if (! $aktif) {
            $aktif = DB::table('periode_akademiks')->orderBy('id', 'desc')->first();
        }

        if ($aktif) {
            $label = trim("{$aktif->tahun_ajaran} {$aktif->semester}");

            DB::table('kuisioner_kategoris')
                ->whereNull('tahun_ajaran_id')
                ->update([
                    'tahun_ajaran_id' => $aktif->id,
                    'tahun_ajaran' => $label,
                ]);

            DB::table('kuisioner_pertanyaans')
                ->whereNull('tahun_ajaran_id')
                ->update([
                    'tahun_ajaran_id' => $aktif->id,
                    'tahun_ajaran' => $label,
                ]);

            $jawabans = DB::table('kuisioner_jawabans')->whereNull('tahun_ajaran_id')->get();
            foreach ($jawabans as $j) {
                $pendaftar = $j->pendaftaran_mbkm_id
                    ? DB::table('pendaftaran_mbkms')->where('id', $j->pendaftaran_mbkm_id)->first()
                    : null;
                $tId = $pendaftar?->periode_akademik_id ?? $aktif->id;
                $pObj = DB::table('periode_akademiks')->where('id', $tId)->first() ?? $aktif;
                $tLabel = trim("{$pObj->tahun_ajaran} {$pObj->semester}");

                DB::table('kuisioner_jawabans')->where('id', $j->id)->update([
                    'tahun_ajaran_id' => $tId,
                    'tahun_ajaran' => $tLabel,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kuisioner_jawabans', 'tahun_ajaran_id')) {
            Schema::table('kuisioner_jawabans', function (Blueprint $table) {
                $table->dropForeign(['tahun_ajaran_id']);
                $table->dropColumn(['tahun_ajaran_id', 'tahun_ajaran']);
            });
        }

        if (Schema::hasColumn('kuisioner_pertanyaans', 'tahun_ajaran_id')) {
            Schema::table('kuisioner_pertanyaans', function (Blueprint $table) {
                $table->dropForeign(['tahun_ajaran_id']);
                $table->dropColumn(['tahun_ajaran_id', 'tahun_ajaran']);
            });
        }

        if (Schema::hasColumn('kuisioner_kategoris', 'tahun_ajaran_id')) {
            Schema::table('kuisioner_kategoris', function (Blueprint $table) {
                $table->dropForeign(['tahun_ajaran_id']);
                $table->dropColumn(['tahun_ajaran_id', 'tahun_ajaran']);
                $table->unique('slug');
            });
        }
    }
};
