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

        // 2. Cek jika dikirim via rkm_id di request payload
        if ($request->filled('rkm_id') && is_numeric($request->input('rkm_id'))) {
            $rkmId = (int) $request->input('rkm_id');
            $rkm = Rkm::where('id', $rkmId)->orWhere('pendaftaran_mbkm_id', $rkmId)->first();
            $rkmPeriodeId = $rkm?->periode_akademik_id ?? $rkm?->pendaftaranMbkm?->periode_akademik_id;
            if ($rkmPeriodeId && (int) $rkmPeriodeId !== (int) $periodeAktif->id) {
                return $this->forbidden();
            }
        }

        // 3. Cek jika rute mengarah ke resource spesifik via modelClass & routeParam
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
                    } elseif ($instance instanceof BimbinganMagang) {
                        $itemPeriodeId = $instance->pendaftaranMbkm?->periode_akademik_id;
                    } elseif ($instance instanceof SuratPengantar) {
                        $itemPeriodeId = $instance->periode_akademik_id;
                    } elseif ($instance instanceof \App\Models\PengajuanSemhas) {
                        $itemPeriodeId = $instance->periode_akademik_id ?? $instance->pendaftaranMbkm?->periode_akademik_id;
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
