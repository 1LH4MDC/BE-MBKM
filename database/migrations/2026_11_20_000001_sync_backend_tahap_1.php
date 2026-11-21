<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // 1. Sinkronisasi kolom pendaftaran_mbkms
        if (Schema::hasTable('pendaftaran_mbkms')) {
            Schema::table('pendaftaran_mbkms', function (Blueprint $table) {
                if (! Schema::hasColumn('pendaftaran_mbkms', 'lampiran_revisi_path')) {
                    $table->string('lampiran_revisi_path', 255)->nullable()->after('catatan');
                }
            });

            // Composite unique index ['user_id', 'periode_akademik_id'] jika belum ada
            if ($driver === 'pgsql') {
                $indexExists = DB::selectOne("
                    SELECT 1 FROM pg_indexes 
                    WHERE tablename = 'pendaftaran_mbkms' AND indexname = 'pendaftaran_mbkms_user_periode_unique'
                ");
                if (! $indexExists) {
                    Schema::table('pendaftaran_mbkms', function (Blueprint $table) {
                        $table->unique(['user_id', 'periode_akademik_id'], 'pendaftaran_mbkms_user_periode_unique');
                    });
                }
            } else {
                try {
                    Schema::table('pendaftaran_mbkms', function (Blueprint $table) {
                        $table->unique(['user_id', 'periode_akademik_id'], 'pendaftaran_mbkms_user_periode_unique');
                    });
                } catch (\Throwable $e) {
                    // index already exists
                }
            }
        }

        // 2. Sinkronisasi kolom rkms
        if (Schema::hasTable('rkms')) {
            // Pastikan kolom periode_akademik_id dan tahun_ajaran ada
            Schema::table('rkms', function (Blueprint $table) {
                if (! Schema::hasColumn('rkms', 'periode_akademik_id')) {
                    $table->foreignId('periode_akademik_id')->nullable()->after('pendaftaran_mbkm_id')->constrained('periode_akademiks')->nullOnDelete();
                }
                if (! Schema::hasColumn('rkms', 'tahun_ajaran')) {
                    $table->string('tahun_ajaran', 50)->nullable()->after('periode_akademik_id');
                }
            });

            // Backfill periode_akademik_id dari pendaftaran_mbkms jika masih null
            $nullRkms = DB::table('rkms')->whereNull('periode_akademik_id')->get();
            foreach ($nullRkms as $r) {
                $p = DB::table('pendaftaran_mbkms')->where('id', $r->pendaftaran_mbkm_id)->first();
                if ($p) {
                    $pa = DB::table('periode_akademiks')->where('id', $p->periode_akademik_id)->first();
                    $taLabel = $p->tahun_ajaran ?? ($pa ? trim("{$pa->tahun_ajaran} {$pa->semester}") : null);
                    DB::table('rkms')->where('id', $r->id)->update([
                        'periode_akademik_id' => $p->periode_akademik_id,
                        'tahun_ajaran' => $taLabel,
                    ]);
                }
            }

            // Composite unique index ['user_id', 'periode_akademik_id'] jika belum ada
            if ($driver === 'pgsql') {
                $rkmIndexExists = DB::selectOne("
                    SELECT 1 FROM pg_indexes 
                    WHERE tablename = 'rkms' AND indexname = 'rkms_user_periode_unique'
                ");
                if (! $rkmIndexExists) {
                    Schema::table('rkms', function (Blueprint $table) {
                        $table->unique(['user_id', 'periode_akademik_id'], 'rkms_user_periode_unique');
                    });
                }
            } else {
                try {
                    Schema::table('rkms', function (Blueprint $table) {
                        $table->unique(['user_id', 'periode_akademik_id'], 'rkms_user_periode_unique');
                    });
                } catch (\Throwable $e) {
                    // index already exists
                }
            }
        }

        // 3. Sinkronisasi tabel surat_pengantars
        if (Schema::hasTable('surat_pengantars')) {
            Schema::table('surat_pengantars', function (Blueprint $table) {
                if (! Schema::hasColumn('surat_pengantars', 'periode_akademik_id')) {
                    $table->foreignId('periode_akademik_id')->nullable()->after('user_id')->constrained('periode_akademiks')->nullOnDelete();
                }
            });

            // Backfill periode_akademik_id pada surat_pengantars
            $periodeAktif = DB::table('periode_akademiks')->where('is_aktif', true)->first();
            $surats = DB::table('surat_pengantars')->whereNull('periode_akademik_id')->get();
            foreach ($surats as $s) {
                $matchedPeriode = null;
                if (! empty($s->tahun_ajaran)) {
                    $parts = explode(' ', trim($s->tahun_ajaran));
                    $matchedPeriode = DB::table('periode_akademiks')
                        ->where('tahun_ajaran', $parts[0] ?? '')
                        ->when(isset($parts[1]), fn ($q) => $q->where('semester', $parts[1]))
                        ->first();
                }

                $targetId = $matchedPeriode?->id ?? $periodeAktif?->id;
                if ($targetId) {
                    $label = $s->tahun_ajaran;
                    if (empty($label) && $periodeAktif) {
                        $label = trim("{$periodeAktif->tahun_ajaran} {$periodeAktif->semester}");
                    }
                    DB::table('surat_pengantars')->where('id', $s->id)->update([
                        'periode_akademik_id' => $targetId,
                        'tahun_ajaran' => $label,
                    ]);
                }
            }
        }

        // 4. Sinkronisasi tabel mitras (pastikan kolom file_pks dkk ada jika migrasi sebelumnya belum run)
        if (Schema::hasTable('mitras')) {
            Schema::table('mitras', function (Blueprint $table) {
                if (! Schema::hasColumn('mitras', 'file_pks')) {
                    $table->text('file_pks')->nullable()->after('nomor_pks');
                }
                if (! Schema::hasColumn('mitras', 'file_pks_name')) {
                    $table->string('file_pks_name', 255)->nullable()->after('file_pks');
                }
                if (! Schema::hasColumn('mitras', 'file_pks_size')) {
                    $table->unsignedBigInteger('file_pks_size')->nullable()->after('file_pks_name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pendaftaran_mbkms')) {
            Schema::table('pendaftaran_mbkms', function (Blueprint $table) {
                $table->dropUnique('pendaftaran_mbkms_user_periode_unique');
                if (Schema::hasColumn('pendaftaran_mbkms', 'lampiran_revisi_path')) {
                    $table->dropColumn('lampiran_revisi_path');
                }
            });
        }

        if (Schema::hasTable('rkms')) {
            Schema::table('rkms', function (Blueprint $table) {
                $table->dropUnique('rkms_user_periode_unique');
            });
        }

        if (Schema::hasTable('surat_pengantars')) {
            Schema::table('surat_pengantars', function (Blueprint $table) {
                if (Schema::hasColumn('surat_pengantars', 'periode_akademik_id')) {
                    $table->dropForeign(['periode_akademik_id']);
                    $table->dropColumn('periode_akademik_id');
                }
            });
        }
    }
};
