<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PeriodeAkademik;
use App\Models\ProgramStudi;
use Illuminate\Http\JsonResponse;

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
}
