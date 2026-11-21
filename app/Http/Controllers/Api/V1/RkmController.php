<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MataKuliah;
use App\Models\PaketKonversi;
use App\Models\PendaftaranMbkm;
use App\Models\Rkm;
use App\Models\RkmJobdesk;
use App\Models\RkmMataKuliah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RkmController extends Controller
{
    public function availableCourses(Request $request): JsonResponse
    {
        $user = $request->user();

        $pendaftaran = $this->getPendaftaranMahasiswa($request, ['programStudi', 'periodeAkademik']);

        if (! $pendaftaran) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_PENDAFTARAN_NOT_FOUND',
                'message' => 'Anda belum memiliki data pendaftaran MBKM.',
            ], 404);
        }

        $pastConverted = DB::table('rkm_mata_kuliahs as rmk')
            ->join('rkms as r', 'r.id', '=', 'rmk.rkm_id')
            ->join('pendaftaran_mbkms as p', 'p.id', '=', 'r.pendaftaran_mbkm_id')
            ->join('periode_akademiks as pa', 'pa.id', '=', 'p.periode_akademik_id')
            ->where('p.user_id', $user->id)
            ->where('p.id', '!=', $pendaftaran->id)
            ->where('r.status', 'Disetujui')
            ->where('rmk.status_mk', 'aktif')
            ->select('rmk.mata_kuliah_id', 'pa.tahun_ajaran', 'pa.semester')
            ->get()
            ->keyBy('mata_kuliah_id');

        $paket = null;
        if (! $pendaftaran->is_custom_role) {
            $posisi = trim((string) $pendaftaran->posisi_magang);
            $pakets = PaketKonversi::with(['mataKuliahs.jobdesks'])
                ->where('program_studi_id', $pendaftaran->program_studi_id)
                ->get();

            $paket = $pakets->first(fn ($p) => strcasecmp((string) $p->target_posisi, $posisi) === 0
                || strcasecmp((string) $p->nama_paket, $posisi) === 0);

            if (! $paket && $posisi !== '') {
                $paket = $pakets->first(fn ($p) => stripos((string) $p->target_posisi, $posisi) !== false
                    || stripos((string) $p->nama_paket, $posisi) !== false
                    || ($p->target_posisi && stripos($posisi, (string) $p->target_posisi) !== false)
                    || ($p->nama_paket && stripos($posisi, (string) $p->nama_paket) !== false));
            }
        }

        if ($paket) {
            $courses = $paket->mataKuliahs;
            $fakultasCourses = MataKuliah::with('jobdesks')
                ->where('lingkup', 'Fakultas')
                ->whereNotIn('id', $courses->pluck('id'))
                ->get();
            $courses = $courses->concat($fakultasCourses);
            $namaPaket = $paket->nama_paket;
            $totalSksPool = $courses->sum('sks');
        } else {
            $courses = MataKuliah::with('jobdesks')
                ->where(function ($q) use ($pendaftaran) {
                    $q->where('lingkup', 'Fakultas')
                        ->orWhere('program_studi_id', $pendaftaran->program_studi_id);
                })
                ->get();
            $namaPaket = 'Semua Mata Kuliah Prodi & Fakultas';
            $totalSksPool = $courses->sum('sks');
        }

        $formattedCourses = $courses->map(function ($mk) use ($pastConverted) {
            $past = $pastConverted->get($mk->id);
            $durasiJam = $mk->sks * 40;
            if ($mk->kode === 'KSU1701') {
                $durasiJam = 80;
            }

            return [
                'mata_kuliah_id' => $mk->id,
                'kode' => $mk->kode,
                'nama_mk' => $mk->nama_mk,
                'sks' => $mk->sks,
                'durasi_jam' => $durasiJam,
                'kompetensi' => $mk->kompetensi,
                'already_converted_in_past' => (bool) $past,
                'converted_period' => $past ? "{$past->tahun_ajaran} {$past->semester}" : null,
                'default_jobdesks' => $mk->jobdesks->pluck('deskripsi')->values()->all(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'paket_konversi' => $namaPaket,
                'total_sks_pool' => $totalSksPool,
                'courses' => $formattedCourses,
            ],
        ]);
    }

    public function myRkm(Request $request): JsonResponse
    {
        $user = $request->user();
        $periodeAktif = \App\Models\PeriodeAkademik::where('is_aktif', true)->first();

        $pendaftaran = $this->getPendaftaranMahasiswa($request, ['programStudi', 'mitra', 'picVerifikator', 'periodeAkademik']);

        if (! $pendaftaran) {
            return response()->json([
                'success' => true,
                'data' => [
                    'rkm_id' => null,
                    'status' => 'Belum Mendaftar',
                    'total_sks' => 0,
                    'is_archived' => false,
                    'tahun_ajaran' => $periodeAktif?->nama_lengkap ?? '',
                    'items' => [],
                    'pendaftaran' => null,
                    'riwayat_rkm' => [],
                ],
            ]);
        }

        $pendaftaranData = $this->formatPendaftaran($pendaftaran);
        $isArchived = $periodeAktif ? ((int) $pendaftaran->periode_akademik_id !== (int) $periodeAktif->id) : false;

        $rkm = Rkm::with(['items.jobdesks' => fn ($q) => $q->orderBy('urutan')])
            ->where('pendaftaran_mbkm_id', $pendaftaran->id)
            ->first();

        $riwayatRkm = Rkm::with(['pendaftaranMbkm.periodeAkademik'])
            ->where('user_id', $user->id)
            ->where('pendaftaran_mbkm_id', '!=', $pendaftaran->id)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($oldRkm) => [
                'rkm_id' => $oldRkm->id,
                'pendaftaran_id' => $oldRkm->pendaftaran_mbkm_id,
                'tahun_ajaran' => $oldRkm->pendaftaranMbkm?->periodeAkademik?->tahun_ajaran,
                'semester' => $oldRkm->pendaftaranMbkm?->periodeAkademik?->semester,
                'status' => $oldRkm->status,
                'total_sks' => $oldRkm->total_sks,
            ]);

        if (! $rkm) {
            return response()->json([
                'success' => true,
                'data' => [
                    'rkm_id' => null,
                    'status' => 'Belum Mengajukan',
                    'total_sks' => 0,
                    'is_archived' => $isArchived,
                    'tahun_ajaran' => $pendaftaran->tahun_ajaran ?: ($pendaftaran->periodeAkademik?->nama_lengkap ?? ''),
                    'items' => [],
                    'pendaftaran' => $pendaftaranData,
                    'riwayat_rkm' => $riwayatRkm,
                ],
            ]);
        }

        $items = $rkm->items->map(function ($item) {
            return [
                'id' => $item->id,
                'mata_kuliah_id' => $item->mata_kuliah_id,
                'kode_mk' => $item->kode_mk,
                'nama_mk' => $item->nama_mk,
                'sks' => $item->sks,
                'durasi_jam' => $item->durasi_jam,
                'kompetensi' => $item->kompetensi,
                'status_mk' => $item->status_mk,
                'jobdesk_list' => $item->jobdesks->map(fn ($j) => [
                    'id' => $j->id,
                    'deskripsi' => $j->deskripsi,
                    'origin' => $j->origin,
                ]),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'rkm_id' => $rkm->id,
                'status' => $rkm->status,
                'total_sks' => $rkm->total_sks,
                'is_archived' => $isArchived,
                'tahun_ajaran' => $pendaftaran->tahun_ajaran ?: ($pendaftaran->periodeAkademik?->nama_lengkap ?? ''),
                'catatan_pic' => $rkm->catatan_pic,
                'approved_at' => $rkm->approved_at,
                'items' => $items,
                'pendaftaran' => $pendaftaranData,
                'riwayat_rkm' => $riwayatRkm,
            ],
        ]);
    }

    public function saveSelection(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->has('mata_kuliah_ids') && ! $request->has('selected_mata_kuliah_ids')) {
            $request->merge(['selected_mata_kuliah_ids' => $request->input('mata_kuliah_ids')]);
        }

        $data = $request->validate([
            'selected_mata_kuliah_ids' => ['required', 'array', 'min:1'],
            'selected_mata_kuliah_ids.*' => ['integer', 'exists:mata_kuliahs,id'],
        ]);

        $pendaftaran = $this->getPendaftaranMahasiswa($request);

        if (! $pendaftaran) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_PENDAFTARAN_NOT_FOUND',
                'message' => 'Anda belum memiliki data pendaftaran MBKM pada periode ini.',
            ], 404);
        }

        $selectedMks = MataKuliah::with('jobdesks')
            ->whereIn('id', $data['selected_mata_kuliah_ids'])
            ->get();

        $totalSks = $selectedMks->sum('sks');
        if ($totalSks > 20) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_RKM_SKS_LIMIT_EXCEEDED',
                'message' => 'Total SKS mata kuliah aktif yang dipilih melebihi batas maksimal konversi (Maksimal 20 SKS).',
                'errors' => [
                    'selected_mata_kuliah_ids' => ["Maksimal konversi MBKM adalah 20 SKS. Anda memilih {$totalSks} SKS."],
                ],
            ], 422);
        }

        $pastConverted = DB::table('rkm_mata_kuliahs as rmk')
            ->join('rkms as r', 'r.id', '=', 'rmk.rkm_id')
            ->join('pendaftaran_mbkms as p', 'p.id', '=', 'r.pendaftaran_mbkm_id')
            ->where('p.user_id', $user->id)
            ->where('p.id', '!=', $pendaftaran->id)
            ->where('r.status', 'Disetujui')
            ->where('rmk.status_mk', 'aktif')
            ->whereIn('rmk.mata_kuliah_id', $data['selected_mata_kuliah_ids'])
            ->pluck('rmk.nama_mk', 'rmk.mata_kuliah_id');

        if ($pastConverted->isNotEmpty()) {
            $nama = $pastConverted->first();

            return response()->json([
                'success' => false,
                'error_code' => 'ERR_RKM_COURSE_ALREADY_CONVERTED',
                'message' => "Mata kuliah [{$nama}] tidak dapat dipilih karena sudah pernah dikonversi pada periode magang sebelumnya.",
                'errors' => [
                    'selected_mata_kuliah_ids' => ["Mata kuliah {$nama} sudah pernah dikonversi."],
                ],
            ], 422);
        }

        return DB::transaction(function () use ($pendaftaran, $user, $selectedMks) {
            $periode = $pendaftaran->periodeAkademik;
            $tahunAjaranLabel = $pendaftaran->tahun_ajaran
                ?? ($periode ? trim("{$periode->tahun_ajaran} {$periode->semester}") : null);

            $rkm = Rkm::firstOrCreate(
                ['pendaftaran_mbkm_id' => $pendaftaran->id],
                [
                    'periode_akademik_id' => $pendaftaran->periode_akademik_id,
                    'tahun_ajaran' => $tahunAjaranLabel,
                    'user_id' => $user->id,
                    'status' => 'Belum Mengajukan',
                    'total_sks' => 0,
                ]
            );

            $rkm->items()->whereNotIn('mata_kuliah_id', $selectedMks->pluck('id'))->delete();

            foreach ($selectedMks as $mk) {
                $durasiJam = $mk->sks * 40;
                if ($mk->kode === 'KSU1701') {
                    $durasiJam = 80;
                }

                $rkmMk = $rkm->items()->firstOrCreate(
                    ['mata_kuliah_id' => $mk->id],
                    [
                        'kode_mk' => $mk->kode,
                        'nama_mk' => $mk->nama_mk,
                        'sks' => $mk->sks,
                        'durasi_jam' => $durasiJam,
                        'kompetensi' => $mk->kompetensi,
                        'status_mk' => 'aktif',
                    ]
                );

                if ($rkmMk->wasRecentlyCreated) {
                    foreach ($mk->jobdesks as $j) {
                        $rkmMk->jobdesks()->create([
                            'urutan' => $j->urutan,
                            'deskripsi' => $j->deskripsi,
                            'origin' => 'master_mk',
                        ]);
                    }
                }
            }

            $totalActiveSks = $rkm->items()->where('status_mk', 'aktif')->sum('sks');
            $rkm->update(['total_sks' => $totalActiveSks]);

            return response()->json([
                'success' => true,
                'message' => 'Pemilihan mata kuliah RKM berhasil disimpan.',
                'data' => [
                    'rkm_id' => $rkm->id,
                    'total_sks' => $totalActiveSks,
                    'items_count' => $rkm->items()->count(),
                ],
            ]);
        });
    }

    public function addJobdesk(int $rkmMkId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'deskripsi' => ['required', 'string', 'max:500'],
        ]);

        $rkmMk = RkmMataKuliah::with('rkm')->findOrFail($rkmMkId);
        if ($rkmMk->rkm?->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $nextUrutan = $rkmMk->jobdesks()->max('urutan') + 1;

        $jobdesk = $rkmMk->jobdesks()->create([
            'urutan' => $nextUrutan,
            'deskripsi' => $data['deskripsi'],
            'origin' => 'custom',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jobdesk custom berhasil ditambahkan.',
            'data' => $jobdesk,
        ], 201);
    }

    public function updateJobdesk(int $jobdeskId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'deskripsi' => ['required', 'string', 'max:500'],
        ]);

        $jobdesk = RkmJobdesk::with('rkmMataKuliah.rkm')->findOrFail($jobdeskId);
        if ($jobdesk->rkmMataKuliah?->rkm?->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        if (! $jobdesk->canBeEditedByStudent()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_JOBDESK_MASTER_LOCKED',
                'message' => 'Jobdesk master mata kuliah bersifat permanen bagi mahasiswa dan hanya dapat disesuaikan oleh PIC Verifikator.',
            ], 403);
        }

        $jobdesk->update(['deskripsi' => $data['deskripsi']]);

        return response()->json([
            'success' => true,
            'message' => 'Jobdesk berhasil diperbarui.',
            'data' => $jobdesk,
        ]);
    }

    public function deleteJobdesk(int $jobdeskId, Request $request): JsonResponse
    {
        $jobdesk = RkmJobdesk::with('rkmMataKuliah.rkm')->findOrFail($jobdeskId);
        if ($jobdesk->rkmMataKuliah?->rkm?->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        if (! $jobdesk->canBeEditedByStudent()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_JOBDESK_MASTER_LOCKED',
                'message' => 'Jobdesk master mata kuliah bersifat permanen bagi mahasiswa dan hanya dapat disesuaikan oleh PIC Verifikator.',
            ], 403);
        }

        $jobdesk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jobdesk berhasil dihapus.',
        ]);
    }

    public function deleteItem(int $rkmMkId, Request $request): JsonResponse
    {
        $rkmMk = RkmMataKuliah::with('rkm')->findOrFail($rkmMkId);
        $rkm = $rkmMk->rkm;
        if ($rkm?->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $rkmMk->delete();

        $totalActiveSks = $rkm->items()->where('status_mk', 'aktif')->sum('sks');
        $rkm->update(['total_sks' => $totalActiveSks]);

        return response()->json([
            'success' => true,
            'message' => 'Mata kuliah berhasil dihapus dari RKM.',
            'data' => ['total_sks' => $totalActiveSks],
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $user = $request->user();

        $pendaftaran = $this->getPendaftaranMahasiswa($request);

        if (! $pendaftaran) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_PENDAFTARAN_NOT_FOUND',
                'message' => 'Anda belum memiliki data pendaftaran MBKM pada periode ini.',
            ], 404);
        }

        $rkm = Rkm::with('items')->where('pendaftaran_mbkm_id', $pendaftaran->id)->firstOrFail();

        if ($rkm->items()->where('status_mk', 'aktif')->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'RKM harus memiliki minimal satu mata kuliah aktif sebelum diajukan.',
            ], 422);
        }

        $rkm->update(['status' => 'Diajukan']);

        return response()->json([
            'success' => true,
            'message' => 'RKM berhasil diajukan ke PIC Verifikator.',
            'data' => [
                'rkm_id' => $rkm->id,
                'status' => $rkm->status,
            ],
        ]);
    }

    private function formatPendaftaran(PendaftaranMbkm $pendaftaran): array
    {
        return [
            'id' => $pendaftaran->id,
            'posisi_magang' => $pendaftaran->posisi_magang,
            'mitra_magang_text' => $pendaftaran->mitra_magang_text,
            'mitra' => $pendaftaran->mitra ? [
                'id' => $pendaftaran->mitra->id,
                'nama' => $pendaftaran->mitra->nama,
            ] : null,
            'mitra_nama' => $pendaftaran->mitra?->nama ?? $pendaftaran->mitra_magang_text,
            'pic_verifikator' => $pendaftaran->picVerifikator ? [
                'id' => $pendaftaran->picVerifikator->id,
                'nama' => $pendaftaran->picVerifikator->nama,
                'nip' => $pendaftaran->picVerifikator->nim_nip,
            ] : null,
            'pic_verifikator_nama' => $pendaftaran->picVerifikator?->nama,
        ];
    }

    private function getPendaftaranMahasiswa(Request $request, array $with = []): ?PendaftaranMbkm
    {
        $user = $request->user();

        if ($request->filled('pendaftaran_id')) {
            $query = PendaftaranMbkm::where('user_id', $user->id)
                ->where('id', $request->input('pendaftaran_id'));
            if (! empty($with)) {
                $query->with($with);
            }

            return $query->first();
        }

        if ($request->filled('periode_id') || $request->filled('periode_akademik_id')) {
            $periodeId = $request->input('periode_id') ?? $request->input('periode_akademik_id');
            $query = PendaftaranMbkm::where('user_id', $user->id)
                ->where('periode_akademik_id', $periodeId)
                ->latest();
            if (! empty($with)) {
                $query->with($with);
            }

            return $query->first();
        }

        $queryAktif = PendaftaranMbkm::where('user_id', $user->id)
            ->whereHas('periodeAkademik', fn ($q) => $q->where('is_aktif', true))
            ->latest();
        if (! empty($with)) {
            $queryAktif->with($with);
        }
        $pendaftaran = $queryAktif->first();

        if ($pendaftaran) {
            return $pendaftaran;
        }

        $fallbackQuery = PendaftaranMbkm::where('user_id', $user->id)->latest();
        if (! empty($with)) {
            $fallbackQuery->with($with);
        }

        return $fallbackQuery->first();
    }
}
