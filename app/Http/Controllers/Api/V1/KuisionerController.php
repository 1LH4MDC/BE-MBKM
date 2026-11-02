<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KuisionerJawaban;
use App\Models\KuisionerKategori;
use App\Models\KuisionerPertanyaan;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KuisionerController extends Controller
{
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

    public function ensureKuisionerTemplateForTahunAjaran($tahunAjaranId = null): ?PeriodeAkademik
    {
        $periode = $this->resolveTahunAjaran($tahunAjaranId);
        if (! $periode) {
            return null;
        }

        $exists = KuisionerKategori::where('tahun_ajaran_id', $periode->id)->exists();
        if ($exists) {
            return $periode;
        }

        $label = trim("{$periode->tahun_ajaran} {$periode->semester}");

        $katDpl = KuisionerKategori::create([
            'tahun_ajaran_id' => $periode->id,
            'tahun_ajaran' => $label,
            'nama' => 'Evaluasi DPL',
            'slug' => 'dpl',
            'target_evaluasi' => 'dpl',
            'is_active' => true,
            'urutan' => 1,
        ]);

        $katMitra = KuisionerKategori::create([
            'tahun_ajaran_id' => $periode->id,
            'tahun_ajaran' => $label,
            'nama' => 'Evaluasi Mitra',
            'slug' => 'mitra',
            'target_evaluasi' => 'mitra',
            'is_active' => true,
            'urutan' => 2,
        ]);

        $katSaran = KuisionerKategori::create([
            'tahun_ajaran_id' => $periode->id,
            'tahun_ajaran' => $label,
            'nama' => 'Saran Pengembangan',
            'slug' => 'saran',
            'target_evaluasi' => 'umum',
            'is_active' => true,
            'urutan' => 3,
        ]);

        $soalDpl = [
            'Kejelasan arahan dan penjelasan DPL sebelum pelaksanaan magang.',
            'Kemudahan mahasiswa dalam menghubungi DPL.',
            'Kecepatan DPL dalam merespons pertanyaan atau permasalahan mahasiswa.',
            'Intensitas pembimbingan yang dilakukan selama kegiatan magang.',
            'Kemampuan DPL memahami aktivitas dan tugas mahasiswa di lokasi magang.',
            'Kemampuan DPL memberikan solusi terhadap kendala yang dihadapi mahasiswa.',
            'Kualitas saran dan masukan yang diberikan untuk mendukung keberhasilan magang.',
            'Konsistensi DPL dalam memonitor perkembangan kegiatan mahasiswa.',
            'Kejelasan mekanisme dan kriteria penilaian magang.',
            'Ketepatan waktu DPL dalam memberikan umpan balik terhadap logbook atau laporan.',
            'Pendampingan DPL dalam penyusunan laporan akhir dan luaran magang.',
            'Dukungan DPL dalam membantu mahasiswa mencapai target pembelajaran magang.',
        ];

        foreach ($soalDpl as $index => $text) {
            KuisionerPertanyaan::create([
                'tahun_ajaran_id' => $periode->id,
                'tahun_ajaran' => $label,
                'kategori_id' => $katDpl->id,
                'kategori' => 'dpl',
                'nomor_urut' => $index + 1,
                'pertanyaan' => $text,
                'tipe_input' => 'likert_5',
                'opsi_pilihan' => null,
                'is_active' => true,
            ]);
        }

        $soalMitra = [
            'Mitra memberikan orientasi awal serta penjelasan yang jelas mengenai tugas, tanggung jawab, dan proses penggalian kebutuhan (requirement).',
            'Tugas yang diberikan sesuai dengan kompetensi mahasiswa serta mendukung pencapaian capaian pembelajaran Capstone Project/Magang Berdampak.',
            'Supervisor lapangan mudah dihubungi dan responsif dalam memberikan pendampingan kepada mahasiswa.',
            'Supervisor lapangan memberikan arahan, pendampingan, dan solusi terhadap kendala yang dihadapi mahasiswa selama Capstone Project/Magang Berdampak berjalan.',
            'Supervisor memberikan umpan balik yang konstruktif terhadap hasil kerja dan perkembangan mahasiswa.',
            'Mahasiswa memperoleh kesempatan untuk mengembangkan pengetahuan, keterampilan, dan pengalaman kerja selama Capstone Project/Magang Berdampak berjalan.',
            'Kegiatan yang diberikan oleh mitra mendukung pencapaian capaian pembelajaran dan kesiapan mahasiswa memasuki dunia kerja.',
            'Lingkungan kerja di mitra mendukung proses belajar, kolaborasi, dan pengembangan mahasiswa.',
            'Fasilitas, perangkat, dan sumber daya yang disediakan mitra memadai untuk menunjang pelaksanaan Capstone Project/Magang Berdampak.',
            'Beban kerja yang diberikan kepada mahasiswa sesuai dengan kapasitas mahasiswa dan tujuan pembelajaran Capstone Project/Magang Berdampak berjalan.',
            'Mitra menciptakan lingkungan kerja yang profesional, menghargai hak mahasiswa, serta memberikan perlakuan yang adil selama pelaksanaan Capstone Project/Magang Berdampak berjalan.',
        ];

        foreach ($soalMitra as $index => $text) {
            KuisionerPertanyaan::create([
                'tahun_ajaran_id' => $periode->id,
                'tahun_ajaran' => $label,
                'kategori_id' => $katMitra->id,
                'kategori' => 'mitra',
                'nomor_urut' => $index + 1,
                'pertanyaan' => $text,
                'tipe_input' => 'likert_5',
                'opsi_pilihan' => null,
                'is_active' => true,
            ]);
        }

        KuisionerPertanyaan::create([
            'tahun_ajaran_id' => $periode->id,
            'tahun_ajaran' => $label,
            'kategori_id' => $katMitra->id,
            'kategori' => 'mitra',
            'nomor_urut' => 12,
            'pertanyaan' => 'Apakah Anda menerima kompensasi dari mitra selama pelaksanaan magang?',
            'tipe_input' => 'checkbox',
            'opsi_pilihan' => [
                'Tidak menerima',
                'Uang saku',
                'Transportasi',
                'Konsumsi',
                'Akomodasi',
                'Insentif/gaji',
                'Sertifikat',
            ],
            'is_active' => true,
        ]);

        KuisionerPertanyaan::create([
            'tahun_ajaran_id' => $periode->id,
            'tahun_ajaran' => $label,
            'kategori_id' => $katMitra->id,
            'kategori' => 'mitra',
            'nomor_urut' => 13,
            'pertanyaan' => 'Jika Ya, berapa rata-rata kompensasi yang Anda terima per bulan?',
            'tipe_input' => 'radio',
            'opsi_pilihan' => [
                '< Rp500.000',
                'Rp500.000 - Rp999.999',
                'Rp1.000.000 - Rp1.999.999',
                'Rp2.000.000 - Rp2.999.999',
                'Rp3.000.000 - Rp4.999.999',
                '≥ Rp5.000.000',
                'Tidak ada',
            ],
            'is_active' => true,
        ]);

        KuisionerPertanyaan::create([
            'tahun_ajaran_id' => $periode->id,
            'tahun_ajaran' => $label,
            'kategori_id' => $katSaran->id,
            'kategori' => 'saran',
            'nomor_urut' => 1,
            'pertanyaan' => 'Apa saran atau masukan Anda kepada dosen maupun Fakultas Ilmu Komputer untuk meningkatkan kualitas pelaksanaan pembelajaran di luar program studi (Capstone & Magang Mandiri) di masa mendatang?',
            'tipe_input' => 'textarea',
            'opsi_pilihan' => null,
            'is_active' => true,
        ]);

        return $periode;
    }

    public function kategori(Request $request): JsonResponse
    {
        $query = KuisionerKategori::query()->withCount('pertanyaans');

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

    public function storeKategori(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'target_evaluasi' => ['nullable', 'string', 'in:dpl,mitra,umum'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['nama']);
        $baseSlug = $slug;
        $counter = 1;
        while (KuisionerKategori::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $maxUrutan = KuisionerKategori::max('urutan') ?? 0;

        $kategori = KuisionerKategori::create([
            'nama' => trim($data['nama']),
            'slug' => $slug,
            'target_evaluasi' => $data['target_evaluasi'] ?? 'umum',
            'is_active' => $data['is_active'] ?? true,
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
                while (KuisionerKategori::where('slug', $slug)->where('id', '!=', $id)->exists()) {
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

        KuisionerPertanyaan::where('kategori_id', $id)->delete();
        $kategori->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori kuisioner berhasil dihapus.',
        ]);
    }

    public function pertanyaan(Request $request): JsonResponse
    {
        $query = KuisionerPertanyaan::query();

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->input('kategori_id'));
        } elseif ($request->filled('kategori')) {
            $kat = $request->input('kategori');
            $query->where(function ($q) use ($kat) {
                $q->where('kategori', $kat)
                    ->orWhereHas('kategoriKategori', function ($sub) use ($kat) {
                        $sub->where('slug', $kat);
                    });
            });
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
        $data = $request->validate([
            'kategori' => ['nullable', 'string'],
            'kategori_id' => ['nullable', 'exists:kuisioner_kategoris,id'],
            'pertanyaan' => ['required', 'string'],
            'tipe_input' => ['nullable', 'string', 'in:likert_5,checkbox,radio,textarea'],
            'opsi_pilihan' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $kategoriId = $data['kategori_id'] ?? null;
        $kategoriSlug = $data['kategori'] ?? null;

        if ($kategoriId && ! $kategoriSlug) {
            $katObj = KuisionerKategori::find($kategoriId);
            $kategoriSlug = $katObj?->slug ?? 'umum';
        } elseif ($kategoriSlug && ! $kategoriId) {
            $katObj = KuisionerKategori::where('slug', $kategoriSlug)->first();
            $kategoriId = $katObj?->id;
        }

        if (! $kategoriSlug) {
            $kategoriSlug = 'dpl';
        }

        $maxUrut = KuisionerPertanyaan::where(function ($q) use ($kategoriId, $kategoriSlug) {
            if ($kategoriId) {
                $q->where('kategori_id', $kategoriId);
            } else {
                $q->where('kategori', $kategoriSlug);
            }
        })->max('nomor_urut') ?? 0;

        $pertanyaan = KuisionerPertanyaan::create([
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

        $data = $request->validate([
            'pertanyaan' => ['sometimes', 'required', 'string'],
            'kategori' => ['sometimes', 'nullable', 'string'],
            'kategori_id' => ['sometimes', 'nullable', 'exists:kuisioner_kategoris,id'],
            'tipe_input' => ['nullable', 'string', 'in:likert_5,checkbox,radio,textarea'],
            'opsi_pilihan' => ['nullable', 'array'],
            'nomor_urut' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($data['kategori_id']) && ! isset($data['kategori'])) {
            $katObj = KuisionerKategori::find($data['kategori_id']);
            if ($katObj) {
                $data['kategori'] = $katObj->slug;
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

    public function rekap(Request $request): JsonResponse
    {
        $paramTahun = $request->input('tahun_ajaran') ?? $request->input('tahun_ajaran_id');
        $isFilterAll = empty($paramTahun) || $paramTahun === 'all' || $paramTahun === 'Semua Tahun Ajaran' || $paramTahun === 'semua';

        $soalDpl = KuisionerPertanyaan::where('kategori', 'dpl')
            ->where('tipe_input', 'likert_5')
            ->orderBy('nomor_urut')
            ->get();

        $soalMitra = KuisionerPertanyaan::where('kategori', 'mitra')
            ->where('tipe_input', 'likert_5')
            ->orderBy('nomor_urut')
            ->get();

        $jawabanQuery = KuisionerJawaban::with(['dpl.dosen', 'mitra']);
        if (! $isFilterAll && ! empty($paramTahun)) {
            if (is_numeric($paramTahun)) {
                $p = PeriodeAkademik::find((int) $paramTahun);
                $label = $p ? trim("{$p->tahun_ajaran} {$p->semester}") : null;
                $jawabanQuery->where(function ($q) use ($paramTahun, $label) {
                    $q->where('tahun_ajaran_id', (int) $paramTahun);
                    if ($label) {
                        $q->orWhere('tahun_ajaran', $label);
                    }
                });
            } else {
                $jawabanQuery->where('tahun_ajaran', $paramTahun);
            }
        }
        $jawabans = $jawabanQuery->get();

        $getMutu = function (float $score): array {
            if ($score >= 4.50) {
                return ['label' => 'Sangat Baik', 'color' => 'emerald'];
            }
            if ($score >= 3.75) {
                return ['label' => 'Baik', 'color' => 'blue'];
            }
            if ($score >= 3.00) {
                return ['label' => 'Cukup', 'color' => 'amber'];
            }

            return ['label' => 'Perlu Evaluasi', 'color' => 'rose'];
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
            $totalDistribusiDpl = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

            foreach ($soalDpl as $soal) {
                $scores = [];
                $distribusi = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

                foreach ($list as $jawaban) {
                    $map = $jawaban->jawaban_dpl ?? [];
                    $val = (int) ($map[$soal->id] ?? $map[(string) $soal->id] ?? 0);
                    if ($val >= 1 && $val <= 5) {
                        $scores[] = $val;
                        $distribusi[$val]++;
                        $totalDistribusiDpl[$val]++;
                    }
                }

                $avgItem = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0.00;
                $totalRataDpl += $avgItem;

                $skorPerItem[] = [
                    'pertanyaan_id' => $soal->id,
                    'nomor_urut' => $soal->nomor_urut,
                    'pertanyaan' => $soal->pertanyaan,
                    'rata_rata' => $avgItem,
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
            $totalDistribusiMitra = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

            foreach ($soalMitra as $soal) {
                $scores = [];
                $distribusi = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

                foreach ($list as $jawaban) {
                    $map = $jawaban->jawaban_mitra ?? [];
                    $val = (int) ($map[$soal->id] ?? $map[(string) $soal->id] ?? 0);
                    if ($val >= 1 && $val <= 5) {
                        $scores[] = $val;
                        $distribusi[$val]++;
                        $totalDistribusiMitra[$val]++;
                    }
                }

                $avgItem = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0.00;
                $totalRataMitra += $avgItem;

                $skorPerItem[] = [
                    'pertanyaan_id' => $soal->id,
                    'nomor_urut' => $soal->nomor_urut,
                    'pertanyaan' => $soal->pertanyaan,
                    'rata_rata' => $avgItem,
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

        $pendaftaran = PendaftaranMbkm::with([
            'mitra',
            'dpl',
            'programStudi',
            'periodeAkademik',
        ])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if (! empty($user->nim_nip)) {
                    $q->orWhere('nim', $user->nim_nip);
                }
            })
            ->latest()
            ->first();

        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->first();
        $tahunAjaranLabel = $pendaftaran?->tahun_ajaran
            ?? ($pendaftaran?->periodeAkademik ? trim("{$pendaftaran->periodeAkademik->tahun_ajaran} {$pendaftaran->periodeAkademik->semester}") : null)
            ?? ($periodeAktif ? trim("{$periodeAktif->tahun_ajaran} {$periodeAktif->semester}") : '2026/2027 Ganjil');

        $kategoris = KuisionerKategori::where('is_active', true)->orderBy('urutan', 'asc')->get();

        $kategoriList = [];
        foreach ($kategoris as $kat) {
            $pertanyaans = KuisionerPertanyaan::where('is_active', true)
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

        $jawabanQuery = KuisionerJawaban::where('mahasiswa_user_id', $user->id);
        if ($pendaftaran?->id) {
            $jawabanQuery->where(function ($q) use ($pendaftaran, $tahunAjaranLabel) {
                $q->where('pendaftaran_mbkm_id', $pendaftaran->id)
                    ->orWhere('tahun_ajaran', $tahunAjaranLabel);
            });
        }
        $existingJawaban = $jawabanQuery->latest()->first();

        $hasDpl = ! empty($pendaftaran?->dpl_id) && $pendaftaran?->dpl !== null;

        return response()->json([
            'success' => true,
            'data' => [
                'has_dpl' => $hasDpl,
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
                'has_submitted' => $existingJawaban !== null,
                'jawaban' => $existingJawaban,
            ],
        ]);
    }

    public function storeJawaban(Request $request): JsonResponse
    {
        $user = $request->user();

        $pendaftaran = PendaftaranMbkm::with(['mitra', 'dpl', 'periodeAkademik'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if (! empty($user->nim_nip)) {
                    $q->orWhere('nim', $user->nim_nip);
                }
            })
            ->latest()
            ->first();

        if (! $pendaftaran || empty($pendaftaran->dpl_id) || ! $pendaftaran->dpl) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum dapat mengisi kuisioner karena Dosen Pembimbing Lapangan (DPL) belum ditentukan.',
            ], 403);
        }

        $periode = $pendaftaran->periodeAkademik ?? PeriodeAkademik::where('is_aktif', true)->first();
        $tahunAjaranLabel = $pendaftaran->tahun_ajaran
            ?? ($periode ? trim("{$periode->tahun_ajaran} {$periode->semester}") : '2026/2027 Ganjil');
        $tahunAjaranId = $periode?->id;

        $data = $request->validate([
            'jawaban_dpl' => ['nullable', 'array'],
            'jawaban_mitra' => ['nullable', 'array'],
            'kompensasi_tipe' => ['nullable', 'array'],
            'kompensasi_nominal' => ['nullable', 'string', 'max:255'],
            'saran_masukan' => ['nullable', 'string', 'max:5000'],
        ]);

        $jawabanDpl = $data['jawaban_dpl'] ?? [];
        $dplScores = array_filter(array_values($jawabanDpl), fn ($v) => is_numeric($v) && $v >= 1 && $v <= 5);
        $rataRataDpl = count($dplScores) > 0 ? round(array_sum($dplScores) / count($dplScores), 2) : 0.00;

        $jawabanMitra = $data['jawaban_mitra'] ?? [];
        $mitraScores = array_filter(array_values($jawabanMitra), fn ($v) => is_numeric($v) && $v >= 1 && $v <= 5);
        $rataRataMitra = count($mitraScores) > 0 ? round(array_sum($mitraScores) / count($mitraScores), 2) : 0.00;

        $namaMitra = $pendaftaran?->mitra?->nama ?? $pendaftaran?->mitra_magang_text ?? 'Mitra Magang';

        $jawaban = KuisionerJawaban::updateOrCreate(
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

        return response()->json([
            'success' => true,
            'message' => 'Evaluasi kuisioner berhasil dikirim. Terima kasih atas partisipasi Anda!',
            'data' => $jawaban,
        ], 200);
    }

    public function indexJawaban(Request $request): JsonResponse
    {
        $query = KuisionerJawaban::with(['mahasiswa', 'dpl.dosen', 'mitra', 'pendaftaranMbkm']);

        if ($request->filled('tahun_ajaran')) {
            $ta = $request->input('tahun_ajaran');
            if ($ta !== 'all' && $ta !== 'Semua Tahun Ajaran') {
                $query->where('tahun_ajaran', $ta);
            }
        } elseif ($request->filled('tahun_ajaran_id')) {
            $query->where('tahun_ajaran_id', $request->input('tahun_ajaran_id'));
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
