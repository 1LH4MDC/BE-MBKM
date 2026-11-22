<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PendaftaranMbkm;
use App\Models\PengajuanSemhas;
use App\Models\PeriodeAkademik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SemhasController extends Controller
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
     * Mengambil status prasyarat Semhas mahasiswa login
     */
    public function prasyarat(Request $request): JsonResponse
    {
        $user = $request->user();
        $periode = $this->resolvePeriode($request);

        $pendaftaran = PendaftaranMbkm::with(['rkm', 'periodeAkademik', 'bimbingans'])
            ->where('user_id', $user->id)
            ->when($periode, fn ($q) => $q->where('periode_akademik_id', $periode->id))
            ->latest('id')
            ->first();

        if (! $pendaftaran) {
            return response()->json([
                'success' => true,
                'data' => [
                    'pendaftaran_id' => null,
                    'status_rkm' => 'Belum Mengajukan',
                    'rkm_approved' => false,
                    'rkmApproved' => false,
                    'bimbingan_count' => 0,
                    'jumlah_bimbingan' => 0,
                    'bimbinganCount' => 0,
                    'bimbingan_min' => 8,
                    'bimbinganMin' => 8,
                    'bimbingan_eligible' => false,
                    'bimbinganEligible' => false,
                    'sudah_isi_kuisioner' => false,
                    'sudahIsiKuisioner' => false,
                    'tahun_ajaran' => $periode?->nama_lengkap ?? '2026/2027 Ganjil',
                ],
            ]);
        }

        $rkmStatus = $pendaftaran->rkm?->status ?? 'Belum Mengajukan';
        $rkmApproved = in_array($rkmStatus, ['Disetujui', 'Terverifikasi'], true);
        $bimbinganCount = $pendaftaran->bimbingans()->count();
        $sudahIsiKuisioner = (bool) $pendaftaran->sudah_isi_kuisioner;
        $tahunAjaranLabel = $pendaftaran->tahun_ajaran ?: ($pendaftaran->periodeAkademik?->nama_lengkap ?? $periode?->nama_lengkap ?? '2026/2027 Ganjil');

        return response()->json([
            'success' => true,
            'data' => [
                'pendaftaran_id' => $pendaftaran->id,
                'status_rkm' => $rkmStatus,
                'rkm_approved' => $rkmApproved,
                'rkmApproved' => $rkmApproved,
                'bimbingan_count' => $bimbinganCount,
                'jumlah_bimbingan' => $bimbinganCount,
                'bimbinganCount' => $bimbinganCount,
                'bimbingan_min' => 8,
                'bimbinganMin' => 8,
                'bimbingan_eligible' => $bimbinganCount >= 8,
                'bimbinganEligible' => $bimbinganCount >= 8,
                'sudah_isi_kuisioner' => $sudahIsiKuisioner,
                'sudahIsiKuisioner' => $sudahIsiKuisioner,
                'tahun_ajaran' => $tahunAjaranLabel,
            ],
        ]);
    }

    /**
     * Mengambil pengajuan Semhas milik mahasiswa login
     */
    public function my(Request $request): JsonResponse
    {
        $user = $request->user();
        $periode = $this->resolvePeriode($request);

        $pendaftaran = PendaftaranMbkm::where('user_id', $user->id)
            ->when($periode, fn ($q) => $q->where('periode_akademik_id', $periode->id))
            ->latest('id')
            ->first();

        if (! $pendaftaran) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $semhas = PengajuanSemhas::with([
            'pendaftaranMbkm.user',
            'pendaftaranMbkm.programStudi',
            'pendaftaranMbkm.mitra',
            'pendaftaranMbkm.dpl',
            'pendaftaranMbkm.picVerifikator',
            'pendaftaranMbkm.pengujiSemhas',
            'pendaftaranMbkm.rkm',
            'verifiedByPic',
            'overrideByKetua',
            'periodeAkademik',
        ])->where('pendaftaran_mbkm_id', $pendaftaran->id)->first();

        if (! $semhas) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->mapSemhasPayload($semhas),
        ]);
    }

    /**
     * Submit / Perbaikan Berkas Pengajuan Semhas Mahasiswa
     */
    public function submit(Request $request): JsonResponse
    {
        $user = $request->user();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->first();

        $pendaftaran = PendaftaranMbkm::where('user_id', $user->id)
            ->when($periodeAktif, fn ($q) => $q->where('periode_akademik_id', $periodeAktif->id))
            ->latest('id')
            ->first();

        if (! $pendaftaran) {
            $pendaftaran = PendaftaranMbkm::where('user_id', $user->id)->latest('id')->first();
        }

        if (! $pendaftaran) {
            return response()->json([
                'success' => false,
                'message' => 'Data pendaftaran MBKM Anda tidak ditemukan.',
            ], 404);
        }

        $existing = PengajuanSemhas::where('pendaftaran_mbkm_id', $pendaftaran->id)->first();

        // Validasi: jika pengajuan baru, 3 berkas wajib. Jika perbaikan, berkas opsional (parsial)
        $rules = [
            'laporan_akhir' => [$existing && $existing->laporan_akhir_path ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:10240'],
            'logbook_fb07' => [$existing && $existing->logbook_fb07_path ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:10240'],
            'nilai_supervisor' => [$existing && $existing->nilai_supervisor_path ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:10240'],
        ];

        $request->validate($rules);

        $laporanPath = $existing?->laporan_akhir_path;
        $laporanName = $existing?->laporan_akhir_name;
        if ($request->hasFile('laporan_akhir')) {
            if ($existing?->laporan_akhir_path && Storage::disk('public')->exists($existing->laporan_akhir_path)) {
                Storage::disk('public')->delete($existing->laporan_akhir_path);
            }
            $file = $request->file('laporan_akhir');
            $laporanPath = $file->store('semhas/laporan_akhir', 'public');
            $laporanName = $file->getClientOriginalName();
        }

        $logbookPath = $existing?->logbook_fb07_path;
        $logbookName = $existing?->logbook_fb07_name;
        if ($request->hasFile('logbook_fb07')) {
            if ($existing?->logbook_fb07_path && Storage::disk('public')->exists($existing->logbook_fb07_path)) {
                Storage::disk('public')->delete($existing->logbook_fb07_path);
            }
            $file = $request->file('logbook_fb07');
            $logbookPath = $file->store('semhas/logbook_fb07', 'public');
            $logbookName = $file->getClientOriginalName();
        }

        $nilaiPath = $existing?->nilai_supervisor_path;
        $nilaiName = $existing?->nilai_supervisor_name;
        if ($request->hasFile('nilai_supervisor')) {
            if ($existing?->nilai_supervisor_path && Storage::disk('public')->exists($existing->nilai_supervisor_path)) {
                Storage::disk('public')->delete($existing->nilai_supervisor_path);
            }
            $file = $request->file('nilai_supervisor');
            $nilaiPath = $file->store('semhas/nilai_supervisor', 'public');
            $nilaiName = $file->getClientOriginalName();
        }

        $semhas = PengajuanSemhas::updateOrCreate(
            ['pendaftaran_mbkm_id' => $pendaftaran->id],
            [
                'periode_akademik_id' => $pendaftaran->periode_akademik_id ?? $periodeAktif?->id,
                'laporan_akhir_path' => $laporanPath,
                'laporan_akhir_name' => $laporanName,
                'logbook_fb07_path' => $logbookPath,
                'logbook_fb07_name' => $logbookName,
                'nilai_supervisor_path' => $nilaiPath,
                'nilai_supervisor_name' => $nilaiName,
                'status_kelayakan' => 'Menunggu Verifikasi',
                'is_override_ketua' => false,
                'catatan_pic' => null,
            ]
        );

        $semhas->load([
            'pendaftaranMbkm.user',
            'pendaftaranMbkm.programStudi',
            'pendaftaranMbkm.mitra',
            'pendaftaranMbkm.dpl',
            'pendaftaranMbkm.picVerifikator',
            'pendaftaranMbkm.rkm',
            'verifiedByPic',
            'overrideByKetua',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berkas persyaratan Seminar Hasil berhasil disimpan dan diajukan ke Tim MBKM.',
            'data' => $this->mapSemhasPayload($semhas),
        ]);
    }

    /**
     * Preview / Stream Berkas PDF Semhas
     */
    public function streamFile(Request $request, int $id, string $jenis): mixed
    {
        $jenis = str_replace('-', '_', strtolower($jenis));
        if (! in_array($jenis, ['laporan_akhir', 'logbook_fb07', 'nilai_supervisor'], true)) {
            return response()->json(['success' => false, 'message' => 'Jenis berkas semhas tidak valid.'], 404);
        }

        $semhas = PengajuanSemhas::with('pendaftaranMbkm')->findOrFail($id);
        $user = $request->user();

        // Otorisasi: Mahasiswa pemilik, DPL pembimbing, PIC, Penguji Semhas tertugas, Ketua, atau Admin
        $isOwner = (int) $semhas->pendaftaranMbkm?->user_id === (int) $user->id;
        $isDpl = (int) $semhas->pendaftaranMbkm?->dpl_id === (int) $user->id;
        $isPic = (int) $semhas->pendaftaranMbkm?->pic_verifikator_id === (int) $user->id;
        $isPenguji = (int) $semhas->pendaftaranMbkm?->penguji_semhas_id === (int) $user->id;
        $isStaff = $user->hasRole('Tim MBKM', 'Ketua Tim MBKM', 'Admin');

        if (! $isOwner && ! $isDpl && ! $isPic && ! $isPenguji && ! $isStaff) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat berkas ini.',
            ], 403);
        }

        $path = match ($jenis) {
            'laporan_akhir' => $semhas->laporan_akhir_path,
            'logbook_fb07' => $semhas->logbook_fb07_path,
            'nilai_supervisor' => $semhas->nilai_supervisor_path,
        };

        $name = match ($jenis) {
            'laporan_akhir' => $semhas->laporan_akhir_name ?? 'Laporan_Akhir.pdf',
            'logbook_fb07' => $semhas->logbook_fb07_name ?? 'Logbook_FB07.pdf',
            'nilai_supervisor' => $semhas->nilai_supervisor_name ?? 'Nilai_Supervisor.pdf',
        };

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return response()->json(['success' => false, 'message' => 'Berkas fisik tidak ditemukan di penyimpanan server.'], 404);
        }

        $fullPath = Storage::disk('public')->path($path);
        $mode = $request->query('mode', 'inline');

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($mode === 'download' ? 'attachment' : 'inline')."; filename=\"{$name}\"",
        ]);
    }

    /**
     * Mengambil daftar pengajuan Semhas khusus tugas PIC Verifikator
     */
    public function indexPic(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = PengajuanSemhas::with([
            'pendaftaranMbkm.user',
            'pendaftaranMbkm.programStudi',
            'pendaftaranMbkm.mitra',
            'pendaftaranMbkm.dpl',
            'pendaftaranMbkm.picVerifikator',
            'pendaftaranMbkm.rkm',
            'verifiedByPic',
            'overrideByKetua',
            'periodeAkademik',
        ]);

        if (! $user->hasRole('Admin')) {
            $query->whereHas('pendaftaranMbkm', function ($q) use ($user) {
                $q->where('pic_verifikator_id', $user->id);
            });
        }

        $this->applyFilters($query, $request);

        $items = $query->latest('id')->get()->map(fn ($s) => $this->mapSemhasPayload($s));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Mengambil seluruh pengajuan Semhas lintas PIC untuk Ketua Tim MBKM
     */
    public function indexKetua(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->hasRole('Ketua Tim MBKM', 'Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Ketua Tim MBKM yang memiliki kewenangan mengakses daftar evaluasi kelayakan lintas PIC.',
            ], 403);
        }

        $query = PengajuanSemhas::with([
            'pendaftaranMbkm.user',
            'pendaftaranMbkm.programStudi',
            'pendaftaranMbkm.mitra',
            'pendaftaranMbkm.dpl',
            'pendaftaranMbkm.picVerifikator',
            'pendaftaranMbkm.rkm',
            'verifiedByPic',
            'overrideByKetua',
            'periodeAkademik',
        ]);

        $this->applyFilters($query, $request);

        $items = $query->latest('id')->get()->map(fn ($s) => $this->mapSemhasPayload($s));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Alias endpoint umum /semhas/daftar
     */
    public function indexDaftar(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->hasRole('Ketua Tim MBKM')) {
            return $this->indexKetua($request);
        }

        return $this->indexPic($request);
    }

    /**
     * Verifikasi Kelayakan oleh PIC Verifikator
     */
    public function verifikasiPic(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status_kelayakan' => ['required', 'in:Layak Semhas,Perbaikan Berkas,Tidak Layak'],
            'catatan' => ['required_if:status_kelayakan,Perbaikan Berkas,Tidak Layak', 'nullable', 'string'],
        ]);

        $semhas = PengajuanSemhas::with('pendaftaranMbkm')->findOrFail($id);
        $user = $request->user();

        // Cek PIC: User harus menjadi PIC pendaftaran tersebut, kecuali Admin atau Ketua
        $isPic = (int) $semhas->pendaftaranMbkm?->pic_verifikator_id === (int) $user->id;
        $isPrivileged = $user->hasRole('Admin', 'Ketua Tim MBKM');

        if (! $isPic && ! $isPrivileged) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak verifikasi karena bukan PIC Verifikator yang ditugaskan pada mahasiswa ini.',
            ], 403);
        }

        // Jika status yang dipilih adalah 'Layak Semhas', validasi:
        // 1. Ketiga dokumen PDF wajib terunggah
        // 2. Mahasiswa sudah menyelesaikan Kuesioner Evaluasi
        // CATATAN: Sesi bimbingan < 8 TIDAK DITOLAK
        if ($request->input('status_kelayakan') === 'Layak Semhas') {
            $hasAllDocs = ! empty($semhas->laporan_akhir_path)
                && ! empty($semhas->logbook_fb07_path)
                && ! empty($semhas->nilai_supervisor_path);

            if (! $hasAllDocs) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'ERR_SEMHAS_DOKUMEN_BELUM_LENGKAP',
                    'message' => 'Ketiga dokumen persyaratan (Laporan Akhir, Logbook FB07, dan Nilai Supervisor) wajib terunggah dalam format PDF sebelum mahasiswa dapat dinyatakan Layak Semhas.',
                ], 422);
            }

            if (! $semhas->pendaftaranMbkm?->sudah_isi_kuisioner) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'ERR_SEMHAS_KUISIONER_BELUM_DIISI',
                    'message' => 'Mahasiswa belum menyelesaikan Kuesioner Evaluasi MBKM. Mahasiswa wajib mengisi kuesioner terlebih dahulu sebelum dinyatakan Layak Semhas.',
                ], 422);
            }
        }

        $semhas->status_kelayakan = $request->input('status_kelayakan');
        $semhas->catatan_pic = $request->input('catatan');
        $semhas->verified_by_pic_id = $user->id;
        $semhas->verified_at_pic = now();
        $semhas->save();

        $semhas->load([
            'pendaftaranMbkm.user',
            'pendaftaranMbkm.programStudi',
            'pendaftaranMbkm.mitra',
            'pendaftaranMbkm.dpl',
            'pendaftaranMbkm.picVerifikator',
            'pendaftaranMbkm.rkm',
            'verifiedByPic',
            'overrideByKetua',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hasil verifikasi kelayakan Seminar Hasil berhasil disimpan.',
            'data' => $this->mapSemhasPayload($semhas),
        ]);
    }

    /**
     * Hak Override Kelayakan oleh Ketua Tim MBKM
     */
    public function overrideKetua(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user->hasRole('Ketua Tim MBKM', 'Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Ketua Tim MBKM yang memiliki kewenangan melakukan override kelayakan Semhas.',
            ], 403);
        }

        $request->validate([
            'catatan_override' => ['required', 'string', 'min:5'],
        ]);

        $semhas = PengajuanSemhas::with('pendaftaranMbkm')->findOrFail($id);

        $semhas->status_kelayakan = 'Layak (Override Ketua)';
        $semhas->is_override_ketua = true;
        $semhas->catatan_override_ketua = $request->input('catatan_override');
        $semhas->override_by_ketua_id = $user->id;
        $semhas->override_at = now();
        $semhas->save();

        $semhas->load([
            'pendaftaranMbkm.user',
            'pendaftaranMbkm.programStudi',
            'pendaftaranMbkm.mitra',
            'pendaftaranMbkm.dpl',
            'pendaftaranMbkm.picVerifikator',
            'pendaftaranMbkm.rkm',
            'verifiedByPic',
            'overrideByKetua',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mahasiswa berhasil diloloskan ke Seminar Hasil melalui mekanisme Hak Override Ketua Tim MBKM.',
            'data' => $this->mapSemhasPayload($semhas),
        ]);
    }

    private function applyFilters($query, Request $request): void
    {
        // 1. Filter Periode / Tahun Ajaran
        $periodeId = $request->input('periode_akademik_id') ?? $request->input('periode_id') ?? $request->query('periode_akademik_id') ?? $request->query('periode_id');
        if (! empty($periodeId) && is_numeric($periodeId)) {
            $pId = (int) $periodeId;
            $query->where(function ($sq) use ($pId) {
                $sq->where('periode_akademik_id', $pId)
                    ->orWhereHas('pendaftaranMbkm', fn ($pq) => $pq->where('periode_akademik_id', $pId));
            });
        } elseif ($request->filled('tahun_ajaran')) {
            $ta = $request->input('tahun_ajaran') ?? $request->query('tahun_ajaran');
            if ($ta && ! in_array(strtolower(trim($ta)), ['semua', 'semua tahun ajaran', 'all'], true)) {
                $trimmed = trim($ta);
                $parts = preg_split('/\s+/', $trimmed);
                $tahun = $parts[0] ?? null;
                $semester = $parts[1] ?? null;
                $query->where(function ($sq) use ($trimmed, $tahun, $semester) {
                    $sq->whereHas('periodeAkademik', function ($q) use ($tahun, $semester) {
                        if ($tahun) {
                            $q->where('tahun_ajaran', $tahun);
                        }
                        if ($semester) {
                            $q->where('semester', $semester);
                        }
                    })->orWhereHas('pendaftaranMbkm', function ($pq) use ($trimmed, $tahun, $semester) {
                        $pq->where('tahun_ajaran', $trimmed)
                            ->orWhereHas('periodeAkademik', function ($q) use ($tahun, $semester) {
                                if ($tahun) {
                                    $q->where('tahun_ajaran', $tahun);
                                }
                                if ($semester) {
                                    $q->where('semester', $semester);
                                }
                            });
                    });
                });
            }
        }

        // 2. Filter Status Kelayakan
        if ($request->filled('status_kelayakan') && ! in_array($request->input('status_kelayakan'), ['Semua', 'Semua Status'], true)) {
            $query->where('status_kelayakan', $request->input('status_kelayakan'));
        }

        // 3. Filter Program Studi
        if ($request->filled('prodi') && ! in_array($request->input('prodi'), ['Semua', 'Semua Prodi'], true)) {
            $prodi = $request->input('prodi');
            $query->whereHas('pendaftaranMbkm.programStudi', function ($q) use ($prodi) {
                $q->where('nama', $prodi)->orWhere('kode', $prodi);
            });
        }

        // 4. Pencarian Mahasiswa
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('pendaftaranMbkm', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%");
            });
        }
    }

    private function mapSemhasPayload(PengajuanSemhas $s): array
    {
        $p = $s->pendaftaranMbkm;
        $dplNama = $p?->dpl?->nama ?? '-';
        $picNama = $p?->picVerifikator?->nama ?? '-';
        $pengujiNama = $p?->pengujiSemhas?->nama ?? '-';
        $bimbinganCount = (int) ($p?->bimbingans()->count() ?? 0);

        $jadwalSemhas = [
            'jadwal_tanggal' => $s->jadwal_tanggal ? (is_string($s->jadwal_tanggal) ? $s->jadwal_tanggal : $s->jadwal_tanggal->format('Y-m-d')) : null,
            'jadwal_waktu_mulai' => $s->jadwal_waktu_mulai,
            'jadwal_waktu_selesai' => $s->jadwal_waktu_selesai,
            'jadwal_lokasi' => $s->jadwal_lokasi,
        ];

        return [
            'id' => (string) $s->id,
            'pendaftaran_mbkm_id' => (string) $s->pendaftaran_mbkm_id,
            'pendaftaran_id' => (string) $s->pendaftaran_mbkm_id,
            'nim' => $p?->nim ?? '-',
            'nama_lengkap' => $p?->nama_lengkap ?? '-',
            'nama' => $p?->nama_lengkap ?? '-',
            'program_studi' => $p?->programStudi?->nama ?? 'Teknologi Informasi',
            'mitra_magang' => $p?->mitra?->nama ?? $p?->mitra_magang_text ?? '-',
            'posisi_magang' => $p?->posisi_magang ?? '-',
            'tahun_ajaran' => $s->tahun_ajaran,
            'periode_akademik_id' => $s->periode_akademik_id ?? $p?->periode_akademik_id,
            'dpl' => $dplNama,
            'dpl_id' => $p?->dpl_id,
            'pic_verifikator' => $picNama,
            'pic_verifikator_id' => $p?->pic_verifikator_id,
            'penguji_semhas' => $pengujiNama,
            'penguji_semhas_id' => $p?->penguji_semhas_id,
            'jadwal_semhas' => $jadwalSemhas,
            'jadwal_tanggal' => $jadwalSemhas['jadwal_tanggal'],
            'jadwal_waktu_mulai' => $jadwalSemhas['jadwal_waktu_mulai'],
            'jadwal_waktu_selesai' => $jadwalSemhas['jadwal_waktu_selesai'],
            'jadwal_lokasi' => $jadwalSemhas['jadwal_lokasi'],
            'bimbingan_count' => $bimbinganCount,
            'sudah_isi_kuisioner' => (bool) ($p?->sudah_isi_kuisioner ?? false),
            'status_rkm' => $p?->rkm?->status ?? 'Disetujui',
            'laporan_akhir_name' => $s->laporan_akhir_name ?: ($s->laporan_akhir_path ? basename($s->laporan_akhir_path) : 'Laporan_Akhir.pdf'),
            'laporan_akhir_path' => $s->laporan_akhir_path,
            'laporan_akhir_url' => $s->laporan_akhir_url,
            'logbook_fb07_name' => $s->logbook_fb07_name ?: ($s->logbook_fb07_path ? basename($s->logbook_fb07_path) : 'Logbook_FB07.pdf'),
            'logbook_fb07_path' => $s->logbook_fb07_path,
            'logbook_fb07_url' => $s->logbook_fb07_url,
            'nilai_supervisor_name' => $s->nilai_supervisor_name ?: ($s->nilai_supervisor_path ? basename($s->nilai_supervisor_path) : 'Nilai_Supervisor.pdf'),
            'nilai_supervisor_path' => $s->nilai_supervisor_path,
            'nilai_supervisor_url' => $s->nilai_supervisor_url,
            'status_kelayakan' => $s->status_kelayakan,
            'catatan_pic' => $s->catatan_pic ?? '',
            'verified_by_pic_id' => $s->verified_by_pic_id,
            'verified_by_pic_nama' => $s->verifiedByPic?->nama,
            'verified_by_pic' => $s->verifiedByPic ? [
                'id' => $s->verifiedByPic->id,
                'nama' => $s->verifiedByPic->nama,
            ] : null,
            'verified_at_pic' => $s->verified_at_pic?->toISOString(),
            'is_override_ketua' => (bool) $s->is_override_ketua,
            'catatan_override_ketua' => $s->catatan_override_ketua ?? '',
            'override_by_ketua_id' => $s->override_by_ketua_id,
            'override_by_ketua_nama' => $s->overrideByKetua?->nama,
            'override_by_ketua' => $s->overrideByKetua ? [
                'id' => $s->overrideByKetua->id,
                'nama' => $s->overrideByKetua->nama,
            ] : null,
            'override_at' => $s->override_at?->toISOString(),
            'submitted_at' => $s->created_at?->toISOString(),
            'created_at' => $s->created_at?->toISOString(),
            'updated_at' => $s->updated_at?->toISOString(),
        ];
    }
}
