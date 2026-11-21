<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\MonevMitra;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\PlottingPemonevMitra;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MonevMitraController extends Controller
{
    private const TAHAP_MONEV = ['Minggu ke-0', 'Minggu ke-8', 'Minggu ke-18'];

    private const TAHAP_KEY_MAP = [
        'Minggu ke-0' => 'minggu_0',
        'Minggu ke-8' => 'minggu_8',
        'Minggu ke-18' => 'minggu_18',
    ];

    private function resolvePeriode(Request $request): ?PeriodeAkademik
    {
        if ($request->filled('periode_akademik_id')) {
            return PeriodeAkademik::find($request->input('periode_akademik_id'));
        }
        if ($request->filled('periode_id')) {
            return PeriodeAkademik::find($request->input('periode_id'));
        }
        if ($request->filled('tahun_ajaran')) {
            $ta = trim($request->input('tahun_ajaran'));
            if ($ta && ! in_array($ta, ['Semua', 'Semua Tahun Ajaran', 'all'], true)) {
                $parts = preg_split('/\s+/', $ta);
                $th = $parts[0] ?? null;
                $sem = $parts[1] ?? null;
                $found = PeriodeAkademik::where(function ($q) use ($th, $sem, $ta) {
                    if ($th && $sem) {
                        $q->where('tahun_ajaran', $th)->where('semester', $sem);
                    } else {
                        $q->where('tahun_ajaran', $ta);
                    }
                })->first();
                if ($found) {
                    return $found;
                }
            }
        }

        return PeriodeAkademik::where('is_aktif', true)->first()
            ?? PeriodeAkademik::latest('id')->first();
    }

    /**
     * Mengambil daftar mitra aktif & kandidat pemonev untuk Tab Plotting (Ketua Tim MBKM & Admin)
     */
    public function plottingList(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->hasRole('Ketua Tim MBKM', 'Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Ketua Tim MBKM dan Admin yang memiliki akses ke data plotting pemonev mitra.',
            ], 403);
        }

        $periode = $this->resolvePeriode($request);
        if (! $periode) {
            return response()->json(['success' => true, 'data' => []]);
        }

        // Ambil seluruh pendaftaran aktif pada periode terkait yang memiliki mitra_id
        $pendaftaranQuery = PendaftaranMbkm::with(['user.mahasiswa', 'programStudi', 'dpl.dosen'])
            ->whereIn('status', ['Terverifikasi', 'Disetujui'])
            ->where('periode_akademik_id', $periode->id)
            ->whereNotNull('mitra_id');

        $activeMitraIds = (clone $pendaftaranQuery)->distinct()->pluck('mitra_id');

        $mitrasQuery = Mitra::whereIn('id', $activeMitraIds);
        if ($request->filled('search')) {
            $search = $request->input('search');
            $mitrasQuery->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        $mitras = $mitrasQuery->orderBy('nama')->get();

        // Seluruh user Tim MBKM & Ketua Tim MBKM sebagai kandidat global
        $timMbkmUsers = User::with('dosen')->whereHas('roles', function ($q) {
            $q->whereIn('name', ['Tim MBKM', 'Ketua Tim MBKM']);
        })->get();

        $allPendaftarans = $pendaftaranQuery->get()->groupBy('mitra_id');

        // Ambil seluruh plotting pemonev pada periode terkait
        $allPlottings = PlottingPemonevMitra::with(['dosen.dosen'])
            ->where('periode_akademik_id', $periode->id)
            ->get()
            ->groupBy('mitra_id');

        // Ambil seluruh data monev pada periode terkait
        $allMonevs = MonevMitra::with('filledBy')
            ->where('periode_akademik_id', $periode->id)
            ->get()
            ->groupBy('mitra_id');

        $result = $mitras->map(function (Mitra $mitra) use ($periode, $allPendaftarans, $allPlottings, $allMonevs, $timMbkmUsers) {
            $pendaftarList = $allPendaftarans->get($mitra->id, collect());

            // Mahasiswa aktif di mitra ini
            $mahasiswaList = $pendaftarList->map(function (PendaftaranMbkm $p) {
                return [
                    'id' => $p->id,
                    'nim' => $p->nim,
                    'nama_lengkap' => $p->nama_lengkap,
                    'program_studi' => $p->programStudi?->nama ?? '-',
                    'posisi_magang' => $p->posisi_magang ?? '-',
                    'dpl_id' => $p->dpl_id,
                    'dpl_nama' => $p->dpl?->nama ?? '-',
                ];
            });

            // Pemonev yang sudah diplot
            $currentPemonevs = $allPlottings->get($mitra->id, collect())->map(function (PlottingPemonevMitra $plot) {
                return [
                    'id' => $plot->dosen_id,
                    'nama' => $plot->dosen?->nama,
                    'nip' => $plot->dosen?->nim_nip ?? $plot->dosen?->dosen?->nip ?? '-',
                    'email' => $plot->dosen?->email,
                ];
            });

            $currentPemonevIds = $currentPemonevs->pluck('id')->all();

            // Status keterisian Monev (3 tahap)
            $mitraMonevs = $allMonevs->get($mitra->id, collect());
            $monevStatus = [];
            $totalSelesai = 0;
            foreach (self::TAHAP_MONEV as $tahap) {
                $m = $mitraMonevs->firstWhere('periode_monev', $tahap);
                $isFilled = $m !== null;
                if ($isFilled) {
                    $totalSelesai++;
                }
                $key = self::TAHAP_KEY_MAP[$tahap] ?? 'minggu_0';
                $monevStatus[$key] = [
                    'periode_monev' => $tahap,
                    'is_filled' => $isFilled,
                    'status_text' => $isFilled ? 'Selesai' : 'Belum Diisi',
                    'tanggal_pelaksanaan' => $m?->tanggal_pelaksanaan?->format('Y-m-d'),
                    'metode_pelaksanaan' => $m?->metode_pelaksanaan,
                    'filled_by' => $m?->filledBy?->nama,
                ];
            }

            // Kandidat Dosen Gabungan (DPL Mitra + Tim MBKM)
            $dplUsers = $pendaftarList->pluck('dpl')->filter()->unique('id');
            $candidatesMap = collect();

            // 1. Masukkan DPL Mitra
            foreach ($dplUsers as $dpl) {
                $isTimMbkm = $dpl->hasRole('Tim MBKM', 'Ketua Tim MBKM');
                $kategori = $isTimMbkm ? 'DPL Mitra & Tim MBKM' : 'DPL Mitra';
                $candidatesMap->put($dpl->id, [
                    'id' => $dpl->id,
                    'nama' => $dpl->nama,
                    'nip' => $dpl->nim_nip ?? $dpl->dosen?->nip ?? '-',
                    'email' => $dpl->email,
                    'kategori' => $kategori,
                    'is_dpl_mitra' => true,
                    'is_tim_mbkm' => $isTimMbkm,
                    'is_selected' => in_array($dpl->id, $currentPemonevIds, true),
                ]);
            }

            // 2. Masukkan Anggota Tim MBKM lainnya
            foreach ($timMbkmUsers as $tUser) {
                if (! $candidatesMap->has($tUser->id)) {
                    $candidatesMap->put($tUser->id, [
                        'id' => $tUser->id,
                        'nama' => $tUser->nama,
                        'nip' => $tUser->nim_nip ?? $tUser->dosen?->nip ?? '-',
                        'email' => $tUser->email,
                        'kategori' => 'Tim MBKM',
                        'is_dpl_mitra' => false,
                        'is_tim_mbkm' => true,
                        'is_selected' => in_array($tUser->id, $currentPemonevIds, true),
                    ]);
                }
            }

            return [
                'mitra_id' => $mitra->id,
                'id' => $mitra->id,
                'nama_mitra' => $mitra->nama,
                'nama' => $mitra->nama,
                'alamat' => $mitra->alamat ?? '-',
                'jumlah_mahasiswa' => $mahasiswaList->count(),
                'mahasiswa' => $mahasiswaList->values(),
                'pemonev' => $currentPemonevs->values(),
                'pemonev_count' => $currentPemonevs->count(),
                'is_pemonev_locked' => $totalSelesai > 0,
                'periode_akademik_id' => $periode->id,
                'tahun_ajaran' => $periode->nama_lengkap,
                'monev_status' => $monevStatus,
                'progres_monev' => "{$totalSelesai} / 3 Selesai",
                'kandidat_dosen' => $candidatesMap->values(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $result->values(),
        ]);
    }

    /**
     * Menyimpan / Memperbarui Plotting Pemonev Mitra (Maksimal 2 Dosen)
     */
    public function storePlotting(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->hasRole('Ketua Tim MBKM', 'Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Ketua Tim MBKM dan Admin yang memiliki wewenang menetapkan dosen pemonev mitra.',
            ], 403);
        }

        $data = $request->validate([
            'mitra_id' => ['required', 'exists:mitras,id'],
            'periode_akademik_id' => ['nullable', 'exists:periode_akademiks,id'],
            'dosen_ids' => ['present', 'array'],
            'dosen_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $periode = $this->resolvePeriode($request);
        if (! $periode) {
            return response()->json([
                'success' => false,
                'message' => 'Tahun ajaran aktif tidak ditemukan.',
            ], 422);
        }

        $mitraId = (int) $data['mitra_id'];
        $dosenIds = array_values(array_unique(array_map('intval', $data['dosen_ids'])));

        if (count($dosenIds) > 2) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_MAX_PEMONEV_EXCEEDED',
                'message' => 'Maksimal 2 dosen pemonev untuk setiap mitra magang.',
                'errors' => ['dosen_ids' => ['Jumlah dosen pemonev tidak boleh melebihi 2 orang.']],
            ], 422);
        }

        // Validasi bahwa dosen yang dipilih adalah kandidat yang sah (DPL mitra tersebut ATAU Tim MBKM)
        if (! empty($dosenIds)) {
            $validDplIds = PendaftaranMbkm::where('mitra_id', $mitraId)
                ->where('periode_akademik_id', $periode->id)
                ->whereIn('status', ['Terverifikasi', 'Disetujui'])
                ->pluck('dpl_id')
                ->filter()
                ->all();

            $validTimMbkmIds = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['Tim MBKM', 'Ketua Tim MBKM']);
            })->pluck('id')->all();

            $allowedCandidateIds = array_unique(array_merge($validDplIds, $validTimMbkmIds));

            foreach ($dosenIds as $did) {
                if (! in_array($did, $allowedCandidateIds, true)) {
                    return response()->json([
                        'success' => false,
                        'error_code' => 'ERR_INVALID_PEMONEV_CANDIDATE',
                        'message' => 'Dosen pemonev harus berasal dari DPL mahasiswa di mitra terkait atau anggota Tim MBKM.',
                        'errors' => ['dosen_ids' => ["Dosen ID {$did} tidak termasuk dalam kandidat pemonev mitra ini."]],
                    ], 422);
                }
            }
        }

        // Cek penguncian Pemonev Mitra: Tidak dapat diubah jika pelaksanaan Monev Mitra sudah berjalan
        $hasExistingMonev = MonevMitra::where('mitra_id', $mitraId)
            ->where('periode_akademik_id', $periode->id)
            ->exists();

        if ($hasExistingMonev) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_PLOTTING_PEMONEV_LOCKED',
                'message' => 'Dosen Pemonev tidak dapat diubah karena pelaksanaan Monev Mitra sudah berjalan.',
                'errors' => [
                    'mitra_id' => ['Dosen Pemonev tidak dapat diubah karena pelaksanaan Monev Mitra sudah berjalan.'],
                ],
            ], 422);
        }

        DB::transaction(function () use ($mitraId, $periode, $dosenIds, $user) {
            PlottingPemonevMitra::where('mitra_id', $mitraId)
                ->where('periode_akademik_id', $periode->id)
                ->delete();

            foreach ($dosenIds as $dosenId) {
                PlottingPemonevMitra::create([
                    'mitra_id' => $mitraId,
                    'periode_akademik_id' => $periode->id,
                    'dosen_id' => $dosenId,
                    'assigned_by' => $user->id,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Plotting Dosen Pemonev Mitra berhasil disimpan!',
        ]);
    }

    /**
     * Mengambil daftar mitra penugasan monev untuk user yang login
     * Mendukung ?scope=all khusus untuk Ketua Tim MBKM / Admin (Rekap Seluruh Mitra)
     */
    public function myAssignments(Request $request): JsonResponse
    {
        $user = $request->user();
        $periode = $this->resolvePeriode($request);

        if (! $periode) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $scope = $request->query('scope', 'assigned');
        $isKetuaOrAdmin = $user->hasRole('Ketua Tim MBKM', 'Admin');

        // Jika scope=all dan role berwenang, tampilkan seluruh mitra aktif
        if ($scope === 'all' && $isKetuaOrAdmin) {
            $activeMitraIds = PendaftaranMbkm::whereIn('status', ['Terverifikasi', 'Disetujui'])
                ->where('periode_akademik_id', $periode->id)
                ->whereNotNull('mitra_id')
                ->distinct()
                ->pluck('mitra_id');
        } else {
            // Default: hanya mitra yang di-plotting ke user yang sedang login
            $activeMitraIds = PlottingPemonevMitra::where('periode_akademik_id', $periode->id)
                ->where('dosen_id', $user->id)
                ->pluck('mitra_id');
        }

        $mitrasQuery = Mitra::whereIn('id', $activeMitraIds);
        if ($request->filled('search')) {
            $search = $request->input('search');
            $mitrasQuery->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        $mitras = $mitrasQuery->orderBy('nama')->get();

        $allPendaftarans = PendaftaranMbkm::with(['user.mahasiswa', 'programStudi', 'dpl.dosen'])
            ->whereIn('status', ['Terverifikasi', 'Disetujui'])
            ->where('periode_akademik_id', $periode->id)
            ->whereIn('mitra_id', $activeMitraIds)
            ->get()
            ->groupBy('mitra_id');

        $allPlottings = PlottingPemonevMitra::with(['dosen.dosen'])
            ->where('periode_akademik_id', $periode->id)
            ->whereIn('mitra_id', $activeMitraIds)
            ->get()
            ->groupBy('mitra_id');

        $allMonevs = MonevMitra::with('filledBy')
            ->where('periode_akademik_id', $periode->id)
            ->whereIn('mitra_id', $activeMitraIds)
            ->get()
            ->groupBy('mitra_id');

        $result = $mitras->map(function (Mitra $mitra) use ($periode, $user, $allPendaftarans, $allPlottings, $allMonevs) {
            $pendaftarList = $allPendaftarans->get($mitra->id, collect());
            $currentPemonevs = $allPlottings->get($mitra->id, collect());
            $pemonevIds = $currentPemonevs->pluck('dosen_id')->all();

            $isAssignedPemonev = in_array($user->id, $pemonevIds, true);
            $canEdit = $isAssignedPemonev && (bool) $periode->is_aktif;

            $mitraMonevs = $allMonevs->get($mitra->id, collect());
            $monevStatus = [];
            $totalSelesai = 0;

            foreach (self::TAHAP_MONEV as $tahap) {
                $m = $mitraMonevs->firstWhere('periode_monev', $tahap);
                $isFilled = $m !== null;
                if ($isFilled) {
                    $totalSelesai++;
                }
                $key = self::TAHAP_KEY_MAP[$tahap] ?? 'minggu_0';
                $monevStatus[$key] = [
                    'id' => $m?->id,
                    'periode_monev' => $tahap,
                    'is_filled' => $isFilled,
                    'status_text' => $isFilled ? 'Selesai' : 'Belum Diisi',
                    'tanggal_pelaksanaan' => $m?->tanggal_pelaksanaan?->format('Y-m-d'),
                    'metode_pelaksanaan' => $m?->metode_pelaksanaan,
                    'catatan_evaluasi' => $m?->catatan_evaluasi,
                    'kendala_tindak_lanjut' => $m?->kendala_tindak_lanjut,
                    'dokumentasi_name' => $m?->dokumentasi_name,
                    'dokumentasi_url' => $m?->dokumentasi_url,
                    'filled_by' => $m?->filledBy?->nama,
                    'filled_by_id' => $m?->filled_by_id,
                    'updated_at' => $m?->updated_at?->toISOString(),
                ];
            }

            return [
                'mitra_id' => $mitra->id,
                'id' => $mitra->id,
                'nama_mitra' => $mitra->nama,
                'nama' => $mitra->nama,
                'alamat' => $mitra->alamat ?? '-',
                'jumlah_mahasiswa' => $pendaftarList->count(),
                'pemonev' => $currentPemonevs->map(fn ($p) => [
                    'id' => $p->dosen_id,
                    'nama' => $p->dosen?->nama,
                    'nip' => $p->dosen?->nim_nip ?? $p->dosen?->dosen?->nip ?? '-',
                ])->values(),
                'is_assigned' => $isAssignedPemonev,
                'can_edit' => $canEdit,
                'periode_akademik_id' => $periode->id,
                'tahun_ajaran' => $periode->nama_lengkap,
                'monev_status' => $monevStatus,
                'progres_monev' => "{$totalSelesai} / 3 Selesai",
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $result->values(),
        ]);
    }

    /**
     * Mengambil detail mitra dan riwayat 3 tahap monev
     */
    public function show(Request $request, int $mitraId): JsonResponse
    {
        $user = $request->user();
        $periode = $this->resolvePeriode($request);

        if (! $periode) {
            return response()->json(['success' => false, 'message' => 'Tahun ajaran tidak ditemukan.'], 404);
        }

        $mitra = Mitra::findOrFail($mitraId);

        $pemonevPlottings = PlottingPemonevMitra::with(['dosen.dosen'])
            ->where('mitra_id', $mitraId)
            ->where('periode_akademik_id', $periode->id)
            ->get();

        $pemonevIds = $pemonevPlottings->pluck('dosen_id')->all();
        $isAssigned = in_array($user->id, $pemonevIds, true);
        $isPrivileged = $user->hasRole('Ketua Tim MBKM', 'Admin');

        if (! $isAssigned && ! $isPrivileged) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat data Monev mitra ini.',
            ], 403);
        }

        $canEdit = $isAssigned && (bool) $periode->is_aktif;

        $pendaftarList = PendaftaranMbkm::with(['user.mahasiswa', 'programStudi', 'dpl.dosen'])
            ->where('mitra_id', $mitraId)
            ->where('periode_akademik_id', $periode->id)
            ->whereIn('status', ['Terverifikasi', 'Disetujui'])
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nim' => $p->nim,
                    'nama_lengkap' => $p->nama_lengkap,
                    'program_studi' => $p->programStudi?->nama ?? '-',
                    'posisi_magang' => $p->posisi_magang ?? '-',
                    'dpl' => $p->dpl?->nama ?? '-',
                ];
            });

        $mitraMonevs = MonevMitra::with('filledBy')
            ->where('mitra_id', $mitraId)
            ->where('periode_akademik_id', $periode->id)
            ->get();

        $tahapData = [];
        foreach (self::TAHAP_MONEV as $tahap) {
            $m = $mitraMonevs->firstWhere('periode_monev', $tahap);
            $key = self::TAHAP_KEY_MAP[$tahap] ?? 'minggu_0';
            $tahapData[$key] = [
                'id' => $m?->id,
                'periode_monev' => $tahap,
                'is_filled' => $m !== null,
                'status_text' => $m !== null ? 'Selesai' : 'Belum Diisi',
                'tanggal_pelaksanaan' => $m?->tanggal_pelaksanaan?->format('Y-m-d'),
                'metode_pelaksanaan' => $m?->metode_pelaksanaan,
                'catatan_evaluasi' => $m?->catatan_evaluasi,
                'kendala_tindak_lanjut' => $m?->kendala_tindak_lanjut,
                'dokumentasi_name' => $m?->dokumentasi_name,
                'dokumentasi_url' => $m?->dokumentasi_url,
                'filled_by' => $m?->filledBy?->nama,
                'filled_by_id' => $m?->filled_by_id,
                'updated_at' => $m?->updated_at?->toISOString(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'mitra' => [
                    'id' => $mitra->id,
                    'nama' => $mitra->nama,
                    'alamat' => $mitra->alamat ?? '-',
                    'kontak' => $mitra->kontak ?? '-',
                    'email' => $mitra->email ?? '-',
                ],
                'periode_akademik' => [
                    'id' => $periode->id,
                    'nama_lengkap' => $periode->nama_lengkap,
                    'is_aktif' => (bool) $periode->is_aktif,
                ],
                'pemonev' => $pemonevPlottings->map(fn ($p) => [
                    'id' => $p->dosen_id,
                    'nama' => $p->dosen?->nama,
                    'nip' => $p->dosen?->nim_nip ?? $p->dosen?->dosen?->nip ?? '-',
                ])->values(),
                'is_assigned' => $isAssigned,
                'can_edit' => $canEdit,
                'mahasiswa' => $pendaftarList->values(),
                'monevs' => $tahapData,
            ],
        ]);
    }

    /**
     * Menyimpan hasil evaluasi Monev Mitra per tahap
     */
    public function submit(Request $request, int $mitraId): JsonResponse
    {
        $user = $request->user();
        $periode = $this->resolvePeriode($request);

        if (! $periode) {
            return response()->json(['success' => false, 'message' => 'Tahun ajaran aktif tidak ditemukan.'], 422);
        }

        // Cek otorisasi penugasan pemonev
        $isAssigned = PlottingPemonevMitra::where('mitra_id', $mitraId)
            ->where('periode_akademik_id', $periode->id)
            ->where('dosen_id', $user->id)
            ->exists();

        if (! $isAssigned) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_NOT_ASSIGNED_PEMONEV',
                'message' => 'Anda tidak memiliki hak pengisian karena bukan dosen pemonev yang ditugaskan pada mitra ini.',
            ], 403);
        }

        $data = $request->validate([
            'periode_monev' => ['required', 'in:Minggu ke-0,Minggu ke-8,Minggu ke-18'],
            'tanggal_pelaksanaan' => ['required', 'date'],
            'metode_pelaksanaan' => ['required', 'in:Luring,Daring'],
            'catatan_evaluasi' => ['required', 'string', 'min:5'],
            'kendala_tindak_lanjut' => ['nullable', 'string'],
            'dokumentasi' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $existing = MonevMitra::where('mitra_id', $mitraId)
            ->where('periode_akademik_id', $periode->id)
            ->where('periode_monev', $data['periode_monev'])
            ->first();

        $docPath = $existing?->dokumentasi_path;
        $docName = $existing?->dokumentasi_name;

        if ($request->hasFile('dokumentasi')) {
            if ($existing?->dokumentasi_path && Storage::disk('public')->exists($existing->dokumentasi_path)) {
                Storage::disk('public')->delete($existing->dokumentasi_path);
            }
            $file = $request->file('dokumentasi');
            $docPath = $file->store('monev-dokumentasi', 'public');
            $docName = $file->getClientOriginalName();
        }

        $monev = MonevMitra::updateOrCreate(
            [
                'mitra_id' => $mitraId,
                'periode_akademik_id' => $periode->id,
                'periode_monev' => $data['periode_monev'],
            ],
            [
                'tanggal_pelaksanaan' => $data['tanggal_pelaksanaan'],
                'metode_pelaksanaan' => $data['metode_pelaksanaan'],
                'catatan_evaluasi' => $data['catatan_evaluasi'],
                'kendala_tindak_lanjut' => $data['kendala_tindak_lanjut'] ?? null,
                'dokumentasi_path' => $docPath,
                'dokumentasi_name' => $docName,
                'filled_by_id' => $user->id,
            ]
        );

        $monev->load('filledBy');

        return response()->json([
            'success' => true,
            'message' => "Hasil evaluasi Monev Mitra ({$data['periode_monev']}) berhasil disimpan.",
            'data' => $monev,
        ]);
    }

    /**
     * Preview / Stream Berkas Dokumentasi Monev Mitra
     */
    public function streamFile(Request $request, int $monevId): mixed
    {
        $monev = MonevMitra::with(['mitra', 'periodeAkademik'])->findOrFail($monevId);
        $user = $request->user();

        // Otorisasi: Pemonev mitra tersebut, Ketua Tim MBKM, Admin, atau DPL mahasiswa di mitra
        $isAssigned = PlottingPemonevMitra::where('mitra_id', $monev->mitra_id)
            ->where('periode_akademik_id', $monev->periode_akademik_id)
            ->where('dosen_id', $user->id)
            ->exists();

        $isPrivileged = $user->hasRole('Ketua Tim MBKM', 'Admin');

        if (! $isAssigned && ! $isPrivileged) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat berkas dokumentasi ini.',
            ], 403);
        }

        if (! $monev->dokumentasi_path || ! Storage::disk('public')->exists($monev->dokumentasi_path)) {
            return response()->json(['success' => false, 'message' => 'Berkas dokumentasi tidak ditemukan.'], 404);
        }

        $fullPath = Storage::disk('public')->path($monev->dokumentasi_path);
        $name = $monev->dokumentasi_name ?? basename($fullPath);
        $mime = Storage::disk('public')->mimeType($monev->dokumentasi_path) ?? 'application/octet-stream';
        $mode = $request->query('mode', 'inline');

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => ($mode === 'download' ? 'attachment' : 'inline')."; filename=\"{$name}\"",
        ]);
    }
}
