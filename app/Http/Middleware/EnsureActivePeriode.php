<?php

namespace App\Http\Middleware;

use App\Models\PeriodeAkademik;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActivePeriode
{
    public function handle(Request $request, Closure $next, ?string $modelClass = null, ?string $routeParam = null): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->first();
        if (! $periodeAktif) {
            return $next($request);
        }

        if ($request->filled('periode_akademik_id') && (int) $request->input('periode_akademik_id') !== (int) $periodeAktif->id) {
            return $this->forbidden();
        }

        if ($modelClass && $routeParam && $request->route($routeParam)) {
            $fqcn = str_contains($modelClass, '\\') ? $modelClass : 'App\\Models\\'.$modelClass;
            if (class_exists($fqcn)) {
                $instance = $fqcn::find($request->route($routeParam));
                if ($instance && ! empty($instance->periode_akademik_id) && (int) $instance->periode_akademik_id !== (int) $periodeAktif->id) {
                    return $this->forbidden();
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
