<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KuisionerJawaban;
use App\Models\KuisionerKategori;
use App\Models\KuisionerPertanyaan;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Services\KuisionerPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KuisionerController extends Controller
{
    public function __construct(
        private readonly KuisionerPeriodService $kuisionerPeriodService
    ) {}

    public function resolveTahunAjaran($tahunAjaranId = null): ?PeriodeAkademik
    {
        if (! empty($tahunAjaranId) && is_numeric($tahunAjaranId)) {
            $p = PeriodeAkademik::find($tahunAjaranId);
            if ($p) {
                return $p;
            }
        }

        $aktif = PeriodeAkademik::where('is_aktif', true)->first();
        if ($aktif) {
            return $aktif;
        }

        return PeriodeAkademik::orderBy('id', 'desc')->first();
    }

    private function resolvePeriodeFromRequest(Request $request): ?PeriodeAkademik
    {
        $rawId = $request->input('periode_akademik_id')
            ?? $request->input('periode_id')
            ?? $request->input('tahun_ajaran_id')
            ?? $request->query('periode_akademik_id')
            ?? $request->query('periode_id')
            ?? $request->query('tahun_ajaran_id');

        if (! empty($rawId) && is_numeric($rawId)) {
            $p = PeriodeAkademik::find((int) $rawId);
            if ($p) {
                return $p;
            }
        }

        $rawTa = trim((string) ($request->input('tahun_ajaran') ?? $request->query('tahun_ajaran') ?? ''));
        if ($rawTa !== '' && ! in_array(strtolower($rawTa), ['all', 'semua', 'semua tahun ajaran'], true)) {
            $parts = preg_split('/\s+/', $rawTa);
            $th = $parts[0] ?? null;
            $sem = $parts[1] ?? null;
            $p = PeriodeAkademik::where(function ($q) use ($th, $sem, $rawTa) {
                if ($th && $sem) {
                    $q->where('tahun_ajaran', $th)->where('semester', $sem);
                } else {
                    $q->where('tahun_ajaran', $rawTa);
                }
            })->first();

            if ($p) {
                return $p;
            }
        }

        return $this->resolveTahunAjaran(null);
    }

    public function ensureKuisionerTemplateForTahunAjaran($tahunAjaranId = null): ?PeriodeAkademik
    {
        $periode = $this->resolveTahunAjaran($tahunAjaranId);
        if (! $periode) {
            return null;
        }

        $this->kuisionerPeriodService->ensurePeriodeInitialized($periode);

        return $periode;
    }

    public function kategori(Request $request): JsonResponse
    {
        $periode = $this->resolvePeriodeFromRequest($request);
        if ($periode) {
            $this->kuisionerPeriodService->ensurePeriodeInitialized($periode);
        }

        $query = KuisionerKategori::query()
            ->when($periode, fn ($q) => $q->where('tahun_ajaran_id', $periode->id))
            ->withCount('pertanyaans');

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        $query->orderBy('urutan', 'asc');

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

    private function checkSessionLock(?PeriodeAkademik $periode = null): ?JsonResponse
    {
        $targetPeriode = $periode ?? $this->resolveTahunAjaran(null);
        if ($targetPeriode) {
            $sesi = \App\Models\KuisionerSesi::where('periode_akademik_id', $targetPeriode->id)->first();
            if ($sesi && $sesi->status === 'published') {
                return response()->json([
                    'success' => false,
                    'error_code' => 'ERR_KUISIONER_SESSION_LOCKED',
                    'message' => 'Template pertanyaan kuesioner tidak dapat diubah selama sesi sedang dibuka (Dipublikasikan).',
                ], 422);
            }
        }

        return null;
    }

    public function storeKategori(Request $request): JsonResponse
    {
        $periode = $this->resolvePeriodeFromRequest($request);
        if ($periode) {
            $this->kuisionerPeriodService->ensurePeriodeInitialized($periode);
        }
        if ($lock = $this->checkSessionLock($periode)) {
            return $lock;
        }

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'target_evaluasi' => ['nullable', 'string', 'in:dpl,mitra,umum'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['nama']);
        $baseSlug = $slug;
        $counter = 1;
        while (KuisionerKategori::where('slug', $slug)
            ->when($periode, fn ($q) => $q->where('tahun_ajaran_id', $periode->id))
            ->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $maxUrutan = KuisionerKategori::query()
            ->when($periode, fn ($q) => $q->where('tahun_ajaran_id', $periode->id))
            ->max('urutan') ?? 0;

        $kategori = KuisionerKategori::create([
            'tahun_ajaran_id' => $periode?->id,
            'tahun_ajaran' => $periode ? $this->kuisionerPeriodService->formatPeriodeLabel($periode) : null,
            'nama' => trim($data['nama']),
            'slug' => $slug,
            'target_evaluasi' => $data['target_evaluasi'] ?? 'umum',
            'is_active' => true,
            'urutan' => $maxUrutan + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori kuisioner berhasil ditambahkan.',
            'data' => $kategori,
        ], 201);
    }

    public function updateKategori(int $id, Request $request): JsonResponse
    {
        $kategori = KuisionerKategori::findOrFail($id);
        $oldSlug = $kategori->slug;
        $tahunAjaranId = $kategori->tahun_ajaran_id;
        $periode = $tahunAjaranId ? PeriodeAkademik::find($tahunAjaranId) : $this->resolvePeriodeFromRequest($request);

        if ($lock = $this->checkSessionLock($periode)) {
            return $lock;
        }

        $data = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'target_evaluasi' => ['sometimes', 'nullable', 'string', 'in:dpl,mitra,umum'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'urutan' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        if (isset($data['nama']) && empty($data['slug'])) {
            if (in_array($oldSlug, ['dpl', 'mitra', 'saran'])) {
                $data['slug'] = $oldSlug;
            } else {
                $baseSlug = Str::slug($data['nama']);
                $slug = $baseSlug;
                $counter = 1;
                while (KuisionerKategori::where('slug', $slug)
                    ->when($tahunAjaranId, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
                    ->where('id', '!=', $id)
                    ->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }
                $data['slug'] = $slug;
            }
        }

        if (isset($data['slug']) && $data['slug'] !== $oldSlug) {
            KuisionerPertanyaan::where('kategori_id', $id)
                ->update(['kategori' => $data['slug']]);
        }

        $kategori->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Kategori kuisioner berhasil diperbarui.',
            'data' => $kategori,
        ]);
    }

    public function destroyKategori(int $id): JsonResponse
    {
        $kategori = KuisionerKategori::findOrFail($id);
        $periode = $kategori->tahun_ajaran_id ? PeriodeAkademik::find($kategori->tahun_ajaran_id) : null;

        if ($lock = $this->checkSessionLock($periode)) {
            return $lock;
        }

        KuisionerPertanyaan::where('kategori_id', $id)->delete();
        $kategori->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori kuisioner berhasil dihapus.',
        ]);
    }

    public function pertanyaan(Request $request): JsonResponse
    {
        $periode = $this->resolvePeriodeFromRequest($request);
        if ($periode) {
            $this->kuisionerPeriodService->ensurePeriodeInitialized($periode);
        }

        $query = KuisionerPertanyaan::query();

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->input('kategori_id'));
        } elseif ($request->filled('kategori')) {
            $kat = $request->input('kategori');
            if ($periode) {
                $query->where('tahun_ajaran_id', $periode->id);
            }
            $query->where(function ($q) use ($kat) {
                $q->where('kategori', $kat)
                    ->orWhereHas('kategoriKategori', function ($sub) use ($kat) {
                        $sub->where('slug', $kat);
                    });
            });
        } elseif ($periode) {
            $query->where('tahun_ajaran_id', $periode->id);
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        $query->orderBy('nomor_urut', 'asc');

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

    public function storePertanyaan(Request $request): JsonResponse
    {
        $periode = $this->resolvePeriodeFromRequest($request);
        if ($periode) {
            $this->kuisionerPeriodService->ensurePeriodeInitialized($periode);
        }

        $data = $request->validate([
            'kategori' => ['nullable', 'string'],
            'kategori_id' => ['nullable', 'exists:kuisioner_kategoris,id'],
            'pertanyaan' => ['required', 'string'],
            'tipe_input' => ['nullable', 'string', 'in:likert_4,likert_5,checkbox,radio,textarea'],
            'opsi_pilihan' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $kategoriId = $data['kategori_id'] ?? null;
        $kategoriSlug = $data['kategori'] ?? null;
        $katObj = null;

        if ($kategoriId) {
            $katObj = KuisionerKategori::find($kategoriId);
            if (! $kategoriSlug) {
                $kategoriSlug = $katObj?->slug ?? 'umum';
            }
        } elseif ($kategoriSlug) {
            $katObj = KuisionerKategori::where('slug', $kategoriSlug)
                ->when($periode, fn ($q) => $q->where('tahun_ajaran_id', $periode->id))
                ->first();
            $kategoriId = $katObj?->id;
        }

        if ($katObj?->tahun_ajaran_id) {
            $periode = PeriodeAkademik::find($katObj->tahun_ajaran_id) ?? $periode;
        }

        if ($lock = $this->checkSessionLock($periode)) {
            return $lock;
        }

        if (! $kategoriSlug) {
            $kategoriSlug = 'dpl';
        }

        $maxUrut = KuisionerPertanyaan::where(function ($q) use ($kategoriId, $kategoriSlug, $periode) {
            if ($kategoriId) {
                $q->where('kategori_id', $kategoriId);
            } else {
                $q->where('kategori', $kategoriSlug)
                    ->when($periode, fn ($sub) => $sub->where('tahun_ajaran_id', $periode->id));
            }
        })->max('nomor_urut') ?? 0;

        $pertanyaan = KuisionerPertanyaan::create([
            'tahun_ajaran_id' => $periode?->id,
            'tahun_ajaran' => $periode ? $this->kuisionerPeriodService->formatPeriodeLabel($periode) : null,
            'kategori_id' => $kategoriId,
            'kategori' => $kategoriSlug,
            'nomor_urut' => $maxUrut + 1,
            'pertanyaan' => trim($data['pertanyaan']),
            'tipe_input' => $data['tipe_input'] ?? 'likert_5',
            'opsi_pilihan' => $data['opsi_pilihan'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pertanyaan kuisioner berhasil ditambahkan.',
            'data' => $pertanyaan,
        ], 201);
    }

    public function updatePertanyaan(int $id, Request $request): JsonResponse
    {
        $pertanyaan = KuisionerPertanyaan::findOrFail($id);
        $periode = $pertanyaan->tahun_ajaran_id
            ? PeriodeAkademik::find($pertanyaan->tahun_ajaran_id)
            : $this->resolvePeriodeFromRequest($request);

        if ($lock = $this->checkSessionLock($periode)) {
            return $lock;
        }

        $data = $request->validate([
            'pertanyaan' => ['sometimes', 'required', 'string'],
            'kategori' => ['sometimes', 'nullable', 'string'],
            'kategori_id' => ['sometimes', 'nullable', 'exists:kuisioner_kategoris,id'],
            'tipe_input' => ['nullable', 'string', 'in:likert_4,likert_5,checkbox,radio,textarea'],
            'opsi_pilihan' => ['nullable', 'array'],
            'nomor_urut' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($data['kategori_id'])) {
            $katObj = KuisionerKategori::find($data['kategori_id']);
            if ($katObj) {
                if (! isset($data['kategori'])) {
                    $data['kategori'] = $katObj->slug;
                }
                if ($katObj->tahun_ajaran_id) {
                    $data['tahun_ajaran_id'] = $katObj->tahun_ajaran_id;
                    $data['tahun_ajaran'] = $katObj->tahun_ajaran;
                }
            }
        }

        $pertanyaan->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Pertanyaan kuisioner berhasil diperbarui.',
            'data' => $pertanyaan,
        ]);
    }

    public function destroyPertanyaan(int $id): JsonResponse
    {
        $pertanyaan = KuisionerPertanyaan::findOrFail($id);
        $periode = $pertanyaan->tahun_ajaran_id ? PeriodeAkademik::find($pertanyaan->tahun_ajaran_id) : null;

        if ($lock = $this->checkSessionLock($periode)) {
            return $lock;
        }
        $kategoriId = $pertanyaan->kategori_id;
        $kategori = $pertanyaan->kategori;

        $pertanyaan->delete();

        $siblings = KuisionerPertanyaan::where(function ($q) use ($kategoriId, $kategori) {
            if ($kategoriId) {
                $q->where('kategori_id', $kategoriId);
            } else {
                $q->where('kategori', $kategori);
            }
        })
            ->orderBy('nomor_urut', 'asc')
            ->get();

        foreach ($siblings as $idx => $item) {
            $item->update(['nomor_urut' => $idx + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pertanyaan berhasil dihapus.',
        ]);
    }

    private function resolveJawabanScore(array $map, KuisionerPertanyaan $soal, ?int $jawabanTahunAjaranId, $siblingSoals): int
    {
        $direct = (int) ($map[$soal->id] ?? $map[(string) $soal->id] ?? 0);
        if ($direct >= 1 && $direct <= 4) {
            return $direct;
        }

        if ($jawabanTahunAjaranId) {
            $periodMatch = $siblingSoals->first(function ($candidate) use ($jawabanTahunAjaranId, $soal) {
                return (int) $candidate->tahun_ajaran_id === $jawabanTahunAjaranId
                    && (
                        mb_strtolower(trim((string) $candidate->pertanyaan)) === mb_strtolower(trim((string) $soal->pertanyaan))
                        || (int) $candidate->nomor_urut === (int) $soal->nomor_urut
                    );
            });
            if ($periodMatch) {
                $val = (int) ($map[$periodMatch->id] ?? $map[(string) $periodMatch->id] ?? 0);
                if ($val >= 1 && $val <= 4) {
                    return $val;
                }
            }
        }

        foreach ($siblingSoals as $candidate) {
            if (mb_strtolower(trim((string) $candidate->pertanyaan)) === mb_strtolower(trim((string) $soal->pertanyaan))) {
                $val = (int) ($map[$candidate->id] ?? $map[(string) $candidate->id] ?? 0);
                if ($val >= 1 && $val <= 4) {
                    return $val;
                }
            }
        }

        return 0;
    }

    public function rekap(Request $request): JsonResponse
    {
        $paramTahun = $request->input('tahun_ajaran') ?? $request->input('tahun_ajaran_id') ?? $request->input('periode_akademik_id') ?? $request->input('periode_id') ?? $request->query('tahun_ajaran') ?? $request->query('tahun_ajaran_id') ?? $request->query('periode_akademik_id') ?? $request->query('periode_id');
        $isFilterAll = empty($paramTahun) || in_array(strtolower(trim((string) $paramTahun)), ['all', 'semua tahun ajaran', 'semua'], true);

        $targetPeriode = null;
        $label = null;
        if (! $isFilterAll && ! empty($paramTahun)) {
            if (is_numeric($paramTahun)) {
                $targetPeriode = PeriodeAkademik::find((int) $paramTahun);
                $label = $targetPeriode ? trim("{$targetPeriode->tahun_ajaran} {$targetPeriode->semester}") : null;
            } else {
                $rawTa = trim((string) $paramTahun);
                $parts = preg_split('/\s+/', $rawTa);
                $th = $parts[0] ?? null;
                $sem = $parts[1] ?? null;
                $targetPeriode = PeriodeAkademik::where(function ($q) use ($th, $sem, $rawTa) {
                    if ($th && $sem) {
                        $q->where('tahun_ajaran', $th)->where('semester', $sem);
                    } else {
                        $q->where('tahun_ajaran', $rawTa);
                    }
                })->first();
                $label = $targetPeriode ? trim("{$targetPeriode->tahun_ajaran} {$targetPeriode->semester}") : $rawTa;
            }
        }

        $displayPeriode = $targetPeriode;
        if (! $displayPeriode && $isFilterAll) {
            $displayPeriode = PeriodeAkademik::where('is_aktif', true)->first()
                ?? PeriodeAkademik::orderByDesc('id')->first();
        }
        if ($displayPeriode) {
            $this->kuisionerPeriodService->ensurePeriodeInitialized($displayPeriode);
        }

        $soalDplQuery = KuisionerPertanyaan::where('kategori', 'dpl')
            ->where(fn ($q) => $q->whereNull('tipe_input')->orWhere('tipe_input', '!=', 'textarea'));
        $soalMitraQuery = KuisionerPertanyaan::where('kategori', 'mitra')
            ->where(fn ($q) => $q->whereNull('tipe_input')->orWhere('tipe_input', '!=', 'textarea'));

        if ($displayPeriode) {
            $soalDplQuery->where('tahun_ajaran_id', $displayPeriode->id);
            $soalMitraQuery->where('tahun_ajaran_id', $displayPeriode->id);
        }

        $soalDpl = $soalDplQuery->orderBy('nomor_urut')->get();
        $soalMitra = $soalMitraQuery->orderBy('nomor_urut')->get();

        $allSoalDpl = $isFilterAll
            ? KuisionerPertanyaan::where('kategori', 'dpl')
                ->where(fn ($q) => $q->whereNull('tipe_input')->orWhere('tipe_input', '!=', 'textarea'))
                ->get()
            : $soalDpl;
        $allSoalMitra = $isFilterAll
            ? KuisionerPertanyaan::where('kategori', 'mitra')
                ->where(fn ($q) => $q->whereNull('tipe_input')->orWhere('tipe_input', '!=', 'textarea'))
                ->get()
            : $soalMitra;

        $jawabanQuery = KuisionerJawaban::with(['dpl.dosen', 'mitra']);
        if (! $isFilterAll && ! empty($paramTahun)) {
            if ($targetPeriode) {
                $jawabanQuery->where(function ($q) use ($targetPeriode, $label) {
                    $q->where('tahun_ajaran_id', $targetPeriode->id);
                    if ($label) {
                        $q->orWhere(function ($sub) use ($label) {
                            $sub->whereNull('tahun_ajaran_id')->where('tahun_ajaran', $label);
                        });
                    }
                });
            } elseif ($label) {
                $jawabanQuery->where('tahun_ajaran', $label);
            }
        }
        $jawabans = $jawabanQuery->get();

        $getMutu = function (float $score): array {
            if ($score >= 3.25) {
                return ['label' => 'Sangat Baik', 'color' => 'emerald'];
            }
            if ($score >= 2.50) {
                return ['label' => 'Baik', 'color' => 'blue'];
            }
            if ($score >= 1.75) {
                return ['label' => 'Kurang', 'color' => 'amber'];
            }

            return ['label' => 'Sangat Kurang', 'color' => 'rose'];
        };

        $dplGroups = $jawabans->filter(fn ($j) => $j->dpl_user_id !== null)->groupBy('dpl_user_id');
        $rankingDpl = [];

        foreach ($dplGroups as $dplId => $list) {
            $dplUser = $list->first()->dpl;
            $namaDpl = $dplUser?->nama ?? 'DPL #'.$dplId;
            $nip = $dplUser?->nim_nip ?? $dplUser?->dosen?->nip ?? '-';
            $count = $list->count();

            $skorPerItem = [];
            $totalRataDpl = 0;
            $totalDistribusiDpl = ['1' => 0, '2' => 0, '3' => 0, '4' => 0];

            foreach ($soalDpl as $soal) {
                $scores = [];
                $distribusi = ['1' => 0, '2' => 0, '3' => 0, '4' => 0];

                foreach ($list as $jawaban) {
                    $map = $jawaban->jawaban_dpl ?? [];
                    $val = $this->resolveJawabanScore(
                        is_array($map) ? $map : [],
                        $soal,
                        $jawaban->tahun_ajaran_id ? (int) $jawaban->tahun_ajaran_id : null,
                        $allSoalDpl
                    );
                    if ($val >= 1 && $val <= 4) {
                        $scores[] = $val;
                        $distribusi[(string) $val]++;
                        $totalDistribusiDpl[(string) $val]++;
                    }
                }

                $avgItem = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0.00;
                $totalRataDpl += $avgItem;

                $skorPerItem[] = [
                    'pertanyaan_id' => $soal->id,
                    'nomor_urut' => $soal->nomor_urut,
                    'pertanyaan' => $soal->pertanyaan,
                    'rata_rata' => $avgItem,
                    'persentase' => round(($avgItem / 4.00) * 100, 1),
                    'distribusi' => $distribusi,
                ];
            }

            $rataRataTotal = count($soalDpl) > 0 ? round($totalRataDpl / count($soalDpl), 2) : 0.00;
            $mutu = $getMutu($rataRataTotal);

            $rankingDpl[] = [
                'dpl_id' => $dplId,
                'nama' => $namaDpl,
                'nip' => $nip,
                'jumlah_responden' => $count,
                'rata_rata' => $rataRataTotal,
                'persentase' => round(($rataRataTotal / 4.00) * 100, 1),
                'kategori_mutu' => $mutu['label'],
                'mutu_color' => $mutu['color'],
                'skor_per_pertanyaan' => $skorPerItem,
                'total_distribusi' => $totalDistribusiDpl,
            ];
        }

        usort($rankingDpl, fn ($a, $b) => $b['rata_rata'] <=> $a['rata_rata']);

        $mitraGroups = $jawabans->groupBy(function ($j) {
            return $j->mitra_id ? 'id_'.$j->mitra_id : 'name_'.($j->nama_mitra_snapshot ?? 'Lainnya');
        });
        $rankingMitra = [];

        foreach ($mitraGroups as $key => $list) {
            $mitraObj = $list->first()->mitra;
            $namaMitra = $mitraObj?->nama ?? $list->first()->nama_mitra_snapshot ?? 'Mitra Industri';
            $mitraId = $mitraObj?->id ?? null;
            $count = $list->count();

            $skorPerItem = [];
            $totalRataMitra = 0;
            $totalDistribusiMitra = ['1' => 0, '2' => 0, '3' => 0, '4' => 0];

            foreach ($soalMitra as $soal) {
                $scores = [];
                $distribusi = ['1' => 0, '2' => 0, '3' => 0, '4' => 0];

                foreach ($list as $jawaban) {
                    $map = $jawaban->jawaban_mitra ?? [];
                    $val = $this->resolveJawabanScore(
                        is_array($map) ? $map : [],
                        $soal,
                        $jawaban->tahun_ajaran_id ? (int) $jawaban->tahun_ajaran_id : null,
                        $allSoalMitra
                    );
                    if ($val >= 1 && $val <= 4) {
                        $scores[] = $val;
                        $distribusi[(string) $val]++;
                        $totalDistribusiMitra[(string) $val]++;
                    }
                }

                $avgItem = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0.00;
                $totalRataMitra += $avgItem;

                $skorPerItem[] = [
                    'pertanyaan_id' => $soal->id,
                    'nomor_urut' => $soal->nomor_urut,
                    'pertanyaan' => $soal->pertanyaan,
                    'rata_rata' => $avgItem,
                    'persentase' => round(($avgItem / 4.00) * 100, 1),
                    'distribusi' => $distribusi,
                ];
            }

            $rataRataTotal = count($soalMitra) > 0 ? round($totalRataMitra / count($soalMitra), 2) : 0.00;
            $mutu = $getMutu($rataRataTotal);

            $kompensasiCount = [];
            $nominalCount = [];
            $rincianResponden = [];
            $respNum = 1;

            foreach ($list as $jawaban) {
                $tipeArr = is_array($jawaban->kompensasi_tipe) ? $jawaban->kompensasi_tipe : [];
                $nomVal = $jawaban->kompensasi_nominal ?? 'Tidak ada';

                foreach ($tipeArr as $k) {
                    $kompensasiCount[$k] = ($kompensasiCount[$k] ?? 0) + 1;
                }
                if ($nomVal) {
                    $nominalCount[$nomVal] = ($nominalCount[$nomVal] ?? 0) + 1;
                }

                $rincianResponden[] = [
                    'responden_ke' => $respNum++,
                    'kompensasi_tipe' => $tipeArr,
                    'kompensasi_nominal' => $nomVal,
                ];
            }

            $filteredBenefits = array_filter(
                array_keys($kompensasiCount),
                fn ($b) => ! Str::contains(strtolower($b), 'tidak')
            );

            $topNominal = 'Tidak ada';
            if (! empty($nominalCount)) {
                arsort($nominalCount);
                $topNominal = array_key_first($nominalCount);
            }

            if (! empty($filteredBenefits)) {
                $benefitText = implode(', ', array_slice($filteredBenefits, 0, 3));
                $ringkasanKompensasi = $topNominal && $topNominal !== 'Tidak ada'
                    ? "{$benefitText} ({$topNominal})"
                    : $benefitText;
            } else {
                $ringkasanKompensasi = 'Tidak memberikan';
            }

            $rankingMitra[] = [
                'mitra_id' => $mitraId,
                'nama' => $namaMitra,
                'alamat' => $mitraObj?->alamat ?? '-',
                'jumlah_responden' => $count,
                'rata_rata' => $rataRataTotal,
                'persentase' => round(($rataRataTotal / 4.00) * 100, 1),
                'kategori_mutu' => $mutu['label'],
                'mutu_color' => $mutu['color'],
                'skor_per_pertanyaan' => $skorPerItem,
                'kompensasi_diterima' => array_values($filteredBenefits),
                'rata_rata_nominal' => $topNominal,
                'ringkasan_kompensasi' => $ringkasanKompensasi,
                'rincian_kompensasi_responden' => $rincianResponden,
                'total_distribusi' => $totalDistribusiMitra,
                'distribusi_kompensasi' => $kompensasiCount,
                'distribusi_nominal' => $nominalCount,
            ];
        }

        usort($rankingMitra, fn ($a, $b) => $b['rata_rata'] <=> $a['rata_rata']);

        $daftarSaranCollection = $jawabans->filter(fn ($j) => ! empty($j->saran_masukan))
            ->values()
            ->map(function ($j, $idx) {
                return [
                    'id' => $j->id,
                    'responden_label' => 'Responden Mahasiswa #'.($idx + 1),
                    'saran_masukan' => $j->saran_masukan,
                    'created_at' => $j->created_at?->format('d M Y') ?? '-',
                ];
            });

        $totalSaran = $daftarSaranCollection->count();
        $paginationSaran = null;

        if ($request->has('saran_page') || $request->has('saran_per_page')) {
            $sPage = max((int) $request->input('saran_page', 1), 1);
            $sPerPage = min(max((int) $request->input('saran_per_page', 10), 1), 100);
            $daftarSaran = $daftarSaranCollection->forPage($sPage, $sPerPage)->values()->all();
            $paginationSaran = [
                'current_page' => $sPage,
                'last_page' => (int) ceil($totalSaran / $sPerPage),
                'per_page' => $sPerPage,
                'total' => $totalSaran,
            ];
        } else {
            $daftarSaran = $daftarSaranCollection->all();
        }

        $totalResponden = $jawabans->count();
        $rataDplFakultas = count($rankingDpl) > 0
            ? round(array_sum(array_column($rankingDpl, 'rata_rata')) / count($rankingDpl), 2)
            : 0.00;
        $rataMitraFakultas = count($rankingMitra) > 0
            ? round(array_sum(array_column($rankingMitra, 'rata_rata')) / count($rankingMitra), 2)
            : 0.00;

        $allPeriode = PeriodeAkademik::orderBy('id', 'desc')->get()->map(fn ($p) => [
            'id' => $p->id,
            'tahun_ajaran' => $p->tahun_ajaran,
            'semester' => $p->semester,
            'is_aktif' => (bool) $p->is_aktif,
            'label' => trim("{$p->tahun_ajaran} {$p->semester}"),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'statistik' => [
                    'total_responden' => $totalResponden,
                    'total_dpl_dinilai' => count($rankingDpl),
                    'total_mitra_dinilai' => count($rankingMitra),
                    'rata_rata_fakultas_dpl' => $rataDplFakultas,
                    'rata_rata_fakultas_mitra' => $rataMitraFakultas,
                ],
                'ranking_dpl' => $rankingDpl,
                'ranking_mitra' => $rankingMitra,
                'daftar_saran' => $daftarSaran,
                'pagination_saran' => $paginationSaran,
                'tahun_ajaran_list' => $allPeriode,
                'tahun_ajarans' => $allPeriode,
                'selected_tahun_ajaran' => $isFilterAll ? 'Semua Tahun Ajaran' : $paramTahun,
            ],
        ]);
    }

    public function mahasiswaKuisioner(Request $request): JsonResponse
    {
        $user = $request->user();

        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->first();

        $pendaftaranQuery = PendaftaranMbkm::with([
            'mitra',
            'dpl',
            'programStudi',
            'periodeAkademik',
        ])->where(function ($q) use ($user) {
            $q->where('user_id', $user->id);
            if (! empty($user->nim_nip)) {
                $q->orWhere('nim', $user->nim_nip);
            }
        });

        $targetPeriode = $request->input('periode_id') ?? $request->input('periode_akademik_id');
        if (! $targetPeriode && $request->filled('tahun_ajaran')) {
            $rawTa = trim((string) $request->input('tahun_ajaran'));
            if (! in_array(strtolower($rawTa), ['semua', 'semua tahun ajaran', 'all'], true)) {
                $parts = preg_split('/\s+/', $rawTa);
                $th = $parts[0] ?? null;
                $sem = $parts[1] ?? null;
                $targetPeriode = PeriodeAkademik::where(function ($q) use ($th, $sem, $rawTa) {
                    if ($th && $sem) {
                        $q->where('tahun_ajaran', $th)->where('semester', $sem);
                    } else {
                        $q->where('tahun_ajaran', $rawTa);
                    }
                })->value('id');
            }
        }

        if ($request->filled('pendaftaran_mbkm_id') || $request->filled('pendaftaran_id')) {
            $targetId = $request->input('pendaftaran_mbkm_id') ?? $request->input('pendaftaran_id');
            $pendaftaran = (clone $pendaftaranQuery)->where('id', $targetId)->first();
        } elseif ($targetPeriode) {
            $pendaftaran = (clone $pendaftaranQuery)->where('periode_akademik_id', $targetPeriode)->latest()->first();
        } elseif ($periodeAktif) {
            $pendaftaran = (clone $pendaftaranQuery)->where('periode_akademik_id', $periodeAktif->id)->latest()->first();
        } else {
            $pendaftaran = (clone $pendaftaranQuery)->latest()->first();
        }

        $periode = $pendaftaran?->periodeAkademik
            ?? ($targetPeriode ? PeriodeAkademik::find($targetPeriode) : null)
            ?? $periodeAktif
            ?? PeriodeAkademik::orderByDesc('id')->first();

        $statusSesi = 'draft';
        if ($periode) {
            $sesi = $this->kuisionerPeriodService->ensurePeriodeInitialized($periode);
            $statusSesi = $sesi->status;
        }

        $tahunAjaranLabel = $pendaftaran?->tahun_ajaran
            ?? ($periode ? trim("{$periode->tahun_ajaran} {$periode->semester}") : '2026/2027 Ganjil');

        $kategoris = KuisionerKategori::where('is_active', true)
            ->when($periode, fn ($q) => $q->where('tahun_ajaran_id', $periode->id))
            ->orderBy('urutan', 'asc')
            ->get();

        $kategoriList = [];
        foreach ($kategoris as $kat) {
            $pertanyaans = KuisionerPertanyaan::where('is_active', true)
                ->when($periode, fn ($q) => $q->where('tahun_ajaran_id', $periode->id))
                ->where(function ($q) use ($kat) {
                    $q->where('kategori_id', $kat->id)
                        ->orWhere('kategori', $kat->slug);
                })
                ->orderBy('nomor_urut', 'asc')
                ->get();

            $kategoriList[] = [
                'id' => $kat->id,
                'nama' => $kat->nama,
                'slug' => $kat->slug,
                'target_evaluasi' => $kat->target_evaluasi,
                'urutan' => $kat->urutan,
                'is_active' => $kat->is_active,
                'pertanyaans' => $pertanyaans,
            ];
        }

        $existingJawaban = null;
        if ($pendaftaran?->id) {
            $existingJawaban = KuisionerJawaban::where('mahasiswa_user_id', $user->id)
                ->where(function ($q) use ($pendaftaran, $periode) {
                    $q->where('pendaftaran_mbkm_id', $pendaftaran->id);
                    if ($periode) {
                        $q->orWhere(function ($sub) use ($periode) {
                            $sub->whereNull('pendaftaran_mbkm_id')
                                ->where('tahun_ajaran_id', $periode->id);
                        });
                    }
                })
                ->latest()
                ->first();
        }

        $hasDpl = ! empty($pendaftaran?->dpl_id) && $pendaftaran?->dpl !== null;

        return response()->json([
            'success' => true,
            'data' => [
                'has_dpl' => $hasDpl,
                'status_sesi' => $statusSesi,
                'mahasiswa' => [
                    'id' => $user->id,
                    'name' => $user->nama ?? $user->name,
                    'nim' => $user->nim_nip ?? $pendaftaran?->nim ?? '-',
                    'prodi' => $pendaftaran?->programStudi?->nama ?? '-',
                ],
                'pendaftaran' => $pendaftaran ? [
                    'id' => $pendaftaran->id,
                    'posisi_magang' => $pendaftaran->posisi_magang ?? '-',
                    'jenis_kegiatan' => $pendaftaran->jenis_kegiatan ?? 'Magang MBKM',
                    'mitra_id' => $pendaftaran->mitra_id,
                    'nama_mitra' => $pendaftaran->mitra?->nama ?? $pendaftaran->mitra_magang_text ?? 'Mitra MBKM',
                    'dpl_id' => $pendaftaran->dpl_id,
                    'nama_dpl' => $pendaftaran->dpl?->nama ?? $pendaftaran->dpl?->name ?? 'Belum ditentukan',
                    'nip_dpl' => $pendaftaran->dpl?->nim_nip ?? $pendaftaran->dpl?->dosen?->nip ?? '-',
                    'tahun_ajaran' => $tahunAjaranLabel,
                ] : null,
                'kategoris' => $kategoriList,
                'has_submitted' => (bool) ($pendaftaran?->sudah_isi_kuisioner || $existingJawaban !== null),
                'jawaban' => $existingJawaban,
            ],
        ]);
    }

    public function storeJawaban(Request $request): JsonResponse
    {
        $user = $request->user();

        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->first();

        $pendaftaranQuery = PendaftaranMbkm::with(['mitra', 'dpl', 'periodeAkademik'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if (! empty($user->nim_nip)) {
                    $q->orWhere('nim', $user->nim_nip);
                }
            });

        if ($request->filled('pendaftaran_mbkm_id') || $request->filled('pendaftaran_id')) {
            $targetId = $request->input('pendaftaran_mbkm_id') ?? $request->input('pendaftaran_id');
            $pendaftaran = (clone $pendaftaranQuery)->where('id', $targetId)->first();
        } elseif ($periodeAktif) {
            $pendaftaran = (clone $pendaftaranQuery)->where('periode_akademik_id', $periodeAktif->id)->latest()->first();
        } else {
            $pendaftaran = (clone $pendaftaranQuery)->latest()->first();
        }

        if (! $pendaftaran || empty($pendaftaran->dpl_id) || ! $pendaftaran->dpl) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum dapat mengisi kuisioner karena Dosen Pembimbing Lapangan (DPL) belum ditentukan.',
            ], 403);
        }

        $periode = $pendaftaran->periodeAkademik ?? PeriodeAkademik::where('is_aktif', true)->first();
        if ($periode) {
            $sesi = \App\Models\KuisionerSesi::where('periode_akademik_id', $periode->id)->first();
            if (! $sesi || $sesi->status !== 'published') {
                return response()->json([
                    'success' => false,
                    'error_code' => 'ERR_KUISIONER_SESSION_NOT_PUBLISHED',
                    'message' => 'Sesi pengisian kuisioner evaluasi belum dibuka atau telah ditutup.',
                ], 403);
            }
        }

        $alreadyHasJawaban = KuisionerJawaban::where('mahasiswa_user_id', $user->id)
            ->where('pendaftaran_mbkm_id', $pendaftaran->id)
            ->exists();

        if ($pendaftaran->sudah_isi_kuisioner || $alreadyHasJawaban) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_KUISIONER_ALREADY_SUBMITTED',
                'message' => 'Anda telah menyelesaikan pengisian kuisioner evaluasi pada semester ini.',
            ], 422);
        }

        $tahunAjaranLabel = $pendaftaran->tahun_ajaran
            ?? ($periode ? trim("{$periode->tahun_ajaran} {$periode->semester}") : '2026/2027 Ganjil');
        $tahunAjaranId = $periode?->id;

        $data = $request->validate([
            'jawaban' => ['nullable', 'array'],
            'jawaban.*.pertanyaan_id' => ['nullable', 'integer'],
            'jawaban.*.skor' => ['nullable', 'integer', 'between:1,4'],
            'jawaban_dpl' => ['nullable', 'array'],
            'jawaban_mitra' => ['nullable', 'array'],
            'kompensasi_tipe' => ['nullable', 'array'],
            'kompensasi_nominal' => ['nullable', 'string', 'max:255'],
            'saran_masukan' => ['nullable', 'string', 'max:5000'],
        ]);

        $jawabanDpl = $data['jawaban_dpl'] ?? [];
        $jawabanMitra = $data['jawaban_mitra'] ?? [];

        if ($request->has('jawaban') && is_array($request->input('jawaban'))) {
            foreach ($request->input('jawaban') as $item) {
                if (isset($item['pertanyaan_id']) && isset($item['skor'])) {
                    $pId = $item['pertanyaan_id'];
                    $skor = $item['skor'];
                    $soal = KuisionerPertanyaan::with('kategoriKategori')->find($pId);
                    if ($soal && ($soal->kategori === 'dpl' || $soal->kategoriKategori?->target_evaluasi === 'dpl')) {
                        $jawabanDpl[$pId] = $skor;
                    } else {
                        $jawabanMitra[$pId] = $skor;
                    }
                }
            }
        }

        $allScores = array_merge(array_values($jawabanDpl), array_values($jawabanMitra));
        foreach ($allScores as $s) {
            if (! is_numeric($s) || (int) $s < 1 || (int) $s > 4) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'ERR_VALIDATION_FAILED',
                    'message' => 'Skor kuisioner harus bernilai antara 1 sampai 4.',
                    'errors' => ['skor' => ['Nilai skor harus bernilai antara 1 sampai 4.']],
                ], 422);
            }
        }

        $dplScores = array_filter(array_values($jawabanDpl), fn ($v) => is_numeric($v) && $v >= 1 && $v <= 4);
        $rataRataDpl = count($dplScores) > 0 ? round(array_sum($dplScores) / count($dplScores), 2) : 0.00;

        $mitraScores = array_filter(array_values($jawabanMitra), fn ($v) => is_numeric($v) && $v >= 1 && $v <= 4);
        $rataRataMitra = count($mitraScores) > 0 ? round(array_sum($mitraScores) / count($mitraScores), 2) : 0.00;

        $namaMitra = $pendaftaran?->mitra?->nama ?? $pendaftaran?->mitra_magang_text ?? 'Mitra Magang';

        $jawaban = \Illuminate\Support\Facades\DB::transaction(function () use ($user, $pendaftaran, $tahunAjaranId, $tahunAjaranLabel, $namaMitra, $jawabanDpl, $jawabanMitra, $rataRataDpl, $rataRataMitra, $data) {
            $j = KuisionerJawaban::updateOrCreate(
                [
                    'mahasiswa_user_id' => $user->id,
                    'pendaftaran_mbkm_id' => $pendaftaran->id,
                ],
                [
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'tahun_ajaran' => $tahunAjaranLabel,
                    'dpl_user_id' => $pendaftaran->dpl_id,
                    'mitra_id' => $pendaftaran->mitra_id,
                    'nama_mitra_snapshot' => $namaMitra,
                    'jawaban_dpl' => $jawabanDpl,
                    'jawaban_mitra' => $jawabanMitra,
                    'rata_rata_dpl' => $rataRataDpl,
                    'rata_rata_mitra' => $rataRataMitra,
                    'kompensasi_tipe' => $data['kompensasi_tipe'] ?? [],
                    'kompensasi_nominal' => $data['kompensasi_nominal'] ?? 'Tidak ada',
                    'saran_masukan' => $data['saran_masukan'] ?? null,
                ]
            );

            $pendaftaran->update([
                'sudah_isi_kuisioner' => true,
                'kuisioner_submitted_at' => now(),
            ]);

            return $j;
        });

        return response()->json([
            'success' => true,
            'message' => 'Evaluasi kuisioner berhasil dikirim. Terima kasih atas partisipasi Anda!',
            'data' => $jawaban,
        ], 200);
    }

    public function indexJawaban(Request $request): JsonResponse
    {
        $query = KuisionerJawaban::with(['mahasiswa', 'dpl.dosen', 'mitra', 'pendaftaranMbkm']);

        $periodeId = $request->input('periode_akademik_id') ?? $request->input('periode_id') ?? $request->input('tahun_ajaran_id');
        if (! empty($periodeId) && is_numeric($periodeId)) {
            $query->where('tahun_ajaran_id', (int) $periodeId);
        } elseif ($request->filled('tahun_ajaran')) {
            $ta = trim((string) $request->input('tahun_ajaran'));
            if (! in_array(strtolower($ta), ['all', 'semua tahun ajaran', 'semua'], true)) {
                $parts = preg_split('/\s+/', $ta);
                $th = $parts[0] ?? null;
                $sem = $parts[1] ?? null;
                $p = PeriodeAkademik::where(function ($q) use ($th, $sem, $ta) {
                    if ($th && $sem) {
                        $q->where('tahun_ajaran', $th)->where('semester', $sem);
                    } else {
                        $q->where('tahun_ajaran', $ta);
                    }
                })->first();

                $label = $p ? trim("{$p->tahun_ajaran} {$p->semester}") : $ta;
                $query->where(function ($q) use ($p, $label) {
                    $q->where('tahun_ajaran', $label);
                    if ($p) {
                        $q->orWhere('tahun_ajaran_id', $p->id);
                    }
                });
            }
        }

        if ($request->filled('dpl_user_id')) {
            $query->where('dpl_user_id', $request->input('dpl_user_id'));
        }

        if ($request->filled('mitra_id')) {
            $query->where('mitra_id', $request->input('mitra_id'));
        }

        $query->orderBy('id', 'desc');

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
}
