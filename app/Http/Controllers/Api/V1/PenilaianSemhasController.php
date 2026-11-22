<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PendaftaranMbkm;
use App\Models\PengajuanSemhas;
use App\Models\PenilaianSemhas;
use App\Models\PenilaianSemhasMataKuliah;
use App\Models\PeriodeAkademik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PenilaianSemhasController extends Controller
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

    /**
     * GET /api/v1/penilaian-semhas/penguji
     * Menampilkan daftar mahasiswa yang ditugaskan ke Penguji Semhas login
     */
    public function indexPenguji(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('Penguji Semhas')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses sebagai Penguji Semhas.',
            ], Response::HTTP_FORBIDDEN);
        }

        $periode = $this->resolvePeriode($request);

        $query = PendaftaranMbkm::with([
            'user',
            'programStudi',
            'mitra',
            'dpl',
            'pengajuanSemhas',
            'penilaianSemhas',
            'periodeAkademik',
        ])
            ->where('penguji_semhas_id', $user->id);

        // Filter Periode Akademik / Tahun Ajaran
        if ($request->filled('tahun_ajaran') && in_array(strtolower(trim($request->input('tahun_ajaran'))), ['semua', 'semua tahun ajaran', 'all'], true)) {
            // Tampilkan semua tahun ajaran
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

        // Filter Status Penilaian: Semua, Sudah Dinilai, Belum Dinilai
        if ($request->filled('status_penilaian')) {
            $status = $request->input('status_penilaian');
            if (in_array($status, ['Sudah Dinilai', 'Sudah'], true)) {
                $query->whereHas('penilaianSemhas');
            } elseif (in_array($status, ['Belum Dinilai', 'Belum'], true)) {
                $query->whereDoesntHave('penilaianSemhas');
            }
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

        $data = $students->map(function (PendaftaranMbkm $p) {
            $semhas = $p->pengajuanSemhas;
            $penilaian = $p->penilaianSemhas;

            $jadwalSemhas = [
                'jadwal_tanggal' => $semhas?->jadwal_tanggal ? (is_string($semhas->jadwal_tanggal) ? $semhas->jadwal_tanggal : $semhas->jadwal_tanggal->format('Y-m-d')) : null,
                'jadwal_waktu_mulai' => $semhas?->jadwal_waktu_mulai,
                'jadwal_waktu_selesai' => $semhas?->jadwal_waktu_selesai,
                'jadwal_lokasi' => $semhas?->jadwal_lokasi,
            ];

            return [
                'pendaftaran_mbkm_id' => $p->id,
                'pengajuan_semhas_id' => $semhas?->id,
                'nim' => $p->nim,
                'nama_lengkap' => $p->nama_lengkap,
                'program_studi' => $p->programStudi?->nama ?? 'Teknologi Informasi',
                'mitra_magang' => $p->mitra?->nama ?? $p->mitra_magang_text ?? '-',
                'posisi_magang' => $p->posisi_magang ?? '-',
                'tahun_ajaran' => $p->tahun_ajaran,
                'periode_akademik_id' => $p->periode_akademik_id,
                'dpl' => $p->dpl?->nama ?? '-',
                'jadwal_semhas' => $jadwalSemhas,
                'sudah_dinilai' => $penilaian !== null,
                'nilai_presentasi' => $penilaian?->nilai_presentasi !== null ? (float) $penilaian->nilai_presentasi : null,
                'rata_rata_nilai_total' => $penilaian?->rata_rata_nilai_total !== null ? (float) $penilaian->rata_rata_nilai_total : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * GET /api/v1/penilaian-semhas/{pendaftaranId}
     * Menampilkan detail mahasiswa, berkas Semhas, jadwal, MK konversi, & nilai (jika ada)
     */
    public function show(Request $request, int $pendaftaranId): JsonResponse
    {
        $user = $request->user();

        $pendaftaran = PendaftaranMbkm::with([
            'user',
            'programStudi',
            'mitra',
            'dpl',
            'pengujiSemhas',
            'periodeAkademik',
            'pengajuanSemhas',
            'penilaianSemhas.mataKuliahs',
            'rkm.items' => function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('status_mk')
                        ->orWhere('status_mk', 'aktif')
                        ->orWhere('status_mk', '!=', 'drop');
                })->with(['jobdesks' => fn ($jq) => $jq->orderBy('urutan')]);
            },
        ])->findOrFail($pendaftaranId);

        $isAssignedPenguji = (int) $pendaftaran->penguji_semhas_id === (int) $user->id;
        $isKetua = $user->hasRole('Ketua Tim MBKM');

        // Tolak Admin atau Penguji lain
        if (! $isAssignedPenguji && ! $isKetua) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat atau menilai seminar hasil ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->first();
        $isPeriodeAktif = $periodeAktif && (int) $pendaftaran->periode_akademik_id === (int) $periodeAktif->id;
        $canEdit = $isAssignedPenguji && $isPeriodeAktif;

        $semhas = $pendaftaran->pengajuanSemhas;
        $penilaian = $pendaftaran->penilaianSemhas;

        // Siapkan MK Konversi dari RKM aktif
        $rkmItems = $pendaftaran->rkm?->items ?? collect();
        $mataKuliahs = $rkmItems->map(function ($item) {
            $jobdeskList = $item->jobdesks->pluck('deskripsi')->filter()->values()->all();
            $aktivitasStr = ! empty($jobdeskList)
                ? implode("\n", array_map(fn ($d, $i) => ($i + 1).". {$d}", $jobdeskList, array_keys($jobdeskList)))
                : '-';

            return [
                'mata_kuliah_id' => $item->mata_kuliah_id,
                'kode_mk' => $item->kode_mk,
                'nama_mk' => $item->nama_mk,
                'sks' => (int) $item->sks,
                'kompetensi' => $item->kompetensi ?? '-',
                'jobdesks' => $jobdeskList,
                'aktivitas_pembelajaran' => $aktivitasStr,
            ];
        })->values()->all();

        // Siapkan detail penilaian jika sudah dinilai
        $penilaianData = null;
        if ($penilaian) {
            $penilaianData = [
                'id' => $penilaian->id,
                'nilai_kelengkapan_laporan' => (float) $penilaian->nilai_kelengkapan_laporan,
                'deskripsi_kelengkapan_laporan' => $penilaian->deskripsi_kelengkapan_laporan ?? '',
                'nilai_slide' => (float) $penilaian->nilai_slide,
                'deskripsi_slide' => $penilaian->deskripsi_slide ?? '',
                'nilai_cara_presentasi' => (float) $penilaian->nilai_cara_presentasi,
                'deskripsi_cara_presentasi' => $penilaian->deskripsi_cara_presentasi ?? '',
                'nilai_tanya_jawab' => (float) $penilaian->nilai_tanya_jawab,
                'deskripsi_tanya_jawab' => $penilaian->deskripsi_tanya_jawab ?? '',
                'nilai_presentasi' => (float) $penilaian->nilai_presentasi,
                'rata_rata_nilai_total' => (float) $penilaian->rata_rata_nilai_total,
                'updated_at' => $penilaian->updated_at?->toISOString(),
                'mata_kuliahs' => $penilaian->mataKuliahs->map(fn ($mk) => [
                    'id' => $mk->id,
                    'mata_kuliah_id' => $mk->mata_kuliah_id,
                    'kode_mk' => $mk->kode_mk,
                    'nama_mk' => $mk->nama_mk,
                    'sks' => (int) $mk->sks,
                    'kompetensi' => $mk->kompetensi,
                    'aktivitas_pembelajaran' => $mk->aktivitas_pembelajaran,
                    'nilai_mata_kuliah' => (float) $mk->nilai_mata_kuliah,
                    'nilai_presentasi' => (float) $mk->nilai_presentasi,
                    'nilai_total' => (float) $mk->nilai_total,
                ])->values()->all(),
            ];
        }

        $jadwalSemhas = [
            'jadwal_tanggal' => $semhas?->jadwal_tanggal ? (is_string($semhas->jadwal_tanggal) ? $semhas->jadwal_tanggal : $semhas->jadwal_tanggal->format('Y-m-d')) : null,
            'jadwal_waktu_mulai' => $semhas?->jadwal_waktu_mulai,
            'jadwal_waktu_selesai' => $semhas?->jadwal_waktu_selesai,
            'jadwal_lokasi' => $semhas?->jadwal_lokasi,
        ];

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

        return response()->json([
            'success' => true,
            'data' => [
                'pendaftaran_mbkm_id' => $pendaftaran->id,
                'pengajuan_semhas_id' => $semhas?->id,
                'nim' => $pendaftaran->nim,
                'nama_lengkap' => $pendaftaran->nama_lengkap,
                'program_studi' => $pendaftaran->programStudi?->nama ?? 'Teknologi Informasi',
                'mitra_magang' => $pendaftaran->mitra?->nama ?? $pendaftaran->mitra_magang_text ?? '-',
                'posisi_magang' => $pendaftaran->posisi_magang ?? '-',
                'tahun_ajaran' => $pendaftaran->tahun_ajaran,
                'periode_akademik_id' => $pendaftaran->periode_akademik_id,
                'is_periode_aktif' => $isPeriodeAktif,
                'can_edit' => $canEdit,
                'dpl' => [
                    'id' => $pendaftaran->dpl?->id,
                    'nama' => $pendaftaran->dpl?->nama ?? '-',
                    'nip' => $pendaftaran->dpl?->nip ?? '-',
                ],
                'penguji_semhas' => [
                    'id' => $pendaftaran->pengujiSemhas?->id,
                    'nama' => $pendaftaran->pengujiSemhas?->nama ?? '-',
                    'nip' => $pendaftaran->pengujiSemhas?->nip ?? '-',
                ],
                'jadwal_semhas' => $jadwalSemhas,
                'berkas_semhas' => $berkasSemhas,
                'mata_kuliah_konversi' => $mataKuliahs,
                'penilaian' => $penilaianData,
            ],
        ]);
    }

    /**
     * POST /api/v1/penilaian-semhas/{pendaftaranId}/jadwal
     * Menyimpan / memperbarui jadwal Seminar Hasil mahasiswa oleh Penguji Semhas
     */
    public function storeJadwal(Request $request, int $pendaftaranId): JsonResponse
    {
        $user = $request->user();
        $pendaftaran = PendaftaranMbkm::findOrFail($pendaftaranId);

        if ((int) $pendaftaran->penguji_semhas_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan dosen penguji semhas untuk mahasiswa ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'jadwal_tanggal' => ['required', 'date'],
            'jadwal_waktu_mulai' => ['required', 'string', 'max:20'],
            'jadwal_waktu_selesai' => ['nullable', 'string', 'max:20'],
            'jadwal_lokasi' => ['nullable', 'string', 'max:255'],
        ]);

        $semhas = PengajuanSemhas::firstOrCreate(
            ['pendaftaran_mbkm_id' => $pendaftaran->id],
            ['periode_akademik_id' => $pendaftaran->periode_akademik_id]
        );

        $semhas->update([
            'jadwal_tanggal' => $validated['jadwal_tanggal'],
            'jadwal_waktu_mulai' => $validated['jadwal_waktu_mulai'],
            'jadwal_waktu_selesai' => $validated['jadwal_waktu_selesai'] ?? null,
            'jadwal_lokasi' => $validated['jadwal_lokasi'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal Seminar Hasil berhasil disimpan.',
            'data' => [
                'jadwal_tanggal' => is_string($semhas->jadwal_tanggal) ? $semhas->jadwal_tanggal : $semhas->jadwal_tanggal?->format('Y-m-d'),
                'jadwal_waktu_mulai' => $semhas->jadwal_waktu_mulai,
                'jadwal_waktu_selesai' => $semhas->jadwal_waktu_selesai,
                'jadwal_lokasi' => $semhas->jadwal_lokasi,
            ],
        ]);
    }

    /**
     * POST /api/v1/penilaian-semhas/{pendaftaranId}/nilai
     * Menyimpan / memperbarui nilai presentasi & nilai substansi per mata kuliah
     */
    public function storeNilai(Request $request, int $pendaftaranId): JsonResponse
    {
        $user = $request->user();
        $pendaftaran = PendaftaranMbkm::with('pengajuanSemhas')->findOrFail($pendaftaranId);

        if ((int) $pendaftaran->penguji_semhas_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan dosen penguji semhas untuk mahasiswa ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'nilai_kelengkapan_laporan' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_slide' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_cara_presentasi' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_tanya_jawab' => ['required', 'numeric', 'min:0', 'max:100'],
            'deskripsi_kelengkapan_laporan' => ['nullable', 'string', 'max:1000'],
            'deskripsi_slide' => ['nullable', 'string', 'max:1000'],
            'deskripsi_cara_presentasi' => ['nullable', 'string', 'max:1000'],
            'deskripsi_tanya_jawab' => ['nullable', 'string', 'max:1000'],

            'nilai_mata_kuliahs' => ['required', 'array', 'min:1'],
            'nilai_mata_kuliahs.*.kode_mk' => ['required', 'string'],
            'nilai_mata_kuliahs.*.nama_mk' => ['required', 'string'],
            'nilai_mata_kuliahs.*.sks' => ['nullable', 'integer'],
            'nilai_mata_kuliahs.*.mata_kuliah_id' => ['nullable', 'integer'],
            'nilai_mata_kuliahs.*.kompetensi' => ['nullable', 'string'],
            'nilai_mata_kuliahs.*.aktivitas_pembelajaran' => ['nullable', 'string'],
            'nilai_mata_kuliahs.*.nilai_mata_kuliah' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $laporan = (float) $validated['nilai_kelengkapan_laporan'];
        $slide = (float) $validated['nilai_slide'];
        $presentasi = (float) $validated['nilai_cara_presentasi'];
        $tanyaJawab = (float) $validated['nilai_tanya_jawab'];

        // 1. Hitung Nilai Presentasi: (20% Laporan) + (20% Slide) + (20% Presentasi) + (40% Tanya Jawab)
        $nilaiPresentasi = PenilaianSemhas::kalkulasiNilaiPresentasi($laporan, $slide, $presentasi, $tanyaJawab);

        // 2. Hitung Nilai Total per Mata Kuliah: (75% Nilai MK) + (25% Nilai Presentasi)
        $totalScores = [];
        foreach ($validated['nilai_mata_kuliahs'] as $mk) {
            $totalScores[] = PenilaianSemhasMataKuliah::kalkulasiNilaiTotal(
                (float) $mk['nilai_mata_kuliah'],
                $nilaiPresentasi
            );
        }

        // 3. Rata-rata Nilai Total
        $rataRataTotal = count($totalScores) > 0 ? round(array_sum($totalScores) / count($totalScores), 2) : 0;

        $penilaian = DB::transaction(function () use (
            $pendaftaran,
            $user,
            $validated,
            $laporan,
            $slide,
            $presentasi,
            $tanyaJawab,
            $nilaiPresentasi,
            $rataRataTotal
        ) {
            $record = PenilaianSemhas::updateOrCreate(
                ['pendaftaran_mbkm_id' => $pendaftaran->id],
                [
                    'pengajuan_semhas_id' => $pendaftaran->pengajuanSemhas?->id,
                    'periode_akademik_id' => $pendaftaran->periode_akademik_id,
                    'penguji_id' => $user->id,
                    'nilai_kelengkapan_laporan' => $laporan,
                    'deskripsi_kelengkapan_laporan' => $validated['deskripsi_kelengkapan_laporan'] ?? null,
                    'nilai_slide' => $slide,
                    'deskripsi_slide' => $validated['deskripsi_slide'] ?? null,
                    'nilai_cara_presentasi' => $presentasi,
                    'deskripsi_cara_presentasi' => $validated['deskripsi_cara_presentasi'] ?? null,
                    'nilai_tanya_jawab' => $tanyaJawab,
                    'deskripsi_tanya_jawab' => $validated['deskripsi_tanya_jawab'] ?? null,
                    'nilai_presentasi' => $nilaiPresentasi,
                    'rata_rata_nilai_total' => $rataRataTotal,
                ]
            );

            // Simpan / update setiap mata kuliah
            foreach ($validated['nilai_mata_kuliahs'] as $mk) {
                $nilaiMk = (float) $mk['nilai_mata_kuliah'];
                $nilaiTotal = PenilaianSemhasMataKuliah::kalkulasiNilaiTotal($nilaiMk, $nilaiPresentasi);

                PenilaianSemhasMataKuliah::updateOrCreate(
                    [
                        'penilaian_semhas_id' => $record->id,
                        'kode_mk' => $mk['kode_mk'],
                    ],
                    [
                        'mata_kuliah_id' => $mk['mata_kuliah_id'] ?? null,
                        'nama_mk' => $mk['nama_mk'],
                        'sks' => $mk['sks'] ?? 0,
                        'kompetensi' => $mk['kompetensi'] ?? null,
                        'aktivitas_pembelajaran' => $mk['aktivitas_pembelajaran'] ?? null,
                        'nilai_mata_kuliah' => $nilaiMk,
                        'nilai_presentasi' => $nilaiPresentasi,
                        'nilai_total' => $nilaiTotal,
                    ]
                );
            }

            return $record->load('mataKuliahs');
        });

        return response()->json([
            'success' => true,
            'message' => 'Penilaian Seminar Hasil berhasil disimpan.',
            'data' => [
                'id' => $penilaian->id,
                'nilai_presentasi' => $penilaian->nilai_presentasi,
                'rata_rata_nilai_total' => $penilaian->rata_rata_nilai_total,
                'mata_kuliahs' => $penilaian->mataKuliahs->map(fn ($mk) => [
                    'kode_mk' => $mk->kode_mk,
                    'nama_mk' => $mk->nama_mk,
                    'nilai_mata_kuliah' => $mk->nilai_mata_kuliah,
                    'nilai_presentasi' => $mk->nilai_presentasi,
                    'nilai_total' => $mk->nilai_total,
                ]),
            ],
        ]);
    }

    /**
     * GET /api/v1/penilaian-semhas/rekap-ketua
     * Rekap nilai Semhas seluruh mahasiswa lintas dosen penguji (Khusus Ketua Tim MBKM)
     */
    public function rekapKetua(Request $request): JsonResponse
    {
        $user = $request->user();

        // Khusus Role Ketua Tim MBKM, tolak Admin dan role lain
        if (! $user->hasRole('Ketua Tim MBKM')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Ketua Tim MBKM yang memiliki akses ke Rekap Nilai Semhas.',
            ], Response::HTTP_FORBIDDEN);
        }

        $periode = $this->resolvePeriode($request);

        $query = PendaftaranMbkm::with([
            'user',
            'programStudi',
            'mitra',
            'pengujiSemhas',
            'pengajuanSemhas',
            'penilaianSemhas',
            'periodeAkademik',
        ])
            ->whereNotNull('penguji_semhas_id');

        // Filter Periode Akademik / Tahun Ajaran
        if ($request->filled('tahun_ajaran') && in_array(strtolower(trim($request->input('tahun_ajaran'))), ['semua', 'semua tahun ajaran', 'all'], true)) {
            // Tampilkan semua tahun ajaran
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

        // Filter Status Penilaian: Semua, Sudah Dinilai, Belum Dinilai
        if ($request->filled('status_penilaian')) {
            $status = $request->input('status_penilaian');
            if (in_array($status, ['Sudah Dinilai', 'Sudah'], true)) {
                $query->whereHas('penilaianSemhas');
            } elseif (in_array($status, ['Belum Dinilai', 'Belum'], true)) {
                $query->whereDoesntHave('penilaianSemhas');
            }
        }

        // Search NIM, Nama Mahasiswa, Mitra, Penguji
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('posisi_magang', 'like', "%{$search}%")
                    ->orWhereHas('mitra', fn ($mq) => $mq->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('pengujiSemhas', fn ($dq) => $dq->where('nama', 'like', "%{$search}%"));
            });
        }

        $students = $query->orderBy('id', 'desc')->get();

        $data = $students->map(function (PendaftaranMbkm $p) {
            $semhas = $p->pengajuanSemhas;
            $penilaian = $p->penilaianSemhas;

            $jadwalSemhas = [
                'jadwal_tanggal' => $semhas?->jadwal_tanggal ? (is_string($semhas->jadwal_tanggal) ? $semhas->jadwal_tanggal : $semhas->jadwal_tanggal->format('Y-m-d')) : null,
                'jadwal_waktu_mulai' => $semhas?->jadwal_waktu_mulai,
                'jadwal_waktu_selesai' => $semhas?->jadwal_waktu_selesai,
                'jadwal_lokasi' => $semhas?->jadwal_lokasi,
            ];

            return [
                'pendaftaran_mbkm_id' => $p->id,
                'pengajuan_semhas_id' => $semhas?->id,
                'nim' => $p->nim,
                'nama_lengkap' => $p->nama_lengkap,
                'program_studi' => $p->programStudi?->nama ?? 'Teknologi Informasi',
                'mitra_magang' => $p->mitra?->nama ?? $p->mitra_magang_text ?? '-',
                'posisi_magang' => $p->posisi_magang ?? '-',
                'tahun_ajaran' => $p->tahun_ajaran,
                'periode_akademik_id' => $p->periode_akademik_id,
                'penguji_semhas' => $p->pengujiSemhas?->nama ?? '-',
                'penguji_semhas_id' => $p->penguji_semhas_id,
                'jadwal_semhas' => $jadwalSemhas,
                'sudah_dinilai' => $penilaian !== null,
                'nilai_presentasi' => $penilaian?->nilai_presentasi !== null ? (float) $penilaian->nilai_presentasi : null,
                'rata_rata_nilai_total' => $penilaian?->rata_rata_nilai_total !== null ? (float) $penilaian->rata_rata_nilai_total : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
