<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait FilterableByPeriode
{
    /**
     * Scope untuk menyaring data berdasarkan parameter request ?tahun_ajaran= atau ?periode_akademik_id= / ?periode_id=
     */
    public function scopeFilterTahunAjaran(Builder $query, Request $request): Builder
    {
        // 1. Jika dikirim via ID langsung (?periode_akademik_id=2 atau ?periode_id=2)
        $id = $request->input('periode_akademik_id') ?? $request->input('periode_id') ?? $request->query('periode_akademik_id') ?? $request->query('periode_id');
        if (! empty($id) && is_numeric($id)) {
            $table = $this->getTable();

            return $query->where("{$table}.periode_akademik_id", (int) $id);
        }

        // 2. Jika dikirim via label string (?tahun_ajaran=2026/2027 Ganjil)
        $rawTahun = $request->input('tahun_ajaran') ?? $request->query('tahun_ajaran');
        if (! $rawTahun || in_array(strtolower(trim((string) $rawTahun)), ['semua', 'semua tahun ajaran', 'all'], true)) {
            return $query;
        }

        $trimmed = trim((string) $rawTahun);

        // Pecah string "2026/2027 Ganjil" -> tahun: "2026/2027", semester: "Ganjil"
        $parts = preg_split('/\s+/', $trimmed);
        $tahun = $parts[0] ?? null;
        $semester = $parts[1] ?? null;

        $table = $this->getTable();

        return $query->where(function (Builder $subQ) use ($trimmed, $tahun, $semester, $table) {
            $subQ->where("{$table}.tahun_ajaran", $trimmed);

            // Relasi langsung periodeAkademik jika ada
            if (method_exists($this, 'periodeAkademik')) {
                $subQ->orWhereHas('periodeAkademik', function ($q) use ($trimmed, $tahun, $semester) {
                    if ($tahun && $semester) {
                        $q->where('tahun_ajaran', $tahun)->where('semester', $semester);
                    } elseif ($tahun) {
                        $q->where('tahun_ajaran', $tahun);
                    }
                });
            }

            // Untuk model yang terhubung ke pendaftaranMbkm (misal Rkm)
            if (method_exists($this, 'pendaftaranMbkm')) {
                $subQ->orWhereHas('pendaftaranMbkm', function ($pq) use ($trimmed, $tahun, $semester) {
                    $pq->where('tahun_ajaran', $trimmed)
                        ->orWhereHas('periodeAkademik', function ($q) use ($trimmed, $tahun, $semester) {
                            if ($tahun && $semester) {
                                $q->where('tahun_ajaran', $tahun)->where('semester', $semester);
                            } elseif ($tahun) {
                                $q->where('tahun_ajaran', $tahun);
                            }
                        });
                });
            }
        });
    }
}
