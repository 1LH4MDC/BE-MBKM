<?php

namespace Database\Seeders;

use App\Models\KuisionerJawaban;
use App\Models\KuisionerKategori;
use App\Models\KuisionerPertanyaan;
use Illuminate\Database\Seeder;

class KuisionerSeeder extends Seeder
{
    public function run(): void
    {

        KuisionerJawaban::truncate();
        KuisionerPertanyaan::truncate();
        KuisionerKategori::truncate();

        $katDpl = KuisionerKategori::create([
            'nama' => 'Evaluasi DPL',
            'slug' => 'dpl',
            'target_evaluasi' => 'dpl',
            'is_active' => true,
            'urutan' => 1,
        ]);

        $katMitra = KuisionerKategori::create([
            'nama' => 'Evaluasi Mitra',
            'slug' => 'mitra',
            'target_evaluasi' => 'mitra',
            'is_active' => true,
            'urutan' => 2,
        ]);

        $katSaran = KuisionerKategori::create([
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
            'kategori_id' => $katSaran->id,
            'kategori' => 'saran',
            'nomor_urut' => 1,
            'pertanyaan' => 'Apa saran atau masukan Anda kepada dosen maupun Fakultas Ilmu Komputer untuk meningkatkan kualitas pelaksanaan pembelajaran di luar program studi (Capstone & Magang Mandiri) di masa mendatang?',
            'tipe_input' => 'textarea',
            'opsi_pilihan' => null,
            'is_active' => true,
        ]);
    }
}
