<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MataKuliah;
use App\Models\PaketKonversi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaketKonversiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PaketKonversi::with(['programStudi', 'mataKuliahs.cpmks.cpl']);

        if ($request->filled('program_studi_id')) {
            $query->where('program_studi_id', $request->input('program_studi_id'));
        }

        $query->orderBy('id', 'asc');

        $shouldPaginate = ($request->has('page') || $request->has('per_page') || $request->input('paginate') === 'true')
            && $request->input('paginate') !== 'false'
            && ! $request->boolean('all');

        if (! $shouldPaginate) {
            return response()->json([
                'success' => true,
                'data' => $query->get(),
                'pagination' => null,
            ]);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $paket = PaketKonversi::with(['programStudi', 'mataKuliahs.cpmks.cpl'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $paket,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama_paket' => ['required', 'string', 'max:150'],
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'target_posisi' => ['nullable', 'string', 'max:100'],
            'mata_kuliah_ids' => ['required', 'array', 'min:1'],
            'mata_kuliah_ids.*' => ['integer', 'exists:mata_kuliahs,id'],
        ], [
            'program_studi_id.required' => 'Pilih Program Studi terlebih dahulu sebelum memilih mata kuliah.',
            'mata_kuliah_ids.required' => 'Pilih minimal satu mata kuliah untuk dimasukkan ke dalam paket konversi.',
        ]);

        $mks = MataKuliah::whereIn('id', $data['mata_kuliah_ids'])->get();

        foreach ($mks as $mk) {
            if ($mk->lingkup !== 'Fakultas' && (int) $mk->program_studi_id !== (int) $data['program_studi_id']) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'ERR_PAKET_PRODI_MISMATCH',
                    'message' => 'Mata kuliah yang dimasukkan ke dalam paket konversi harus berasal dari program studi yang sama atau lingkup Fakultas.',
                ], 422);
            }
        }

        $jumlahMk = $mks->count();
        $totalSks = $mks->sum('sks');

        return DB::transaction(function () use ($data, $jumlahMk, $totalSks) {
            $paket = PaketKonversi::create([
                'nama_paket' => strtoupper(trim($data['nama_paket'])),
                'program_studi_id' => $data['program_studi_id'],
                'target_posisi' => $data['target_posisi'] ?? ucwords(strtolower($data['nama_paket'])),
                'jumlah_mk' => $jumlahMk,
                'total_sks_pool' => $totalSks,
            ]);

            $pivotData = [];
            foreach ($data['mata_kuliah_ids'] as $idx => $mkId) {
                $pivotData[$mkId] = ['urutan' => $idx + 1];
            }
            $paket->mataKuliahs()->sync($pivotData);

            return response()->json([
                'success' => true,
                'message' => 'Paket konversi berhasil dibuat.',
                'data' => $paket->load(['programStudi', 'mataKuliahs']),
            ], 201);
        });
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $paket = PaketKonversi::findOrFail($id);

        $data = $request->validate([
            'nama_paket' => ['required', 'string', 'max:150'],
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'target_posisi' => ['nullable', 'string', 'max:100'],
            'mata_kuliah_ids' => ['required', 'array', 'min:1'],
            'mata_kuliah_ids.*' => ['integer', 'exists:mata_kuliahs,id'],
        ]);

        $mks = MataKuliah::whereIn('id', $data['mata_kuliah_ids'])->get();

        foreach ($mks as $mk) {
            if ($mk->lingkup !== 'Fakultas' && (int) $mk->program_studi_id !== (int) $data['program_studi_id']) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'ERR_PAKET_PRODI_MISMATCH',
                    'message' => 'Mata kuliah yang dimasukkan ke dalam paket konversi harus berasal dari program studi yang sama atau lingkup Fakultas.',
                ], 422);
            }
        }

        $jumlahMk = $mks->count();
        $totalSks = $mks->sum('sks');

        return DB::transaction(function () use ($paket, $data, $jumlahMk, $totalSks) {
            $paket->update([
                'nama_paket' => strtoupper(trim($data['nama_paket'])),
                'program_studi_id' => $data['program_studi_id'],
                'target_posisi' => $data['target_posisi'] ?? ucwords(strtolower($data['nama_paket'])),
                'jumlah_mk' => $jumlahMk,
                'total_sks_pool' => $totalSks,
            ]);

            $pivotData = [];
            foreach ($data['mata_kuliah_ids'] as $idx => $mkId) {
                $pivotData[$mkId] = ['urutan' => $idx + 1];
            }
            $paket->mataKuliahs()->sync($pivotData);

            return response()->json([
                'success' => true,
                'message' => 'Paket konversi berhasil diperbarui.',
                'data' => $paket->load(['programStudi', 'mataKuliahs']),
            ]);
        });
    }

    public function destroy(int $id): JsonResponse
    {
        $paket = PaketKonversi::findOrFail($id);

        if ($paket->rkms()->exists()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FOREIGN_KEY_CONSTRAINT',
                'message' => 'Paket konversi tidak dapat dihapus karena telah digunakan pada dokumen RKM mahasiswa.',
            ], 409);
        }

        $paket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paket konversi berhasil dihapus.',
        ]);
    }
}
