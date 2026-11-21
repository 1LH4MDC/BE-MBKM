<?php

namespace App\Http\Middleware;

use App\Models\BimbinganMagang;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\Rkm;
use App\Models\RkmJobdesk;
use App\Models\RkmMataKuliah;
use App\Models\SuratPengantar;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActivePeriode
{
    /**
     * Memblokir aksi mutasi (POST, PUT, PATCH, DELETE) jika entitas berada pada Periode Akademik Arsip.
     */
    public function handle(Request $request, Closure $next, ?string $modelClass = null, ?string $routeParam = null): Response
    {
        // Hanya cegah pada metode mutasi
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->first();
        if (! $periodeAktif) {
            return $this->forbidden('Tidak ada Tahun Ajaran aktif saat ini. Seluruh data terkunci.');
        }

        // 1. Cek parameter periode_akademik_id / periode_id di payload atau query string
        $targetPeriodeId = $request->input('periode_akademik_id') ?? $request->input('periode_id') ?? $request->query('periode_akademik_id') ?? $request->query('periode_id');
        if (! empty($targetPeriodeId) && is_numeric($targetPeriodeId)) {
            if ((int) $targetPeriodeId !== (int) $periodeAktif->id) {
                return $this->forbidden();
            }
        }

        // 2. Cek parameter tahun_ajaran di payload atau query string jika dikirim sebagai string
        $rawTahun = $request->input('tahun_ajaran') ?? $request->query('tahun_ajaran');
        if (! empty($rawTahun) && is_string($rawTahun) && ! in_array(strtolower(trim($rawTahun)), ['semua', 'semua tahun ajaran', 'all'], true)) {
            $parts = preg_split('/\s+/', trim($rawTahun));
            $th = $parts[0] ?? null;
            $sem = $parts[1] ?? null;
            $foundPeriode = PeriodeAkademik::where(function ($q) use ($th, $sem, $rawTahun) {
                if ($th && $sem) {
                    $q->where('tahun_ajaran', $th)->where('semester', $sem);
                } else {
                    $q->where('tahun_ajaran', $rawTahun);
                }
            })->first();

            if ($foundPeriode && (int) $foundPeriode->id !== (int) $periodeAktif->id) {
                return $this->forbidden();
            }
        }

        // 3. Cek jika dikirim via pendaftaran_mbkm_id / pendaftaran_id di request payload/query
        $pId = $request->input('pendaftaran_mbkm_id') ?? $request->input('pendaftaran_id') ?? $request->query('pendaftaran_mbkm_id') ?? $request->query('pendaftaran_id');
        if (! empty($pId) && is_numeric($pId)) {
            $pendaftaran = PendaftaranMbkm::find((int) $pId);
            $pPeriodeId = $pendaftaran?->periode_akademik_id;
            if ($pPeriodeId && (int) $pPeriodeId !== (int) $periodeAktif->id) {
                return $this->forbidden();
            }
        }

        // 4. Cek jika dikirim via rkm_id di request payload/query
        $rId = $request->input('rkm_id') ?? $request->query('rkm_id');
        if (! empty($rId) && is_numeric($rId)) {
            $rkmId = (int) $rId;
            $rkm = Rkm::where('id', $rkmId)->orWhere('pendaftaran_mbkm_id', $rkmId)->first();
            $rkmPeriodeId = $rkm?->periode_akademik_id ?? $rkm?->pendaftaranMbkm?->periode_akademik_id;
            if ($rkmPeriodeId && (int) $rkmPeriodeId !== (int) $periodeAktif->id) {
                return $this->forbidden();
            }
        }

        // 5. Cek jika rute mengarah ke resource spesifik via modelClass & routeParam
        if ($modelClass && $routeParam && $request->route($routeParam)) {
            $fqcn = str_contains($modelClass, '\\') ? $modelClass : 'App\\Models\\'.$modelClass;
            if (class_exists($fqcn)) {
                $id = $request->route($routeParam);
                $instance = $fqcn::find($id);

                if (! $instance && $fqcn === Rkm::class) {
                    $instance = Rkm::where('pendaftaran_mbkm_id', $id)->first();
                }

                if ($instance) {
                    $itemPeriodeId = null;

                    if (isset($instance->periode_akademik_id) && ! empty($instance->periode_akademik_id)) {
                        $itemPeriodeId = $instance->periode_akademik_id;
                    } elseif ($instance instanceof Rkm) {
                        $itemPeriodeId = $instance->periode_akademik_id ?? $instance->pendaftaranMbkm?->periode_akademik_id;
                    } elseif ($instance instanceof RkmMataKuliah) {
                        $itemPeriodeId = $instance->rkm?->periode_akademik_id ?? $instance->rkm?->pendaftaranMbkm?->periode_akademik_id;
                    } elseif ($instance instanceof RkmJobdesk) {
                        $itemPeriodeId = $instance->rkmMataKuliah?->rkm?->periode_akademik_id ?? $instance->rkmMataKuliah?->rkm?->pendaftaranMbkm?->periode_akademik_id;
                    } elseif ($instance instanceof \App\Models\RkpDokumen) {
                        $itemPeriodeId = $instance->rkm?->periode_akademik_id ?? $instance->rkm?->pendaftaranMbkm?->periode_akademik_id;
                    } elseif ($instance instanceof BimbinganMagang) {
                        $itemPeriodeId = $instance->periode_akademik_id ?? $instance->pendaftaranMbkm?->periode_akademik_id;
                    } elseif ($instance instanceof SuratPengantar) {
                        $itemPeriodeId = $instance->periode_akademik_id;
                    } elseif ($instance instanceof \App\Models\PengajuanSemhas) {
                        $itemPeriodeId = $instance->periode_akademik_id ?? $instance->pendaftaranMbkm?->periode_akademik_id;
                    } elseif ($instance instanceof \App\Models\MonevMitra || $instance instanceof \App\Models\PlottingPemonevMitra) {
                        $itemPeriodeId = $instance->periode_akademik_id;
                    }

                    if ($itemPeriodeId && (int) $itemPeriodeId !== (int) $periodeAktif->id) {
                        return $this->forbidden();
                    }
                }
            }
        }

        return $next($request);
    }

    private function forbidden(string $message = 'Data berasal dari Tahun Ajaran Arsip dan hanya dapat dilihat (Read-Only).'): Response
    {
        return response()->json([
            'success' => false,
            'error_code' => 'ERR_ARCHIVE_READ_ONLY',
            'message' => $message,
        ], Response::HTTP_FORBIDDEN);
    }
}
