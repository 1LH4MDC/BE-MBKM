<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\MonevHasilMahasiswa;
use App\Models\MonevMitra;
use App\Models\MonevProsesMahasiswa;
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

    private const INDIKATOR_HASIL_KEYS = [
        'kompleksitas_jobdesk',
        'kesesuaian_rkm',
        'relevansi_pl',
        'kualitas_supervisi',
        'ketercapaian_cpmk',
        'outcome_pekerjaan',
        'pengembangan_skill',
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
     * Mengambil detail mitra dan riwayat 3 tahap monev beserta instrumen per mahasiswa
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

        $mitraMonevs = MonevMitra::with(['filledBy', 'prosesMahasiswas', 'hasilMahasiswas'])
            ->where('mitra_id', $mitraId)
            ->where('periode_akademik_id', $periode->id)
            ->get();

        $tahapData = [];
        foreach (self::TAHAP_MONEV as $tahap) {
            $m = $mitraMonevs->firstWhere('periode_monev', $tahap);
            $key = self::TAHAP_KEY_MAP[$tahap] ?? 'minggu_0';

            $baseStage = [
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

            if ($key === 'minggu_8') {
                $prosesMap = $m ? $m->prosesMahasiswas->keyBy('pendaftaran_mbkm_id') : collect();
                $penilaianMahasiswa = $pendaftarList->map(function ($p) use ($prosesMap) {
                    $row = $prosesMap->get($p['id']);
                    return [
                        'pendaftaran_mbkm_id' => $p['id'],
                        'nim' => $p['nim'],
                        'nama_lengkap' => $p['nama_lengkap'],
                        'program_studi' => $p['program_studi'],
                        'posisi_magang' => $p['posisi_magang'],
                        'dpl' => $p['dpl'],
                        'kehadiran_ketepatan_waktu' => $row?->kehadiran_ketepatan_waktu ?? 5,
                        'ketaatan_aturan' => $row?->ketaatan_aturan ?? 5,
                        'pencapaian_tugas' => $row?->pencapaian_tugas ?? 5,
                        'kualitas_hasil_kerja' => $row?->kualitas_hasil_kerja ?? 5,
                        'kemampuan_menyelesaikan_masalah' => $row?->kemampuan_menyelesaikan_masalah ?? 5,
                        'penguasaan_tools' => $row?->penguasaan_tools ?? 5,
                        'kemampuan_analisis' => $row?->kemampuan_analisis ?? 5,
                        'komunikasi_kolaborasi' => $row?->komunikasi_kolaborasi ?? 5,
                        'tanggung_jawab_inisiatif' => $row?->tanggung_jawab_inisiatif ?? 5,
                        'etika_profesional' => $row?->etika_profesional ?? 5,
                        'relevansi_ilmu_komputer' => $row?->relevansi_ilmu_komputer ?? 5,
                        'kontribusi_kompetensi' => $row?->kontribusi_kompetensi ?? 5,
                        'catatan_pemonev' => $row?->catatan_pemonev ?? '',
                        'temuan_monev' => $row?->temuan_monev ?? '',
                        'status_monev' => $row?->status_monev ?? 'Tidak Ada Temuan',
                        'is_assessed' => $row !== null,
                    ];
                });

                $baseStage['penilaian_mahasiswas'] = $penilaianMahasiswa->values();
                $baseStage['total_mahasiswa_dinilai'] = $prosesMap->count();
            } elseif ($key === 'minggu_18') {
                $hasilMap = $m ? $m->hasilMahasiswas->keyBy('pendaftaran_mbkm_id') : collect();
                $penilaianMahasiswa = $pendaftarList->map(function ($p) use ($hasilMap) {
                    $row = $hasilMap->get($p['id']);
                    return [
                        'pendaftaran_mbkm_id' => $p['id'],
                        'nim' => $p['nim'],
                        'nama_lengkap' => $p['nama_lengkap'],
                        'program_studi' => $p['program_studi'],
                        'posisi_magang' => $p['posisi_magang'],
                        'dpl' => $p['dpl'],
                        'kompleksitas_jobdesk' => $row?->kompleksitas_jobdesk ?? 3,
                        'kesesuaian_rkm' => $row?->kesesuaian_rkm ?? 4,
                        'relevansi_pl' => $row?->relevansi_pl ?? 4,
                        'kualitas_supervisi' => $row?->kualitas_supervisi ?? 4,
                        'ketercapaian_cpmk' => $row?->ketercapaian_cpmk ?? 4,
                        'outcome_pekerjaan' => $row?->outcome_pekerjaan ?? 4,
                        'pengembangan_skill' => $row?->pengembangan_skill ?? 4,
                        'is_assessed' => $row !== null,
                    ];
                });

                $assessedHasil = $hasilMap->values();
                $countHasil = $assessedHasil->count();
                $rataRata = [];
                foreach (self::INDIKATOR_HASIL_KEYS as $indKey) {
                    $rataRata[$indKey] = $countHasil > 0 ? round((float) $assessedHasil->avg($indKey), 2) : 0.00;
                }

                $baseStage['proyeksi_magang_selanjutnya'] = is_array($m?->proyeksi_magang_selanjutnya)
                    ? $m->proyeksi_magang_selanjutnya
                    : ($m?->proyeksi_magang_selanjutnya ? json_decode($m->proyeksi_magang_selanjutnya, true) : []);
                $baseStage['penilaian_mahasiswas'] = $penilaianMahasiswa->values();
                $baseStage['rata_rata_indikator'] = $rataRata;
                $baseStage['total_mahasiswa_dinilai'] = $countHasil;
            }

            $tahapData[$key] = $baseStage;
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
                    'target_nilai_monev' => (float) ($periode->target_nilai_monev ?? 4.00),
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

        $periodeMonev = $request->input('periode_monev');
        if (! in_array($periodeMonev, self::TAHAP_MONEV, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Tahap Monev tidak valid. Pilihan: Minggu ke-0, Minggu ke-8, Minggu ke-18.',
            ], 422);
        }

        // Validasi dasar
        $baseRules = [
            'periode_monev' => ['required', 'in:Minggu ke-0,Minggu ke-8,Minggu ke-18'],
            'tanggal_pelaksanaan' => ['required', 'date'],
            'kendala_tindak_lanjut' => ['nullable', 'string'],
            'dokumentasi' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];

        if ($periodeMonev === 'Minggu ke-0') {
            $baseRules['catatan_evaluasi'] = ['required', 'string', 'min:5'];
        } else {
            $baseRules['catatan_evaluasi'] = ['nullable', 'string'];
        }

        $data = $request->validate($baseRules);

        $existing = MonevMitra::where('mitra_id', $mitraId)
            ->where('periode_akademik_id', $periode->id)
            ->where('periode_monev', $periodeMonev)
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

        // Penanganan Khusus Minggu ke-0
        if ($periodeMonev === 'Minggu ke-0') {
            $monev = MonevMitra::updateOrCreate(
                [
                    'mitra_id' => $mitraId,
                    'periode_akademik_id' => $periode->id,
                    'periode_monev' => 'Minggu ke-0',
                ],
                [
                    'tanggal_pelaksanaan' => $data['tanggal_pelaksanaan'],
                    'metode_pelaksanaan' => $request->input('metode_pelaksanaan', 'Luring'),
                    'catatan_evaluasi' => $data['catatan_evaluasi'],
                    'kendala_tindak_lanjut' => $data['kendala_tindak_lanjut'] ?? null,
                    'dokumentasi_path' => $docPath,
                    'dokumentasi_name' => $docName,
                    'filled_by_id' => $user->id,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Hasil evaluasi Monev Mitra (Minggu ke-0) berhasil disimpan.',
                'data' => $monev,
            ]);
        }

        // Penanganan Khusus Minggu ke-8 (Monev Proses - 12 Indikator)
        if ($periodeMonev === 'Minggu ke-8') {
            $rawPenilaian = $request->input('penilaian_mahasiswas');
            if (is_string($rawPenilaian)) {
                $rawPenilaian = json_decode($rawPenilaian, true);
            }

            if (! is_array($rawPenilaian) || empty($rawPenilaian)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penilaian 12 indikator mahasiswa magang (Minggu ke-8) wajib diisi.',
                ], 422);
            }

            $savedMonev = DB::transaction(function () use ($mitraId, $periode, $data, $docPath, $docName, $user, $rawPenilaian) {
                $monev = MonevMitra::updateOrCreate(
                    [
                        'mitra_id' => $mitraId,
                        'periode_akademik_id' => $periode->id,
                        'periode_monev' => 'Minggu ke-8',
                    ],
                    [
                        'tanggal_pelaksanaan' => $data['tanggal_pelaksanaan'],
                        'metode_pelaksanaan' => null,
                        'catatan_evaluasi' => $data['catatan_evaluasi'] ?? null,
                        'kendala_tindak_lanjut' => $data['kendala_tindak_lanjut'] ?? null,
                        'dokumentasi_path' => $docPath,
                        'dokumentasi_name' => $docName,
                        'filled_by_id' => $user->id,
                    ]
                );

                foreach ($rawPenilaian as $item) {
                    $pId = (int) ($item['pendaftaran_mbkm_id'] ?? 0);
                    if ($pId <= 0) {
                        continue;
                    }

                    $statusMonev = $item['status_monev'] ?? 'Tidak Ada Temuan';
                    if (! in_array($statusMonev, ['Tidak Ada Temuan', 'Observasi', 'Mayor'], true)) {
                        $statusMonev = 'Tidak Ada Temuan';
                    }

                    MonevProsesMahasiswa::updateOrCreate(
                        [
                            'monev_mitra_id' => $monev->id,
                            'pendaftaran_mbkm_id' => $pId,
                        ],
                        [
                            'kehadiran_ketepatan_waktu' => max(1, min(5, (int) ($item['kehadiran_ketepatan_waktu'] ?? 5))),
                            'ketaatan_aturan' => max(1, min(5, (int) ($item['ketaatan_aturan'] ?? 5))),
                            'pencapaian_tugas' => max(1, min(5, (int) ($item['pencapaian_tugas'] ?? 5))),
                            'kualitas_hasil_kerja' => max(1, min(5, (int) ($item['kualitas_hasil_kerja'] ?? 5))),
                            'kemampuan_menyelesaikan_masalah' => max(1, min(5, (int) ($item['kemampuan_menyelesaikan_masalah'] ?? 5))),
                            'penguasaan_tools' => max(1, min(5, (int) ($item['penguasaan_tools'] ?? 5))),
                            'kemampuan_analisis' => max(1, min(5, (int) ($item['kemampuan_analisis'] ?? 5))),
                            'komunikasi_kolaborasi' => max(1, min(5, (int) ($item['komunikasi_kolaborasi'] ?? 5))),
                            'tanggung_jawab_inisiatif' => max(1, min(5, (int) ($item['tanggung_jawab_inisiatif'] ?? 5))),
                            'etika_profesional' => max(1, min(5, (int) ($item['etika_profesional'] ?? 5))),
                            'relevansi_ilmu_komputer' => max(1, min(5, (int) ($item['relevansi_ilmu_komputer'] ?? 5))),
                            'kontribusi_kompetensi' => max(1, min(5, (int) ($item['kontribusi_kompetensi'] ?? 5))),
                            'catatan_pemonev' => $item['catatan_pemonev'] ?? null,
                            'temuan_monev' => $item['temuan_monev'] ?? null,
                            'status_monev' => $statusMonev,
                        ]
                    );
                }

                return $monev;
            });

            return response()->json([
                'success' => true,
                'message' => 'Hasil evaluasi Monev Proses (Minggu ke-8) berhasil disimpan.',
                'data' => $savedMonev,
            ]);
        }

        // Penanganan Khusus Minggu ke-18 (Monev Hasil - 7 Indikator & Proyeksi Magang)
        if ($periodeMonev === 'Minggu ke-18') {
            $rawPenilaian = $request->input('penilaian_mahasiswas');
            if (is_string($rawPenilaian)) {
                $rawPenilaian = json_decode($rawPenilaian, true);
            }

            if (! is_array($rawPenilaian) || empty($rawPenilaian)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penilaian 7 indikator mahasiswa magang (Minggu ke-18) wajib diisi.',
                ], 422);
            }

            $rawProyeksi = $request->input('proyeksi_magang_selanjutnya');
            if (is_string($rawProyeksi)) {
                $rawProyeksi = json_decode($rawProyeksi, true);
            }
            if (! is_array($rawProyeksi)) {
                $rawProyeksi = [];
            }
            $cleanProyeksi = array_values(array_filter(array_map('trim', $rawProyeksi)));

            $savedMonev = DB::transaction(function () use ($mitraId, $periode, $data, $docPath, $docName, $user, $cleanProyeksi, $rawPenilaian) {
                $monev = MonevMitra::updateOrCreate(
                    [
                        'mitra_id' => $mitraId,
                        'periode_akademik_id' => $periode->id,
                        'periode_monev' => 'Minggu ke-18',
                    ],
                    [
                        'tanggal_pelaksanaan' => $data['tanggal_pelaksanaan'],
                        'metode_pelaksanaan' => null,
                        'catatan_evaluasi' => $data['catatan_evaluasi'] ?? null,
                        'kendala_tindak_lanjut' => $data['kendala_tindak_lanjut'] ?? null,
                        'proyeksi_magang_selanjutnya' => $cleanProyeksi,
                        'dokumentasi_path' => $docPath,
                        'dokumentasi_name' => $docName,
                        'filled_by_id' => $user->id,
                    ]
                );

                foreach ($rawPenilaian as $item) {
                    $pId = (int) ($item['pendaftaran_mbkm_id'] ?? 0);
                    if ($pId <= 0) {
                        continue;
                    }

                    MonevHasilMahasiswa::updateOrCreate(
                        [
                            'monev_mitra_id' => $monev->id,
                            'pendaftaran_mbkm_id' => $pId,
                        ],
                        [
                            'kompleksitas_jobdesk' => max(1, min(5, (int) ($item['kompleksitas_jobdesk'] ?? 3))),
                            'kesesuaian_rkm' => max(1, min(5, (int) ($item['kesesuaian_rkm'] ?? 4))),
                            'relevansi_pl' => max(1, min(5, (int) ($item['relevansi_pl'] ?? 4))),
                            'kualitas_supervisi' => max(1, min(5, (int) ($item['kualitas_supervisi'] ?? 4))),
                            'ketercapaian_cpmk' => max(1, min(5, (int) ($item['ketercapaian_cpmk'] ?? 4))),
                            'outcome_pekerjaan' => max(1, min(5, (int) ($item['outcome_pekerjaan'] ?? 4))),
                            'pengembangan_skill' => max(1, min(5, (int) ($item['pengembangan_skill'] ?? 4))),
                        ]
                    );
                }

                return $monev;
            });

            // Hitung rata-rata 7 indikator untuk response balik
            $allHasil = MonevHasilMahasiswa::where('monev_mitra_id', $savedMonev->id)->get();
            $rataRata = [];
            foreach (self::INDIKATOR_HASIL_KEYS as $indKey) {
                $rataRata[$indKey] = $allHasil->count() > 0 ? round((float) $allHasil->avg($indKey), 2) : 0.00;
            }

            return response()->json([
                'success' => true,
                'message' => 'Hasil evaluasi Monev Hasil (Minggu ke-18) berhasil disimpan.',
                'data' => array_merge($savedMonev->toArray(), [
                    'rata_rata_indikator' => $rataRata,
                    'proyeksi_magang_selanjutnya' => $cleanProyeksi,
                ]),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Permintaan tidak dapat diproses.'], 400);
    }

    /**
     * Rekap Hasil Evaluasi Global (Minggu ke-18) - Khusus Ketua Tim MBKM
     * Menghasilkan data Radar Chart Global (Actual vs Target) & Rincian per Mitra
     */
    public function rekapHasilGlobal(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->hasRole('Ketua Tim MBKM')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Ketua Tim MBKM yang memiliki wewenang mengakses rekapitulasi hasil evaluasi global.',
            ], 403);
        }

        $periode = $this->resolvePeriode($request);
        if (! $periode) {
            return response()->json(['success' => false, 'message' => 'Tahun ajaran tidak ditemukan.'], 404);
        }

        $targetNilai = (float) ($periode->target_nilai_monev ?? 4.00);

        // Ambil seluruh monev minggu ke-18 pada periode terkait
        $monev18List = MonevMitra::with(['mitra', 'hasilMahasiswas'])
            ->where('periode_akademik_id', $periode->id)
            ->where('periode_monev', 'Minggu ke-18')
            ->get();

        $allHasilMahasiswas = MonevHasilMahasiswa::whereIn('monev_mitra_id', $monev18List->pluck('id'))->get();
        $totalMahasiswaDinilai = $allHasilMahasiswas->count();
        $totalMitraDinilai = $monev18List->count();

        // Hitung rerata global per 7 indikator
        $rataRataGlobal = [];
        $sumAverages = 0;
        foreach (self::INDIKATOR_HASIL_KEYS as $key) {
            $val = $totalMahasiswaDinilai > 0 ? round((float) $allHasilMahasiswas->avg($key), 2) : 0.00;
            $rataRataGlobal[$key] = $val;
            $sumAverages += $val;
        }

        $rerataGlobalKeseluruhan = count(self::INDIKATOR_HASIL_KEYS) > 0
            ? round($sumAverages / count(self::INDIKATOR_HASIL_KEYS), 2)
            : 0.00;

        // Breakdown per Mitra
        $detailPerMitra = $monev18List->map(function (MonevMitra $m) {
            $hasilMhs = $m->hasilMahasiswas;
            $mhsCount = $hasilMhs->count();

            $mitraAverages = [];
            $sumMitra = 0;
            foreach (self::INDIKATOR_HASIL_KEYS as $key) {
                $v = $mhsCount > 0 ? round((float) $hasilMhs->avg($key), 2) : 0.00;
                $mitraAverages[$key] = $v;
                $sumMitra += $v;
            }

            $rerataMitra = count(self::INDIKATOR_HASIL_KEYS) > 0
                ? round($sumMitra / count(self::INDIKATOR_HASIL_KEYS), 2)
                : 0.00;

            return [
                'mitra_id' => $m->mitra_id,
                'nama_mitra' => $m->mitra?->nama ?? '-',
                'jumlah_mahasiswa_dinilai' => $mhsCount,
                'rata_rata_indikator' => $mitraAverages,
                'rerata_keseluruhan' => $rerataMitra,
                'proyeksi_magang_selanjutnya' => is_array($m->proyeksi_magang_selanjutnya)
                    ? $m->proyeksi_magang_selanjutnya
                    : ($m->proyeksi_magang_selanjutnya ? json_decode($m->proyeksi_magang_selanjutnya, true) : []),
                'tanggal_pelaksanaan' => $m->tanggal_pelaksanaan?->format('Y-m-d'),
                'updated_at' => $m->updated_at?->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'periode_akademik' => [
                    'id' => $periode->id,
                    'nama_lengkap' => $periode->nama_lengkap,
                    'is_aktif' => (bool) $periode->is_aktif,
                ],
                'target_nilai_monev' => $targetNilai,
                'total_mitra_dinilai' => $totalMitraDinilai,
                'total_mahasiswa_dinilai' => $totalMahasiswaDinilai,
                'rata_rata_global' => $rataRataGlobal,
                'rerata_global_keseluruhan' => $rerataGlobalKeseluruhan,
                'detail_per_mitra' => $detailPerMitra->values(),
            ],
        ]);
    }

    /**
     * Memperbarui Target Nilai Monev Global pada Tahun Ajaran Terkait (Khusus Ketua Tim MBKM)
     */
    public function updateTargetNilaiGlobal(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->hasRole('Ketua Tim MBKM')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Ketua Tim MBKM yang berwenang menetapkan nilai target evaluasi Monev.',
            ], 403);
        }

        $request->validate([
            'target_nilai_monev' => ['required', 'numeric', 'min:1.0', 'max:5.0'],
            'periode_akademik_id' => ['nullable', 'exists:periode_akademiks,id'],
        ]);

        $periode = $this->resolvePeriode($request);
        if (! $periode) {
            return response()->json(['success' => false, 'message' => 'Tahun ajaran tidak ditemukan.'], 404);
        }

        $target = round((float) $request->input('target_nilai_monev'), 2);
        $periode->update(['target_nilai_monev' => $target]);

        return response()->json([
            'success' => true,
            'message' => "Nilai target evaluasi Monev ({$target}) berhasil disimpan.",
            'data' => [
                'periode_akademik_id' => $periode->id,
                'target_nilai_monev' => $target,
            ],
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
