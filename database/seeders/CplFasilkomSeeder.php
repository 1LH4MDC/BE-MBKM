<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CplFasilkomSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $prodi = DB::table('program_studis')->pluck('id', 'kode');

        $data = [
            'PSIF' => [
                'Mampu menginternalisasi sikap beriman dan bertakwa kepada Tuhan Yang Maha Esa, berakhlak mulia, berkarakter sesuai nilai-nilai pancasila dan cinta tanah air dalam menerapkan ilmu pengetahuan dan teknologi yang bermanfaat bagi masyarakat.',
                'Mampu menganalisis permasalahan pertanian industrial berkelanjutan dengan sikap logis, kritis, inovatif serta adaptif terhadap perubahan teknologi dan kemajuan bidang Teknologi Informasi dan Komunikasi dengan pendekatan ilmiah, etika ilmiah dan nilai utama Universitas Jember.',
                'Mampu mengintegrasikan pengetahuan tentang analisis data, manajemen, dan/atau kewirausahaan dalam penerapannya di bidang Teknologi Informasi dan Komunikasi yang berwawasan lingkungan, bisnis dan/atau pertanian industrial.',
                'Mampu membangun wirausaha dalam bidang Teknologi, Informasi dan Komunikasi khususnya yang mendukung ekosistem agroindustri baik secara individu maupun kelompok',
                'Mampu memecahkan masalah berbasis komputasi dengan konsep matematika, pemodelan, statistik, dan visualisasi grafis',
                'Mampu memvalidasi berbagai aspek teknologi dan jaringan yang digunakan dalam pengembangan suatu program, aplikasi, dan sistem informasi',
                'Mampu mendesain konsep pemrograman dan algoritma untuk menyelesaikan permasalahan menggunakan perangkat lunak dan sistem komputer',
                'Mampu menerapkan solusi berbasis kecerdasan buatan untuk mendukung pertanian industrial dengan cara mengintegrasikan konsep pemodelan, proses, validasi dan evaluasi',
                'Mampu membuat perangkat lunak, dengan menerapkan algoritma dan pemodelan sistem cerdas baik secara individu maupun kelompok',
                'Mampu membuat karya dengan konsep grafika atau visualisasi',
            ],
            'PSTI' => [
                'Mampu menginternalisasi sikap takwa kepada Tuhan Yang Maha Esa dan cinta tanah air.',
                'Mampu menunjukkan sikap logis, kritis, inovatif serta adaptif terhadap perubahan teknologi dan kemajuan bidang Teknologi Informasi dan Komunikasi dengan pendekatan ilmiah dan etika ilmiah.',
                'Mampu mengintegrasikan pengetahuan tentang analisis data, manajemen, dan keamanan sistem dalam penerapannya di bidang Teknologi, Informasi dan Komunikasi yang berwawasan lingkungan, dan/atau pertanian industrial',
                'Mampu mengelola wirausaha dalam bidang Teknologi, Informasi dan Komunikasi khususnya yang mendukung ekosistem agroindustri baik secara individu maupun kelompok.',
                'Mampu mengorganisasikan penggunaan berbagai perangkat jaringan komputer untuk mendukung ekosistem agroindustri.',
                'Mampu memilih model pengembangan aplikasi dan keamanan sistem yang mendukung ekosistem agroindustri.',
                'Mampu mengorganisasikan berbagai sumber daya komputer sebagai pendukung operasional bagi organisasi atau perusahaan.',
                'Mampu mendiagnosis topologi jaringan komputer yang sesuai dengan kebutuhan organisasi.',
                'Mampu mengorganisasikan infrastruktur jaringan komputer dan produk digital yang berbasis cloud computing untuk mendukung ekosistem agroindustri.',
                'Mampu mengimplementasikan bahasa pemrograman dan algoritma dalam bentuk program dan aplikasi berbasis teknologi platform.',
                'Mampu mendiagnosis keamanan jaringan dan sistem informasi untuk mendukung ekosistem agroindustri.',
            ],
            'PSSI' => [
                'Mampu menunjukkan sikap beriman, bertaqwa, berakhlak mulia, berkarakter sesuai dengan nilai-nilai Pancasila dalam menerapkan ilmu pengetahuan dan teknologi yang bermanfaat bagi masyarakat.',
                'Mampu menunjukkan cara berfikir yang logis, kritis, inovatif serta adaptif terhadap perubahan teknologi dan kemajuan bidang Teknologi, Informasi dan Komunikasi dengan pendekatan ilmiah dan etika ilmiah dalam analisis permasalahan pertanian industrial berkelanjutan sesuai dengan nilai utama Universitas Jember',
                'Mampu mengintegrasikan pengetahuan tentang analisis data, manajemen, keamanan sistem, dan kewirausahaan dalam penerapannya di bidang Teknologi, Informasi dan Komunikasi yang berwawasan lingkungan, bisnis dan/atau pertanian industrial.',
                'Mampu membangun jiwa kewirausahaan dalam bidang Teknologi, Informasi dan Komunikasi khususnya yang mendukung ekosistem agroindustri baik secara individu maupun kelompok',
                'Mampu menerapkan konsep data dan informasi, matematika, statistik, teknik pemrograman, serta tata kelola Teknologi Informasi dalam pengembangan sistem informasi organisasi',
                'Mampu menelaah pendekatan manajemen dan arsitektur organisasi dalam penyusunan perangkat lunak dan sistem komputer, khususnya dalam bidang pertanian industrial',
                'Mampu mengolah data dengan cara mengintegrasikan teknik pemrograman, statistik, data analitik untuk mendukung pengambilan keputusan',
                'Mampu membuat perangkat lunak sesuai dengan kebutuhan tata kelola organisasi baik secara individu maupun kelompok, khususnya yang mendukung pengembangan ekosistem pertanian industrial',
                'Mampu mengevaluasi kinerja sistem informasi dalam organisasi',
                'Mampu merancang arsitektur teknologi informasi dalam organisasi baik secara individu maupun kelompok',
                'Mampu mengelola proyek sistem informasi, risiko, dan mitigasi risiko',
            ],
        ];

        foreach ($data as $kodeProdi => $list) {
            foreach ($list as $i => $deskripsi) {
                $kode = sprintf('%s_CPL%02d', $kodeProdi, $i + 1);
                DB::table('cpls')->updateOrInsert(
                    ['kode' => $kode],
                    [
                        'program_studi_id' => $prodi[$kodeProdi],
                        'deskripsi' => $deskripsi,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}
