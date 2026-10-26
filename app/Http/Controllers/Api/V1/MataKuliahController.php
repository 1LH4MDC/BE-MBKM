<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\MkJobdesk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MataKuliahController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MataKuliah::with(['cpmks.cpl', 'cpls', 'jobdesks', 'programStudi']);

        if ($request->filled('prodi')) {
            $prodi = $request->input('prodi');
            $query->where(function ($q) use ($prodi) {
                $q->where('lingkup', 'Fakultas')
                    ->orWhereHas('programStudi', function ($p) use ($prodi) {
                        $p->where('nama', $prodi)->orWhere('kode', $prodi);
                    });
            });
        } elseif ($request->filled('program_studi_id')) {
            $prodiId = $request->input('program_studi_id');
            $query->where(function ($q) use ($prodiId) {
                $q->where('lingkup', 'Fakultas')
                    ->orWhere('program_studi_id', $prodiId);
            });
        }

        $list = $query->get()->map(function ($mk) {
            return [
                'id' => $mk->id,
                'kode' => $mk->kode,
                'nama_mk' => $mk->nama_mk,
                'sks' => $mk->sks,
                'lingkup' => $mk->lingkup,
                'program_studi_id' => $mk->program_studi_id,
                'program_studi' => $mk->programStudi?->nama,
                'kompetensi' => $mk->kompetensi,
                'cpls' => $mk->cpls->map(fn ($c) => [
                    'id' => $c->id,
                    'kode' => $c->kode,
                    'deskripsi' => $c->deskripsi,
                ]),
                'cpmks' => $mk->cpmks->map(fn ($c) => [
                    'id' => $c->id,
                    'kode' => $c->kode,
                    'deskripsi' => $c->deskripsi,
                    'cpl_kode' => $c->cpl?->kode,
                    'cpl_id' => $c->cpl_id,
                ]),
                'jobdesks' => $mk->jobdesks->map(fn ($j) => [
                    'id' => $j->id,
                    'urutan' => $j->urutan,
                    'deskripsi' => $j->deskripsi,
                ]),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $list,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $mk = MataKuliah::with(['cpmks.cpl', 'cpls', 'jobdesks', 'programStudi'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $mk->id,
                'kode' => $mk->kode,
                'nama_mk' => $mk->nama_mk,
                'sks' => $mk->sks,
                'lingkup' => $mk->lingkup,
                'program_studi_id' => $mk->program_studi_id,
                'program_studi' => $mk->programStudi?->nama,
                'kompetensi' => $mk->kompetensi,
                'cpls' => $mk->cpls->map(fn ($c) => [
                    'id' => $c->id,
                    'kode' => $c->kode,
                    'deskripsi' => $c->deskripsi,
                ]),
                'cpmks' => $mk->cpmks->map(fn ($c) => [
                    'id' => $c->id,
                    'kode' => $c->kode,
                    'deskripsi' => $c->deskripsi,
                    'cpl_kode' => $c->cpl?->kode,
                    'cpl_id' => $c->cpl_id,
                ]),
                'jobdesks' => $mk->jobdesks->map(fn ($j) => [
                    'id' => $j->id,
                    'urutan' => $j->urutan,
                    'deskripsi' => $j->deskripsi,
                ]),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:20', 'unique:mata_kuliahs,kode'],
            'nama_mk' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:20'],
            'lingkup' => ['nullable', 'in:Fakultas,Prodi'],
            'kompetensi' => ['nullable', 'string'],
            'cpls' => ['required', 'array', 'min:1'],
            'cpls.*.cpl_id' => ['required', 'exists:cpls,id'],
            'cpls.*.cpmk_deskripsi' => ['required', 'string', 'min:3'],
            'jobdesks' => ['nullable', 'array'],
            'jobdesks.*' => ['string'],
        ], [
            'program_studi_id.required' => 'Pilih Program Studi terlebih dahulu sebelum mengisi data mata kuliah.',
            'cpls.required' => 'Setiap mata kuliah wajib memiliki minimal 1 Capaian Pembelajaran Lulusan (CPL) dan CPMK.',
            'cpls.*.cpmk_deskripsi.required' => 'Deskripsi CPMK wajib diisi untuk setiap CPL yang dipilih.',
        ]);

        return DB::transaction(function () use ($data) {
            $mk = MataKuliah::create([
                'program_studi_id' => $data['program_studi_id'],
                'kode' => strtoupper(trim($data['kode'])),
                'nama_mk' => $data['nama_mk'],
                'sks' => $data['sks'],
                'lingkup' => $data['lingkup'] ?? 'Prodi',
                'kompetensi' => $data['kompetensi'] ?? 'Mampu memahami dan menerapkan konsep keilmuan secara profesional.',
            ]);

            $cplIds = [];
            foreach ($data['cpls'] as $idx => $cplItem) {
                $cplId = $cplItem['cpl_id'];
                $cplIds[] = $cplId;
                $cpmkKode = $cplItem['cpmk_kode'] ?? ('CPMK-'.($idx + 1));

                Cpmk::create([
                    'mata_kuliah_id' => $mk->id,
                    'cpl_id' => $cplId,
                    'kode' => $cpmkKode,
                    'deskripsi' => $cplItem['cpmk_deskripsi'],
                ]);
            }
            $mk->cpls()->sync(array_unique($cplIds));

            if (! empty($data['jobdesks'])) {
                foreach ($data['jobdesks'] as $idx => $jdText) {
                    MkJobdesk::create([
                        'mata_kuliah_id' => $mk->id,
                        'urutan' => $idx + 1,
                        'deskripsi' => $jdText,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Mata kuliah berhasil ditambahkan.',
                'data' => $mk->load(['cpmks.cpl', 'cpls', 'jobdesks']),
            ], 201);
        });
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $mk = MataKuliah::findOrFail($id);

        $data = $request->validate([
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:20', Rule::unique('mata_kuliahs', 'kode')->ignore($mk->id)],
            'nama_mk' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:20'],
            'lingkup' => ['nullable', 'in:Fakultas,Prodi'],
            'kompetensi' => ['nullable', 'string'],
            'cpls' => ['required', 'array', 'min:1'],
            'cpls.*.cpl_id' => ['required', 'exists:cpls,id'],
            'cpls.*.cpmk_deskripsi' => ['required', 'string', 'min:3'],
            'jobdesks' => ['nullable', 'array'],
            'jobdesks.*' => ['string'],
        ]);

        return DB::transaction(function () use ($mk, $data) {
            $mk->update([
                'program_studi_id' => $data['program_studi_id'],
                'kode' => strtoupper(trim($data['kode'])),
                'nama_mk' => $data['nama_mk'],
                'sks' => $data['sks'],
                'lingkup' => $data['lingkup'] ?? $mk->lingkup,
                'kompetensi' => $data['kompetensi'] ?? $mk->kompetensi,
            ]);

            $mk->cpmks()->delete();
            $cplIds = [];
            foreach ($data['cpls'] as $idx => $cplItem) {
                $cplId = $cplItem['cpl_id'];
                $cplIds[] = $cplId;
                $cpmkKode = $cplItem['cpmk_kode'] ?? ('CPMK-'.($idx + 1));

                Cpmk::create([
                    'mata_kuliah_id' => $mk->id,
                    'cpl_id' => $cplId,
                    'kode' => $cpmkKode,
                    'deskripsi' => $cplItem['cpmk_deskripsi'],
                ]);
            }
            $mk->cpls()->sync(array_unique($cplIds));

            if (isset($data['jobdesks'])) {
                $mk->jobdesks()->delete();
                foreach ($data['jobdesks'] as $idx => $jdText) {
                    MkJobdesk::create([
                        'mata_kuliah_id' => $mk->id,
                        'urutan' => $idx + 1,
                        'deskripsi' => $jdText,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Mata kuliah berhasil diperbarui.',
                'data' => $mk->load(['cpmks.cpl', 'cpls', 'jobdesks']),
            ]);
        });
    }

    public function destroy(int $id): JsonResponse
    {
        $mk = MataKuliah::findOrFail($id);

        if ($mk->paketKonversis()->exists()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FOREIGN_KEY_CONSTRAINT',
                'message' => 'Mata kuliah tidak dapat dihapus karena masih terdaftar dalam paket konversi.',
            ], 409);
        }

        $mk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mata kuliah berhasil dihapus.',
        ]);
    }
}
