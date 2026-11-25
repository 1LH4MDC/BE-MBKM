<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MataKuliah;
use App\Models\Rkm;
use App\Models\RkmJobdesk;
use App\Models\RkmMataKuliah;
use App\Services\RkpGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerifikasiRkmController extends Controller
{
    public function __construct(private RkpGeneratorService $rkpService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Rkm::with([
            'pendaftaranMbkm.programStudi',
            'pendaftaranMbkm.mitra',
            'pendaftaranMbkm.periodeAkademik',
            'pendaftaranMbkm.picVerifikator',
            'pendaftaranMbkm.dpl',
            'pendaftaranMbkm.pengujiSemhas',
            'periodeAkademik',
            'items.jobdesks',
        ])
            ->whereIn('status', ['Diajukan', 'Disetujui', 'Perbaikan RKM']);

        // Jika bukan Admin atau Ketua Tim MBKM, batasi ke mahasiswa bimbingan PIC yang sedang login
        if ($user && ! $user->hasRole(['Admin', 'Ketua Tim MBKM'])) {
            $query->whereHas('pendaftaranMbkm', function ($q) use ($user) {
                $q->where('pic_verifikator_id', $user->id);
                if ($user->dosen) {
                    $q->orWhere('pic_verifikator_id', $user->dosen->id);
                }
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'Semua' && $request->input('status') !== 'Semua Status RKM') {
            $st = $request->input('status');
            $st = ($st === 'Menunggu Verifikasi') ? 'Diajukan' : $st;
            $query->where('status', $st);
        }

        if ($request->filled('status_rkm') && $request->input('status_rkm') !== 'Semua Status RKM') {
            $st = $request->input('status_rkm');
            $st = ($st === 'Menunggu Verifikasi') ? 'Diajukan' : $st;
            $query->where('status', $st);
        }

        if ($request->filled('pic')) {
            $pic = $request->input('pic');
            $query->whereHas('pendaftaranMbkm.picVerifikator', function ($q) use ($pic) {
                $q->where('nama', 'like', "%{$pic}%");
            });
        }

        if ($request->filled('prodi') && $request->input('prodi') !== 'Semua Prodi') {
            $prodi = $request->input('prodi');
            $query->whereHas('pendaftaranMbkm.programStudi', function ($q) use ($prodi) {
                $q->where('nama', $prodi)->orWhere('kode', $prodi);
            });
        }

        // Scope filter tahun ajaran
        $query->filterTahunAjaran($request);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('pendaftaranMbkm', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('posisi_magang', 'like', "%{$search}%")
                    ->orWhere('mitra_magang_text', 'like', "%{$search}%")
                    ->orWhereHas('mitra', fn ($m) => $m->where('nama', 'like', "%{$search}%"));
            });
        }

        $mapRkm = function ($rkm) {
            $p = $rkm->pendaftaranMbkm;
            $activeSks = (int) $rkm->items->where('status_mk', '!=', 'dropped')->sum('sks');
            $tahunAjaranLabel = $p?->tahun_ajaran ?: ($rkm->tahun_ajaran ?: ($p?->periodeAkademik?->nama_lengkap ?? $rkm->periodeAkademik?->nama_lengkap ?? ''));

            return [
                'id' => $rkm->id,
                'rkm_id' => $rkm->id,
                'pendaftaran_id' => $p?->id,
                'pendaftaran_mbkm_id' => $p?->id,
                'nim' => $p?->nim,
                'nama' => $p?->nama_lengkap,
                'nama_lengkap' => $p?->nama_lengkap,
                'program_studi_id' => $p?->program_studi_id,
                'program_studi' => $p?->programStudi?->nama,
                'prodi' => $p?->programStudi?->nama,
                'mitra' => $p?->mitra?->nama ?? $p?->mitra_magang_text ?? '-',
                'mitra_magang' => $p?->mitra?->nama ?? $p?->mitra_magang_text ?? '-',
                'posisi' => $p?->posisi_magang ?? '-',
                'posisi_magang' => $p?->posisi_magang ?? '-',
                'tahun_ajaran' => $tahunAjaranLabel,
                'periode_akademik_id' => $rkm->periode_akademik_id ?? $p?->periode_akademik_id,
                'periode_akademik' => $rkm->periodeAkademik ?? $p?->periodeAkademik,
                'status_pendaftaran' => $p?->status ?? 'Terverifikasi',
                'status_rkm' => $rkm->status === 'Diajukan' ? 'Menunggu Verifikasi' : $rkm->status,
                'status' => $rkm->status,
                'total_sks' => $activeSks,
                'pic_verifikator_id' => $p?->pic_verifikator_id,
                'pic_verifikator' => $p?->picVerifikator?->nama,
                'pic_verifikator_nama' => $p?->picVerifikator?->nama,
                'dpl_id' => $p?->dpl_id,
                'dpl' => $p?->dpl?->nama,
                'dpl_nama' => $p?->dpl?->nama,
                'penguji_semhas_id' => $p?->penguji_semhas_id,
                'penguji_semhas' => $p?->pengujiSemhas?->nama,
                'catatan_pic' => $rkm->catatan_pic,
                'approved_at' => $rkm->approved_at?->toISOString(),
                'submitted_at' => $rkm->updated_at?->toISOString(),
            ];
        };

        $query->orderBy('updated_at', 'desc');

        if ($request->input('paginate') === 'false' || $request->boolean('all')) {
            return response()->json([
                'success' => true,
                'data' => $query->get()->map($mapRkm),
                'pagination' => null,
            ]);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map($mapRkm),
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

    public function show($rkmId): JsonResponse
    {
        $rkm = Rkm::with([
            'pendaftaranMbkm.programStudi',
            'pendaftaranMbkm.mitra',
            'pendaftaranMbkm.periodeAkademik',
            'pendaftaranMbkm.picVerifikator',
            'items.jobdesks' => fn ($q) => $q->orderBy('urutan'),
        ])
            ->where('id', $rkmId)
            ->orWhere('pendaftaran_mbkm_id', $rkmId)
            ->firstOrFail();

        $p = $rkm->pendaftaranMbkm;
        $activeSks = (int) $rkm->items->where('status_mk', '!=', 'dropped')->sum('sks');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $rkm->id,
                'rkm_id' => $rkm->id,
                'pendaftaran_id' => $p?->id,
                'status' => $rkm->status,
                'total_sks' => $activeSks,
                'catatan_pic' => $rkm->catatan_pic,
                'approved_at' => $rkm->approved_at?->toISOString(),
                'mahasiswa' => [
                    'nim' => $p?->nim,
                    'nama_lengkap' => $p?->nama_lengkap,
                    'program_studi_id' => $p?->program_studi_id,
                    'program_studi' => $p?->programStudi?->nama,
                    'mitra' => $p?->mitra?->nama ?? $p?->mitra_magang_text,
                    'posisi' => $p?->posisi_magang,
                ],
                'items' => $rkm->items->map(fn ($item) => [
                    'id' => $item->id,
                    'mata_kuliah_id' => $item->mata_kuliah_id,
                    'kode_mk' => $item->kode_mk,
                    'nama_mk' => $item->nama_mk,
                    'sks' => (int) $item->sks,
                    'durasi_jam' => (int) ($item->durasi_jam ?: ((int) $item->sks * 40)),
                    'kompetensi' => $item->kompetensi,
                    'status_mk' => $item->status_mk,
                    'jobdesks' => $item->jobdesks->map(fn ($j) => [
                        'id' => $j->id,
                        'deskripsi' => $j->deskripsi,
                        'origin' => $j->origin ?: 'master_mk',
                    ]),
                ]),
            ],
        ]);
    }

    public function addItemByPic(Request $request): JsonResponse
    {
        if (! $request->filled('mata_kuliah_id')) {
            $mk = null;
            if ($request->filled('kode_mata_kuliah') || $request->filled('kode_mk') || $request->filled('kode')) {
                $kode = $request->input('kode_mata_kuliah') ?? $request->input('kode_mk') ?? $request->input('kode');
                $mk = MataKuliah::where('kode', $kode)->first();
            } elseif ($request->filled('nama_mata_kuliah') || $request->filled('nama_mk') || $request->filled('nama')) {
                $nama = $request->input('nama_mata_kuliah') ?? $request->input('nama_mk') ?? $request->input('nama');
                $mk = MataKuliah::where('nama', 'like', "%{$nama}%")->first();
            }
            if ($mk) {
                $request->merge(['mata_kuliah_id' => $mk->id]);
            }
        }

        $request->validate([
            'rkm_id' => ['required'],
            'mata_kuliah_id' => ['required', 'exists:mata_kuliahs,id'],
            'kompetensi' => ['required', 'string', 'max:1000'],
            'jobdesks' => ['nullable', 'array'],
            'jobdesks.*' => ['string', 'max:500'],
        ]);

        $rkmId = $request->input('rkm_id');
        $rkm = Rkm::with('items')
            ->where('id', $rkmId)
            ->orWhere('pendaftaran_mbkm_id', $rkmId)
            ->firstOrFail();

        if ($lockedResponse = $this->ensureRkmNotFinalLocked($rkm)) {
            return $lockedResponse;
        }

        $mk = MataKuliah::findOrFail($request->input('mata_kuliah_id'));

        // Cek total SKS aktif saat ini (exclude dropped)
        $currentActiveSks = (int) $rkm->items()
            ->where('status_mk', '!=', 'dropped')
            ->sum('sks');

        $newSks = (int) $mk->sks;

        if ($currentActiveSks + $newSks > 20) {
            return response()->json([
                'success' => false,
                'message' => 'Total SKS aktif melebihi batas 20 SKS. Sisa kuota konversi: '.(20 - $currentActiveSks).' SKS.',
            ], 422);
        }

        $durasiJam = $newSks * 40;
        if ($mk->kode === 'KSU1701') {
            $durasiJam = 80;
        }

        $newItem = DB::transaction(function () use ($rkm, $mk, $request, $newSks, $durasiJam) {
            $item = RkmMataKuliah::create([
                'rkm_id' => $rkm->id,
                'mata_kuliah_id' => $mk->id,
                'kode_mk' => $mk->kode,
                'nama_mk' => $mk->nama_mk ?: $mk->nama,
                'sks' => $newSks,
                'durasi_jam' => $durasiJam,
                'kompetensi' => $request->input('kompetensi'),
                'status_mk' => 'aktif',
            ]);

            if (is_array($request->input('jobdesks'))) {
                $urutan = 1;
                foreach ($request->input('jobdesks') as $jdText) {
                    if (trim((string) $jdText)) {
                        RkmJobdesk::create([
                            'rkm_mata_kuliah_id' => $item->id,
                            'urutan' => $urutan++,
                            'deskripsi' => trim((string) $jdText),
                            'origin' => 'custom',
                        ]);
                    }
                }
            }

            $totalActive = (int) $rkm->items()->where('status_mk', '!=', 'dropped')->sum('sks');
            $rkm->update(['total_sks' => $totalActive]);

            return $item->load('jobdesks');
        });

        return response()->json([
            'success' => true,
            'message' => "Mata kuliah {$newItem->nama_mk} ({$newSks} SKS) berhasil ditambahkan ke RKM.",
            'data' => [
                'id' => $newItem->id,
                'mata_kuliah_id' => $newItem->mata_kuliah_id,
                'kode_mk' => $newItem->kode_mk,
                'nama_mk' => $newItem->nama_mk,
                'sks' => $newItem->sks,
                'durasi_jam' => $newItem->durasi_jam,
                'kompetensi' => $newItem->kompetensi,
                'status_mk' => $newItem->status_mk,
                'jobdesks' => $newItem->jobdesks->map(fn ($j) => [
                    'id' => $j->id,
                    'deskripsi' => $j->deskripsi,
                    'origin' => $j->origin,
                ]),
            ],
        ], 201);
    }

    public function updateItemByPic(int $rkmMkId, Request $request): JsonResponse
    {
        $request->validate([
            'kompetensi' => ['nullable', 'string', 'max:1000'],
        ]);

        $item = RkmMataKuliah::with(['jobdesks', 'rkm'])->findOrFail($rkmMkId);

        if ($item->rkm && ($lockedResponse = $this->ensureRkmNotFinalLocked($item->rkm))) {
            return $lockedResponse;
        }

        if ($request->has('kompetensi')) {
            $item->update(['kompetensi' => $request->input('kompetensi')]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kompetensi mata kuliah berhasil diperbarui.',
            'data' => $item->fresh('jobdesks'),
        ]);
    }

    public function toggleDropItem(int $rkmMkId): JsonResponse
    {
        $item = RkmMataKuliah::with('rkm')->findOrFail($rkmMkId);

        if ($item->rkm && ($lockedResponse = $this->ensureRkmNotFinalLocked($item->rkm))) {
            return $lockedResponse;
        }

        $newStatus = ($item->status_mk === 'dropped') ? 'aktif' : 'dropped';
        $item->update(['status_mk' => $newStatus]);

        $totalActiveSks = (int) $item->rkm->items()->where('status_mk', '!=', 'dropped')->sum('sks');
        $item->rkm->update(['total_sks' => $totalActiveSks]);

        return response()->json([
            'success' => true,
            'message' => $newStatus === 'dropped'
                ? "Mata kuliah {$item->nama_mk} berhasil digugurkan (drop)."
                : "Mata kuliah {$item->nama_mk} berhasil dipulihkan.",
            'data' => [
                'id' => $item->id,
                'status_mk' => $newStatus,
                'total_sks' => $totalActiveSks,
            ],
        ]);
    }

    public function updateJobdesk(int $jobdeskId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'deskripsi' => ['required', 'string', 'max:500'],
        ]);

        $jobdesk = RkmJobdesk::with('rkmMataKuliah.rkm')->findOrFail($jobdeskId);
        if ($jobdesk->rkmMataKuliah?->rkm && ($lockedResponse = $this->ensureRkmNotFinalLocked($jobdesk->rkmMataKuliah->rkm))) {
            return $lockedResponse;
        }
        $jobdesk->update(['deskripsi' => $data['deskripsi']]);

        return response()->json([
            'success' => true,
            'message' => 'Jobdesk berhasil disesuaikan oleh PIC.',
            'data' => $jobdesk,
        ]);
    }

    public function addJobdesk(int $rkmMkId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'deskripsi' => ['required', 'string', 'max:500'],
        ]);

        $rkmMk = RkmMataKuliah::with('rkm')->findOrFail($rkmMkId);
        if ($rkmMk->rkm && ($lockedResponse = $this->ensureRkmNotFinalLocked($rkmMk->rkm))) {
            return $lockedResponse;
        }
        $nextUrutan = ($rkmMk->jobdesks()->max('urutan') ?? 0) + 1;

        $jobdesk = $rkmMk->jobdesks()->create([
            'urutan' => $nextUrutan,
            'deskripsi' => $data['deskripsi'],
            'origin' => 'custom',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jobdesk tambahan berhasil ditambahkan oleh PIC.',
            'data' => $jobdesk,
        ], 201);
    }

    public function deleteJobdesk(int $jobdeskId): JsonResponse
    {
        $jobdesk = RkmJobdesk::with('rkmMataKuliah.rkm')->findOrFail($jobdeskId);
        if ($jobdesk->rkmMataKuliah?->rkm && ($lockedResponse = $this->ensureRkmNotFinalLocked($jobdesk->rkmMataKuliah->rkm))) {
            return $lockedResponse;
        }
        $jobdesk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jobdesk berhasil dihapus.',
        ]);
    }

    public function keputusan($rkmId, Request $request): JsonResponse
    {
        if ($request->has('status') && ! $request->has('keputusan')) {
            $st = $request->input('status');
            $request->merge(['keputusan' => ($st === 'Perbaikan' || $st === 'Perbaikan RKM') ? 'Perbaikan RKM' : $st]);
        }

        $data = $request->validate([
            'keputusan' => ['required', 'in:Disetujui,Perbaikan,Perbaikan RKM'],
            'catatan' => ['nullable', 'string', 'required_if:keputusan,Perbaikan RKM', 'required_if:keputusan,Perbaikan'],
        ]);

        $rkm = Rkm::with('items')
            ->where('id', $rkmId)
            ->orWhere('pendaftaran_mbkm_id', $rkmId)
            ->firstOrFail();

        if ($lockedResponse = $this->ensureRkmNotFinalLocked($rkm)) {
            return $lockedResponse;
        }

        $statusNormalized = in_array($data['keputusan'], ['Perbaikan RKM', 'Perbaikan'], true) ? 'Perbaikan RKM' : 'Disetujui';

        $updateData = [
            'status' => $statusNormalized,
            'catatan_pic' => $data['catatan'] ?? null,
            'reviewed_by' => $request->user()->id,
        ];

        if ($statusNormalized === 'Disetujui') {
            $updateData['approved_at'] = now();
        }

        $rkm->update($updateData);

        if ($statusNormalized === 'Disetujui') {
            $this->rkpService->generateFromRkm($rkm, $request->user());
        }

        return response()->json([
            'success' => true,
            'message' => $statusNormalized === 'Disetujui'
                ? 'RKM berhasil disetujui dan dokumen RKP telah digenerate otomatis.'
                : 'Catatan perbaikan RKM berhasil dikirimkan ke mahasiswa.',
            'data' => [
                'id' => $rkm->id,
                'rkm_id' => $rkm->id,
                'status' => $rkm->status,
                'approved_at' => $rkm->approved_at,
            ],
        ]);
    }

    private function ensureRkmNotFinalLocked(Rkm $rkm): ?JsonResponse
    {
        if ($rkm->status === 'Disetujui') {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_RKM_FINAL_LOCKED',
                'message' => 'RKM sudah berstatus Disetujui dan terkunci permanen.',
            ], 422);
        }

        return null;
    }
}
