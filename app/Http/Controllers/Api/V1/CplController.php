<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cpl;
use App\Models\ProgramStudi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CplController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Cpl::with('programStudi');

        if ($request->filled('prodi')) {
            $prodi = $request->input('prodi');
            $query->whereHas('programStudi', function ($p) use ($prodi) {
                $p->where('nama', $prodi)->orWhere('kode', $prodi);
            });
        } elseif ($request->filled('program_studi_id')) {
            $query->where('program_studi_id', $request->input('program_studi_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $cpl = Cpl::with('programStudi')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $cpl,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:30'],
            'deskripsi' => ['required', 'string'],
        ], [
            'program_studi_id.required' => 'Pilih Program Studi terlebih dahulu.',
        ]);

        $prodi = ProgramStudi::findOrFail($data['program_studi_id']);

        $rawKode = strtoupper(trim($data['kode']));
        if (! str_starts_with($rawKode, $prodi->cpl_prefix)) {
            $rawKode = $prodi->cpl_prefix.ltrim($rawKode, '_');
        }

        if (Cpl::where('kode', $rawKode)->exists()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_DUPLICATE_ENTRY',
                'message' => "Kode CPL [{$rawKode}] sudah terdaftar pada sistem.",
            ], 409);
        }

        $cpl = Cpl::create([
            'program_studi_id' => $prodi->id,
            'kode' => $rawKode,
            'deskripsi' => $data['deskripsi'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'CPL berhasil ditambahkan.',
            'data' => $cpl->load('programStudi'),
        ], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $cpl = Cpl::findOrFail($id);

        $data = $request->validate([
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:30'],
            'deskripsi' => ['required', 'string'],
        ]);

        $prodi = ProgramStudi::findOrFail($data['program_studi_id']);
        $rawKode = strtoupper(trim($data['kode']));
        if (! str_starts_with($rawKode, $prodi->cpl_prefix)) {
            $rawKode = $prodi->cpl_prefix.ltrim($rawKode, '_');
        }

        $duplicate = Cpl::where('kode', $rawKode)->where('id', '!=', $cpl->id)->exists();
        if ($duplicate) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_DUPLICATE_ENTRY',
                'message' => "Kode CPL [{$rawKode}] sudah digunakan oleh CPL lain.",
            ], 409);
        }

        $cpl->update([
            'program_studi_id' => $prodi->id,
            'kode' => $rawKode,
            'deskripsi' => $data['deskripsi'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'CPL berhasil diperbarui.',
            'data' => $cpl->load('programStudi'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $cpl = Cpl::findOrFail($id);

        if ($cpl->cpmks()->exists() || $cpl->mataKuliahs()->exists()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_CPL_IN_USE',
                'message' => 'CPL tidak dapat dihapus karena masih digunakan oleh satu atau lebih mata kuliah.',
            ], 409);
        }

        $cpl->delete();

        return response()->json([
            'success' => true,
            'message' => 'CPL berhasil dihapus.',
        ]);
    }
}
