<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KuisionerJawaban;
use App\Models\KuisionerSesi;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Services\KuisionerPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KuisionerSessionController extends Controller
{
    public function __construct(private KuisionerPeriodService $kuisionerPeriodService) {}

    private function resolvePeriode(Request $request): ?PeriodeAkademik
    {
        if ($request->filled('periode_akademik_id')) {
            return PeriodeAkademik::find($request->input('periode_akademik_id'));
        }
        if ($request->filled('periode_id')) {
            return PeriodeAkademik::find($request->input('periode_id'));
        }

        return PeriodeAkademik::where('is_aktif', true)->first()
            ?? PeriodeAkademik::latest('id')->first();
    }

    public function status(Request $request): JsonResponse
    {
        $periode = $this->resolvePeriode($request);
        if (! $periode) {
            return response()->json([
                'success' => false,
                'message' => 'Periode akademik tidak ditemukan.',
            ], 404);
        }

        $sesi = $this->kuisionerPeriodService->ensurePeriodeInitialized($periode);

        $respondenPendaftaran = PendaftaranMbkm::where('periode_akademik_id', $periode->id)
            ->where('sudah_isi_kuisioner', true)
            ->count();

        $respondenJawaban = KuisionerJawaban::where(function ($q) use ($periode) {
            $q->where('tahun_ajaran_id', $periode->id)
                ->orWhere('tahun_ajaran', $periode->nama_lengkap);
        })
            ->distinct('mahasiswa_user_id')
            ->count('mahasiswa_user_id');

        $totalResponden = max($respondenPendaftaran, $respondenJawaban);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $sesi->status,
                'status_sesi' => $sesi->status,
                'published_at' => $sesi->published_at?->toISOString(),
                'closed_at' => $sesi->closed_at?->toISOString(),
                'total_responden' => $totalResponden,
                'periode_akademik_id' => $periode->id,
                'tahun_ajaran' => $periode->nama_lengkap,
            ],
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $periode = $this->resolvePeriode($request);
        if (! $periode) {
            return response()->json(['success' => false, 'message' => 'Tahun ajaran aktif tidak ditemukan.'], 422);
        }

        $sesi = $this->kuisionerPeriodService->ensurePeriodeInitialized($periode);

        $sesi->status = 'published';
        $sesi->published_at = now();
        $sesi->save();

        return response()->json([
            'success' => true,
            'message' => 'Sesi kuesioner berhasil dibuka (dipublikasikan) untuk mahasiswa.',
            'data' => $sesi,
        ]);
    }

    public function unpublish(Request $request): JsonResponse
    {
        $periode = $this->resolvePeriode($request);
        if (! $periode) {
            return response()->json(['success' => false, 'message' => 'Tahun ajaran aktif tidak ditemukan.'], 422);
        }

        $sesi = $this->kuisionerPeriodService->ensurePeriodeInitialized($periode);

        $respondenPendaftaran = PendaftaranMbkm::where('periode_akademik_id', $periode->id)
            ->where('sudah_isi_kuisioner', true)
            ->count();

        $respondenJawaban = KuisionerJawaban::where(function ($q) use ($periode) {
            $q->where('tahun_ajaran_id', $periode->id)
                ->orWhere('tahun_ajaran', $periode->nama_lengkap);
        })
            ->distinct('mahasiswa_user_id')
            ->count('mahasiswa_user_id');

        $totalResponden = max($respondenPendaftaran, $respondenJawaban);

        if ($totalResponden > 0) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_KUISIONER_ALREADY_HAS_RESPONSES',
                'message' => "Kuesioner tidak dapat dikembalikan ke draf karena sudah ada {$totalResponden} responden yang masuk. Silakan gunakan tombol Tutup Sesi untuk menutup periode pengisian.",
            ], 422);
        }

        $sesi->status = 'draft';
        $sesi->save();

        return response()->json([
            'success' => true,
            'message' => 'Status kuesioner berhasil dikembalikan ke Draf.',
            'data' => $sesi,
        ]);
    }

    public function close(Request $request): JsonResponse
    {
        $periode = $this->resolvePeriode($request);
        if (! $periode) {
            return response()->json(['success' => false, 'message' => 'Tahun ajaran aktif tidak ditemukan.'], 422);
        }

        $sesi = $this->kuisionerPeriodService->ensurePeriodeInitialized($periode);

        $sesi->status = 'closed';
        $sesi->closed_at = now();
        $sesi->save();

        return response()->json([
            'success' => true,
            'message' => 'Sesi pengisian kuesioner resmi ditutup.',
            'data' => $sesi,
        ]);
    }

    public function draft(Request $request): JsonResponse
    {
        $periode = $this->resolvePeriode($request);
        if (! $periode) {
            return response()->json(['success' => false, 'message' => 'Tahun ajaran aktif tidak ditemukan.'], 422);
        }

        $sesi = $this->kuisionerPeriodService->ensurePeriodeInitialized($periode);

        $sesi->status = 'draft';
        $sesi->save();

        return response()->json([
            'success' => true,
            'message' => 'Sesi baru dalam status Draf berhasil disiapkan. Data responden sebelumnya tetap tersimpan.',
            'data' => $sesi,
        ]);
    }
}
