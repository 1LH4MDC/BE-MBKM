<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BimbinganMagang;
use App\Models\PendaftaranMbkm;
use App\Models\PenilaianDpl;
use App\Models\PenilaianDplMataKuliah;
use App\Models\PenilaianSupervisor;
use App\Models\PenilaianSupervisorMataKuliah;
use App\Models\PeriodeAkademik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PenilaianAkhirController extends Controller
{
    private function resolvePeriode(Request $request): ?PeriodeAkademik
    {
        if ($request->filled('periode_akademik_id')) {
            return PeriodeAkademik::find($request->input('periode_akademik_id'));
        }
        if ($request->filled('periode_id')) {
            return PeriodeAkademik::find($request->input('periode_id'));
        }
        if ($request->filled('tahun_ajaran')) {
            $rawTa = trim((string) $request->input('tahun_ajaran'));
            if (! in_array(strtolower($rawTa), ['semua', 'semua tahun ajaran', 'all'], true)) {
                $parts = preg_split('/\s+/', $rawTa);
                $th = $parts[0] ?? null;
                $sem = $parts[1] ?? null;
                $found = PeriodeAkademik::where(function ($q) use ($th, $sem, $rawTa) {
                    if ($th && $sem) {
                        $q->where('tahun_ajaran', $th)->where('semester', $sem);
                    } else {
                        $q->where('tahun_ajaran', $rawTa);
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

    private function getActiveMataKuliahs(PendaftaranMbkm $pendaftaran): array
    {
        $rkmItems = $pendaftaran->rkm?->items ?? collect();

        return $rkmItems->filter(function ($item) {
            return is_null($item->status_mk) || $item->status_mk === 'aktif' || $item->status_mk !== 'drop';
        })->map(function ($item) {
            $jobdeskList = $item->jobdesks ? $item->jobdesks->pluck('deskripsi')->filter()->values()->all() : [];
            $jobDescription = ! empty($jobdeskList)
                ? implode("\n", array_map(fn ($d, $i) => ($i + 1).". {$d}", $jobdeskList, array_keys($jobdeskList)))
                : ($item->job_description ?? '-');

            return [
                'mata_kuliah_id' => $item->mata_kuliah_id,
                'kode_mk' => $item->kode_mk,
                'nama_mk' => $item->nama_mk,
                'sks' => (int) $item->sks,
                'jobdesks' => $jobdeskList,
                'job_description' => $jobDescription,
            ];
        })->values()->all();
    }

    /**
     * Hitung nilai gabungan per MK dan ringkasan nilai akhir mahasiswa
     * Formula resmi: (SPV 45%) + (DPL 35%) + (Penguji Semhas 20%)
     */
    private function hitungRekapMahasiswa(PendaftaranMbkm $pendaftaran): array
    {
        $activeMks = $this->getActiveMataKuliahs($pendaftaran);

        $spv = $pendaftaran->penilaianSupervisor;
        $dpl = $pendaftaran->penilaianDpl;
        $penguji = $pendaftaran->penilaianSemhas;

        $sudahSpv = $spv !== null;
        $sudahDpl = $dpl !== null;
        $sudahPenguji = $penguji !== null;

        $komponenTerisi = ($sudahSpv ? 1 : 0) + ($sudahDpl ? 1 : 0) + ($sudahPenguji ? 1 : 0);
        $statusKelengkapan = match ($komponenTerisi) {
            3 => 'Lengkap',
            1, 2 => 'Sebagian',
            default => 'Belum Dinilai',
        };

        $spvMks = $spv?->mataKuliahs ?? collect();
        $dplMks = $dpl?->mataKuliahs ?? collect();
        $pengujiMks = $penguji?->mataKuliahs ?? collect();

        $mataKuliahResults = [];
        $nilaiAkhirList = [];

        foreach ($activeMks as $mk) {
            $kodeMk = $mk['kode_mk'];

            $nilaiSpvRecord = $spvMks->firstWhere('kode_mk', $kodeMk);
            $nilaiDplRecord = $dplMks->firstWhere('kode_mk', $kodeMk);
            $nilaiPengujiRecord = $pengujiMks->firstWhere('kode_mk', $kodeMk);

            $valSpv = $nilaiSpvRecord !== null ? (float) $nilaiSpvRecord->nilai : null;
            $valDpl = $nilaiDplRecord !== null ? (float) $nilaiDplRecord->nilai : null;
            $valPenguji = $nilaiPengujiRecord !== null ? (float) $nilaiPengujiRecord->nilai_total : null;

            $nilaiAkhirMk = null;
            if ($valSpv !== null && $valDpl !== null && $valPenguji !== null) {
                $nilaiAkhirMk = round(($valSpv * 0.45) + ($valDpl * 0.35) + ($valPenguji * 0.20), 2);
                $nilaiAkhirList[] = $nilaiAkhirMk;
            }

            $mataKuliahResults[] = [
                'mata_kuliah_id' => $mk['mata_kuliah_id'],
                'kode_mk' => $mk['kode_mk'],
                'nama_mk' => $mk['nama_mk'],
                'sks' => $mk['sks'],
                'job_description' => $mk['job_description'],
                'nilai_supervisor' => $valSpv,
                'nilai_dpl' => $valDpl,
                'nilai_penguji' => $valPenguji,
                'nilai_akhir' => $nilaiAkhirMk,
            ];
        }

        $rataRataAkhir = null;
        if ($komponenTerisi === 3 && count($nilaiAkhirList) === count($activeMks) && count($activeMks) > 0) {
            $rataRataAkhir = round(array_sum($nilaiAkhirList) / count($nilaiAkhirList), 2);
        }

        return [
            'komponen_terisi' => $komponenTerisi,
            'status_kelengkapan' => $statusKelengkapan,
            'sudah_nilai_supervisor' => $sudahSpv,
            'sudah_nilai_dpl' => $sudahDpl,
            'sudah_nilai_penguji' => $sudahPenguji,
            'nama_supervisor' => $spv?->nama_supervisor,
            'rata_rata_supervisor' => $spv?->rata_rata_nilai !== null ? (float) $spv->rata_rata_nilai : null,
            'rata_rata_dpl' => $dpl?->rata_rata_nilai !== null ? (float) $dpl->rata_rata_nilai : null,
            'rata_rata_penguji' => $penguji?->rata_rata_nilai_total !== null ? (float) $penguji->rata_rata_nilai_total : null,
            'rata_rata_nilai_akhir' => $rataRataAkhir,
            'mata_kuliahs' => $mataKuliahResults,
        ];
    }

    /* ========================================================================= */
    /* BAGIAN A: NILAI SUPERVISOR (45%) OLEH PIC VERIFIKATOR                     */
    /* ========================================================================= */

    /**
     * GET /api/v1/penilaian-akhir/supervisor/{pendaftaranId}
     * Menampilkan detail mahasiswa, berkas lembar nilai supervisor, MK aktif RKM, dan penilaian tersimpan
     */
    public function showSupervisor(Request $request, int $pendaftaranId): JsonResponse
    {
        $user = $request->user();

        $pendaftaran = PendaftaranMbkm::with([
            'user',
            'programStudi',
            'mitra',
            'dpl.dosen',
            'picVerifikator',
            'periodeAkademik',
            'pengajuanSemhas',
            'penilaianSupervisor.mataKuliahs',
            'rkm.items' => function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('status_mk')
                        ->orWhere('status_mk', 'aktif')
                        ->orWhere('status_mk', '!=', 'drop');
                })->with(['jobdesks' => fn ($jq) => $jq->orderBy('urutan')]);
            },
        ])->findOrFail($pendaftaranId);

        $isAssignedPic = (int) $pendaftaran->pic_verifikator_id === (int) $user->id;
        $isKetua = $user->hasRole('Ketua Tim MBKM');
        $isAdmin = $user->hasRole('Admin');

        if (! $isAssignedPic && ! $isKetua && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat atau menginput nilai supervisor mahasiswa ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->first();
        $isPeriodeAktif = $periodeAktif && (int) $pendaftaran->periode_akademik_id === (int) $periodeAktif->id;
        $canEdit = ($isAssignedPic || $isAdmin) && $isPeriodeAktif;

        $semhas = $pendaftaran->pengajuanSemhas;
        $spv = $pendaftaran->penilaianSupervisor;

        $mataKuliahs = $this->getActiveMataKuliahs($pendaftaran);

        $penilaianData = null;
        if ($spv) {
            $penilaianData = [
                'id' => $spv->id,
                'nama_supervisor' => $spv->nama_supervisor,
                'rata_rata_nilai' => (float) $spv->rata_rata_nilai,
                'updated_at' => $spv->updated_at?->toISOString(),
                'mata_kuliahs' => $spv->mataKuliahs->map(fn ($mk) => [
                    'id' => $mk->id,
                    'mata_kuliah_id' => $mk->mata_kuliah_id,
                    'kode_mk' => $mk->kode_mk,
                    'nama_mk' => $mk->nama_mk,
                    'sks' => (int) $mk->sks,
                    'job_description' => $mk->job_description,
                    'nilai' => (float) $mk->nilai,
                ])->values()->all(),
            ];
        }

        $berkasNilaiSupervisor = [
            'nama' => $semhas?->nilai_supervisor_name ?: ($semhas?->nilai_supervisor_path ? basename($semhas->nilai_supervisor_path) : null),
            'stream_url' => $semhas?->id ? url("/api/v1/semhas/{$semhas->id}/file/nilai_supervisor") : null,
            'download_url' => $semhas?->id ? url("/api/v1/semhas/{$semhas->id}/file/nilai_supervisor?mode=download") : null,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'pendaftaran_mbkm_id' => $pendaftaran->id,
                'nim' => $pendaftaran->nim,
                'nama_lengkap' => $pendaftaran->nama_lengkap,
                'program_studi' => $pendaftaran->programStudi?->nama ?? 'Teknologi Informasi',
                'mitra_magang' => $pendaftaran->mitra?->nama ?? $pendaftaran->mitra_magang_text ?? '-',
                'posisi_magang' => $pendaftaran->posisi_magang ?? '-',
                'tahun_ajaran' => $pendaftaran->tahun_ajaran,
                'periode_akademik_id' => $pendaftaran->periode_akademik_id,
                'is_periode_aktif' => $isPeriodeAktif,
                'can_edit' => $canEdit,
                'berkas_nilai_supervisor' => $berkasNilaiSupervisor,
                'mata_kuliah_konversi' => $mataKuliahs,
                'penilaian_supervisor' => $penilaianData,
            ],
        ]);
    }

    /**
     * POST /api/v1/penilaian-akhir/supervisor/{pendaftaranId}
     * Menyimpan / memperbarui nilai supervisor oleh PIC Verifikator
     */
    public function storeSupervisor(Request $request, int $pendaftaranId): JsonResponse
    {
        $user = $request->user();
        $pendaftaran = PendaftaranMbkm::findOrFail($pendaftaranId);

        $isAdmin = $user->hasRole('Admin');
        if (! $isAdmin && (int) $pendaftaran->pic_verifikator_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan PIC verifikator untuk mahasiswa ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'nama_supervisor' => ['required', 'string', 'max:255'],
            'nilai_mata_kuliahs' => ['required', 'array', 'min:1'],
            'nilai_mata_kuliahs.*.kode_mk' => ['required', 'string'],
            'nilai_mata_kuliahs.*.nama_mk' => ['required', 'string'],
            'nilai_mata_kuliahs.*.sks' => ['nullable', 'integer'],
            'nilai_mata_kuliahs.*.mata_kuliah_id' => ['nullable', 'integer'],
            'nilai_mata_kuliahs.*.job_description' => ['nullable', 'string'],
            'nilai_mata_kuliahs.*.nilai' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $scores = array_map(fn ($m) => (float) $m['nilai'], $validated['nilai_mata_kuliahs']);
        $rataRata = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0;

        $record = DB::transaction(function () use ($pendaftaran, $user, $validated, $rataRata) {
            $spv = PenilaianSupervisor::updateOrCreate(
                ['pendaftaran_mbkm_id' => $pendaftaran->id],
                [
                    'periode_akademik_id' => $pendaftaran->periode_akademik_id,
                    'pic_id' => $user->id,
                    'nama_supervisor' => trim($validated['nama_supervisor']),
                    'rata_rata_nilai' => $rataRata,
                ]
            );

            foreach ($validated['nilai_mata_kuliahs'] as $mk) {
                PenilaianSupervisorMataKuliah::updateOrCreate(
                    [
                        'penilaian_supervisor_id' => $spv->id,
                        'kode_mk' => $mk['kode_mk'],
                    ],
                    [
                        'mata_kuliah_id' => $mk['mata_kuliah_id'] ?? null,
                        'nama_mk' => $mk['nama_mk'],
                        'sks' => $mk['sks'] ?? 0,
                        'job_description' => $mk['job_description'] ?? null,
                        'nilai' => (float) $mk['nilai'],
                    ]
                );
            }

            return $spv->load('mataKuliahs');
        });

        return response()->json([
            'success' => true,
            'message' => 'Nilai supervisor mitra berhasil disimpan.',
            'data' => [
                'id' => $record->id,
                'nama_supervisor' => $record->nama_supervisor,
                'rata_rata_nilai' => (float) $record->rata_rata_nilai,
                'mata_kuliahs' => $record->mataKuliahs->map(fn ($mk) => [
                    'kode_mk' => $mk->kode_mk,
                    'nama_mk' => $mk->nama_mk,
                    'nilai' => (float) $mk->nilai,
                ]),
            ],
        ]);
    }

    /* ========================================================================= */
    /* BAGIAN B: NILAI DPL (35%) OLEH DOSEN PEMBIMBING LAPANGAN                  */
    /* ========================================================================= */

    /**
     * GET /api/v1/penilaian-akhir/dpl
     * Daftar mahasiswa bimbingan untuk DPL login
     */
    public function indexDpl(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('DPL')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses sebagai Dosen Pembimbing Lapangan.',
            ], Response::HTTP_FORBIDDEN);
        }

        $periode = $this->resolvePeriode($request);

        $query = PendaftaranMbkm::with([
            'user',
            'programStudi',
            'mitra',
            'periodeAkademik',
            'penilaianDpl',
            'bimbingans',
        ])
            ->where('dpl_id', $user->id)
            ->where('status', 'Terverifikasi');

        // Filter Tahun Ajaran / Periode
        if ($request->filled('tahun_ajaran') && in_array(strtolower(trim($request->input('tahun_ajaran'))), ['semua', 'semua tahun ajaran', 'all'], true)) {
            // semua tahun ajaran
        } elseif ($periode) {
            $query->where('periode_akademik_id', $periode->id);
        }

        // Filter Program Studi
        if ($request->filled('prodi') && ! in_array($request->input('prodi'), ['Semua', 'Semua Prodi'], true)) {
            $prodi = $request->input('prodi');
            $query->whereHas('programStudi', function ($q) use ($prodi) {
                $q->where('nama', $prodi)->orWhere('kode', $prodi);
            });
        }

        // Search NIM, Nama Mahasiswa, Mitra, Posisi
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('posisi_magang', 'like', "%{$search}%")
                    ->orWhereHas('mitra', fn ($mq) => $mq->where('nama', 'like', "%{$search}%"));
            });
        }

        $students = $query->orderBy('id', 'desc')->get();

        // Map and Filter Status Penilaian
        $data = $students->map(function (PendaftaranMbkm $p) {
            $bimbinganCount = $p->bimbingans->count();
            $isUnlocked = $bimbinganCount >= 8;
            $dplRecord = $p->penilaianDpl;
            $sudahDinilai = $dplRecord !== null;

            return [
                'pendaftaran_mbkm_id' => $p->id,
                'nim' => $p->nim,
                'nama_lengkap' => $p->nama_lengkap,
                'program_studi' => $p->programStudi?->nama ?? 'Teknologi Informasi',
                'mitra_magang' => $p->mitra?->nama ?? $p->mitra_magang_text ?? '-',
                'posisi_magang' => $p->posisi_magang ?? '-',
                'tahun_ajaran' => $p->tahun_ajaran,
                'periode_akademik_id' => $p->periode_akademik_id,
                'bimbingan_count' => $bimbinganCount,
                'min_bimbingan' => 8,
                'is_unlocked' => $isUnlocked,
                'sudah_dinilai' => $sudahDinilai,
                'rata_rata_nilai_dpl' => $dplRecord?->rata_rata_nilai !== null ? (float) $dplRecord->rata_rata_nilai : null,
            ];
        });

        // Filter status penilaian jika ada
        if ($request->filled('status_penilaian') && ! in_array($request->input('status_penilaian'), ['Semua', 'Semua Status'], true)) {
            $status = $request->input('status_penilaian');
            $data = $data->filter(function ($item) use ($status) {
                if (in_array($status, ['Sudah Dinilai', 'Sudah'], true)) {
                    return $item['sudah_dinilai'] === true;
                }
                if (in_array($status, ['Belum Dinilai', 'Belum'], true)) {
                    return $item['sudah_dinilai'] === false && $item['is_unlocked'] === true;
                }
                if (in_array($status, ['Terkunci', 'Terkunci (< 8 Bimbingan)'], true)) {
                    return $item['is_unlocked'] === false;
                }

                return true;
            })->values();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * GET /api/v1/penilaian-akhir/dpl/{pendaftaranId}
     * Menampilkan detail mahasiswa, progres bimbingan, MK konversi aktif, dan nilai DPL tersimpan
     */
    public function showDpl(Request $request, int $pendaftaranId): JsonResponse
    {
        $user = $request->user();

        $pendaftaran = PendaftaranMbkm::with([
            'user',
            'programStudi',
            'mitra',
            'dpl.dosen',
            'periodeAkademik',
            'penilaianDpl.mataKuliahs',
            'bimbingans',
            'rkm.items' => function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('status_mk')
                        ->orWhere('status_mk', 'aktif')
                        ->orWhere('status_mk', '!=', 'drop');
                })->with(['jobdesks' => fn ($jq) => $jq->orderBy('urutan')]);
            },
        ])->findOrFail($pendaftaranId);

        $isAssignedDpl = (int) $pendaftaran->dpl_id === (int) $user->id;
        $isKetua = $user->hasRole('Ketua Tim MBKM');
        $isAdmin = $user->hasRole('Admin');

        if (! $isAssignedDpl && ! $isKetua && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat atau menilai bimbingan mahasiswa ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        $bimbinganCount = $pendaftaran->bimbingans->count();
        $isUnlocked = $bimbinganCount >= 8;

        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->first();
        $isPeriodeAktif = $periodeAktif && (int) $pendaftaran->periode_akademik_id === (int) $periodeAktif->id;
        $canEdit = ($isAssignedDpl || $isAdmin) && $isUnlocked && $isPeriodeAktif;

        $mataKuliahs = $this->getActiveMataKuliahs($pendaftaran);
        $dplRecord = $pendaftaran->penilaianDpl;

        $penilaianData = null;
        if ($dplRecord) {
            $penilaianData = [
                'id' => $dplRecord->id,
                'jumlah_bimbingan_saat_dinilai' => $dplRecord->jumlah_bimbingan_saat_dinilai,
                'rata_rata_nilai' => (float) $dplRecord->rata_rata_nilai,
                'updated_at' => $dplRecord->updated_at?->toISOString(),
                'mata_kuliahs' => $dplRecord->mataKuliahs->map(fn ($mk) => [
                    'id' => $mk->id,
                    'mata_kuliah_id' => $mk->mata_kuliah_id,
                    'kode_mk' => $mk->kode_mk,
                    'nama_mk' => $mk->nama_mk,
                    'sks' => (int) $mk->sks,
                    'job_description' => $mk->job_description,
                    'nilai' => (float) $mk->nilai,
                ])->values()->all(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pendaftaran_mbkm_id' => $pendaftaran->id,
                'nim' => $pendaftaran->nim,
                'nama_lengkap' => $pendaftaran->nama_lengkap,
                'program_studi' => $pendaftaran->programStudi?->nama ?? 'Teknologi Informasi',
                'mitra_magang' => $pendaftaran->mitra?->nama ?? $pendaftaran->mitra_magang_text ?? '-',
                'posisi_magang' => $pendaftaran->posisi_magang ?? '-',
                'tahun_ajaran' => $pendaftaran->tahun_ajaran,
                'periode_akademik_id' => $pendaftaran->periode_akademik_id,
                'bimbingan_count' => $bimbinganCount,
                'min_bimbingan' => 8,
                'is_unlocked' => $isUnlocked,
                'is_periode_aktif' => $isPeriodeAktif,
                'can_edit' => $canEdit,
                'mata_kuliah_konversi' => $mataKuliahs,
                'penilaian_dpl' => $penilaianData,
            ],
        ]);
    }

    /**
     * POST /api/v1/penilaian-akhir/dpl/{pendaftaranId}
     * Menyimpan / memperbarui nilai DPL (wajib >= 8 kali bimbingan)
     */
    public function storeDpl(Request $request, int $pendaftaranId): JsonResponse
    {
        $user = $request->user();
        $pendaftaran = PendaftaranMbkm::with('bimbingans')->findOrFail($pendaftaranId);

        $isAdmin = $user->hasRole('Admin');
        if (! $isAdmin && (int) $pendaftaran->dpl_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan Dosen Pembimbing Lapangan (DPL) untuk mahasiswa ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        // VALIDASI PENGUNCIAN 8 KALI BIMBINGAN
        $bimbinganCount = $pendaftaran->bimbingans()->count();
        if ($bimbinganCount < 8) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_DPL_BIMBINGAN_KURANG',
                'message' => "Penilaian DPL baru dapat dibuka setelah mahasiswa melaksanakan minimal 8 kali bimbingan (tercatat: {$bimbinganCount} sesi).",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'nilai_mata_kuliahs' => ['required', 'array', 'min:1'],
            'nilai_mata_kuliahs.*.kode_mk' => ['required', 'string'],
            'nilai_mata_kuliahs.*.nama_mk' => ['required', 'string'],
            'nilai_mata_kuliahs.*.sks' => ['nullable', 'integer'],
            'nilai_mata_kuliahs.*.mata_kuliah_id' => ['nullable', 'integer'],
            'nilai_mata_kuliahs.*.job_description' => ['nullable', 'string'],
            'nilai_mata_kuliahs.*.nilai' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $scores = array_map(fn ($m) => (float) $m['nilai'], $validated['nilai_mata_kuliahs']);
        $rataRata = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0;

        $record = DB::transaction(function () use ($pendaftaran, $user, $validated, $rataRata, $bimbinganCount) {
            $dpl = PenilaianDpl::updateOrCreate(
                ['pendaftaran_mbkm_id' => $pendaftaran->id],
                [
                    'periode_akademik_id' => $pendaftaran->periode_akademik_id,
                    'dpl_id' => $user->id,
                    'jumlah_bimbingan_saat_dinilai' => $bimbinganCount,
                    'rata_rata_nilai' => $rataRata,
                ]
            );

            foreach ($validated['nilai_mata_kuliahs'] as $mk) {
                PenilaianDplMataKuliah::updateOrCreate(
                    [
                        'penilaian_dpl_id' => $dpl->id,
                        'kode_mk' => $mk['kode_mk'],
                    ],
                    [
                        'mata_kuliah_id' => $mk['mata_kuliah_id'] ?? null,
                        'nama_mk' => $mk['nama_mk'],
                        'sks' => $mk['sks'] ?? 0,
                        'job_description' => $mk['job_description'] ?? null,
                        'nilai' => (float) $mk['nilai'],
                    ]
                );
            }

            return $dpl->load('mataKuliahs');
        });

        return response()->json([
            'success' => true,
            'message' => 'Penilaian DPL berhasil disimpan.',
            'data' => [
                'id' => $record->id,
                'jumlah_bimbingan_saat_dinilai' => $record->jumlah_bimbingan_saat_dinilai,
                'rata_rata_nilai' => (float) $record->rata_rata_nilai,
                'mata_kuliahs' => $record->mataKuliahs->map(fn ($mk) => [
                    'kode_mk' => $mk->kode_mk,
                    'nama_mk' => $mk->nama_mk,
                    'nilai' => (float) $mk->nilai,
                ]),
            ],
        ]);
    }

    /* ========================================================================= */
    /* BAGIAN C: REKAP NILAI AKHIR GABUNGAN (KHUSUS KETUA TIM MBKM)              */
    /* ========================================================================= */

    /**
     * GET /api/v1/penilaian-akhir/rekap-ketua
     * Rekap nilai akhir seluruh mahasiswa lintas komponen (Khusus Ketua Tim MBKM)
     */
    public function rekapKetua(Request $request): JsonResponse
    {
        $user = $request->user();

        // Khusus Role Ketua Tim MBKM, tolak Admin, Tim biasa, DPL, Mahasiswa, dsb.
        if (! $user->hasRole('Ketua Tim MBKM')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Ketua Tim MBKM yang memiliki hak akses ke Rekap Nilai Akhir.',
            ], Response::HTTP_FORBIDDEN);
        }

        $periode = $this->resolvePeriode($request);

        $query = PendaftaranMbkm::with([
            'user',
            'programStudi',
            'mitra',
            'dpl',
            'picVerifikator',
            'pengujiSemhas',
            'penilaianSupervisor.mataKuliahs',
            'penilaianDpl.mataKuliahs',
            'penilaianSemhas.mataKuliahs',
            'periodeAkademik',
            'rkm.items.jobdesks',
        ])
            ->where('status', 'Terverifikasi')
            ->whereHas('rkm', fn ($rq) => $rq->where('status', 'Disetujui'));

        // Filter Tahun Ajaran
        if ($request->filled('tahun_ajaran') && in_array(strtolower(trim($request->input('tahun_ajaran'))), ['semua', 'semua tahun ajaran', 'all'], true)) {
            // semua tahun ajaran
        } elseif ($periode) {
            $query->where('periode_akademik_id', $periode->id);
        }

        // Filter Program Studi
        if ($request->filled('prodi') && ! in_array($request->input('prodi'), ['Semua', 'Semua Prodi'], true)) {
            $prodi = $request->input('prodi');
            $query->whereHas('programStudi', function ($q) use ($prodi) {
                $q->where('nama', $prodi)->orWhere('kode', $prodi);
            });
        }

        // Search NIM, Nama Mahasiswa, Mitra, DPL, Penguji
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('posisi_magang', 'like', "%{$search}%")
                    ->orWhereHas('mitra', fn ($mq) => $mq->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('dpl', fn ($dq) => $dq->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('pengujiSemhas', fn ($pq) => $pq->where('nama', 'like', "%{$search}%"));
            });
        }

        $students = $query->orderBy('id', 'desc')->get();

        $studentList = $students->map(function (PendaftaranMbkm $p) {
            $rekap = $this->hitungRekapMahasiswa($p);

            return [
                'pendaftaran_mbkm_id' => $p->id,
                'nim' => $p->nim,
                'nama_lengkap' => $p->nama_lengkap,
                'program_studi' => $p->programStudi?->nama ?? 'Teknologi Informasi',
                'mitra_magang' => $p->mitra?->nama ?? $p->mitra_magang_text ?? '-',
                'posisi_magang' => $p->posisi_magang ?? '-',
                'tahun_ajaran' => $p->tahun_ajaran,
                'periode_akademik_id' => $p->periode_akademik_id,
                'pic_verifikator' => $p->picVerifikator?->nama ?? '-',
                'dpl' => $p->dpl?->nama ?? '-',
                'penguji_semhas' => $p->pengujiSemhas?->nama ?? '-',
                'nama_supervisor' => $rekap['nama_supervisor'] ?? '-',
                'rata_rata_supervisor' => $rekap['rata_rata_supervisor'],
                'rata_rata_dpl' => $rekap['rata_rata_dpl'],
                'rata_rata_penguji' => $rekap['rata_rata_penguji'],
                'rata_rata_nilai_akhir' => $rekap['rata_rata_nilai_akhir'],
                'komponen_terisi' => $rekap['komponen_terisi'],
                'status_kelengkapan' => $rekap['status_kelengkapan'],
            ];
        });

        // Filter status kelengkapan jika diminta
        if ($request->filled('status_kelengkapan') && ! in_array($request->input('status_kelengkapan'), ['Semua', 'Semua Status'], true)) {
            $filterStatus = $request->input('status_kelengkapan');
            $studentList = $studentList->filter(function ($item) use ($filterStatus) {
                if ($filterStatus === 'Lengkap') {
                    return $item['status_kelengkapan'] === 'Lengkap';
                }
                if ($filterStatus === 'Sebagian' || $filterStatus === 'Sebagian / Proses') {
                    return $item['status_kelengkapan'] === 'Sebagian';
                }
                if ($filterStatus === 'Belum Dinilai') {
                    return $item['status_kelengkapan'] === 'Belum Dinilai';
                }

                return true;
            })->values();
        }

        // Statistik global
        $totalMhs = $studentList->count();
        $lengkapCount = $studentList->where('status_kelengkapan', 'Lengkap')->count();
        $sebagianCount = $studentList->where('status_kelengkapan', 'Sebagian')->count();
        $belumCount = $studentList->where('status_kelengkapan', 'Belum Dinilai')->count();

        $completedAverages = $studentList->pluck('rata_rata_nilai_akhir')->filter(fn ($v) => $v !== null)->values()->all();
        $rataRataGlobal = count($completedAverages) > 0 ? round(array_sum($completedAverages) / count($completedAverages), 2) : 0;

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_mahasiswa' => $totalMhs,
                'lengkap_count' => $lengkapCount,
                'sebagian_count' => $sebagianCount,
                'belum_dinilai_count' => $belumCount,
                'rata_rata_akhir_global' => $rataRataGlobal,
            ],
            'data' => $studentList->values()->all(),
        ]);
    }

    /**
     * GET /api/v1/penilaian-akhir/rekap-ketua/{pendaftaranId}
     * Menampilkan detail lengkap Read-Only nilai gabungan dan rincian semhas (Khusus Ketua Tim MBKM)
     */
    public function showRekapKetua(Request $request, int $pendaftaranId): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('Ketua Tim MBKM')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Ketua Tim MBKM yang memiliki hak akses ke detail Rekap Nilai Akhir.',
            ], Response::HTTP_FORBIDDEN);
        }

        $pendaftaran = PendaftaranMbkm::with([
            'user',
            'programStudi',
            'mitra',
            'dpl.dosen',
            'picVerifikator',
            'pengujiSemhas.dosen',
            'periodeAkademik',
            'penilaianSupervisor.mataKuliahs',
            'penilaianDpl.mataKuliahs',
            'penilaianSemhas.mataKuliahs',
            'pengajuanSemhas',
            'bimbingans',
            'rkm.items.jobdesks',
        ])->findOrFail($pendaftaranId);

        $rekap = $this->hitungRekapMahasiswa($pendaftaran);
        $semhas = $pendaftaran->pengajuanSemhas;
        $penilaianSemhas = $pendaftaran->penilaianSemhas;

        $berkasSemhas = [
            'laporan_akhir' => [
                'nama' => $semhas?->laporan_akhir_name ?: ($semhas?->laporan_akhir_path ? basename($semhas->laporan_akhir_path) : null),
                'stream_url' => $semhas?->id ? url("/api/v1/semhas/{$semhas->id}/file/laporan_akhir") : null,
                'download_url' => $semhas?->id ? url("/api/v1/semhas/{$semhas->id}/file/laporan_akhir?mode=download") : null,
            ],
            'logbook_fb07' => [
                'nama' => $semhas?->logbook_fb07_name ?: ($semhas?->logbook_fb07_path ? basename($semhas->logbook_fb07_path) : null),
                'stream_url' => $semhas?->id ? url("/api/v1/semhas/{$semhas->id}/file/logbook_fb07") : null,
                'download_url' => $semhas?->id ? url("/api/v1/semhas/{$semhas->id}/file/logbook_fb07?mode=download") : null,
            ],
            'nilai_supervisor' => [
                'nama' => $semhas?->nilai_supervisor_name ?: ($semhas?->nilai_supervisor_path ? basename($semhas->nilai_supervisor_path) : null),
                'stream_url' => $semhas?->id ? url("/api/v1/semhas/{$semhas->id}/file/nilai_supervisor") : null,
                'download_url' => $semhas?->id ? url("/api/v1/semhas/{$semhas->id}/file/nilai_supervisor?mode=download") : null,
            ],
        ];

        $jadwalSemhas = [
            'jadwal_tanggal' => $semhas?->jadwal_tanggal ? (is_string($semhas->jadwal_tanggal) ? $semhas->jadwal_tanggal : $semhas->jadwal_tanggal->format('Y-m-d')) : null,
            'jadwal_waktu_mulai' => $semhas?->jadwal_waktu_mulai,
            'jadwal_waktu_selesai' => $semhas?->jadwal_waktu_selesai,
            'jadwal_lokasi' => $semhas?->jadwal_lokasi,
        ];

        $rincianSemhas = null;
        if ($penilaianSemhas) {
            $rincianSemhas = [
                'nilai_kelengkapan_laporan' => (float) $penilaianSemhas->nilai_kelengkapan_laporan,
                'deskripsi_kelengkapan_laporan' => $penilaianSemhas->deskripsi_kelengkapan_laporan,
                'nilai_slide' => (float) $penilaianSemhas->nilai_slide,
                'deskripsi_slide' => $penilaianSemhas->deskripsi_slide,
                'nilai_cara_presentasi' => (float) $penilaianSemhas->nilai_cara_presentasi,
                'deskripsi_cara_presentasi' => $penilaianSemhas->deskripsi_cara_presentasi,
                'nilai_tanya_jawab' => (float) $penilaianSemhas->nilai_tanya_jawab,
                'deskripsi_tanya_jawab' => $penilaianSemhas->deskripsi_tanya_jawab,
                'nilai_presentasi' => (float) $penilaianSemhas->nilai_presentasi,
                'mata_kuliahs' => $penilaianSemhas->mataKuliahs->map(fn ($mk) => [
                    'kode_mk' => $mk->kode_mk,
                    'nama_mk' => $mk->nama_mk,
                    'nilai_mata_kuliah' => (float) $mk->nilai_mata_kuliah,
                    'nilai_presentasi' => (float) $mk->nilai_presentasi,
                    'nilai_total' => (float) $mk->nilai_total,
                ]),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pendaftaran_mbkm_id' => $pendaftaran->id,
                'nim' => $pendaftaran->nim,
                'nama_lengkap' => $pendaftaran->nama_lengkap,
                'program_studi' => $pendaftaran->programStudi?->nama ?? 'Teknologi Informasi',
                'mitra_magang' => $pendaftaran->mitra?->nama ?? $pendaftaran->mitra_magang_text ?? '-',
                'posisi_magang' => $pendaftaran->posisi_magang ?? '-',
                'tahun_ajaran' => $pendaftaran->tahun_ajaran,
                'periode_akademik_id' => $pendaftaran->periode_akademik_id,
                'pic_verifikator' => $pendaftaran->picVerifikator?->nama ?? '-',
                'nama_supervisor' => $rekap['nama_supervisor'] ?? '-',
                'dpl' => [
                    'nama' => $pendaftaran->dpl?->nama ?? '-',
                    'nip' => $pendaftaran->dpl?->dosen?->nip ?? $pendaftaran->dpl?->nim_nip ?? '-',
                    'bimbingan_count' => $pendaftaran->bimbingans->count(),
                ],
                'penguji_semhas' => [
                    'nama' => $pendaftaran->pengujiSemhas?->nama ?? '-',
                    'nip' => $pendaftaran->pengujiSemhas?->dosen?->nip ?? $pendaftaran->pengujiSemhas?->nim_nip ?? '-',
                ],
                'jadwal_semhas' => $jadwalSemhas,
                'berkas_semhas' => $berkasSemhas,
                'rekap' => $rekap,
                'rincian_semhas' => $rincianSemhas,
            ],
        ]);
    }

    /* ========================================================================= */
    /* BAGIAN D: PORTAL NILAI AKHIR MAHASISWA                                    */
    /* ========================================================================= */

    /**
     * GET /api/v1/penilaian-akhir/my
     * Nilai akhir gabungan khusus mahasiswa login
     */
    public function my(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('Mahasiswa')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya mahasiswa yang dapat mengakses halaman ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        $periode = $this->resolvePeriode($request);

        $query = PendaftaranMbkm::with([
            'programStudi',
            'mitra',
            'dpl',
            'picVerifikator',
            'pengujiSemhas',
            'penilaianSupervisor.mataKuliahs',
            'penilaianDpl.mataKuliahs',
            'penilaianSemhas.mataKuliahs',
            'rkm.items.jobdesks',
            'periodeAkademik',
        ])
            ->where('user_id', $user->id)
            ->where('status', 'Terverifikasi');

        if ($request->filled('periode_akademik_id')) {
            $query->where('periode_akademik_id', $request->input('periode_akademik_id'));
        } elseif ($periode) {
            $query->where('periode_akademik_id', $periode->id);
        }

        $pendaftaran = $query->latest('id')->first();

        if (! $pendaftaran) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $rekap = $this->hitungRekapMahasiswa($pendaftaran);

        return response()->json([
            'success' => true,
            'data' => [
                'pendaftaran_mbkm_id' => $pendaftaran->id,
                'nim' => $pendaftaran->nim,
                'nama_lengkap' => $pendaftaran->nama_lengkap,
                'program_studi' => $pendaftaran->programStudi?->nama ?? 'Teknologi Informasi',
                'mitra_magang' => $pendaftaran->mitra?->nama ?? $pendaftaran->mitra_magang_text ?? '-',
                'posisi_magang' => $pendaftaran->posisi_magang ?? '-',
                'tahun_ajaran' => $pendaftaran->tahun_ajaran,
                'periode_akademik_id' => $pendaftaran->periode_akademik_id,
                'pic_verifikator' => $pendaftaran->picVerifikator?->nama ?? '-',
                'dpl' => $pendaftaran->dpl?->nama ?? '-',
                'penguji_semhas' => $pendaftaran->pengujiSemhas?->nama ?? '-',
                'nama_supervisor' => $rekap['nama_supervisor'] ?? '-',
                'komponen_terisi' => $rekap['komponen_terisi'],
                'status_kelengkapan' => $rekap['status_kelengkapan'],
                'sudah_nilai_supervisor' => $rekap['sudah_nilai_supervisor'],
                'sudah_nilai_dpl' => $rekap['sudah_nilai_dpl'],
                'sudah_nilai_penguji' => $rekap['sudah_nilai_penguji'],
                'rata_rata_supervisor' => $rekap['rata_rata_supervisor'],
                'rata_rata_dpl' => $rekap['rata_rata_dpl'],
                'rata_rata_penguji' => $rekap['rata_rata_penguji'],
                'rata_rata_nilai_akhir' => $rekap['rata_rata_nilai_akhir'],
                'mata_kuliahs' => $rekap['mata_kuliahs'],
            ],
        ]);
    }
}
