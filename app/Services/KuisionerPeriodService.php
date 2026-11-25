<?php

namespace App\Services;

use App\Models\KuisionerKategori;
use App\Models\KuisionerPertanyaan;
use App\Models\KuisionerSesi;
use App\Models\PeriodeAkademik;
use Illuminate\Support\Facades\DB;

class KuisionerPeriodService
{
    public function formatPeriodeLabel(PeriodeAkademik $periode): string
    {
        return trim("{$periode->tahun_ajaran} {$periode->semester}");
    }

    /**
     * Memastikan periode memiliki sesi kuisioner (default Draf) dan template kategori/pertanyaan.
     * Jika periode baru belum memiliki kategori, otomatis melakukan clone dari periode sebelumnya.
     */
    public function ensurePeriodeInitialized(PeriodeAkademik $periode): KuisionerSesi
    {
        $sesi = KuisionerSesi::firstOrCreate(
            ['periode_akademik_id' => $periode->id],
            ['status' => 'draft']
        );

        $label = $this->formatPeriodeLabel($periode);

        // Migrasi data seeder/legacy/fixture tanpa tahun_ajaran_id ke periode ini
        if (KuisionerKategori::whereNull('tahun_ajaran_id')->exists()) {
            DB::transaction(function () use ($periode, $label) {
                KuisionerKategori::whereNull('tahun_ajaran_id')->update([
                    'tahun_ajaran_id' => $periode->id,
                    'tahun_ajaran' => $label,
                    'is_active' => true,
                ]);
                KuisionerPertanyaan::whereNull('tahun_ajaran_id')->update([
                    'tahun_ajaran_id' => $periode->id,
                    'tahun_ajaran' => $label,
                ]);
            });
        }

        // Kaitkan pertanyaan orphan tanpa tahun_ajaran_id (misal dari test seeder) ke periode ini bila kategori periode sudah ada
        if (KuisionerKategori::where('tahun_ajaran_id', $periode->id)->exists()
            && KuisionerPertanyaan::whereNull('tahun_ajaran_id')->exists()) {
            $katsById = KuisionerKategori::where('tahun_ajaran_id', $periode->id)->get()->keyBy('id');
            $katsBySlug = KuisionerKategori::where('tahun_ajaran_id', $periode->id)->get()->keyBy('slug');
            $orphans = KuisionerPertanyaan::whereNull('tahun_ajaran_id')->get();
            foreach ($orphans as $q) {
                $matchedKat = ($q->kategori_id ? $katsById->get($q->kategori_id) : null)
                    ?? ($q->kategori ? $katsBySlug->get($q->kategori) : null);
                if ($matchedKat) {
                    $q->update([
                        'tahun_ajaran_id' => $periode->id,
                        'tahun_ajaran' => $label,
                        'kategori_id' => $matchedKat->id,
                        'kategori' => $matchedKat->slug,
                    ]);
                }
            }
        }

        if (KuisionerKategori::where('tahun_ajaran_id', $periode->id)->exists()) {
            // Jika hanya ada kategori kerangka bawaan migrasi tanpa pertanyaan sama sekali di DB, isi template default
            if (KuisionerPertanyaan::where('tahun_ajaran_id', $periode->id)->doesntExist()
                && KuisionerPertanyaan::whereNotNull('tahun_ajaran_id')->doesntExist()) {
                $this->seedDefaultTemplate($periode);
            }

            return $sesi;
        }

        // Cari periode lain terbaru yang sudah memiliki template kategori kuisioner untuk di-clone
        $sourcePeriodeId = KuisionerKategori::whereNotNull('tahun_ajaran_id')
            ->where('tahun_ajaran_id', '!=', $periode->id)
            ->orderByDesc('tahun_ajaran_id')
            ->value('tahun_ajaran_id');

        if ($sourcePeriodeId) {
            $this->cloneTemplateFromPeriode((int) $sourcePeriodeId, $periode);
        } else {
            $this->seedDefaultTemplate($periode);
        }

        return $sesi;
    }

    public function cloneTemplateFromPeriode(int $sourcePeriodeId, PeriodeAkademik $targetPeriode): void
    {
        $targetLabel = $this->formatPeriodeLabel($targetPeriode);

        DB::transaction(function () use ($sourcePeriodeId, $targetPeriode, $targetLabel) {
            if (KuisionerKategori::where('tahun_ajaran_id', $targetPeriode->id)->exists()) {
                return;
            }

            $sourceCategories = KuisionerKategori::where('tahun_ajaran_id', $sourcePeriodeId)
                ->orderBy('urutan')
                ->orderBy('id')
                ->get();

            $sourceQuestions = KuisionerPertanyaan::where('tahun_ajaran_id', $sourcePeriodeId)
                ->orderBy('nomor_urut')
                ->orderBy('id')
                ->get();

            $clonedCategoryIds = [];

            foreach ($sourceCategories as $srcCat) {
                $newCat = KuisionerKategori::create([
                    'tahun_ajaran_id' => $targetPeriode->id,
                    'tahun_ajaran' => $targetLabel,
                    'nama' => $srcCat->nama,
                    'slug' => $srcCat->slug,
                    'target_evaluasi' => $srcCat->target_evaluasi,
                    'is_active' => true,
                    'urutan' => $srcCat->urutan,
                ]);
                $clonedCategoryIds[$srcCat->id] = $newCat->id;

                $catQuestions = $sourceQuestions->filter(function ($q) use ($srcCat) {
                    if ($q->kategori_id !== null) {
                        return (int) $q->kategori_id === (int) $srcCat->id;
                    }

                    return $q->kategori === $srcCat->slug;
                });

                foreach ($catQuestions as $srcQ) {
                    KuisionerPertanyaan::create([
                        'tahun_ajaran_id' => $targetPeriode->id,
                        'tahun_ajaran' => $targetLabel,
                        'kategori_id' => $newCat->id,
                        'kategori' => $newCat->slug,
                        'nomor_urut' => $srcQ->nomor_urut,
                        'pertanyaan' => $srcQ->pertanyaan,
                        'tipe_input' => $srcQ->tipe_input,
                        'opsi_pilihan' => $srcQ->opsi_pilihan,
                        'is_active' => true,
                    ]);
                }
            }
        });
    }

    public function seedDefaultTemplate(PeriodeAkademik $periode): void
    {
        $label = $this->formatPeriodeLabel($periode);

        DB::transaction(function () use ($periode, $label) {
            if (KuisionerPertanyaan::where('tahun_ajaran_id', $periode->id)->exists()) {
                return;
            }

            $katDpl = KuisionerKategori::firstOrCreate(
                ['tahun_ajaran_id' => $periode->id, 'slug' => 'dpl'],
                [
                    'tahun_ajaran' => $label,
                    'nama' => 'Evaluasi DPL',
                    'target_evaluasi' => 'dpl',
                    'is_active' => true,
                    'urutan' => 1,
                ]
            );

            $katMitra = KuisionerKategori::firstOrCreate(
                ['tahun_ajaran_id' => $periode->id, 'slug' => 'mitra'],
                [
                    'tahun_ajaran' => $label,
                    'nama' => 'Evaluasi Mitra',
                    'target_evaluasi' => 'mitra',
                    'is_active' => true,
                    'urutan' => 2,
                ]
            );

            $katSaran = KuisionerKategori::firstOrCreate(
                ['tahun_ajaran_id' => $periode->id, 'slug' => 'saran'],
                [
                    'tahun_ajaran' => $label,
                    'nama' => 'Saran Pengembangan',
                    'target_evaluasi' => 'umum',
                    'is_active' => true,
                    'urutan' => 3,
                ]
            );

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
        });
    }
}
