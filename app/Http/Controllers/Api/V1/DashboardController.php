<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DplPeriodeLink;
use App\Models\KuisionerJawaban;
use App\Models\KuisionerSesi;
use App\Models\MonevHasilMahasiswa;
use App\Models\MonevMitra;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\SuratPengantar;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    private const TAHAP_MONEV = ['Minggu ke-0', 'Minggu ke-8', 'Minggu ke-18'];

    private const TAHAP_MONEV_SHORT = [
        'Minggu ke-0' => 'Mg-0',
        'Minggu ke-8' => 'Mg-8',
        'Minggu ke-18' => 'Mg-18',
    ];

    private const MONEV_INDIKATOR_LABELS = [
        'kompleksitas_jobdesk' => 'Kompleksitas Jobdesk',
        'kesesuaian_rkm' => 'Kesesuaian RKM',
        'relevansi_pl' => 'Relevansi PL',
        'kualitas_supervisi' => 'Kualitas Supervisi',
        'ketercapaian_cpmk' => 'Ketercapaian CPMK',
        'outcome_pekerjaan' => 'Outcome Pekerjaan',
        'pengembangan_skill' => 'Pengembangan Skill',
    ];

    public function ketuaMbkm(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('Ketua Tim MBKM', 'Tim MBKM')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses ke Dashboard Ketua / Tim MBKM.',
            ], 403);
        }

        $requestedRole = trim((string) (
            $request->input('active_role')
            ?? $request->query('active_role')
            ?? $request->query('role')
            ?? $request->header('X-Active-Role')
            ?? ''
        ));
        $normalizedRole = strtolower($requestedRole);

        if ($normalizedRole !== '' && ! in_array($normalizedRole, ['ketua tim mbkm', 'tim mbkm'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Peran aktif tidak memiliki hak akses ke Dashboard Ketua / Tim MBKM.',
            ], 403);
        }

        $isKetuaScope = $user->hasRole('Ketua Tim MBKM') && $normalizedRole !== 'tim mbkm';

        $periodeId = $request->input('periode_akademik_id')
            ?? $request->query('periode_akademik_id')
            ?? $request->query('periode_id');

        $periode = ! empty($periodeId)
            ? PeriodeAkademik::find($periodeId)
            : (PeriodeAkademik::where('is_aktif', true)->first() ?? PeriodeAkademik::orderByDesc('id')->first());

        if (! $periode) {
            return response()->json([
                'success' => false,
                'message' => 'Periode akademik tidak ditemukan.',
            ], 404);
        }

        $pendaftaranQuery = PendaftaranMbkm::with([
            'programStudi',
            'mitra',
            'rkm.items',
            'pengajuanSemhas',
            'penilaianSupervisor.mataKuliahs',
            'penilaianDpl.mataKuliahs',
            'penilaianSemhas.mataKuliahs',
        ])
            ->withCount('bimbingans')
            ->where('periode_akademik_id', $periode->id);

        if (! $isKetuaScope) {
            $pendaftaranQuery->where('pic_verifikator_id', $user->id);
        }

        $allPendaftarans = $pendaftaranQuery->get();

        $terverifikasis = $allPendaftarans->filter(
            fn (PendaftaranMbkm $p) => in_array($p->status, ['Terverifikasi', 'Disetujui'], true)
        )->values();

        $verifCount = $terverifikasis->count();

        // Hitung Layak Semhas
        $isLayakSemhas = fn (PendaftaranMbkm $p): bool => $p->pengajuanSemhas !== null && (
            in_array($p->pengajuanSemhas->status_kelayakan, ['Layak Semhas', 'Layak (Override Ketua)'], true)
            || (bool) $p->pengajuanSemhas->is_override_ketua
        );

        $layakSemhasCount = $terverifikasis->filter($isLayakSemhas)->count();
        $layakSemhasPersen = $verifCount > 0 ? round(($layakSemhasCount / $verifCount) * 100, 1) : 0.0;

        // Hitung RKM Disetujui & Nilai Akhir Gabungan (45% SPV + 35% DPL + 20% Penguji Semhas)
        $rkmDisetujuiList = $terverifikasis->filter(
            fn (PendaftaranMbkm $p) => in_array($p->rkm?->status, ['Disetujui', 'Terverifikasi'], true)
        )->values();

        $nilaiLengkapCount = 0;
        $nilaiAkhirList = [];

        foreach ($rkmDisetujuiList as $p) {
            $finalScore = $this->computeFinalScoreIfComplete($p);
            if ($finalScore !== null) {
                $nilaiLengkapCount++;
                $nilaiAkhirList[] = $finalScore;
            }
        }

        $rkmDisetujuiCount = $rkmDisetujuiList->count();
        $nilaiLengkapPersen = $rkmDisetujuiCount > 0 ? round(($nilaiLengkapCount / $rkmDisetujuiCount) * 100, 1) : 0.0;
        $rataRataNilaiAkhir = count($nilaiAkhirList) > 0
            ? round(array_sum($nilaiAkhirList) / count($nilaiAkhirList), 2)
            : null;

        // Data Monev Mitra pada periode terpilih
        $activeMitraGroups = $terverifikasis->whereNotNull('mitra_id')->groupBy('mitra_id');
        $activeMitraIds = $activeMitraGroups->keys()->map(fn ($id) => (int) $id)->values();

        $allMonevsPeriode = MonevMitra::with('mitra')
            ->where('periode_akademik_id', $periode->id)
            ->orderByDesc('tanggal_pelaksanaan')
            ->orderByDesc('id')
            ->get();

        $monevsByMitra = $allMonevsPeriode->groupBy('mitra_id');

        // 4. Ringkasan Mitra Aktif (urut jumlah mahasiswa terbanyak)
        $ringkasanMitra = $activeMitraGroups->map(function (Collection $students, $mitraId) use ($monevsByMitra) {
            /** @var PendaftaranMbkm $first */
            $first = $students->first();
            $jumlah = $students->count();
            $plottingLengkap = $students->filter(
                fn (PendaftaranMbkm $p) => ! empty($p->pic_verifikator_id) && ! empty($p->dpl_id)
            )->count();

            $mitraMonevs = $monevsByMitra->get($mitraId, collect());
            $monevSelesai = $mitraMonevs
                ->pluck('periode_monev')
                ->filter(fn ($t) => in_array($t, self::TAHAP_MONEV, true))
                ->unique()
                ->count();

            return [
                'mitra_id' => (int) $mitraId,
                'nama_mitra' => $first->mitra?->nama ?? $first->mitra_magang_text ?? '-',
                'jumlah_mahasiswa' => $jumlah,
                'plotting_lengkap_count' => $plottingLengkap,
                'plotting_persen' => $jumlah > 0 ? round(($plottingLengkap / $jumlah) * 100, 1) : 0.0,
                'monev_selesai_count' => $monevSelesai,
            ];
        })->sortBy([
            ['jumlah_mahasiswa', 'desc'],
            ['nama_mitra', 'asc'],
        ])->values()->all();

        // 5. Sebaran Prodi
        $totalPendaftar = $allPendaftarans->count();
        $sebaranProdi = $allPendaftarans
            ->groupBy(fn (PendaftaranMbkm $p) => $p->programStudi?->nama ?? 'Lainnya')
            ->map(function (Collection $group, string $prodiNama) use ($totalPendaftar) {
                $jumlah = $group->count();

                return [
                    'program_studi' => $prodiNama,
                    'jumlah' => $jumlah,
                    'persentase' => $totalPendaftar > 0 ? round(($jumlah / $totalPendaftar) * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('jumlah')
            ->values()
            ->all();

        // 6. Status RKM (dari mahasiswa terverifikasi)
        $rkmDisetujui = 0;
        $rkmMenunggu = 0;
        $rkmPerbaikan = 0;
        $rkmBelumAjukan = 0;

        foreach ($terverifikasis as $p) {
            $rkmStatus = $p->rkm?->status;
            if (in_array($rkmStatus, ['Disetujui', 'Terverifikasi'], true)) {
                $rkmDisetujui++;
            } elseif (in_array($rkmStatus, ['Diajukan', 'Menunggu Verifikasi'], true)) {
                $rkmMenunggu++;
            } elseif (in_array($rkmStatus, ['Perbaikan RKM', 'Perbaikan'], true)) {
                $rkmPerbaikan++;
            } else {
                $rkmBelumAjukan++;
            }
        }

        // 7. Kelengkapan Plotting (dari mahasiswa terverifikasi)
        $plotLengkap = 0;
        $plotSebagian = 0;
        $plotBelumDiplot = 0;
        $belumPic = 0;
        $belumDpl = 0;
        $belumPenguji = 0;

        foreach ($terverifikasis as $p) {
            $hasPic = ! empty($p->pic_verifikator_id);
            $hasDpl = ! empty($p->dpl_id);
            $hasPenguji = ! empty($p->penguji_semhas_id);

            if (! $hasPic) {
                $belumPic++;
            }
            if (! $hasDpl) {
                $belumDpl++;
            }
            if (! $hasPenguji) {
                $belumPenguji++;
            }

            if ($hasPic && $hasDpl && $hasPenguji) {
                $plotLengkap++;
            } elseif (! $hasPic && ! $hasDpl && ! $hasPenguji) {
                $plotBelumDiplot++;
            } else {
                $plotSebagian++;
            }
        }

        // 8. Perlu Ditindaklanjuti
        $verifikasiMbkmMenunggu = $allPendaftarans->filter(
            fn (PendaftaranMbkm $p) => in_array($p->status, ['Menunggu Verifikasi', 'Menunggu'], true)
        )->count();

        $verifikasiSemhasMenunggu = $terverifikasis->filter(
            fn (PendaftaranMbkm $p) => $p->pengajuanSemhas?->status_kelayakan === 'Menunggu Verifikasi'
        )->count();

        $kelayakanSemhasPerluTinjau = $terverifikasis->filter(
            fn (PendaftaranMbkm $p) => $p->pengajuanSemhas !== null && ! $isLayakSemhas($p)
        )->count();

        $monevBelumSelesai = collect($ringkasanMitra)
            ->filter(fn (array $m) => $m['monev_selesai_count'] < 3)
            ->count();

        // 9. Pipeline 6 Tahap
        $bimbinganMemenuhi = $terverifikasis->filter(
            fn (PendaftaranMbkm $p) => (int) ($p->bimbingans_count ?? 0) >= 8
        )->count();

        // 10. Kuesioner Evaluasi GPM pada periode terpilih
        $sesi = KuisionerSesi::where('periode_akademik_id', $periode->id)->first();
        $rawSesiStatus = $sesi?->status ?? 'draft';
        $statusSesiLabel = match ($rawSesiStatus) {
            'published' => 'Dibuka (Dipublikasikan)',
            'closed' => 'Ditutup',
            default => 'Draf',
        };

        $respondedUserIds = KuisionerJawaban::where(function ($q) use ($periode) {
            $q->where('tahun_ajaran_id', $periode->id)
                ->orWhere('tahun_ajaran', $periode->nama_lengkap);
        })
            ->whereIn('mahasiswa_user_id', $terverifikasis->pluck('user_id')->filter()->unique()->values())
            ->pluck('mahasiswa_user_id')
            ->unique();

        $sudahMengisiKuisioner = $terverifikasis->filter(
            fn (PendaftaranMbkm $p) => (bool) $p->sudah_isi_kuisioner || $respondedUserIds->contains($p->user_id)
        )->count();
        $belumMengisiKuisioner = max(0, $verifCount - $sudahMengisiKuisioner);
        $persentasePartisipasi = $verifCount > 0 ? round(($sudahMengisiKuisioner / $verifCount) * 100, 1) : 0.0;

        // 11. Monev Terbaru (maksimal 5 catatan evaluasi mitra terbaru pada periode terpilih)
        $scopedMonevs = $isKetuaScope
            ? $allMonevsPeriode
            : $allMonevsPeriode->filter(
                fn (MonevMitra $m) => $activeMitraIds->contains((int) $m->mitra_id) || (int) $m->filled_by_id === (int) $user->id
            );

        $monevTerbaru = $scopedMonevs->take(5)->map(function (MonevMitra $m) use ($monevsByMitra) {
            $mitraMonevs = $monevsByMitra->get($m->mitra_id, collect());
            $selesaiCount = $mitraMonevs
                ->pluck('periode_monev')
                ->filter(fn ($t) => in_array($t, self::TAHAP_MONEV, true))
                ->unique()
                ->count();

            return [
                'mitra_id' => (int) $m->mitra_id,
                'nama_mitra' => $m->mitra?->nama ?? '-',
                'tahap' => self::TAHAP_MONEV_SHORT[$m->periode_monev] ?? $m->periode_monev,
                'tanggal_monev' => $m->tanggal_pelaksanaan?->format('Y-m-d') ?? $m->created_at?->format('Y-m-d'),
                'progres_tahap' => "{$selesaiCount} / 3 Selesai",
                'ringkasan_catatan' => $this->formatRingkasanCatatanMonev($m),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'periode' => [
                    'id' => $periode->id,
                    'tahun_ajaran' => $periode->nama_lengkap,
                    'is_active' => (bool) $periode->is_aktif,
                ],
                'is_ketua_scope' => $isKetuaScope,
                'kpi' => [
                    'mahasiswa_terdaftar' => $totalPendaftar,
                    'mahasiswa_terverifikasi' => $verifCount,
                    'mitra_aktif' => count($ringkasanMitra),
                    'layak_semhas_count' => $layakSemhasCount,
                    'layak_semhas_persen' => $layakSemhasPersen,
                    'nilai_lengkap_count' => $nilaiLengkapCount,
                    'nilai_total_target' => $rkmDisetujuiCount,
                    'nilai_lengkap_persen' => $nilaiLengkapPersen,
                    'rata_rata_nilai_akhir' => $rataRataNilaiAkhir,
                ],
                'ringkasan_mitra' => $ringkasanMitra,
                'sebaran_prodi' => $sebaranProdi,
                'status_rkm' => [
                    'total' => $verifCount,
                    'disetujui' => $rkmDisetujui,
                    'menunggu' => $rkmMenunggu,
                    'perbaikan' => $rkmPerbaikan,
                    'belum_ajukan' => $rkmBelumAjukan,
                ],
                'kelengkapan_plotting' => [
                    'total' => $verifCount,
                    'lengkap' => $plotLengkap,
                    'sebagian' => $plotSebagian,
                    'belum_diplot' => $plotBelumDiplot,
                    'belum_pic' => $belumPic,
                    'belum_dpl' => $belumDpl,
                    'belum_penguji' => $belumPenguji,
                ],
                'perlu_ditindaklanjuti' => [
                    'verifikasi_mbkm_menunggu' => $verifikasiMbkmMenunggu,
                    'verifikasi_rkm_menunggu' => $rkmMenunggu,
                    'belum_plotting' => $verifCount - $plotLengkap,
                    'verifikasi_semhas_menunggu' => $verifikasiSemhasMenunggu,
                    'kelayakan_semhas_perlu_tinjau' => $kelayakanSemhasPerluTinjau,
                    'nilai_akhir_belum_lengkap' => max(0, $rkmDisetujuiCount - $nilaiLengkapCount),
                    'monev_belum_selesai' => $monevBelumSelesai,
                ],
                'pipeline' => [
                    'pendaftaran' => $totalPendaftar,
                    'terverifikasi' => $verifCount,
                    'rkm_disetujui' => $rkmDisetujuiCount,
                    'bimbingan_memenuhi' => $bimbinganMemenuhi,
                    'layak_semhas' => $layakSemhasCount,
                    'nilai_lengkap' => $nilaiLengkapCount,
                ],
                'kuesioner' => [
                    'status_sesi' => $statusSesiLabel,
                    'total_target' => $verifCount,
                    'sudah_mengisi' => $sudahMengisiKuisioner,
                    'belum_mengisi' => $belumMengisiKuisioner,
                    'persentase_partisipasi' => $persentasePartisipasi,
                ],
                'monev_terbaru' => $monevTerbaru,
            ],
        ]);
    }

    private function computeFinalScoreIfComplete(PendaftaranMbkm $p): ?float
    {
        $spv = $p->penilaianSupervisor;
        $dpl = $p->penilaianDpl;
        $penguji = $p->penilaianSemhas;

        if ($spv === null || $dpl === null || $penguji === null) {
            return null;
        }

        $activeMks = ($p->rkm?->items ?? collect())->filter(function ($item) {
            return is_null($item->status_mk)
                || ! in_array(strtolower((string) $item->status_mk), ['drop', 'dropped'], true);
        })->values();

        if ($activeMks->isNotEmpty()) {
            $spvMks = $spv->mataKuliahs;
            $dplMks = $dpl->mataKuliahs;
            $pengujiMks = $penguji->mataKuliahs;

            $nilaiAkhirList = [];
            foreach ($activeMks as $mk) {
                $sRow = $spvMks->firstWhere('kode_mk', $mk->kode_mk);
                $dRow = $dplMks->firstWhere('kode_mk', $mk->kode_mk);
                $pRow = $pengujiMks->firstWhere('kode_mk', $mk->kode_mk);

                if ($sRow !== null && $dRow !== null && $pRow !== null) {
                    $nilaiAkhirList[] = round(
                        ((float) $sRow->nilai * 0.45)
                        + ((float) $dRow->nilai * 0.35)
                        + ((float) $pRow->nilai_total * 0.20),
                        2
                    );
                }
            }

            if (count($nilaiAkhirList) === $activeMks->count()) {
                return round(array_sum($nilaiAkhirList) / count($nilaiAkhirList), 2);
            }
        }

        if ($spv->rata_rata_nilai !== null && $dpl->rata_rata_nilai !== null && $penguji->rata_rata_nilai_total !== null) {
            return round(
                ((float) $spv->rata_rata_nilai * 0.45)
                + ((float) $dpl->rata_rata_nilai * 0.35)
                + ((float) $penguji->rata_rata_nilai_total * 0.20),
                2
            );
        }

        return null;
    }

    private function formatRingkasanCatatanMonev(MonevMitra $m): string
    {
        $catatan = trim((string) ($m->catatan_evaluasi ?? ''));
        if ($catatan !== '') {
            return $catatan;
        }

        $kendala = trim((string) ($m->kendala_tindak_lanjut ?? ''));
        if ($kendala !== '') {
            return $kendala;
        }

        if (is_array($m->proyeksi_magang_selanjutnya) && count($m->proyeksi_magang_selanjutnya) > 0) {
            $items = collect($m->proyeksi_magang_selanjutnya)
                ->map(fn ($v) => is_string($v) ? trim($v) : '')
                ->filter()
                ->implode(', ');

            if ($items !== '') {
                return 'Proyeksi: '.$items;
            }
        }

        return '-';
    }

    public function mahasiswa(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('Mahasiswa')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya mahasiswa yang dapat mengakses Dashboard Mahasiswa.',
            ], 403);
        }

        $requestedRole = trim((string) (
            $request->input('active_role')
            ?? $request->query('active_role')
            ?? $request->query('role')
            ?? $request->header('X-Active-Role')
            ?? ''
        ));
        $normalizedRole = strtolower($requestedRole);
        if ($normalizedRole !== '' && $normalizedRole !== 'mahasiswa') {
            return response()->json([
                'success' => false,
                'message' => 'Peran aktif tidak memiliki hak akses ke Dashboard Mahasiswa.',
            ], 403);
        }

        $periodeId = $request->input('periode_akademik_id')
            ?? $request->query('periode_akademik_id')
            ?? $request->query('periode_id');

        $periode = ! empty($periodeId)
            ? PeriodeAkademik::find($periodeId)
            : (PeriodeAkademik::where('is_aktif', true)->first() ?? PeriodeAkademik::orderByDesc('id')->first());

        if (! $periode) {
            return response()->json([
                'success' => false,
                'message' => 'Periode akademik tidak ditemukan.',
            ], 404);
        }

        $mhsNim = $user->mahasiswa?->nim ?? $user->nim_nip;

        $suratPengantar = SuratPengantar::where(function ($q) use ($user, $mhsNim) {
            $q->where('user_id', $user->id)
                ->orWhereHas('mahasiswas', function ($mq) use ($user, $mhsNim) {
                    $mq->where('user_id', $user->id);
                    if ($mhsNim) {
                        $mq->orWhere('nim', $mhsNim);
                    }
                });
        })
            ->where(function ($q) use ($periode) {
                $q->where('periode_akademik_id', $periode->id)
                    ->orWhere(function ($sub) use ($periode) {
                        $sub->whereNull('periode_akademik_id')
                            ->where('tahun_ajaran', $periode->nama_lengkap);
                    });
            })
            ->latest('id')
            ->first();

        $pendaftaran = PendaftaranMbkm::with([
            'programStudi',
            'mitra',
            'picVerifikator',
            'dpl',
            'pengujiSemhas',
            'periodeAkademik',
            'rkm.items',
            'pengajuanSemhas',
            'penilaianSupervisor.mataKuliahs',
            'penilaianDpl.mataKuliahs',
            'penilaianSemhas.mataKuliahs',
        ])
            ->withCount('bimbingans')
            ->where('user_id', $user->id)
            ->where('periode_akademik_id', $periode->id)
            ->latest('id')
            ->first();

        $rkm = $pendaftaran?->rkm;
        $semhas = $pendaftaran?->pengajuanSemhas;

        $activeRkmItems = ($rkm?->items ?? collect())->filter(
            fn ($item) => is_null($item->status_mk)
                || ! in_array(strtolower((string) $item->status_mk), ['drop', 'dropped'], true)
        )->values();

        $totalSksAktif = (int) $activeRkmItems->sum('sks');
        $totalMkAktif = $activeRkmItems->count();
        $bimbinganCount = (int) ($pendaftaran?->bimbingans_count ?? 0);

        $statusPendaftaran = $this->normalizeMahasiswaPendaftaranStatus($pendaftaran?->status);
        $statusRkm = $this->normalizeMahasiswaRkmStatus($rkm?->status);
        $statusSemhas = $this->normalizeMahasiswaSemhasStatus($semhas);

        $sesi = KuisionerSesi::where('periode_akademik_id', $periode->id)->first();
        $isSesiPublished = $sesi?->status === 'published';

        $hasJawabanKuisioner = KuisionerJawaban::where('mahasiswa_user_id', $user->id)
            ->where(function ($q) use ($periode) {
                $q->where('tahun_ajaran_id', $periode->id)
                    ->orWhere('tahun_ajaran', $periode->nama_lengkap);
            })
            ->exists();

        $sudahIsiKuisioner = (bool) ($pendaftaran?->sudah_isi_kuisioner ?? false) || $hasJawabanKuisioner;
        $isPendaftaranVerified = $pendaftaran !== null && in_array($pendaftaran->status, ['Terverifikasi', 'Disetujui'], true);

        $statusKuesioner = 'Belum Tersedia';
        if ($sudahIsiKuisioner) {
            $statusKuesioner = 'Sudah Mengisi';
        } elseif ($isSesiPublished && $isPendaftaranVerified) {
            $statusKuesioner = 'Belum Mengisi';
        }

        $jadwalTanggal = $semhas?->jadwal_tanggal
            ? (is_string($semhas->jadwal_tanggal) ? $semhas->jadwal_tanggal : $semhas->jadwal_tanggal->format('Y-m-d'))
            : null;

        $komponenTerisi = 0;
        $rataRataNilaiAkhir = null;
        if ($pendaftaran) {
            if ($pendaftaran->penilaianSupervisor !== null) {
                $komponenTerisi++;
            }
            if ($pendaftaran->penilaianDpl !== null) {
                $komponenTerisi++;
            }
            if ($pendaftaran->penilaianSemhas !== null) {
                $komponenTerisi++;
            }
            $rataRataNilaiAkhir = $this->computeFinalScoreIfComplete($pendaftaran);
        }

        $linkWaDpl = null;
        if ($pendaftaran && $pendaftaran->dpl_id) {
            $linkWaDpl = DplPeriodeLink::where('dpl_id', $pendaftaran->dpl_id)
                ->where('periode_akademik_id', $periode->id)
                ->value('link_wa')
                ?: ($pendaftaran->link_wa_dpl ?: null);
        }

        $perubahanStatus = $this->buildMahasiswaPerubahanStatus($suratPengantar, $pendaftaran, $rkm, $semhas);
        $infoTindakLanjut = $this->buildMahasiswaInfoTindakLanjut(
            $statusRkm,
            $isSesiPublished,
            $sudahIsiKuisioner,
            $semhas,
            $jadwalTanggal
        );
        $alurMagang = $this->buildMahasiswaAlur(
            $pendaftaran,
            $statusPendaftaran,
            $statusRkm,
            $bimbinganCount,
            $statusKuesioner,
            $statusSemhas,
            $komponenTerisi
        );

        return response()->json([
            'success' => true,
            'data' => [
                'periode' => [
                    'id' => $periode->id,
                    'tahun_ajaran' => $periode->nama_lengkap,
                    'is_active' => (bool) $periode->is_aktif,
                ],
                'perubahan_status' => $perubahanStatus,
                'info_tindak_lanjut' => $infoTindakLanjut,
                'ringkasan' => [
                    'has_pendaftaran' => $pendaftaran !== null,
                    'status_pendaftaran' => $statusPendaftaran,
                    'status_rkm' => $statusRkm,
                    'total_sks_aktif' => $totalSksAktif,
                    'total_mk_aktif' => $totalMkAktif,
                    'bimbingan_count' => $bimbinganCount,
                    'min_bimbingan' => 8,
                    'status_kuesioner' => $statusKuesioner,
                    'status_semhas' => $statusSemhas,
                    'jadwal_semhas_tanggal' => $jadwalTanggal,
                    'komponen_nilai_terisi' => $komponenTerisi,
                    'rata_rata_nilai_akhir' => $rataRataNilaiAkhir,
                ],
                'informasi_magang' => [
                    'mitra_magang' => $pendaftaran?->mitra?->nama ?? $pendaftaran?->mitra_magang_text ?? '-',
                    'posisi_magang' => $pendaftaran?->posisi_magang ?? '-',
                    'tahun_ajaran' => $periode->nama_lengkap,
                    'pic_verifikator' => $pendaftaran?->picVerifikator?->nama ?? '-',
                    'dpl' => $pendaftaran?->dpl?->nama ?? '-',
                    'penguji_semhas' => $pendaftaran?->pengujiSemhas?->nama ?? '-',
                    'link_wa_rkm' => $pendaftaran?->link_wa_grup ?: null,
                    'link_wa_dpl' => $linkWaDpl,
                    'link_logbook' => $pendaftaran?->link_logbook ?: null,
                ],
                'alur_magang' => $alurMagang,
            ],
        ]);
    }

    private function normalizeMahasiswaPendaftaranStatus(?string $status): string
    {
        if (! $status) {
            return 'Belum Mendaftar';
        }

        return match (strtolower(trim($status))) {
            'terverifikasi', 'disetujui' => 'Terverifikasi',
            'perbaikan', 'perbaikan berkas', 'revisi' => 'Perbaikan Berkas',
            'ditolak' => 'Ditolak',
            'menunggu', 'menunggu verifikasi' => 'Menunggu Verifikasi',
            default => $status,
        };
    }

    private function normalizeMahasiswaRkmStatus(?string $status): string
    {
        if (! $status || in_array(strtolower(trim($status)), ['belum mengajukan', 'draft'], true)) {
            return 'Belum Diajukan';
        }

        return match (strtolower(trim($status))) {
            'disetujui', 'terverifikasi' => 'Disetujui',
            'diajukan', 'menunggu verifikasi' => 'Menunggu Verifikasi',
            'perbaikan', 'perbaikan rkm', 'revisi' => 'Perbaikan RKM',
            'ditolak' => 'Ditolak',
            default => $status,
        };
    }

    private function normalizeMahasiswaSemhasStatus($semhas): string
    {
        if (! $semhas) {
            return 'Belum Diajukan';
        }

        if ($semhas->is_override_ketua || in_array($semhas->status_kelayakan, ['Layak Semhas', 'Layak (Override Ketua)'], true)) {
            return 'Layak Semhas';
        }

        $lower = strtolower(trim((string) $semhas->status_kelayakan));
        if (str_contains($lower, 'perbaikan') || str_contains($lower, 'revisi')) {
            return 'Perbaikan';
        }
        if (in_array($lower, ['tidak layak', 'ditolak'], true)) {
            return $semhas->status_kelayakan;
        }

        return 'Menunggu Verifikasi';
    }

    private function buildMahasiswaPerubahanStatus(?SuratPengantar $surat, ?PendaftaranMbkm $pendaftaran, $rkm, $semhas): array
    {
        $items = [];
        $fallbackNote = 'Silakan periksa detail dan lengkapi data yang diminta.';

        if ($surat) {
            $lower = strtolower(trim((string) $surat->status));
            if ($lower === 'ditolak' || str_contains($lower, 'perbaikan') || str_contains($lower, 'revisi')) {
                $isDitolak = $lower === 'ditolak';
                $items[] = [
                    'modul' => 'Surat Pengantar',
                    'status' => $isDitolak ? 'Ditolak' : $surat->status,
                    'kategori' => $isDitolak ? 'ditolak' : 'perbaikan',
                    'catatan' => trim((string) ($surat->catatan ?? '')) ?: $fallbackNote,
                    'updated_at' => $surat->updated_at?->toISOString(),
                    'action_url' => '/mahasiswa/surat-pengantar',
                ];
            }
        }

        if ($pendaftaran) {
            $lower = strtolower(trim((string) $pendaftaran->status));
            if ($lower === 'ditolak' || str_contains($lower, 'perbaikan') || str_contains($lower, 'revisi')) {
                $isDitolak = $lower === 'ditolak';
                $items[] = [
                    'modul' => 'Pendaftaran MBKM',
                    'status' => $isDitolak ? 'Ditolak' : 'Perbaikan Berkas',
                    'kategori' => $isDitolak ? 'ditolak' : 'perbaikan',
                    'catatan' => trim((string) ($pendaftaran->catatan ?? '')) ?: $fallbackNote,
                    'updated_at' => $pendaftaran->updated_at?->toISOString(),
                    'action_url' => '/mahasiswa/pendaftaran',
                ];
            }
        }

        if ($rkm) {
            $lower = strtolower(trim((string) $rkm->status));
            if ($lower === 'ditolak' || str_contains($lower, 'perbaikan') || str_contains($lower, 'revisi')) {
                $isDitolak = $lower === 'ditolak';
                $items[] = [
                    'modul' => 'Rencana Kegiatan Mahasiswa (RKM)',
                    'status' => $isDitolak ? 'Ditolak' : 'Perbaikan RKM',
                    'kategori' => $isDitolak ? 'ditolak' : 'perbaikan',
                    'catatan' => trim((string) ($rkm->catatan_pic ?? '')) ?: $fallbackNote,
                    'updated_at' => $rkm->updated_at?->toISOString(),
                    'action_url' => '/mahasiswa/rkm',
                ];
            }
        }

        if ($semhas && ! $semhas->is_override_ketua) {
            $lower = strtolower(trim((string) $semhas->status_kelayakan));
            if (in_array($lower, ['tidak layak', 'ditolak'], true) || str_contains($lower, 'perbaikan') || str_contains($lower, 'revisi')) {
                $isDitolak = in_array($lower, ['tidak layak', 'ditolak'], true);
                $items[] = [
                    'modul' => 'Pendaftaran Seminar Hasil',
                    'status' => $semhas->status_kelayakan,
                    'kategori' => $isDitolak ? 'ditolak' : 'perbaikan',
                    'catatan' => trim((string) ($semhas->catatan_pic ?? '')) ?: $fallbackNote,
                    'updated_at' => $semhas->updated_at?->toISOString(),
                    'action_url' => '/mahasiswa/semhas',
                ];
            }
        }

        return collect($items)
            ->sortByDesc(fn (array $item) => $item['updated_at'] ?? '')
            ->values()
            ->all();
    }

    private function buildMahasiswaInfoTindakLanjut(
        string $statusRkm,
        bool $isSesiPublished,
        bool $sudahIsiKuisioner,
        $semhas,
        ?string $jadwalTanggal
    ): array {
        $infos = [];

        if ($statusRkm === 'Disetujui' && $isSesiPublished && ! $sudahIsiKuisioner) {
            $infos[] = [
                'key' => 'kuesioner',
                'judul' => 'Kuesioner Evaluasi MBKM Telah Dibuka',
                'deskripsi' => 'Selesaikan pengisian kuesioner evaluasi DPL & mitra sebagai syarat pendaftaran Seminar Hasil.',
                'action_label' => 'Isi Kuesioner',
                'action_url' => '/mahasiswa/kuisioner',
            ];
        }

        if ($semhas && ! empty($jadwalTanggal)) {
            $waktu = ($semhas->jadwal_waktu_mulai && $semhas->jadwal_waktu_selesai)
                ? " pukul {$semhas->jadwal_waktu_mulai}–{$semhas->jadwal_waktu_selesai}"
                : ($semhas->jadwal_waktu_mulai ? " pukul {$semhas->jadwal_waktu_mulai}" : '');
            $lokasi = $semhas->jadwal_lokasi ? " di {$semhas->jadwal_lokasi}" : '';

            $infos[] = [
                'key' => 'jadwal_semhas',
                'judul' => 'Jadwal Seminar Hasil Telah Ditetapkan',
                'deskripsi' => "Sidang Seminar Hasil dijadwalkan pada {$jadwalTanggal}{$waktu}{$lokasi}.",
                'action_label' => 'Lihat Jadwal',
                'action_url' => '/mahasiswa/semhas',
            ];
        }

        return $infos;
    }

    private function buildMahasiswaAlur(
        ?PendaftaranMbkm $pendaftaran,
        string $statusPendaftaran,
        string $statusRkm,
        int $bimbinganCount,
        string $statusKuesioner,
        string $statusSemhas,
        int $komponenTerisi
    ): array {
        $tahap1Status = match ($statusPendaftaran) {
            'Terverifikasi' => ['selesai', 'Selesai'],
            'Perbaikan Berkas' => ['perbaikan', 'Perlu Perbaikan'],
            'Ditolak' => ['ditolak', 'Ditolak'],
            'Menunggu Verifikasi' => ['berjalan', 'Sedang Berjalan'],
            default => ['belum', 'Belum Mulai'],
        };

        $tahap2Status = match ($statusRkm) {
            'Disetujui' => ['selesai', 'Selesai'],
            'Perbaikan RKM' => ['perbaikan', 'Perlu Perbaikan'],
            'Ditolak' => ['ditolak', 'Ditolak'],
            'Menunggu Verifikasi' => ['berjalan', 'Sedang Berjalan'],
            default => $statusPendaftaran === 'Terverifikasi' ? ['berjalan', 'Sedang Berjalan'] : ['belum', 'Belum Mulai'],
        };

        if ($bimbinganCount >= 8) {
            $tahap3Status = ['selesai', 'Selesai'];
        } elseif ($bimbinganCount > 0 || ($pendaftaran !== null && ! empty($pendaftaran->dpl_id))) {
            $tahap3Status = ['berjalan', 'Sedang Berjalan'];
        } else {
            $tahap3Status = ['belum', 'Belum Mulai'];
        }

        $tahap4Status = match ($statusKuesioner) {
            'Sudah Mengisi' => ['selesai', 'Selesai'],
            'Belum Mengisi' => ['berjalan', 'Sedang Berjalan'],
            default => ['belum', 'Belum Mulai'],
        };

        $semhasLower = strtolower($statusSemhas);
        if ($statusSemhas === 'Layak Semhas') {
            $tahap5Status = ['selesai', 'Selesai'];
        } elseif ($statusSemhas === 'Perbaikan') {
            $tahap5Status = ['perbaikan', 'Perlu Perbaikan'];
        } elseif (in_array($semhasLower, ['tidak layak', 'ditolak'], true)) {
            $tahap5Status = ['ditolak', 'Ditolak'];
        } elseif ($statusSemhas === 'Menunggu Verifikasi') {
            $tahap5Status = ['berjalan', 'Sedang Berjalan'];
        } else {
            $tahap5Status = ['belum', 'Belum Mulai'];
        }

        if ($komponenTerisi >= 3) {
            $tahap6Status = ['selesai', 'Selesai'];
        } elseif ($komponenTerisi > 0) {
            $tahap6Status = ['berjalan', 'Sedang Berjalan'];
        } else {
            $tahap6Status = ['belum', 'Belum Mulai'];
        }

        return [
            [
                'tahap' => 1,
                'judul' => 'Pendaftaran MBKM',
                'deskripsi' => 'Unggah berkas LoA dan Transkrip untuk verifikasi awal.',
                'status' => $tahap1Status[0],
                'label_status' => $tahap1Status[1],
                'href' => '/mahasiswa/pendaftaran',
            ],
            [
                'tahap' => 2,
                'judul' => 'Verifikasi & RKM',
                'deskripsi' => 'Susun paket mata kuliah konversi & jobdesk dengan PIC.',
                'status' => $tahap2Status[0],
                'label_status' => $tahap2Status[1],
                'href' => '/mahasiswa/rkm',
            ],
            [
                'tahap' => 3,
                'judul' => 'Bimbingan DPL',
                'deskripsi' => "Catat min. 8 sesi bimbingan ({$bimbinganCount}/8 sesi).",
                'status' => $tahap3Status[0],
                'label_status' => $tahap3Status[1],
                'href' => '/mahasiswa/monitoring',
            ],
            [
                'tahap' => 4,
                'judul' => 'Kuesioner Evaluasi',
                'deskripsi' => 'Isi kuesioner evaluasi DPL & mitra dari GPM.',
                'status' => $tahap4Status[0],
                'label_status' => $tahap4Status[1],
                'href' => '/mahasiswa/kuisioner',
            ],
            [
                'tahap' => 5,
                'judul' => 'Seminar Hasil',
                'deskripsi' => 'Unggah laporan akhir, logbook MG-07, & nilai mitra MG-09.',
                'status' => $tahap5Status[0],
                'label_status' => $tahap5Status[1],
                'href' => '/mahasiswa/semhas',
            ],
            [
                'tahap' => 6,
                'judul' => 'Nilai Akhir',
                'deskripsi' => "Rekap gabungan SPV, DPL, & Penguji ({$komponenTerisi}/3).",
                'status' => $tahap6Status[0],
                'label_status' => $tahap6Status[1],
                'href' => '/mahasiswa/nilai-akhir',
            ],
        ];
    }

    /**
     * Endpoint Dashboard Eksekutif Wakil Dekan (100% read-only).
     */
    public function wadek(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('Wakil Dekan')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses ke Dashboard Wakil Dekan.',
            ], 403);
        }

        $requestedRole = trim((string) (
            $request->input('active_role')
            ?? $request->query('active_role')
            ?? $request->query('role')
            ?? $request->header('X-Active-Role')
            ?? ''
        ));
        $normalizedRole = strtolower($requestedRole);

        if ($normalizedRole !== '' && $normalizedRole !== 'wakil dekan') {
            return response()->json([
                'success' => false,
                'message' => 'Peran aktif tidak memiliki hak akses ke Dashboard Wakil Dekan.',
            ], 403);
        }

        $periodeId = $request->input('periode_akademik_id')
            ?? $request->query('periode_akademik_id')
            ?? $request->query('periode_id');

        $periode = ! empty($periodeId)
            ? PeriodeAkademik::find($periodeId)
            : (PeriodeAkademik::where('is_aktif', true)->first() ?? PeriodeAkademik::orderByDesc('id')->first());

        if (! $periode) {
            return response()->json([
                'success' => false,
                'message' => 'Periode akademik tidak ditemukan.',
            ], 404);
        }

        $sortedPeriodes = $this->sortPeriodesChronologically(PeriodeAkademik::all());
        $selectedIndex = $sortedPeriodes->search(fn (PeriodeAkademik $p) => (int) $p->id === (int) $periode->id);
        $prevPeriode = ($selectedIndex !== false && $selectedIndex > 0)
            ? $sortedPeriodes->get($selectedIndex - 1)
            : null;

        $allPendaftarans = PendaftaranMbkm::with([
            'programStudi',
            'mitra',
            'rkm.items',
            'pengajuanSemhas',
            'penilaianSupervisor.mataKuliahs',
            'penilaianDpl.mataKuliahs',
            'penilaianSemhas.mataKuliahs',
        ])
            ->where('periode_akademik_id', $periode->id)
            ->get();

        $terverifikasis = $allPendaftarans->filter(
            fn (PendaftaranMbkm $p) => in_array($p->status, ['Terverifikasi', 'Disetujui'], true)
        )->values();

        $mahasiswaMagangAktif = $terverifikasis->count();
        $selisihPeriodeSebelumnya = null;
        if ($prevPeriode !== null) {
            $prevAktifCount = PendaftaranMbkm::where('periode_akademik_id', $prevPeriode->id)
                ->whereIn('status', ['Terverifikasi', 'Disetujui'])
                ->count();
            $selisihPeriodeSebelumnya = $mahasiswaMagangAktif - $prevAktifCount;
        }

        $layakSemhasCount = 0;
        $nilaiLengkapCount = 0;
        $nilaiAkhirList = [];

        foreach ($terverifikasis as $p) {
            if ($this->isPendaftaranLayakSemhas($p)) {
                $layakSemhasCount++;
            }

            $finalScore = $this->computeFinalScoreIfComplete($p);
            if ($finalScore !== null) {
                $nilaiLengkapCount++;
                $nilaiAkhirList[] = $finalScore;
            }
        }

        $layakSemhasPersen = $mahasiswaMagangAktif > 0
            ? round(($layakSemhasCount / $mahasiswaMagangAktif) * 100, 1)
            : 0.0;
        $tingkatPenyelesaianPersen = $mahasiswaMagangAktif > 0
            ? round(($nilaiLengkapCount / $mahasiswaMagangAktif) * 100, 1)
            : 0.0;
        $rataRataNilaiAkhir = count($nilaiAkhirList) > 0
            ? round(array_sum($nilaiAkhirList) / count($nilaiAkhirList), 2)
            : null;

        $activeMitraGroups = $terverifikasis->whereNotNull('mitra_id')->groupBy('mitra_id');

        $jawabansPeriode = KuisionerJawaban::where(function ($q) use ($periode) {
            $q->where('tahun_ajaran_id', $periode->id)
                ->orWhere(function ($sub) use ($periode) {
                    $sub->whereNull('tahun_ajaran_id')->where('tahun_ajaran', $periode->nama_lengkap);
                });
        })->get();

        $rekapGpm = $this->buildWadekRekapGpm($periode, $terverifikasis, $jawabansPeriode);

        $allMonevsPeriode = MonevMitra::with(['mitra', 'hasilMahasiswas'])
            ->where('periode_akademik_id', $periode->id)
            ->orderByDesc('tanggal_pelaksanaan')
            ->orderByDesc('id')
            ->get();

        $radarMonev = $this->buildWadekRadarMonev($periode, $allMonevsPeriode);
        $trenPeriode = $this->buildWadekTrenPeriode($sortedPeriodes, $periode);
        $sebaranProdi = $this->buildWadekSebaranProdi($allPendaftarans, $mahasiswaMagangAktif);
        $topMitra = $this->buildWadekTopMitra($activeMitraGroups, $allMonevsPeriode, $jawabansPeriode);

        return response()->json([
            'success' => true,
            'data' => [
                'periode' => [
                    'id' => $periode->id,
                    'tahun_ajaran' => $periode->nama_lengkap,
                    'is_active' => (bool) $periode->is_aktif,
                    'periode_sebelumnya' => $prevPeriode?->nama_lengkap,
                ],
                'kpi' => [
                    'mahasiswa_magang_aktif' => $mahasiswaMagangAktif,
                    'mahasiswa_terdaftar' => $allPendaftarans->count(),
                    'selisih_periode_sebelumnya' => $selisihPeriodeSebelumnya,
                    'mitra_aktif' => $activeMitraGroups->count(),
                    'tingkat_penyelesaian_persen' => $tingkatPenyelesaianPersen,
                    'layak_semhas_count' => $layakSemhasCount,
                    'layak_semhas_persen' => $layakSemhasPersen,
                    'nilai_lengkap_count' => $nilaiLengkapCount,
                    'rata_rata_nilai_akhir' => $rataRataNilaiAkhir,
                    'rata_rata_skor_gpm' => $jawabansPeriode->isNotEmpty() && $rekapGpm['rata_rata_keseluruhan'] > 0
                        ? $rekapGpm['rata_rata_keseluruhan']
                        : null,
                ],
                'tren_periode' => $trenPeriode,
                'sebaran_prodi' => $sebaranProdi,
                'radar_monev' => $radarMonev,
                'rekap_gpm' => $rekapGpm,
                'top_mitra' => $topMitra,
            ],
        ]);
    }

    /**
     * Cek kelayakan Semhas mahasiswa berdasarkan status verifikasi atau override Ketua.
     */
    private function isPendaftaranLayakSemhas(PendaftaranMbkm $p): bool
    {
        return $p->pengajuanSemhas !== null && (
            in_array($p->pengajuanSemhas->status_kelayakan, ['Layak Semhas', 'Layak (Override Ketua)'], true)
            || (bool) $p->pengajuanSemhas->is_override_ketua
        );
    }

    /**
     * Urutkan koleksi periode akademik secara kronologis dari terlama ke terbaru.
     */
    private function sortPeriodesChronologically(Collection $periodes): Collection
    {
        return $periodes->sort(function (PeriodeAkademik $a, PeriodeAkademik $b) {
            return $this->buildPeriodeChronoTuple($a) <=> $this->buildPeriodeChronoTuple($b);
        })->values();
    }

    /**
     * Bangun tuple pembanding kronologis [tahunMulai, bobotSemester, id].
     */
    private function buildPeriodeChronoTuple(PeriodeAkademik $periode): array
    {
        preg_match('/(\d{4})/', (string) $periode->tahun_ajaran, $matches);
        $startYear = isset($matches[1]) ? (int) $matches[1] : 0;
        $sem = strtolower((string) $periode->semester);
        $semOrder = str_contains($sem, 'genap')
            ? 2
            : (str_contains($sem, 'pendek') || str_contains($sem, 'antara') ? 3 : 1);

        return [$startYear, $semOrder, (int) $periode->id];
    }

    /**
     * Susun data tren antar periode (maksimal 6 periode terbaru secara kronologis).
     */
    private function buildWadekTrenPeriode(Collection $sortedPeriodes, PeriodeAkademik $selectedPeriode): array
    {
        $trendPeriodes = $sortedPeriodes->slice(-6)->values();
        if ($trendPeriodes->isEmpty()) {
            return [];
        }

        $groupedPendaftarans = PendaftaranMbkm::with([
            'rkm.items',
            'pengajuanSemhas',
            'penilaianSupervisor.mataKuliahs',
            'penilaianDpl.mataKuliahs',
            'penilaianSemhas.mataKuliahs',
        ])
            ->whereIn('periode_akademik_id', $trendPeriodes->pluck('id'))
            ->get()
            ->groupBy('periode_akademik_id');

        return $trendPeriodes->map(function (PeriodeAkademik $tp) use ($groupedPendaftarans, $selectedPeriode) {
            /** @var Collection $rows */
            $rows = $groupedPendaftarans->get($tp->id, collect());
            $aktif = $rows->filter(
                fn (PendaftaranMbkm $p) => in_array($p->status, ['Terverifikasi', 'Disetujui'], true)
            );

            $scores = [];
            $layakCount = 0;
            $nilaiLengkap = 0;

            foreach ($aktif as $p) {
                if ($this->isPendaftaranLayakSemhas($p)) {
                    $layakCount++;
                }
                $score = $this->computeFinalScoreIfComplete($p);
                if ($score !== null) {
                    $nilaiLengkap++;
                    $scores[] = $score;
                }
            }

            return [
                'periode_id' => (int) $tp->id,
                'label' => $tp->nama_lengkap,
                'tahun_ajaran' => $tp->tahun_ajaran,
                'semester' => $tp->semester,
                'is_active' => (bool) $tp->is_aktif,
                'is_selected' => (int) $tp->id === (int) $selectedPeriode->id,
                'jumlah_pendaftar' => $rows->count(),
                'jumlah_aktif' => $aktif->count(),
                'jumlah_layak_semhas' => $layakCount,
                'nilai_lengkap_count' => $nilaiLengkap,
                'rata_rata_nilai_akhir' => count($scores) > 0
                    ? round(array_sum($scores) / count($scores), 2)
                    : null,
            ];
        })->values()->all();
    }

    /**
     * Susun sebaran mahasiswa dan rata-rata nilai akhir per program studi.
     */
    private function buildWadekSebaranProdi(Collection $allPendaftarans, int $mahasiswaMagangAktif): array
    {
        $totalPendaftar = $allPendaftarans->count();
        $denominator = $mahasiswaMagangAktif > 0 ? $mahasiswaMagangAktif : $totalPendaftar;

        return $allPendaftarans
            ->groupBy(fn (PendaftaranMbkm $p) => $p->programStudi?->nama ?? 'Lainnya')
            ->map(function (Collection $group, string $prodiNama) use ($denominator) {
                $aktif = $group->filter(
                    fn (PendaftaranMbkm $p) => in_array($p->status, ['Terverifikasi', 'Disetujui'], true)
                );

                $scores = $aktif
                    ->map(fn (PendaftaranMbkm $p) => $this->computeFinalScoreIfComplete($p))
                    ->filter(fn ($score) => $score !== null)
                    ->values();

                $jumlahAktif = $aktif->count();
                $basisCount = $denominator > 0 && $group->contains(
                    fn (PendaftaranMbkm $p) => in_array($p->status, ['Terverifikasi', 'Disetujui'], true)
                ) ? $jumlahAktif : ($denominator > 0 && $jumlahAktif === 0 ? 0 : $group->count());

                return [
                    'program_studi' => $prodiNama,
                    'jumlah_pendaftar' => $group->count(),
                    'jumlah_aktif' => $jumlahAktif,
                    'persentase' => $denominator > 0 ? round(($basisCount / $denominator) * 100, 1) : 0.0,
                    'rata_rata_nilai_akhir' => $scores->isNotEmpty() ? round((float) $scores->avg(), 2) : null,
                ];
            })
            ->sortBy([
                ['jumlah_aktif', 'desc'],
                ['jumlah_pendaftar', 'desc'],
                ['program_studi', 'asc'],
            ])
            ->values()
            ->all();
    }

    /**
     * Susun agregasi 7 indikator Monev Hasil (Minggu ke-18) untuk radar chart Wakil Dekan.
     */
    private function buildWadekRadarMonev(PeriodeAkademik $periode, Collection $allMonevsPeriode): array
    {
        $monev18List = $allMonevsPeriode->where('periode_monev', 'Minggu ke-18')->values();
        $monev18Ids = $monev18List->pluck('id');
        $allHasil = $monev18Ids->isNotEmpty()
            ? MonevHasilMahasiswa::whereIn('monev_mitra_id', $monev18Ids)->get()
            : collect();

        $indikator = [];
        $sumIndikator = 0.0;

        foreach (self::MONEV_INDIKATOR_LABELS as $key => $label) {
            $nilai = $allHasil->isNotEmpty() ? round((float) $allHasil->avg($key), 2) : 0.0;
            $indikator[] = [
                'key' => $key,
                'label' => $label,
                'nilai' => $nilai,
            ];
            $sumIndikator += $nilai;
        }

        $jumlahIndikator = count(self::MONEV_INDIKATOR_LABELS);
        $rerataKeseluruhan = ($allHasil->isNotEmpty() && $jumlahIndikator > 0)
            ? round($sumIndikator / $jumlahIndikator, 2)
            : 0.0;

        return [
            'target_nilai' => (float) ($periode->target_nilai_monev ?? 4.00),
            'total_mitra_dinilai' => $monev18List->count(),
            'total_mahasiswa_dinilai' => $allHasil->count(),
            'rerata_keseluruhan' => $rerataKeseluruhan,
            'indikator' => $indikator,
        ];
    }

    /**
     * Susun ringkasan eksekutif kuesioner evaluasi GPM (read-only).
     */
    private function buildWadekRekapGpm(
        PeriodeAkademik $periode,
        Collection $terverifikasis,
        Collection $jawabansPeriode
    ): array {
        $sesi = KuisionerSesi::where('periode_akademik_id', $periode->id)->first();
        $rawSesiStatus = $sesi?->status ?? 'draft';
        $statusSesiLabel = match ($rawSesiStatus) {
            'published' => 'Dibuka (Dipublikasikan)',
            'closed' => 'Ditutup',
            default => 'Draf',
        };

        $dplScores = $jawabansPeriode
            ->map(fn (KuisionerJawaban $j) => $this->extractNormalizedGpmScore($j, 'dpl'))
            ->filter(fn ($v) => $v !== null)
            ->values();
        $mitraScores = $jawabansPeriode
            ->map(fn (KuisionerJawaban $j) => $this->extractNormalizedGpmScore($j, 'mitra'))
            ->filter(fn ($v) => $v !== null)
            ->values();

        $rataDpl = $dplScores->isNotEmpty() ? round((float) $dplScores->avg(), 2) : 0.0;
        $rataMitra = $mitraScores->isNotEmpty() ? round((float) $mitraScores->avg(), 2) : 0.0;

        if ($dplScores->isNotEmpty() && $mitraScores->isNotEmpty()) {
            $rataKeseluruhan = round(($rataDpl + $rataMitra) / 2, 2);
        } elseif ($dplScores->isNotEmpty()) {
            $rataKeseluruhan = $rataDpl;
        } elseif ($mitraScores->isNotEmpty()) {
            $rataKeseluruhan = $rataMitra;
        } else {
            $rataKeseluruhan = 0.0;
        }

        $respondenUserIds = $jawabansPeriode->pluck('mahasiswa_user_id')->filter()->unique();
        $targetCount = $terverifikasis->count();
        $sudahMengisi = $terverifikasis->filter(
            fn (PendaftaranMbkm $p) => (bool) $p->sudah_isi_kuisioner || $respondenUserIds->contains($p->user_id)
        )->count();
        $partisipasiPersen = $targetCount > 0
            ? min(100.0, round(($sudahMengisi / $targetCount) * 100, 1))
            : 0.0;

        return [
            'status_sesi' => $statusSesiLabel,
            'total_responden' => $jawabansPeriode->count(),
            'target_mahasiswa' => $targetCount,
            'sudah_mengisi_count' => $sudahMengisi,
            'partisipasi_persen' => $partisipasiPersen,
            'rata_rata_dpl' => $rataDpl,
            'rata_rata_mitra' => $rataMitra,
            'rata_rata_keseluruhan' => $rataKeseluruhan,
        ];
    }

    /**
     * Susun ringkasan mitra aktif teratas beserta progres Monev, Nilai Akhir, dan Skor GPM.
     */
    private function buildWadekTopMitra(
        Collection $activeMitraGroups,
        Collection $allMonevsPeriode,
        Collection $jawabansPeriode
    ): array {
        $monevsByMitra = $allMonevsPeriode->groupBy('mitra_id');
        $jawabansByMitra = $jawabansPeriode->whereNotNull('mitra_id')->groupBy('mitra_id');
        $indikatorKeys = array_keys(self::MONEV_INDIKATOR_LABELS);

        return $activeMitraGroups->map(function (Collection $students, $mitraId) use ($monevsByMitra, $jawabansByMitra, $indikatorKeys) {
            /** @var PendaftaranMbkm $first */
            $first = $students->first();
            $jumlah = $students->count();
            $layakCount = $students->filter(fn (PendaftaranMbkm $p) => $this->isPendaftaranLayakSemhas($p))->count();

            $scores = $students
                ->map(fn (PendaftaranMbkm $p) => $this->computeFinalScoreIfComplete($p))
                ->filter(fn ($score) => $score !== null)
                ->values();

            $mitraMonevs = $monevsByMitra->get($mitraId, collect());
            $monevSelesai = $mitraMonevs
                ->pluck('periode_monev')
                ->filter(fn ($t) => in_array($t, self::TAHAP_MONEV, true))
                ->unique()
                ->count();

            $hasil18 = $mitraMonevs
                ->where('periode_monev', 'Minggu ke-18')
                ->flatMap(fn (MonevMitra $m) => $m->hasilMahasiswas);

            $skorMonevHasil = null;
            if ($hasil18->isNotEmpty()) {
                $avgIndikator = collect($indikatorKeys)->map(fn (string $k) => (float) $hasil18->avg($k))->avg();
                $skorMonevHasil = round((float) $avgIndikator, 2);
            }

            $gpmMitraScores = $jawabansByMitra->get($mitraId, collect())
                ->map(fn (KuisionerJawaban $j) => $this->extractNormalizedGpmScore($j, 'mitra'))
                ->filter(fn ($v) => $v !== null)
                ->values();

            return [
                'mitra_id' => (int) $mitraId,
                'nama_mitra' => $first->mitra?->nama ?? $first->mitra_magang_text ?? '-',
                'alamat' => $first->mitra?->alamat,
                'jumlah_mahasiswa' => $jumlah,
                'layak_semhas_count' => $layakCount,
                'nilai_lengkap_count' => $scores->count(),
                'rata_rata_nilai_akhir' => $scores->isNotEmpty() ? round((float) $scores->avg(), 2) : null,
                'monev_selesai_count' => $monevSelesai,
                'skor_gpm_mitra' => $gpmMitraScores->isNotEmpty() ? round((float) $gpmMitraScores->avg(), 2) : null,
                'skor_monev_hasil' => $skorMonevHasil,
            ];
        })->sortBy([
            ['jumlah_mahasiswa', 'desc'],
            ['nama_mitra', 'asc'],
        ])->values()->all();
    }

    /**
     * Normalisasi skor jawaban GPM ke skala resmi 1-4 agar data legacy 1-5 tidak melewati batas 4.00.
     */
    private function extractNormalizedGpmScore(KuisionerJawaban $jawaban, string $target): ?float
    {
        $mapField = $target === 'dpl' ? 'jawaban_dpl' : 'jawaban_mitra';
        $avgField = $target === 'dpl' ? 'rata_rata_dpl' : 'rata_rata_mitra';

        $items = $jawaban->{$mapField};
        if (is_array($items) && count($items) > 0) {
            $normalizedItems = [];
            foreach ($items as $val) {
                if (is_numeric($val) && (float) $val >= 1) {
                    $normalizedItems[] = min(4.0, (float) $val);
                }
            }

            if (count($normalizedItems) > 0) {
                return round(array_sum($normalizedItems) / count($normalizedItems), 2);
            }
        }

        $rawAvg = $jawaban->{$avgField};
        if (is_numeric($rawAvg) && (float) $rawAvg > 0) {
            return min(4.0, round((float) $rawAvg, 2));
        }

        return null;
    }
}
