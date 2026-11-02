<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cpl;
use App\Models\MataKuliah;
use App\Models\PaketKonversi;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\ProgramStudi;
use App\Models\Rkm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterDataController extends Controller
{
    public function programStudi(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ProgramStudi::all(),
        ]);
    }

    public function periodeAkademik(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => PeriodeAkademik::orderBy('id', 'desc')->get(),
        ]);
    }

    public function periodeAktif(): JsonResponse
    {
        $aktif = PeriodeAkademik::where('is_aktif', true)->first();

        return response()->json([
            'success' => true,
            'data' => $aktif,
        ]);
    }

    public function storePeriodeAkademik(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
            'is_aktif' => ['nullable', 'boolean'],
        ]);

        $isAktif = (bool) ($data['is_aktif'] ?? false);

        return DB::transaction(function () use ($data, $isAktif) {
            if ($isAktif) {
                PeriodeAkademik::where('is_aktif', true)->update(['is_aktif' => false]);
            }

            $periode = PeriodeAkademik::create([
                'tahun_ajaran' => $data['tahun_ajaran'],
                'semester' => $data['semester'],
                'is_aktif' => $isAktif,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Periode akademik berhasil ditambahkan.',
                'data' => $periode,
            ], 201);
        });
    }

    public function updatePeriodeAkademik(int $id, Request $request): JsonResponse
    {
        $periode = PeriodeAkademik::findOrFail($id);

        $data = $request->validate([
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
            'is_aktif' => ['nullable', 'boolean'],
        ]);

        $isAktif = isset($data['is_aktif']) ? (bool) $data['is_aktif'] : $periode->is_aktif;

        return DB::transaction(function () use ($periode, $data, $isAktif) {
            if ($isAktif) {
                PeriodeAkademik::where('id', '!=', $periode->id)
                    ->where('is_aktif', true)
                    ->update(['is_aktif' => false]);
            }

            $periode->update([
                'tahun_ajaran' => $data['tahun_ajaran'],
                'semester' => $data['semester'],
                'is_aktif' => $isAktif,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Periode akademik berhasil diperbarui.',
                'data' => $periode->fresh(),
            ]);
        });
    }

    public function aktifkanPeriodeAkademik(int $id): JsonResponse
    {
        $periode = PeriodeAkademik::findOrFail($id);

        DB::transaction(function () use ($periode) {
            PeriodeAkademik::where('id', '!=', $periode->id)
                ->where('is_aktif', true)
                ->update(['is_aktif' => false]);

            $periode->update(['is_aktif' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => "Periode akademik {$periode->tahun_ajaran} ({$periode->semester}) berhasil diaktifkan.",
            'data' => $periode->fresh(),
        ]);
    }

    public function destroyPeriodeAkademik(int $id): JsonResponse
    {
        $periode = PeriodeAkademik::findOrFail($id);

        if ($periode->is_aktif) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_PERIODE_ACTIVE',
                'message' => 'Periode akademik yang sedang aktif tidak dapat dihapus.',
            ], 422);
        }

        if ($periode->pendaftaranMbkms()->exists()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FOREIGN_KEY_CONSTRAINT',
                'message' => 'Periode akademik tidak dapat dihapus karena sudah memiliki data pendaftaran MBKM.',
            ], 409);
        }

        $periode->delete();

        return response()->json([
            'success' => true,
            'message' => 'Periode akademik berhasil dihapus.',
        ]);
    }

    public function timMbkmDashboardStats(Request $request): JsonResponse
    {
        $ta = $request->input('tahun_ajaran');
        $isAll = empty($ta) || $ta === 'Semua Tahun Ajaran' || $ta === 'all';

        $pendaftarQuery = PendaftaranMbkm::query();
        $rkmQuery = Rkm::query();

        if (! $isAll) {
            $pendaftarQuery->where(function ($q) use ($ta) {
                $q->where('tahun_ajaran', $ta)
                    ->orWhereHas('periodeAkademik', function ($sub) use ($ta) {
                        $sub->whereRaw("CONCAT(tahun_ajaran, ' ', semester) = ?", [$ta]);
                    });
            });
            $rkmQuery->whereHas('pendaftaranMbkm', function ($q) use ($ta) {
                $q->where('tahun_ajaran', $ta)
                    ->orWhereHas('periodeAkademik', function ($sub) use ($ta) {
                        $sub->whereRaw("CONCAT(tahun_ajaran, ' ', semester) = ?", [$ta]);
                    });
            });
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_cpl' => Cpl::count(),
                'total_matkul' => MataKuliah::count(),
                'total_paket_konversi' => PaketKonversi::count(),
                'total_pendaftar' => $pendaftarQuery->count(),
                'total_rkm' => $rkmQuery->count(),
            ],
        ]);
    }
}
