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
        if ($request->filled('periode_akademik_id') || $request->filled('periode_id')) {
            $id = $request->query('periode_akademik_id') ?? $request->query('periode_id');
            $table = $this->getTable();

            return $query->where("{$table}.periode_akademik_id", $id);
        }

        // 2. Jika dikirim via label string (?tahun_ajaran=2026/2027 Ganjil)
        $rawTahun = $request->query('tahun_ajaran');
        if (! $rawTahun || $rawTahun === 'Semua' || $rawTahun === 'Semua Tahun Ajaran' || $rawTahun === 'all') {
            return $query;
        }

        $trimmed = trim((string) $rawTahun);

        // Pecah string "2026/2027 Ganjil" -> tahun: "2026/2027", semester: "Ganjil"
        $parts = explode(' ', $trimmed);
        $tahun = $parts[0] ?? null;
        $semester = $parts[1] ?? null;

        $table = $this->getTable();

        return $query->where(function (Builder $subQ) use ($trimmed, $tahun, $semester, $table) {
            $subQ->where("{$table}.tahun_ajaran", $trimmed);

            // Relasi langsung periodeAkademik jika ada
            if (method_exists($this, 'periodeAkademik')) {
                $subQ->orWhereHas('periodeAkademik', function ($q) use ($trimmed, $tahun, $semester) {
                    $q->whereRaw("CONCAT(tahun_ajaran, ' ', semester) = ?", [$trimmed]);
                    if ($tahun && $semester) {
                        $q->orWhere(function ($pair) use ($tahun, $semester) {
                            $pair->where('tahun_ajaran', $tahun)->where('semester', $semester);
                        });
                    } elseif ($tahun) {
                        $q->orWhere('tahun_ajaran', $tahun);
                    }
                });
            }

            // Untuk model yang terhubung ke pendaftaranMbkm (misal Rkm)
            if (method_exists($this, 'pendaftaranMbkm')) {
                $subQ->orWhereHas('pendaftaranMbkm', function ($pq) use ($trimmed, $tahun, $semester) {
                    $pq->where('tahun_ajaran', $trimmed)
                        ->orWhereHas('periodeAkademik', function ($q) use ($trimmed, $tahun, $semester) {
                            $q->whereRaw("CONCAT(tahun_ajaran, ' ', semester) = ?", [$trimmed]);
                            if ($tahun && $semester) {
                                $q->orWhere(function ($pair) use ($tahun, $semester) {
                                    $pair->where('tahun_ajaran', $tahun)->where('semester', $semester);
                                });
                            } elseif ($tahun) {
                                $q->orWhere('tahun_ajaran', $tahun);
                            }
                        });
                });
            }
        });
    }
}
