<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MataKuliahFasilkomSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $prodiMap = DB::table('program_studis')->pluck('id', 'kode')->all();
        $cplMap = DB::table('cpls')->pluck('id', 'kode')->all();

        $courses = array (
  0 => 
  array (
    'kode' => 'KFU1202',
    'nama' => 'Algoritma Evolusi',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Algoritma Evolusi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Algoritma Evolusi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Algoritma Evolusi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Algoritma Evolusi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dasar algoritma evolusi serta mekanisme seleksi, crossover, dan mutasi dalam proses optimasi',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu menerapkan algoritma evolusi untuk menyelesaikan permasalahan optimasi menggunakan perangkat lunak atau bahasa pemrograman',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu merancang solusi optimasi berbasis algoritma evolusi untuk menyelesaikan studi kasus industri',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan dan mengevaluasi performa algoritma evolusi menggunakan tools pemrograman pada dataset nyata',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu merancang solusi optimasi berbasis algoritma evolusi untuk studi kasus nyata di industri',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mampu mengimplementasikan dan menguji algoritma menggunakan tools pemrograman untuk menghasilkan solusi terukur dan terdokumentasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL05',
      1 => 'PSIF_CPL07',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL10',
      4 => 'PSSI_CPL03',
      5 => 'PSSI_CPL07',
    ),
  ),
  1 => 
  array (
    'kode' => 'KFU1014',
    'nama' => 'Analisis Data Multivariat',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Analisis Data Multivariat secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Analisis Data Multivariat sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Analisis Data Multivariat.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Analisis Data Multivariat.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep analisis data multivariat seperti regresi multivariat, PCA, dan clustering',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menerapkan metode analisis data multivariat untuk mengolah dan menginterpretasikan dataset multidimensi',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu melakukan analisis multivariat pada data riil organisasi untuk menemukan pola dan insight strategis',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL07',
        'deskripsi' => 'Mampu menyusun laporan analitik berbasis data sebagai rekomendasi pengambilan keputusan',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu menganalisis dataset riil organisasi menggunakan teknik multivariat dengan software analitik',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mampu menyusun laporan analisis dan rekomendasi berbasis data untuk kebutuhan pengambilan keputusan mitra industri',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
      1 => 'PSIF_CPL05',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL07',
      4 => 'PSSI_CPL03',
      5 => 'PSSI_CPL07',
    ),
  ),
  2 => 
  array (
    'kode' => 'KFU1401',
    'nama' => 'Arsitektur Perangkat Lunak',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Arsitektur Perangkat Lunak secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Arsitektur Perangkat Lunak sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Arsitektur Perangkat Lunak.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Arsitektur Perangkat Lunak.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep arsitektur perangkat lunak dan berbagai pola arsitektur sistem perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu merancang arsitektur perangkat lunak sesuai kebutuhan sistem dan prinsip rekayasa perangkat lunak',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL04',
        'deskripsi' => 'Mampu merancang arsitektur perangkat lunak berbasis kebutuhan stakeholder dan skenario industri',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mendokumentasikan dan mempresentasikan desain arsitektur sesuai standar profesional',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mampu merancang arsitektur sistem berbasis kebutuhan mitra/klien menggunakan pendekatan dan notasi standar industri',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu menyusun dokumen desain arsitektur dan mempresentasikan solusi teknis kepada stakeholder',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL07',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL04',
      3 => 'PSTI_CPL10',
      4 => 'PSSI_CPL06',
      5 => 'PSSI_CPL08',
    ),
  ),
  3 => 
  array (
    'kode' => 'KFU1016',
    'nama' => 'Augmented Reality',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Augmented Reality secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Augmented Reality sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Augmented Reality.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Augmented Reality.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dasar augmented reality serta komponen sistem AR',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL10',
        'deskripsi' => 'Mahasiswa mampu mengembangkan aplikasi augmented reality sederhana menggunakan perangkat pengembangan yang tersedia',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mampu mengembangkan prototipe aplikasi AR untuk kebutuhan edukasi, pemasaran, atau industri kreatif',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu melakukan pengujian dan evaluasi usability aplikasi AR bersama pengguna',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL04',
        'deskripsi' => 'Mampu mengembangkan prototipe aplikasi AR untuk kebutuhan promosi, edukasi, atau visualisasi industri',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu melakukan uji coba dan evaluasi usability aplikasi AR berdasarkan kebutuhan pengguna lapangan',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL09',
      1 => 'PSIF_CPL10',
      2 => 'PSTI_CPL02',
      3 => 'PSTI_CPL03',
      4 => 'PSSI_CPL04',
      5 => 'PSSI_CPL08',
    ),
  ),
  4 => 
  array (
    'kode' => 'KFU1015',
    'nama' => 'Big Data',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Big Data secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Big Data sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Big Data.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Big Data.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep big data termasuk karakteristik, arsitektur, dan teknologi pengolahan data skala besar',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu mengolah dan menganalisis dataset besar menggunakan tools atau framework big data',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu membangun pipeline pemrosesan data skala besar menggunakan platform big data',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL07',
        'deskripsi' => 'Mampu menganalisis dan memvisualisasikan hasil pengolahan data sebagai dasar rekomendasi bisnis',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu mengelola dan memproses data skala besar menggunakan platform big data yang relevan di industri',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL09',
        'deskripsi' => 'Mampu menyajikan dashboard atau insight analitik yang dapat digunakan sebagai dasar rekomendasi strategis organisasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
      1 => 'PSIF_CPL05',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL07',
      4 => 'PSSI_CPL03',
      5 => 'PSSI_CPL09',
    ),
  ),
  5 => 
  array (
    'kode' => 'KFU1205',
    'nama' => 'Computer Vision',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Computer Vision secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Computer Vision sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Computer Vision.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Computer Vision.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL08',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dasar pengolahan citra dan computer vision seperti ekstraksi fitur dan klasifikasi citra',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan metode computer vision untuk menyelesaikan permasalahan pengenalan objek atau citra digital',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu mengembangkan model computer vision untuk studi kasus seperti deteksi objek atau klasifikasi citra industri',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengevaluasi akurasi model dan melakukan perbaikan berbasis hasil pengujian',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu membangun model computer vision untuk studi kasus riil (misal: deteksi objek, klasifikasi citra)',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu melakukan evaluasi performa model dan menyusun dokumentasi teknis untuk kebutuhan implementasi industri',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL08',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL10',
      4 => 'PSSI_CPL03',
      5 => 'PSSI_CPL08',
    ),
  ),
  6 => 
  array (
    'kode' => 'KFU1306',
    'nama' => 'Configuration Management',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Configuration Management secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Configuration Management sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Configuration Management.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Configuration Management.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mampu menerapkan version control, CI/CD, dan deployment dalam proyek pengembangan perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mampu mengelola dokumentasi konfigurasi dan kolaborasi tim menggunakan tools industri',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mampu menerapkan version control, CI/CD, dan deployment dalam proyek pengembangan perangkat lunak',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL09',
        'deskripsi' => 'Mampu mengelola dokumentasi konfigurasi dan kolaborasi tim menggunakan tools industri',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu menerapkan praktik manajemen konfigurasi menggunakan tools version control dan CI/CD dalam proyek tim',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL09',
        'deskripsi' => 'Mampu menyusun prosedur deployment dan dokumentasi teknis sesuai standar kerja industri',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL05',
      3 => 'PSTI_CPL09',
      4 => 'PSSI_CPL05',
      5 => 'PSSI_CPL09',
    ),
  ),
  7 => 
  array (
    'kode' => 'KFU1104',
    'nama' => 'Data Science',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Data Science secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Data Science sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Data Science.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Data Science.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan proses data science mulai dari pengumpulan data hingga evaluasi model',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL08',
        'deskripsi' => 'Mahasiswa mampu membangun model analisis data menggunakan teknik data science untuk menghasilkan informasi yang bermanfaat',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu menganalisis permasalahan bisnis melalui teknik analitik dan eksplorasi data',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan teknik-teknik analitik dan visualisasi data untuk menyelesaikan permasalahan data',
      ),
      4 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu melakukan deployment dan penarikan insight dari hasil analisis penyelesaian permasalahan bisnis',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mahasiswa mampu menganalisis permasalahan bisnis melalui teknik analitik dan eksplorasi data',
      ),
      6 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan teknik-teknik analitik dan visualisasi data untuk menyelesaikan permasalahan data',
      ),
      7 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mahasiswa mampu melakukan deployment dan penarikan insight dari hasil analisis penyelesaian permasalahan bisnis',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL05',
      1 => 'PSIF_CPL08',
      2 => 'PSIF_CPL10',
      3 => 'PSTI_CPL03',
      4 => 'PSSI_CPL02',
      5 => 'PSSI_CPL05',
    ),
  ),
  8 => 
  array (
    'kode' => 'KFU1103',
    'nama' => 'Data Visualitation',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Data Visualitation secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Data Visualitation sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Data Visualitation.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Data Visualitation.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan prinsip visualisasi data dan teknik penyajian informasi secara visual',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL08',
        'deskripsi' => 'Mahasiswa mampu membuat visualisasi data interaktif menggunakan perangkat lunak atau bahasa pemrograman',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mampu menyajikan data dalam bentuk visual yang informatif dan komunikatif',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menggunakan tools visualisasi untuk mendukung analisis data',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu menganalisis kebutuhan informasi organisasi dan memilih teknik visualisasi data yang tepat untuk mendukung pengambilan keputusan',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu merancang dan mengimplementasikan dashboard atau visualisasi interaktif menggunakan tools yang relevan untuk menyajikan data secara informatif dan komunikatif',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL05',
      1 => 'PSIF_CPL08',
      2 => 'PSIF_CPL10',
      3 => 'PSTI_CPL02',
      4 => 'PSTI_CPL03',
      5 => 'PSSI_CPL03',
      6 => 'PSSI_CPL02',
    ),
  ),
  9 => 
  array (
    'kode' => 'KFU1012',
    'nama' => 'Data Warehouse',
    'sks' => 2,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Data Warehouse secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Data Warehouse sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Data Warehouse.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Data Warehouse.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep data warehouse dan arsitektur penyimpanan data terintegrasi',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu merancang skema data warehouse sederhana untuk mendukung analisis data',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL07',
        'deskripsi' => 'Mampu merancang arsitektur data warehouse',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL08',
        'deskripsi' => 'Mampu melakukan proses ETL untuk integrasi dan konsolidasi data',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL04',
        'deskripsi' => 'Mampu menganalisis kebutuhan data organisasi dan merancang arsitektur data warehouse (termasuk skema dimensional dan proses ETL)',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mampu mengimplementasikan dan mengevaluasi data warehouse untuk mendukung sistem pendukung keputusan dan analitik bisnis',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
      1 => 'PSIF_CPL05',
      2 => 'PSTI_CPL07',
      3 => 'PSTI_CPL08',
      4 => 'PSSI_CPL04',
      5 => 'PSSI_CPL07',
    ),
  ),
  10 => 
  array (
    'kode' => 'KFU1204',
    'nama' => 'Deep Learning',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Deep Learning secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Deep Learning sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Deep Learning.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Deep Learning.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep jaringan saraf dalam dan arsitektur deep learning',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL08',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan model deep learning untuk menyelesaikan permasalahan klasifikasi atau prediksi',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menjelaskan konsep jaringan saraf tiruan dan deep learning',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan model deep learning untuk klasifikasi atau prediksi',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => '(Turunan CPL-05) Mahasiswa mampu menjelaskan konsep dasar neural network dan Deep Learning serta mengimplementasikan model Deep Learning menggunakan bahasa pemrograman yang sesuai',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => '(Turunan CPL-05)
Mahasiswa mampu membangun dan melakukan pelatihan (training) model Deep Learning menggunakan dataset terstruktur maupun tidak terstruktur',
      ),
      6 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => '(Turunan CPL-07)
Mahasiswa mampu menganalisis performa model Deep Learning menggunakan metrik evaluasi yang tepat',
      ),
      7 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => '(Turunan CPL-07)
Mahasiswa mampu merancang solusi berbasis Deep Learning untuk mendukung pengambilan keputusan organisasi, khususnya pada studi kasus agroindustri',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL05',
      1 => 'PSIF_CPL08',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL10',
      4 => 'PSSI_CPL05',
      5 => 'PSSI_CPL07',
    ),
  ),
  11 => 
  array (
    'kode' => 'KFU1405',
    'nama' => 'Design Pattern',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Design Pattern secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Design Pattern sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Design Pattern.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Design Pattern.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dan jenis design pattern dalam pengembangan perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu menerapkan design pattern dalam perancangan perangkat lunak',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL04',
        'deskripsi' => 'Mampu menerapkan design pattern dalam pengembangan perangkat lunak',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengevaluasi pemilihan pola desain sesuai kebutuhan sistem',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL04',
        'deskripsi' => 'Mampu menganalisis kebutuhan sistem informasi dan menentukan design pattern yang tepat untuk mendukung arsitektur perangkat lunak yang efektif dan selaras dengan kebutuhan bisnis',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan design pattern dalam pengembangan aplikasi sistem informasi untuk meningkatkan maintainability, scalability, dan kualitas perangkat lunak',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL07',
      2 => 'PSIF_CPL09',
      3 => 'PSTI_CPL04',
      4 => 'PSTI_CPL10',
      5 => 'PSSI_CPL04',
      6 => 'PSSI_CPL10',
    ),
  ),
  12 => 
  array (
    'kode' => 'KFU1403',
    'nama' => 'Ekonomi Rekayasa Perangkat Lunak',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Ekonomi Rekayasa Perangkat Lunak secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Ekonomi Rekayasa Perangkat Lunak sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Ekonomi Rekayasa Perangkat Lunak.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Ekonomi Rekayasa Perangkat Lunak.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep analisis biaya dan manfaat dalam proyek perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu melakukan estimasi biaya dan sumber daya dalam pengembangan perangkat lunak',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mampu menganalisis kelayakan ekonomi proyek perangkat lunak menggunakan metode evaluasi finansial',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL04',
        'deskripsi' => 'Mampu mengevaluasi keputusan investasi dan pengembangan perangkat lunak berdasarkan aspek biaya, manfaat, dan risiko',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL04',
        'deskripsi' => 'Mampu menganalisis kelayakan finansial proyek perangkat lunak menggunakan metode ekonomi teknik (NPV, IRR, Payback Period, dan analisis biaya-manfaat)',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu mengevaluasi alternatif solusi pengembangan perangkat lunak berdasarkan aspek biaya, risiko, dan nilai bisnis untuk mendukung pengambilan keputusan organisasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
      1 => 'PSTI_CPL02',
      2 => 'PSTI_CPL04',
      3 => 'PSSI_CPL04',
      4 => 'PSSI_CPL02',
    ),
  ),
  13 => 
  array (
    'kode' => 'KFU1402',
    'nama' => 'Evolusi Perangkat Lunak',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Evolusi Perangkat Lunak secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Evolusi Perangkat Lunak sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Evolusi Perangkat Lunak.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Evolusi Perangkat Lunak.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep evolusi dan pemeliharaan perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu merencanakan perubahan perangkat lunak secara sistematis',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL04',
        'deskripsi' => 'Mampu menjelaskan konsep dan faktor yang mempengaruhi evolusi perangkat lunak dalam siklus hidup sistem',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu menerapkan teknik pemeliharaan dan refactoring untuk meningkatkan kualitas perangkat lunak',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL04',
        'deskripsi' => 'Mampu menganalisis perubahan kebutuhan dan dampaknya terhadap arsitektur serta kualitas perangkat lunak dalam siklus hidup sistem informasi',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu merencanakan dan mengelola proses evolusi perangkat lunak (refactoring, reengineering, maintenance, dan versioning) untuk menjaga keberlanjutan sistem informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL09',
      1 => 'PSTI_CPL04',
      2 => 'PSTI_CPL10',
      3 => 'PSSI_CPL04',
      4 => 'PSSI_CPL05',
    ),
  ),
  14 => 
  array (
    'kode' => 'KFU1508',
    'nama' => 'Forensik Digital',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Forensik Digital secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Forensik Digital sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Forensik Digital.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Forensik Digital.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dasar forensik digital dan proses investigasi digital',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu melakukan analisis bukti digital menggunakan perangkat forensik',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mampu menjelaskan konsep, prinsip, dan tahapan forensik digital serta kerangka kerja investigasi digital sesuai standar',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL07',
        'deskripsi' => 'Mampu melakukan proses investigasi forensik digital secara sistematis menggunakan tools forensik untuk mengidentifikasi, mengamankan, menganalisis, dan mendokumentasikan bukti digital secara valid dan dapat dipertanggungjawabkan',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu menjelaskan konsep, ruang lingkup, regulasi, serta prosedur forensik digital dalam konteks sistem informasi dan tata kelola TI organisasi',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL09',
        'deskripsi' => 'Mampu melakukan proses identifikasi, pengumpulan, analisis, dan pelaporan bukti digital pada kasus insiden sistem informasi secara sistematis dan sesuai prinsip legalitas serta etika profesi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSTI_CPL05',
      2 => 'PSTI_CPL07',
      3 => 'PSSI_CPL03',
      4 => 'PSSI_CPL09',
    ),
  ),
  15 => 
  array (
    'kode' => 'KFU1026',
    'nama' => 'Game Theory',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Game Theory secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Game Theory sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Game Theory.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Game Theory.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep teori permainan dan strategi keputusan',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu memodelkan permasalahan pengambilan keputusan menggunakan teori permainan',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menganalisis dan memodelkan permasalahan strategis dalam bidang Teknologi Informasi menggunakan konsep dasar Game Theory',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mampu mengimplementasikan dan mengevaluasi model Game Theory untuk pengambilan keputusan pada sistem teknologi informasi',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dan model dasar Game Theory serta menerapkannya dalam analisis pengambilan keputusan pada sistem informasi dan bisnis digital',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mahasiswa mampu menganalisis dan mengevaluasi strategi optimal menggunakan pendekatan Game Theory untuk mendukung perancangan solusi berbasis sistem informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL05',
      1 => 'PSTI_CPL03',
      2 => 'PSTI_CPL05',
      3 => 'PSSI_CPL02',
      4 => 'PSSI_CPL08',
    ),
  ),
  16 => 
  array (
    'kode' => 'KFU1102',
    'nama' => 'Information Retrieval',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Information Retrieval secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Information Retrieval sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Information Retrieval.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Information Retrieval.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep sistem temu kembali informasi',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan metode pencarian informasi pada dataset teks',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu memahami dan menjelaskan konsep, model, serta teknik dasar information retrieval dalam proses pencarian dan pengelolaan informasi digital',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mahasiswa mampu merancang dan mengimplementasikan sistem information retrieval sederhana serta mengevaluasi kinerjanya menggunakan metrik evaluasi yang relevan',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mampu menjelaskan konsep, model, dan teknik dasar Information Retrieval dalam pengelolaan dan pencarian informasi berbasis teks maupun dokumen digital',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu merancang dan mengimplementasikan sistem Information Retrieval sederhana serta mengevaluasi kinerjanya menggunakan metrik evaluasi yang sesuai',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL07',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL10',
      4 => 'PSSI_CPL07',
      5 => 'PSSI_CPL03',
    ),
  ),
  17 => 
  array (
    'kode' => 'KFU1504',
    'nama' => 'Jaringan Multimedia',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Jaringan Multimedia secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Jaringan Multimedia sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Jaringan Multimedia.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Jaringan Multimedia.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep transmisi data multimedia dalam jaringan komputer',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu merancang sistem distribusi multimedia berbasis jaringan',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menganalisis dan merancang sistem jaringan multimedia yang mendukung layanan audio, video, dan data berbasis IP sesuai kebutuhan performa dan kualitas layanan',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL09',
        'deskripsi' => 'Mampu mengimplementasikan dan mengevaluasi protokol serta teknologi jaringan multimedia untuk memastikan transmisi data multimedia yang efisien, andal, dan aman',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL10',
        'deskripsi' => 'Mampu menganalisis kebutuhan dan merancang solusi jaringan multimedia untuk mendukung proses bisnis dan layanan digital organisasi',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu mengevaluasi kinerja dan kualitas layanan (QoS) jaringan multimedia dalam konteks pengelolaan sistem informasi organisasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL09',
      4 => 'PSSI_CPL10',
      5 => 'PSSI_CPL05',
    ),
  ),
  18 => 
  array (
    'kode' => 'KFU1510',
    'nama' => 'Jaringan Nirkabel',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Jaringan Nirkabel secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Jaringan Nirkabel sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Jaringan Nirkabel.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Jaringan Nirkabel.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep komunikasi jaringan nirkabel dan protokolnya',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu merancang jaringan nirkabel sederhana sesuai kebutuhan sistem',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL09',
        'deskripsi' => 'Mahasiswa mampu menganalisis konsep, arsitektur, dan protokol pada jaringan nirkabel',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL08',
        'deskripsi' => 'Mahasiswa mampu merancang dan mengimplementasikan jaringan nirkabel sesuai kebutuhan teknis dan keamanan sistem',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL10',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dan penerapan jaringan nirkabel dalam mendukung kebutuhan organisasi',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mahasiswa mampu mengevaluasi dan merekomendasikan solusi jaringan nirkabel yang efektif dan efisien bagi sistem informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL09',
      3 => 'PSTI_CPL08',
      4 => 'PSSI_CPL10',
      5 => 'PSSI_CPL02',
    ),
  ),
  19 => 
  array (
    'kode' => 'KFU1101',
    'nama' => 'Jaringan Syaraf Tiruan',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Jaringan Syaraf Tiruan secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Jaringan Syaraf Tiruan sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Jaringan Syaraf Tiruan.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Jaringan Syaraf Tiruan.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL08',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep jaringan syaraf tiruan dan algoritma pembelajaran',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu membangun model jaringan syaraf tiruan untuk klasifikasi atau prediksi data',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep, arsitektur, dan algoritma dasar Jaringan Syaraf Tiruan',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan dan mengevaluasi model Jaringan Syaraf Tiruan untuk menyelesaikan permasalahan komputasi',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mahasiswa mampu memahami konsep dan penerapan Jaringan Syaraf Tiruan dalam konteks sistem informasi dan bisnis',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mahasiswa mampu merancang dan mengaplikasikan model Jaringan Syaraf Tiruan untuk mendukung pengambilan keputusan berbasis data',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL08',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL02',
      3 => 'PSTI_CPL03',
      4 => 'PSSI_CPL03',
      5 => 'PSSI_CPL05',
    ),
  ),
  20 => 
  array (
    'kode' => 'KFU1506',
    'nama' => 'Kompresi Data',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Kompresi Data secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Kompresi Data sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Kompresi Data.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Kompresi Data.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep kompresi data lossless dan lossy',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan algoritma kompresi data sederhana',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mampu menjelaskan konsep, prinsip, dan teknik dasar kompresi data (lossless dan lossy) serta penerapannya pada berbagai jenis media digital',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL09',
        'deskripsi' => 'Mampu menganalisis dan mengimplementasikan algoritma kompresi data untuk meningkatkan efisiensi penyimpanan dan transmisi data',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu memahami konsep dan manfaat kompresi data dalam pengelolaan sistem informasi dan infrastruktur penyimpanan',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu mengevaluasi pemilihan metode kompresi data yang sesuai dengan kebutuhan efisiensi dan kinerja sistem informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL07',
      1 => 'PSTI_CPL02',
      2 => 'PSTI_CPL09',
      3 => 'PSSI_CPL02',
      4 => 'PSSI_CPL05',
    ),
  ),
  21 => 
  array (
    'kode' => 'KFU1507',
    'nama' => 'Komputasi Awan',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Komputasi Awan secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Komputasi Awan sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Komputasi Awan.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Komputasi Awan.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep cloud computing dan model layanan cloud',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan layanan komputasi awan sederhana',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menganalisis dan merancang arsitektur infrastruktur awan (IaaS, PaaS, SaaS) yang efisien, berwawasan lingkungan (green computing), dan sesuai dengan kebutuhan operasional organisasi agroindustri',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL09',
        'deskripsi' => 'Mampu mengorganisasikan dan mengonfigurasi layanan jaringan virtual, penyimpanan data, dan komputasi berbasis cloud untuk mendukung ketersediaan data pada ekosistem pertanian industrial',
      ),
      4 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan aplikasi berbasis platform cloud menggunakan bahasa pemrograman dan algoritma yang relevan melalui mekanisme deployment (seperti containerization atau serverless) untuk solusi cerdas di bidang TIK',
      ),
      5 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu mengintegrasikan aspek keamanan sistem dan manajemen risiko data pada infrastruktur awan untuk menjamin integritas dan privasi informasi dalam rantai pasok agroindustri',
      ),
      6 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu merancang arsitektur infrastruktur awan (IaaS, PaaS, SaaS) yang efisien, aman, dan berwawasan lingkungan untuk mendukung kebutuhan operasional organisasi bisnis atau agroindustri',
      ),
      7 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mampu mengintegrasikan teknik analitik data dan manajemen sumber daya berbasis cloud untuk mendukung pengambilan keputusan strategis berbasis data',
      ),
      8 => 
      array (
        'cpl' => 'PSSI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan layanan cloud computing untuk membangun solusi bisnis atau aplikasi inovatif dalam ekosistem pertanian industrial',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL09',
      4 => 'PSTI_CPL10',
      5 => 'PSSI_CPL03',
      6 => 'PSSI_CPL07',
      7 => 'PSSI_CPL10',
    ),
  ),
  22 => 
  array (
    'kode' => 'KFU1502',
    'nama' => 'Komputasi Bergerak',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Komputasi Bergerak secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Komputasi Bergerak sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Komputasi Bergerak.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Komputasi Bergerak.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep sistem komputasi bergerak dan arsitekturnya',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu mengembangkan aplikasi sederhana pada platform mobile',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu merancang arsitektur aplikasi mobile yang mengintegrasikan data dari sensor lingkungan/lapangan dengan sistem manajemen pusat untuk mendukung efisiensi agroindustri',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL06',
        'deskripsi' => 'Mampu memilih dan menerapkan model pengembangan aplikasi mobile (native/hybrid) beserta mekanisme keamanan sistem yang sesuai dengan karakteristik perangkat mobile dan kebutuhan lingkungan pertanian industrial',
      ),
      4 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan algoritma dan pemrograman pada platform mobile (misal: Android/iOS) untuk membangun aplikasi fungsional yang mendukung aktivitas pengguna di sektor agroindustri',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu menerapkan teknik pemrograman berorientasi objek untuk mengolah konsep data dan informasi pada perangkat bergerak dalam pengembangan sistem informasi organisasi agroindustri',
      ),
      6 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu merancang dan membuat perangkat lunak aplikasi bergerak (mobile app) yang fungsional sesuai kebutuhan tata kelola ekosistem pertanian industrial',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL07',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL06',
      4 => 'PSTI_CPL10',
      5 => 'PSSI_CPL05',
      6 => 'PSSI_CPL08',
    ),
  ),
  23 => 
  array (
    'kode' => 'KFU1505',
    'nama' => 'Komputasi Grid dan Pararel',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Komputasi Grid dan Pararel secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Komputasi Grid dan Pararel sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Komputasi Grid dan Pararel.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Komputasi Grid dan Pararel.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep komputasi paralel dan grid computing',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan pemrosesan paralel sederhana',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menganalisis dan merancang arsitektur sistem komputasi paralel dan grid untuk pemrosesan data besar (big data) dalam simulasi pertanian industrial atau analisis data lingkungan',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL07',
        'deskripsi' => 'Mampu mengorganisasikan dan mengelola sumber daya komputer (compute nodes, storage, jaringan) yang tersebar secara geografis untuk efisiensi operasional organisasi',
      ),
      4 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan algoritma paralel menggunakan bahasa pemrograman tertentu (seperti MPI atau OpenMP) untuk memecah tugas kompleks menjadi sub-tugas yang berjalan serentak',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu menganalisis dan merancang arsitektur sistem komputasi paralel dan grid untuk pemrosesan data besar (big data) dalam simulasi pertanian industrial atau analisis data lingkungan',
      ),
      6 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu mengorganisasikan dan mengelola sumber daya komputer (compute nodes, storage, jaringan) yang tersebar secara geografis untuk efisiensi operasional organisasi',
      ),
      7 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mampu mengimplementasikan algoritma paralel menggunakan teknik pemrograman tertentu (seperti MPI atau OpenMP) untuk memecah tugas kompleks menjadi sub-tugas yang berjalan serentak',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL05',
      1 => 'PSIF_CPL07',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL07',
      4 => 'PSTI_CPL10',
      5 => 'PSSI_CPL03',
      6 => 'PSSI_CPL05',
      7 => 'PSSI_CPL07',
    ),
  ),
  24 => 
  array (
    'kode' => 'KFU1509',
    'nama' => 'Komputasi Pervasif dan Jaringan Sensor',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Komputasi Pervasif dan Jaringan Sensor secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Komputasi Pervasif dan Jaringan Sensor sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Komputasi Pervasif dan Jaringan Sensor.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Komputasi Pervasif dan Jaringan Sensor.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep komputasi pervasif dan jaringan sensor',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu merancang sistem jaringan sensor sederhana',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu merancang arsitektur sistem pervasive yang mengintegrasikan jaringan sensor nirkabel (Wireless Sensor Networks) untuk pengumpulan data lingkungan secara real-time di bidang pertanian industrial',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mampu mengorganisasikan dan mengelola interaksi antarperangkat (IoT) dalam lingkungan cerdas (smart environment) untuk mendukung efisiensi operasional agroindustri',
      ),
      4 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan algoritma konteks (context-aware algorithms) dan pemrograman pada perangkat tertanam (embedded systems) untuk aplikasi pervasive yang hemat energi dan berwawasan lingkungan',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu menganalisis permasalahan pertanian industrial secara logis, kritis, dan inovatif untuk merancang solusi berbasis komputasi pervasif (IoT) yang etis dan berkelanjutan',
      ),
      6 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu menerapkan konsep data dan teknik pemrograman pada perangkat tertanam (embedded systems) untuk membuat perangkat lunak yang mendukung tata kelola ekosistem agroindustri',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL05',
      4 => 'PSTI_CPL10',
      5 => 'PSSI_CPL02',
      6 => 'PSSI_CPL05',
      7 => 'PSSI_CPL08',
    ),
  ),
  25 => 
  array (
    'kode' => 'KFU1108',
    'nama' => 'Machine Learning',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Machine Learning secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Machine Learning sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Machine Learning.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Machine Learning.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL08',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dasar machine learning dan jenis algoritmanya',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu membangun model machine learning untuk analisis data',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menganalisis dan merancang model machine learning untuk memproses data pertanian industrial secara efisien, aman, dan berwawasan lingkungan guna mendukung pengambilan keputusan manajerial',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan algoritma machine learning menggunakan bahasa pemrograman (seperti Python) dalam bentuk program atau aplikasi yang berjalan pada platform komputasi tertentu untuk menyelesaikan masalah agroindustri',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu menganalisis dan merancang model machine learning untuk memecahakan permasalahan di bidang pertanian industrial atau bisnis yang berwawasan lingkungan',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mampu mengolah data menggunakan teknik pemrograman dan statistik untuk membangun algoritma machine learning yang akurat dalam mendukung pengambilan keputusan strategis',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL08',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL10',
      4 => 'PSSI_CPL03',
      5 => 'PSSI_CPL07',
    ),
  ),
  26 => 
  array (
    'kode' => 'KFU1404',
    'nama' => 'Manajemen Risiko TI',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Manajemen Risiko TI secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Manajemen Risiko TI sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Manajemen Risiko TI.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Manajemen Risiko TI.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep manajemen risiko teknologi informasi',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu melakukan analisis risiko pada sistem teknologi informasi',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menganalisis dan merancang kerangka kerja manajemen risiko TI yang mengintegrasikan aspek keamanan sistem dan keberlanjutan lingkungan pada infrastruktur TIK di sektor agroindustri',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mampu mendiagnosis kerentanan jaringan dan sistem informasi dalam ekosistem agroindustri, serta menyusun rencana mitigasi risiko untuk menjamin kelangsungan operasional bisnis',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu menganalisis dan merancang kerangka kerja manajemen risiko TI yang mengintegrasikan aspek keamanan sistem dan keberlanjutan lingkungan pada infrastruktur TIK di sektor agroindustri',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mampu menelaah pendekatan manajemen dan arsitektur organisasi untuk mengidentifikasi potensi risiko dalam penyusunan perangkat lunak dan sistem komputer pertanian industrial',
      ),
      6 => 
      array (
        'cpl' => 'PSSI_CPL11',
        'deskripsi' => 'Mampu mengelola proyek manajemen risiko, mendiagnosis kerentanan sistem informasi, serta menyusun rencana mitigasi untuk menjamin kelangsungan operasional bisnis',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
      1 => 'PSIF_CPL07',
      2 => 'PSIF_CPL09',
      3 => 'PSTI_CPL03',
      4 => 'PSTI_CPL11',
      5 => 'PSSI_CPL03',
      6 => 'PSSI_CPL06',
      7 => 'PSSI_CPL11',
    ),
  ),
  27 => 
  array (
    'kode' => 'KFU1201',
    'nama' => 'Pemodelan dan Simulasi',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pemodelan dan Simulasi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pemodelan dan Simulasi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pemodelan dan Simulasi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pemodelan dan Simulasi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep pemodelan sistem dan simulasi komputer',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu membuat model simulasi untuk menganalisis perilaku sistem',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mampu mengintegrasikan pengetahuan analisis data dan manajemen sistem untuk membangun model simulasi yang berwawasan lingkungan guna mengoptimalkan operasional agroindustri',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu mengimplementasikan model simulasi ke dalam bentuk program komputer dan aplikasi berbasis platform menggunakan algoritma dan bahasa pemrograman yang relevan',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu menganalisis permasalahan pertanian industrial berkelanjutan secara logis, kritis, dan inovatif dengan membangun model konseptual yang menerapkan pendekatan ilmiah',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu mengolah data, mengintegrasikan teknik pemrograman, dan menggunakan statistik untuk merancang simulasi guna mendukung pengambilan keputusan strategis yang berwawasan bisnis dan lingkungan',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL05',
      2 => 'PSTI_CPL02',
      3 => 'PSTI_CPL03',
      4 => 'PSTI_CPL10',
      5 => 'PSSI_CPL02',
      6 => 'PSSI_CPL03',
      7 => 'PSSI_CPL07',
    ),
  ),
  28 => 
  array (
    'kode' => 'KFU1501',
    'nama' => 'Pemrograman Jaringan',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pemrograman Jaringan secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pemrograman Jaringan sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pemrograman Jaringan.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pemrograman Jaringan.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep komunikasi jaringan berbasis socket',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu mengembangkan aplikasi jaringan sederhana',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL07',
        'deskripsi' => 'Mahasiswa mampu mengorganisasikan dan mengelola sumber daya jaringan komputer dalam pengembangan aplikasi berbasis komunikasi data',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mahasiswa mampu mendiagnosis permasalahan keamanan pada aplikasi jaringan serta menerapkan solusi pengamanan yang tepat',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mahasiswa mampu menerapkan konsep pemrograman jaringan dan protokol komunikasi data dalam membangun aplikasi berbasis jaringan',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan mekanisme client-server dan manajemen koneksi jaringan secara efektif dan sesuai prinsip tata kelola TI',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL07',
      2 => 'PSTI_CPL07',
      3 => 'PSTI_CPL11',
      4 => 'PSSI_CPL05',
    ),
  ),
  29 => 
  array (
    'kode' => 'KFU1203',
    'nama' => 'Pemrosesan Bahan Alami',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pemrosesan Bahan Alami secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pemrosesan Bahan Alami sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pemrosesan Bahan Alami.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pemrosesan Bahan Alami.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL08',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep pemrosesan bahan alami berbasis teknologi informatika',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu menerapkan metode komputasi untuk analisis bahan alami',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu mengintegrasikan teknik analisis data dan komputasi dalam pengolahan data bahan alami berbasis teknologi informasi',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL06',
        'deskripsi' => 'Mahasiswa mampu memilih model pengembangan aplikasi yang tepat untuk sistem pemrosesan data bahan alami',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mahasiswa mampu menerapkan teknik analisis data dan pendekatan komputasional dalam pemrosesan bahan alami untuk mendukung pengambilan keputusan berbasis data',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mahasiswa mampu mengolah dan menganalisis data hasil pemrosesan bahan alami menggunakan metode statistik atau komputasi yang relevan',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL08',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL06',
      4 => 'PSSI_CPL03',
      5 => 'PSSI_CPL07',
    ),
  ),
  30 => 
  array (
    'kode' => 'KFU1207',
    'nama' => 'Pengenalan Pola',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pengenalan Pola secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pengenalan Pola sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pengenalan Pola.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pengenalan Pola.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep ekstraksi fitur dan klasifikasi pola',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL08',
        'deskripsi' => 'Mahasiswa mampu membangun sistem pengenalan pola berbasis data',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu menerapkan teknik analisis data dan metode klasifikasi dalam penyelesaian permasalahan pengenalan pola',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan algoritma pengenalan pola ke dalam program atau aplikasi berbasis platform',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan dan mengintegrasikan konsep analisis data serta teknik klasifikasi dalam sistem pengenalan pola',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan algoritma pengenalan pola untuk mengolah dan menganalisis data dalam mendukung pengambilan keputusan',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL05',
      1 => 'PSIF_CPL08',
      2 => 'PSIF_CPL09',
      3 => 'PSTI_CPL03',
      4 => 'PSTI_CPL10',
      5 => 'PSSI_CPL03',
      6 => 'PSSI_CPL07',
    ),
  ),
  31 => 
  array (
    'kode' => 'KFU1105',
    'nama' => 'Pengolahan Citra Digital',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pengolahan Citra Digital secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pengolahan Citra Digital sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pengolahan Citra Digital.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pengolahan Citra Digital.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dasar pengolahan citra digital',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL08',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan metode pengolahan citra digital',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu menerapkan teknik pengolahan citra digital untuk ekstraksi fitur dan analisis visual berbasis komputasi',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan algoritma pengolahan citra digital dalam bentuk aplikasi berbasis teknologi platform',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mahasiswa mampu menerapkan teknik analisis citra digital untuk ekstraksi fitur dan peningkatan kualitas citra',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mahasiswa mampu mengembangkan program pengolahan citra digital menggunakan metode komputasi untuk mendukung analisis data visual',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL05',
      1 => 'PSIF_CPL08',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL10',
      4 => 'PSSI_CPL03',
      5 => 'PSSI_CPL07',
    ),
  ),
  32 => 
  array (
    'kode' => 'KFU1512',
    'nama' => 'Perancangan Keamanan Sistem dan Jaringan',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Perancangan Keamanan Sistem dan Jaringan secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Perancangan Keamanan Sistem dan Jaringan sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Perancangan Keamanan Sistem dan Jaringan.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Perancangan Keamanan Sistem dan Jaringan.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep keamanan sistem dan jaringan komputer',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu merancang mekanisme keamanan sistem jaringan',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mahasiswa mampu mendiagnosis kerentanan keamanan pada sistem dan jaringan komputer',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mahasiswa mampu mengorganisasikan penggunaan perangkat dan infrastruktur jaringan untuk mendukung penerapan sistem keamanan yang efektif',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mahasiswa mampu menganalisis risiko dan kebutuhan keamanan sistem dan jaringan secara logis, kritis, dan adaptif terhadap perkembangan teknologi',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mahasiswa mampu merancang arsitektur keamanan sistem dan jaringan organisasi sesuai prinsip manajemen risiko dan tata kelola TI',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL06',
      2 => 'PSTI_CPL11',
      3 => 'PSTI_CPL05',
      4 => 'PSSI_CPL03',
      5 => 'PSSI_CPL02',
      6 => 'PSSI_CPL10',
    ),
  ),
  33 => 
  array (
    'kode' => 'KFU1301',
    'nama' => 'Requirement Engineering',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Requirement Engineering secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Requirement Engineering sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Requirement Engineering.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Requirement Engineering.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan teknik elisitasi dan analisis kebutuhan perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menyusun dokumen spesifikasi kebutuhan perangkat lunak',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL06',
        'deskripsi' => 'Mahasiswa mampu untuk menggali kebutuhan perangkat lunak',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu untuk menganalisis kebutuhan perangkat lunak',
      ),
      4 => 
      array (
        'cpl' => 'PSTI_CPL06',
        'deskripsi' => 'Mahasiswa mampu memvalidasi kebutuhan yang telah didefinisikan dalam tahapan analisis',
      ),
      5 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu untuk mengelola kebutuhan perangkat lunak',
      ),
      6 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mahasiswa mampu untuk menggali kebutuhan perangkat lunak',
      ),
      7 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mahasiswa mampu untuk menganalisis kebutuhan perangkat lunak',
      ),
      8 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mahasiswa mampu memvalidasi kebutuhan yang telah didefinisikan dalam tahapan analisis',
      ),
      9 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mahasiswa mampu untuk mengelola kebutuhan perangkat lunak',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
      1 => 'PSIF_CPL06',
      2 => 'PSTI_CPL06',
      3 => 'PSTI_CPL03',
      4 => 'PSSI_CPL06',
      5 => 'PSSI_CPL08',
    ),
  ),
  34 => 
  array (
    'kode' => 'KFU1206',
    'nama' => 'Robotika',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Robotika secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Robotika sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Robotika.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Robotika.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep sistem robotika dan komponennya',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu mengembangkan program pengendali robot sederhana',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan algoritma dan bahasa pemrograman dalam pengembangan sistem robotika berbasis platform',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL06',
        'deskripsi' => 'Mahasiswa mampu memilih model perancangan dan pengembangan sistem robotika sesuai kebutuhan aplikasi',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL04',
        'deskripsi' => 'Mahasiswa mampu merancang sistem robotika sederhana dengan mempertimbangkan aspek manajemen sistem dan arsitektur perangkat keras maupun perangkat lunak',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mahasiswa mampu mengembangkan prototipe robotika yang inovatif yang dapat mendukung usaha khsusunya di bidang teknologi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL10',
      3 => 'PSTI_CPL06',
      4 => 'PSSI_CPL04',
      5 => 'PSSI_CPL06',
    ),
  ),
  35 => 
  array (
    'kode' => 'KFU1107',
    'nama' => 'Sistem Pakar',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Sistem Pakar secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Sistem Pakar sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Sistem Pakar.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Sistem Pakar.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep sistem pakar dan representasi pengetahuan',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu membangun sistem pakar sederhana',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menjelaskan konsep, metode representasi pengetahuan, dan mekanisme inferensi pada sistem pakar',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu merancang dan mengimplementasikan sistem pakar berbasis aturan atau metode kecerdasan buatan untuk menyelesaikan permasalahan nyata',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu menjelaskan konsep dasar sistem pakar, representasi pengetahuan, dan mekanisme inferensi dalam pengambilan keputusan',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu merancang dan mengimplementasikan sistem pakar berbasis aturan atau model tertentu untuk menyelesaikan permasalahan organisasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL10',
      4 => 'PSSI_CPL03',
      5 => 'PSSI_CPL08',
    ),
  ),
  36 => 
  array (
    'kode' => 'KFU1011',
    'nama' => 'Sistem Temu Kembali Informasi',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Sistem Temu Kembali Informasi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Sistem Temu Kembali Informasi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Sistem Temu Kembali Informasi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Sistem Temu Kembali Informasi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep pencarian informasi berbasis teks',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu mengembangkan sistem pencarian informasi sederhana',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menjelaskan konsep dan model sistem temu kembali informasi termasuk indexing dan retrieval',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan algoritma pencarian dan evaluasi kinerja sistem temu kembali informasi',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu menjelaskan konsep dasar sistem temu kembali informasi, indexing, dan evaluasi relevansi',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu merancang dan mengimplementasikan model pencarian informasi untuk kebutuhan organisasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL07',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL10',
      4 => 'PSSI_CPL03',
      5 => 'PSSI_CPL08',
    ),
  ),
  37 => 
  array (
    'kode' => 'KFU1503',
    'nama' => 'Sistem Terdistribusi',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Sistem Terdistribusi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Sistem Terdistribusi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Sistem Terdistribusi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Sistem Terdistribusi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep arsitektur sistem terdistribusi',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan layanan sistem terdistribusi sederhana',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mampu menjelaskan arsitektur dan mekanisme komunikasi pada sistem terdistribusi',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL07',
        'deskripsi' => 'Mampu mengimplementasikan dan mengevaluasi sistem terdistribusi berbasis client-server atau cloud',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu menjelaskan arsitektur dan konsep komunikasi pada sistem terdistribusi',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL10',
        'deskripsi' => 'Mampu merancang solusi sistem terdistribusi yang andal dan skalabel untuk kebutuhan sistem informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL06',
      2 => 'PSTI_CPL05',
      3 => 'PSTI_CPL07',
      4 => 'PSSI_CPL05',
      5 => 'PSSI_CPL10',
    ),
  ),
  38 => 
  array (
    'kode' => 'KFU1303',
    'nama' => 'Software Construction',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Software Construction secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Software Construction sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Software Construction.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Software Construction.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan prinsip konstruksi perangkat lunak yang baik',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu mengembangkan perangkat lunak sesuai standar rekayasa perangkat lunak',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan perangkat lunak sesuai spesifikasi kebutuhan',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL04',
        'deskripsi' => 'Mampu menerapkan praktik coding standard, version control, dan integrasi perangkat lunak',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mampu mengimplementasikan konstruksi perangkat lunak sesuai spesifikasi dan standar pengembangan',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu menerapkan praktik pengkodean, debugging, dan integrasi sistem secara efektif',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL07',
      2 => 'PSTI_CPL10',
      3 => 'PSTI_CPL04',
      4 => 'PSSI_CPL06',
      5 => 'PSSI_CPL08',
    ),
  ),
  39 => 
  array (
    'kode' => 'KFU1302',
    'nama' => 'Software Design',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Software Design secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Software Design sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Software Design.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Software Design.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan prinsip dan metode perancangan perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu merancang struktur perangkat lunak menggunakan model desain yang sesuai',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu merancang desain perangkat lunak menggunakan prinsip dan notasi desain yang tepat',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL04',
        'deskripsi' => 'Mampu mengevaluasi alternatif desain berdasarkan kebutuhan sistem dan kualitas perangkat lunak',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mampu merancang desain perangkat lunak menggunakan pendekatan terstruktur maupun berorientasi objek',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu menghasilkan dokumentasi desain sistem yang sesuai kebutuhan organisasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL07',
      2 => 'PSTI_CPL10',
      3 => 'PSTI_CPL04',
      4 => 'PSSI_CPL06',
      5 => 'PSSI_CPL08',
    ),
  ),
  40 => 
  array (
    'kode' => 'KFU1305',
    'nama' => 'Software Maintenance',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Software Maintenance secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Software Maintenance sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Software Maintenance.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Software Maintenance.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep pemeliharaan perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu melakukan modifikasi perangkat lunak secara terstruktur',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL04',
        'deskripsi' => 'Mampu menganalisis kebutuhan perubahan dan dampaknya terhadap sistem perangkat lunak',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL09',
        'deskripsi' => 'Mampu melakukan perbaikan, refactoring, dan peningkatan kualitas perangkat lunak secara terstruktur',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mampu menjelaskan konsep dan jenis-jenis pemeliharaan perangkat lunak',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL09',
        'deskripsi' => 'Mampu mengelola perubahan dan peningkatan sistem secara terstruktur dan terdokumentasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL07',
      2 => 'PSTI_CPL04',
      3 => 'PSTI_CPL09',
      4 => 'PSSI_CPL06',
      5 => 'PSSI_CPL09',
    ),
  ),
  41 => 
  array (
    'kode' => 'KFU1307',
    'nama' => 'Software Quality',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Software Quality secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Software Quality sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Software Quality.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Software Quality.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep kualitas perangkat lunak dan standar kualitas',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu mengevaluasi kualitas perangkat lunak menggunakan metrik yang sesuai',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mampu menjelaskan konsep kualitas perangkat lunak, standar, dan metrik pengukuran',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL04',
        'deskripsi' => 'Mampu menerapkan teknik pengujian dan evaluasi kualitas perangkat lunak untuk memastikan kesesuaian dengan kebutuhan pengguna',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu menjelaskan prinsip dan standar kualitas perangkat lunak',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL09',
        'deskripsi' => 'Mampu menerapkan teknik pengujian dan evaluasi kualitas sistem informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL07',
      2 => 'PSTI_CPL11',
      3 => 'PSTI_CPL04',
      4 => 'PSSI_CPL05',
      5 => 'PSSI_CPL09',
    ),
  ),
  42 => 
  array (
    'kode' => 'KFU1304',
    'nama' => 'Software Testing',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Software Testing secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Software Testing sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Software Testing.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Software Testing.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dan metode pengujian perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu menyusun dan menjalankan pengujian perangkat lunak',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mampu merancang dan menerapkan teknik pengujian perangkat lunak (unit, integrasi, sistem)',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mampu mengevaluasi hasil pengujian untuk meningkatkan kualitas perangkat lunak',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mampu menjelaskan konsep, teknik, dan strategi pengujian perangkat lunak',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu merancang dan mengimplementasikan skenario pengujian untuk menjamin kualitas sistem informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL07',
      2 => 'PSTI_CPL02',
      3 => 'PSTI_CPL11',
      4 => 'PSSI_CPL06',
      5 => 'PSSI_CPL08',
    ),
  ),
  43 => 
  array (
    'kode' => 'KFU1406',
    'nama' => 'Software Validation',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Software Validation secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Software Validation sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Software Validation.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Software Validation.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep validasi perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu melakukan validasi perangkat lunak sesuai kebutuhan pengguna',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menerapkan metode validasi untuk memastikan perangkat lunak memenuhi kebutuhan pengguna',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengevaluasi kesesuaian sistem terhadap standar dan spesifikasi yang ditetapkan',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu menjelaskan prinsip validasi perangkat lunak sesuai kebutuhan pengguna',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL09',
        'deskripsi' => 'Mampu melakukan evaluasi kesesuaian sistem terhadap requirement dan standar organisasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL07',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL10',
      4 => 'PSSI_CPL08',
      5 => 'PSSI_CPL09',
    ),
  ),
  44 => 
  array (
    'kode' => 'KFU1407',
    'nama' => 'Software Verification',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Software Verification secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Software Verification sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Software Verification.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Software Verification.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep verifikasi perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu melakukan verifikasi perangkat lunak sesuai spesifikasi',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menerapkan teknik verifikasi untuk memastikan kesesuaian perangkat lunak dengan spesifikasi teknis',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu menganalisis temuan verifikasi untuk menjamin kualitas dan keamanan sistem',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mampu menjelaskan konsep verifikasi perangkat lunak dalam siklus hidup pengembangan',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL09',
        'deskripsi' => 'Mampu menerapkan teknik review, walkthrough, dan audit teknis untuk memastikan kesesuaian spesifikasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL07',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL10',
      4 => 'PSSI_CPL06',
      5 => 'PSSI_CPL09',
    ),
  ),
  45 => 
  array (
    'kode' => 'KFU1208',
    'nama' => 'Speech Processing',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Speech Processing secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Speech Processing sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Speech Processing.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Speech Processing.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dasar pengolahan sinyal suara',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan metode pengolahan sinyal suara sederhana',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menjelaskan konsep dasar pemrosesan sinyal suara dan ekstraksi fitur',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan algoritma pengenalan atau klasifikasi suara berbasis machine learning',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu menjelaskan konsep dasar pemrosesan sinyal suara',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu mengimplementasikan metode pengolahan suara untuk kebutuhan sistem cerdas berbasis data',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL05',
      2 => 'PSIF_CPL08',
      3 => 'PSTI_CPL03',
      4 => 'PSTI_CPL10',
      5 => 'PSSI_CPL03',
      6 => 'PSSI_CPL08',
    ),
  ),
  46 => 
  array (
    'kode' => 'KFU1023',
    'nama' => 'Swarm Intellegence',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Swarm Intellegence secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Swarm Intellegence sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Swarm Intellegence.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Swarm Intellegence.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep swarm intelligence dan algoritmanya',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL08',
        'deskripsi' => 'Mahasiswa mampu menerapkan algoritma swarm intelligence untuk optimasi',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mampu menjelaskan konsep dan mekanisme algoritma swarm intelligence',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan algoritma swarm untuk optimasi permasalahan kompleks',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu menjelaskan konsep swarm intelligence dan algoritma berbasis populasi',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mampu mengimplementasikan algoritma swarm untuk optimasi permasalahan sistem informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL05',
      1 => 'PSIF_CPL08',
      2 => 'PSTI_CPL03',
      3 => 'PSTI_CPL10',
      4 => 'PSSI_CPL03',
      5 => 'PSSI_CPL07',
    ),
  ),
  47 => 
  array (
    'kode' => 'KFU1511',
    'nama' => 'Teknologi Antar Jaringan',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Teknologi Antar Jaringan secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Teknologi Antar Jaringan sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Teknologi Antar Jaringan.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Teknologi Antar Jaringan.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep interkoneksi jaringan komputer',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu merancang konfigurasi jaringan antar sistem',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mampu menganalisis konsep interkoneksi jaringan dan protokol komunikasi',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL09',
        'deskripsi' => 'Mampu mengimplementasikan konfigurasi jaringan antar sistem secara aman dan efisien',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu menjelaskan arsitektur dan protokol antar jaringan',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL10',
        'deskripsi' => 'Mampu merancang solusi interkoneksi jaringan untuk mendukung kebutuhan organisasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL05',
      3 => 'PSTI_CPL09',
      4 => 'PSSI_CPL05',
      5 => 'PSSI_CPL10',
    ),
  ),
  48 => 
  array (
    'kode' => 'KFU1106',
    'nama' => 'Ubiquitous Computing',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Ubiquitous Computing secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Ubiquitous Computing sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Ubiquitous Computing.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Ubiquitous Computing.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep ubiquitous computing dan perangkat pendukungnya',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu merancang sistem komputasi berbasis perangkat terhubung',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL06',
        'deskripsi' => 'Mampu menjelaskan konsep dan arsitektur sistem ubiquitous computing',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mampu merancang solusi komputasi terintegrasi berbasis sensor dan perangkat mobile',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu menjelaskan konsep pervasive dan ubiquitous computing dalam konteks sistem informasi',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL09',
        'deskripsi' => 'Mampu merancang solusi berbasis komputasi tersebar untuk meningkatkan efisiensi layanan digital',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL06',
      3 => 'PSTI_CPL02',
      4 => 'PSSI_CPL05',
      5 => 'PSSI_CPL09',
    ),
  ),
  49 => 
  array (
    'kode' => 'KIU1025',
    'nama' => 'Aspek Sosial dan Ekonomi Perangkat Lunak',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Aspek Sosial dan Ekonomi Perangkat Lunak secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Aspek Sosial dan Ekonomi Perangkat Lunak sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Aspek Sosial dan Ekonomi Perangkat Lunak.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Aspek Sosial dan Ekonomi Perangkat Lunak.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan dampak sosial dan ekonomi perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menganalisis peran perangkat lunak dalam masyarakat',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
    ),
  ),
  50 => 
  array (
    'kode' => 'KIU1042',
    'nama' => 'Audit TI',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Audit TI secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Audit TI sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Audit TI.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Audit TI.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep audit teknologi informasi',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu melakukan evaluasi kontrol sistem informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
    ),
  ),
  51 => 
  array (
    'kode' => 'KIU1026',
    'nama' => 'Dasar Audit Keuangan',
    'sks' => 2,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Dasar Audit Keuangan secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Dasar Audit Keuangan sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Dasar Audit Keuangan.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Dasar Audit Keuangan.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep audit keuangan dasar',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menginterpretasikan laporan audit sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
    ),
  ),
  52 => 
  array (
    'kode' => 'KIU1021',
    'nama' => 'Dasar-dasar Akuntansi',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Dasar-dasar Akuntansi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Dasar-dasar Akuntansi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Dasar-dasar Akuntansi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Dasar-dasar Akuntansi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dasar akuntansi',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menyusun laporan keuangan sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
    ),
  ),
  53 => 
  array (
    'kode' => 'KIU1023',
    'nama' => 'E-Business',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan E-Business secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait E-Business sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan E-Business.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada E-Business.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep bisnis digital',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL04',
        'deskripsi' => 'Mahasiswa mampu merancang model bisnis berbasis teknologi informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
      1 => 'PSIF_CPL04',
    ),
  ),
  54 => 
  array (
    'kode' => 'KIU1044',
    'nama' => 'E-Goverment',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan E-Goverment secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait E-Goverment sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan E-Goverment.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada E-Goverment.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dan arsitektur sistem e-government',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu merancang layanan e-government sederhana berbasis teknologi informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL09',
    ),
  ),
  55 => 
  array (
    'kode' => 'KIU1052',
    'nama' => 'Hard Skill TI',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Hard Skill TI secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Hard Skill TI sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Hard Skill TI.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Hard Skill TI.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu menggunakan perangkat teknologi informasi untuk menyelesaikan tugas teknis',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu menghasilkan solusi teknis berbasis teknologi informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL07',
      1 => 'PSIF_CPL09',
    ),
  ),
  56 => 
  array (
    'kode' => 'KIU1017',
    'nama' => 'IOT dalam Agroindustri',
    'sks' => 2,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan IOT dalam Agroindustri secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait IOT dalam Agroindustri sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan IOT dalam Agroindustri.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada IOT dalam Agroindustri.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep Internet of Things dalam agroindustri',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu merancang sistem IoT sederhana untuk monitoring agroindustri',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL09',
    ),
  ),
  57 => 
  array (
    'kode' => 'KIU1013',
    'nama' => 'Manajemen Agroindustri Modern',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Manajemen Agroindustri Modern secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Manajemen Agroindustri Modern sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Manajemen Agroindustri Modern.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Manajemen Agroindustri Modern.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep manajemen agroindustri modern',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menganalisis proses agroindustri berbasis teknologi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL03',
    ),
  ),
  58 => 
  array (
    'kode' => 'KIU1043',
    'nama' => 'Manajemen Layanan TI',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Manajemen Layanan TI secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Manajemen Layanan TI sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Manajemen Layanan TI.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Manajemen Layanan TI.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep manajemen layanan teknologi informasi',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu merancang proses layanan TI sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
      1 => 'PSIF_CPL06',
    ),
  ),
  59 => 
  array (
    'kode' => 'KIU1045',
    'nama' => 'Manajemen Resiko TI',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Manajemen Resiko TI secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Manajemen Resiko TI sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Manajemen Resiko TI.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Manajemen Resiko TI.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep manajemen risiko teknologi informasi',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu melakukan identifikasi dan analisis risiko sistem TI',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
      1 => 'PSIF_CPL07',
    ),
  ),
  60 => 
  array (
    'kode' => 'KIU1011',
    'nama' => 'Pengantar Sistem Informasi Geografi',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pengantar Sistem Informasi Geografi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pengantar Sistem Informasi Geografi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pengantar Sistem Informasi Geografi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pengantar Sistem Informasi Geografi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep sistem informasi geografis',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL10',
        'deskripsi' => 'Mahasiswa mampu mengolah data spasial menggunakan perangkat lunak SIG',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL05',
      1 => 'PSIF_CPL10',
    ),
  ),
  61 => 
  array (
    'kode' => 'KIU1015',
    'nama' => 'Penginderaan Jauh',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Penginderaan Jauh secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Penginderaan Jauh sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Penginderaan Jauh.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Penginderaan Jauh.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep penginderaan jauh',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menginterpretasikan citra penginderaan jauh',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL05',
      2 => 'PSIF_CPL08',
    ),
  ),
  62 => 
  array (
    'kode' => 'KIU1041',
    'nama' => 'Pengukuran dan Implementasi',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pengukuran dan Implementasi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pengukuran dan Implementasi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pengukuran dan Implementasi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pengukuran dan Implementasi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep pengukuran kinerja sistem TI',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu melakukan implementasi sistem teknologi informasi sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL05',
      1 => 'PSIF_CPL06',
    ),
  ),
  63 => 
  array (
    'kode' => 'KIU1046',
    'nama' => 'Requirement Engineering',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Requirement Engineering secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Requirement Engineering sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Requirement Engineering.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Requirement Engineering.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu untuk menggali kebutuhan perangkat lunak berdasarkan framework yang digunakan',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu untuk menganalisis hasil penggalian kebutuhan untuk menghasilkan fitur perangkat lunak',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
      1 => 'PSIF_CPL06',
    ),
  ),
  64 => 
  array (
    'kode' => 'KIU1032',
    'nama' => 'Robotika dalam Agroindustri',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Robotika dalam Agroindustri secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Robotika dalam Agroindustri sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Robotika dalam Agroindustri.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Robotika dalam Agroindustri.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep robotika dalam agroindustri',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu merancang sistem robotika sederhana untuk agroindustri',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL09',
    ),
  ),
  65 => 
  array (
    'kode' => 'KIU1034',
    'nama' => 'Sistem Device',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Sistem Device secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Sistem Device sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Sistem Device.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Sistem Device.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep perangkat sistem komputer',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu mengkonfigurasi perangkat sistem komputer',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL07',
    ),
  ),
  66 => 
  array (
    'kode' => 'KIU1022',
    'nama' => 'Sistem Informasi Akuntansi',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Sistem Informasi Akuntansi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Sistem Informasi Akuntansi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Sistem Informasi Akuntansi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Sistem Informasi Akuntansi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep sistem informasi akuntansi',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu merancang sistem akuntansi sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
      1 => 'PSIF_CPL09',
    ),
  ),
  67 => 
  array (
    'kode' => 'KIU1014',
    'nama' => 'Sistem Penunjang Pengambilan Keputusan',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Sistem Penunjang Pengambilan Keputusan secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Sistem Penunjang Pengambilan Keputusan sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Sistem Penunjang Pengambilan Keputusan.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Sistem Penunjang Pengambilan Keputusan.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep sistem penunjang keputusan',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL08',
        'deskripsi' => 'Mahasiswa mampu membangun model keputusan sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL05',
      1 => 'PSIF_CPL08',
    ),
  ),
  68 => 
  array (
    'kode' => 'KIU1031',
    'nama' => 'Sistem Terdistribusi',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Sistem Terdistribusi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Sistem Terdistribusi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Sistem Terdistribusi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Sistem Terdistribusi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep sistem terdistribusi',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan layanan terdistribusi sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL06',
    ),
  ),
  69 => 
  array (
    'kode' => 'KIU1051',
    'nama' => 'Soft Skill TI',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Soft Skill TI secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Soft Skill TI sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Soft Skill TI.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Soft Skill TI.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL01',
        'deskripsi' => 'Mahasiswa mampu menunjukkan sikap profesional dalam bekerja',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL01',
        'deskripsi' => 'Mahasiswa mampu bekerja sama secara efektif dalam tim',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL01',
    ),
  ),
  70 => 
  array (
    'kode' => 'KIU1012',
    'nama' => 'Spatial Basis Data',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Spatial Basis Data secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Spatial Basis Data sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Spatial Basis Data.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Spatial Basis Data.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep basis data spasial',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL05',
        'deskripsi' => 'Mahasiswa mampu mengelola data spasial sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL05',
      2 => 'PSIF_CPL07',
    ),
  ),
  71 => 
  array (
    'kode' => 'KIU1024',
    'nama' => 'Supply Chain Management',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Supply Chain Management secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Supply Chain Management sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Supply Chain Management.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Supply Chain Management.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep manajemen rantai pasok',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menganalisis proses rantai pasok sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL02',
      1 => 'PSIF_CPL03',
    ),
  ),
  72 => 
  array (
    'kode' => 'KIU1047',
    'nama' => 'System Enterprise',
    'sks' => 2,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan System Enterprise secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait System Enterprise sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan System Enterprise.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada System Enterprise.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep sistem enterprise',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu merancang sistem enterprise sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
      1 => 'PSIF_CPL09',
    ),
  ),
  73 => 
  array (
    'kode' => 'KIU1033',
    'nama' => 'Teknik Antarmuka',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSIF',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Teknik Antarmuka secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Teknik Antarmuka sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Teknik Antarmuka.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Teknik Antarmuka.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu menjelaskan prinsip perancangan antarmuka pengguna',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL10',
        'deskripsi' => 'Mahasiswa mampu merancang antarmuka aplikasi sesuai kebutuhan pengguna',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL09',
      1 => 'PSIF_CPL10',
    ),
  ),
  74 => 
  array (
    'kode' => 'KMU1020',
    'nama' => '2D Modelling and Animasi',
    'sks' => 2,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan 2D Modelling and Animasi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait 2D Modelling and Animasi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan 2D Modelling and Animasi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada 2D Modelling and Animasi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu merancang dan mengembangkan model serta animasi 2D berdasarkan prinsip desain, estetika visual, dan kebutuhan media digital',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu menghasilkan karya animasi 2D yang adaptif terhadap perkembangan teknologi dan tren industri kreatif digital',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL02',
    ),
  ),
  75 => 
  array (
    'kode' => 'KMU1026',
    'nama' => 'Analisa Bisnis ERP',
    'sks' => 2,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Analisa Bisnis ERP secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Analisa Bisnis ERP sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Analisa Bisnis ERP.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Analisa Bisnis ERP.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu menganalisis kebutuhan bisnis dan memetakan proses organisasi ke dalam modul ERP secara logis, sistematis, dan terintegrasi',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu mengevaluasi implementasi ERP berdasarkan pendekatan manajemen dan arsitektur organisasi untuk meningkatkan kinerja sistem informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL02',
      1 => 'PSSI_CPL03',
      2 => 'PSSI_CPL06',
    ),
  ),
  76 => 
  array (
    'kode' => 'KMU1025',
    'nama' => 'Artificial Intelligence',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Artificial Intelligence secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Artificial Intelligence sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Artificial Intelligence.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Artificial Intelligence.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu menerapkan konsep data, matematika, dan pemrograman dalam membangun model Artificial Intelligence untuk mendukung pengambilan keputusan',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mampu mengolah dan menganalisis data menggunakan teknik AI untuk menghasilkan solusi berbasis data dalam sistem informasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL05',
      1 => 'PSSI_CPL07',
    ),
  ),
  77 => 
  array (
    'kode' => 'KMU1019',
    'nama' => 'Computational Thinking',
    'sks' => 2,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Computational Thinking secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Computational Thinking sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Computational Thinking.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Computational Thinking.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu menerapkan pola pikir komputasional secara logis dan kritis dalam memecahkan permasalahan sistem informasi',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu merancang algoritma dan solusi komputasional dengan memanfaatkan konsep data, matematika, dan pemrograman secara sistematis',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL02',
      1 => 'PSSI_CPL05',
    ),
  ),
  78 => 
  array (
    'kode' => 'KMU1002',
    'nama' => 'Critical Thinking and Problem Solving',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Critical Thinking and Problem Solving secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Critical Thinking and Problem Solving sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Critical Thinking and Problem Solving.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Critical Thinking and Problem Solving.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu menganalisis informasi, argumen, dan permasalahan komputasi secara logis dan kritis untuk mengidentifikasi akar masalah yang sebenarnya',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu merumuskan alternatif solusi pemecahan masalah yang inovatif dan adaptif dalam analisis sistem dan proses bisnis',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL02',
    ),
  ),
  79 => 
  array (
    'kode' => 'KMU1030',
    'nama' => 'Dasar Cloud Computing',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Dasar Cloud Computing secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Dasar Cloud Computing sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Dasar Cloud Computing.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Dasar Cloud Computing.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL10',
        'deskripsi' => 'Mampu menganalisis kebutuhan bisnis dan operasional suatu organisasi untuk mengidentifikasi solusi layanan cloud yang paling relevan',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL10',
        'deskripsi' => 'Mampu merancang arsitektur cloud tingkat dasar yang memenuhi prinsip ketersediaan tinggi, skalabilitas, dan efisiensi biaya',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL10',
    ),
  ),
  80 => 
  array (
    'kode' => 'KMU1041',
    'nama' => 'Design Thinking',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Design Thinking secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Design Thinking sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Design Thinking.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Design Thinking.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu menganalisis permasalahan pada ekosistem pertanian industrial berkelanjutan secara logis dan kritis melalui pendekatan human-centered design',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu merancang purwarupa solusi sistem informasi yang inovatif dan adaptif untuk merespons dan menyelesaikan tantangan operasional di sektor agroindustri',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL02',
    ),
  ),
  81 => 
  array (
    'kode' => 'KMU1009',
    'nama' => 'Development and Operation',
    'sks' => 4,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Development and Operation secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Development and Operation sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Development and Operation.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Development and Operation.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL11',
        'deskripsi' => 'Mampu merencanakan ruang lingkup, jadwal rilis, dan alokasi sumber daya dalam transisi proyek berpendekatan DevOps',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL10',
        'deskripsi' => 'Mampu memetakan kebutuhan arsitektur transisi dari sistem monolitik menuju pendekatan microservices atau arsitektur terdistribusi',
      ),
      2 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu menyelaraskan alur rilis dan deployment aplikasi dengan standar tata kelola IT organisasi serta pemenuhan kesepakatan tingkat layanan (SLA)',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL11',
      1 => 'PSSI_CPL10',
      2 => 'PSSI_CPL08',
    ),
  ),
  82 => 
  array (
    'kode' => 'KMU1036',
    'nama' => 'Digital Creative',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Digital Creative secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Digital Creative sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Digital Creative.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Digital Creative.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu merancang purwarupa produk kreatif digital yang inovatif sebagai solusi edukasi atau penguatan branding',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu menganalisis data interaksi audiens untuk memvalidasi dan mengoptimalkan nilai komersial dari sebuah kampanye kreatif',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL02',
      1 => 'PSSI_CPL03',
    ),
  ),
  83 => 
  array (
    'kode' => 'KMU1013',
    'nama' => 'Digital Marketing',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Digital Marketing secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Digital Marketing sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Digital Marketing.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Digital Marketing.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu merumuskan perencanaan digital marketing yang berorientasi pada penciptaan nilai wirausaha dan pertumbuhan bisnis',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mampu mengekstraksi dan memvisualisasikan data interaksi pelanggan  menggunakan perangkat data analytics',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL03',
      1 => 'PSSI_CPL07',
    ),
  ),
  84 => 
  array (
    'kode' => 'KMU1022',
    'nama' => 'Graphical Design',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Graphical Design secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Graphical Design sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Graphical Design.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Graphical Design.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu merancang materi promosi visual dan identitas merek yang memiliki nilai komersial untuk mendukung kewirausahaan di sektor bisnis',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu menganalisis data riset pasar dan profil audiens sebagai landasan logis dalam merumuskan gaya visual dan pesan kampanye desain grafis',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL03',
      1 => 'PSSI_CPL02',
    ),
  ),
  85 => 
  array (
    'kode' => 'KMU1032',
    'nama' => 'Inovasi Pembelajaran',
    'sks' => 4,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Inovasi Pembelajaran secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Inovasi Pembelajaran sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Inovasi Pembelajaran.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Inovasi Pembelajaran.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL01',
        'deskripsi' => 'Mampu menganalisis kebutuhan sistem pembelajaran digital berbasis proses bisnis organisasi pendidikan',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu merancang model inovasi pembelajaran berbasis Sistem Informasi (LMS, DSS, Knowledge Management)',
      ),
      2 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu mengembangkan prototype sistem/media pembelajaran digital berbasis kebutuhan pengguna',
      ),
      3 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu mengevaluasi efektivitas solusi menggunakan indikator kinerja dan analisis data',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL01',
      1 => 'PSSI_CPL02',
      2 => 'PSSI_CPL03',
      3 => 'PSSI_CPL05',
      4 => 'PSSI_CPL08',
    ),
  ),
  86 => 
  array (
    'kode' => 'KMU1037',
    'nama' => 'IOT Dalam Agroindustri Lanjut',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan IOT Dalam Agroindustri Lanjut secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait IOT Dalam Agroindustri Lanjut sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan IOT Dalam Agroindustri Lanjut.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada IOT Dalam Agroindustri Lanjut.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu mengimplementasikan integrasi data IoT menggunakan teknik pemrograman, manajemen basis data, dan API untuk mendukung sistem informasi berbasis web/mobile',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu mengevaluasi dan mengolah data IoT menggunakan teknik statistik dan data analitik untuk mendukung pengambilan keputusan manajerial',
      ),
      2 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu mengevaluasi performa, keamanan, risiko, dan keberlanjutan sistem IoT dalam organisasi agroindustri',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL02',
      1 => 'PSSI_CPL03',
      2 => 'PSSI_CPL05',
      3 => 'PSSI_CPL06',
      4 => 'PSSI_CPL07',
      5 => 'PSSI_CPL08',
      6 => 'PSSI_CPL09',
      7 => 'PSSI_CPL10',
      8 => 'PSSI_CPL11',
    ),
  ),
  87 => 
  array (
    'kode' => 'KMU1006',
    'nama' => 'Jejaring Dunia Kerja',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Jejaring Dunia Kerja secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Jejaring Dunia Kerja sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Jejaring Dunia Kerja.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Jejaring Dunia Kerja.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL01',
        'deskripsi' => 'Mampu menyusun rencana pengembangan karier berbasis roadmap kompetensi SI',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu membangun jejaring profesional menggunakan platform digital secara etis dan strategis',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL01',
      1 => 'PSSI_CPL02',
      2 => 'PSSI_CPL03',
      3 => 'PSSI_CPL04',
    ),
  ),
  88 => 
  array (
    'kode' => 'KMU1004',
    'nama' => 'Kerja Tim',
    'sks' => 2,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Kerja Tim secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Kerja Tim sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Kerja Tim.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Kerja Tim.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL01',
        'deskripsi' => 'Mampu menganalisis pembagian peran (System Analyst, Developer, Project Manager, User)',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu mengelola kolaborasi tim dalam penyelesaian studi kasus berbasis sistem organisasi',
      ),
      2 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu mengevaluasi kinerja tim berdasarkan indikator proyek SI',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL01',
      1 => 'PSSI_CPL02',
      2 => 'PSSI_CPL08',
      3 => 'PSSI_CPL11',
    ),
  ),
  89 => 
  array (
    'kode' => 'KMU1001',
    'nama' => 'Komunikasi Kerja',
    'sks' => 2,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Komunikasi Kerja secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Komunikasi Kerja sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Komunikasi Kerja.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Komunikasi Kerja.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL01',
        'deskripsi' => 'Mampu menyusun dokumen analisis kebutuhan sistem (SRS, BRD) secara sistematis',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu menyampaikan presentasi solusi SI kepada stakeholder secara argumentatif dan berbasis data',
      ),
      2 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu menganalisis potensi konflik komunikasi dalam proyek TI dan solusinya',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL01',
      1 => 'PSSI_CPL02',
      2 => 'PSSI_CPL03',
    ),
  ),
  90 => 
  array (
    'kode' => 'KMU1011',
    'nama' => 'Manajemen Proyek TI Lanjut',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Manajemen Proyek TI Lanjut secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Manajemen Proyek TI Lanjut sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Manajemen Proyek TI Lanjut.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Manajemen Proyek TI Lanjut.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu merancang perencanaan proyek menggunakan pendekatan Agile/Hybrid berbasis kebutuhan organisasi',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mampu mengimplementasikan monitoring dan controlling proyek berbasis KPI dan dashboard',
      ),
      2 => 
      array (
        'cpl' => 'PSSI_CPL09',
        'deskripsi' => 'Mampu mengevaluasi keberhasilan proyek berdasarkan aspek waktu, biaya, mutu, risiko, dan kepuasan stakeholder',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL02',
      1 => 'PSSI_CPL06',
      2 => 'PSSI_CPL09',
      3 => 'PSSI_CPL10',
      4 => 'PSSI_CPL11',
    ),
  ),
  91 => 
  array (
    'kode' => 'KMU1039',
    'nama' => 'Pemetaan Sosial',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pemetaan Sosial secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pemetaan Sosial sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pemetaan Sosial.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pemetaan Sosial.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL01',
        'deskripsi' => 'Mampu menganalisis data sosial menggunakan pendekatan sistem informasi dan business intelligence',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu mengembangkan visualisasi dashboard atau sistem informasi berbasis data sosial',
      ),
      2 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mampu mengevaluasi hasil pemetaan sebagai dasar rekomendasi kebijakan berbasis SI',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL01',
      1 => 'PSSI_CPL02',
      2 => 'PSSI_CPL03',
      3 => 'PSSI_CPL07',
      4 => 'PSSI_CPL09',
    ),
  ),
  92 => 
  array (
    'kode' => 'KMU1008',
    'nama' => 'Pemrograman Antarmuka Aplikasi',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pemrograman Antarmuka Aplikasi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pemrograman Antarmuka Aplikasi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pemrograman Antarmuka Aplikasi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pemrograman Antarmuka Aplikasi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu membangun antarmuka pemrograman aplikasi (API) yang menghubungkan berbagai platform sistem informasi dengan menerapkan teknik pemrograman untuk menjamin ketersediaan data bagi kebutuhan organisasi',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mampu menelaah dan mengimplementasikan standar pertukaran data (seperti REST atau JSON) serta dokumentasi API untuk mengintegrasikan berbagai layanan informasi yang mendukung analisis data organisasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL05',
      1 => 'PSSI_CPL07',
    ),
  ),
  93 => 
  array (
    'kode' => 'KMU1016',
    'nama' => 'Pemrograman Aplikasi Mobile',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pemrograman Aplikasi Mobile secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pemrograman Aplikasi Mobile sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pemrograman Aplikasi Mobile.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pemrograman Aplikasi Mobile.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu merancang dan membangun antarmuka pengguna aplikasi mobile yang inovatif serta adaptif terhadap berbagai karakteristik perangkat (Android/iOS) untuk meningkatkan efisiensi interaksi pengguna di sektor agroindustri',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu mengimplementasikan logika pemrograman mobile dan mengintegrasikannya dengan layanan data (API) serta fitur perangkat keras (seperti GPS atau Kamera) untuk mendukung tata kelola informasi organisasi yang dinamis',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL02',
      1 => 'PSSI_CPL08',
    ),
  ),
  94 => 
  array (
    'kode' => 'KMU1018',
    'nama' => 'Pemrograman Back-End',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pemrograman Back-End secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pemrograman Back-End sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pemrograman Back-End.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pemrograman Back-End.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep pemrograman sisi server untuk mendukung proses bisnis serta tata kelola informasi dalam organisasi',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mahasiswa mampu mengembangkan layanan backend aplikasi untuk mendukung proses bisnis serta tata kelola informasi dalam organisasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL03',
      1 => 'PSSI_CPL08',
    ),
  ),
  95 => 
  array (
    'kode' => 'KMU1021',
    'nama' => 'Pemrograman Basis data Lanjut',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pemrograman Basis data Lanjut secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pemrograman Basis data Lanjut sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pemrograman Basis data Lanjut.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pemrograman Basis data Lanjut.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu membangun program di sisi server basis data menggunakan Stored Procedures, Functions, dan Triggers untuk mengotomatisasi proses bisnis organisasi dan memastikan konsistensi data yang kompleks',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mampu menelaah dan mengoptimalkan struktur serta kueri basis data (melalui indexing dan query tuning) untuk mendukung pemrosesan data skala besar dan kecepatan pengambilan keputusan di lingkungan agroindustri',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL05',
      1 => 'PSSI_CPL07',
    ),
  ),
  96 => 
  array (
    'kode' => 'KMU1015',
    'nama' => 'Pemrograman Berbasis Platform',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pemrograman Berbasis Platform secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pemrograman Berbasis Platform sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pemrograman Berbasis Platform.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pemrograman Berbasis Platform.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mampu menelaah karakteristik berbagai platform teknologi dan merancang arsitektur aplikasi yang adaptif untuk memastikan sistem dapat beroperasi secara konsisten di berbagai lingkungan organisasi',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL10',
        'deskripsi' => 'Mampu membangun aplikasi fungsional yang memanfaatkan fitur spesifik platform (seperti asynchronous processing atau native API) untuk mendukung inovasi layanan informasi pada ekosistem industri',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL02',
      1 => 'PSSI_CPL10',
      2 => 'PSSI_CPL01',
    ),
  ),
  97 => 
  array (
    'kode' => 'KMU1007',
    'nama' => 'Pemrograman Framework Lanjut',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pemrograman Framework Lanjut secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pemrograman Framework Lanjut sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pemrograman Framework Lanjut.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pemrograman Framework Lanjut.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mampu membangun aplikasi organisasi yang kompleks dengan menerapkan arsitektur framework tingkat lanjut untuk menjamin integritas kode dan kemudahan pemeliharaan sistem informasi',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu menelaah dan mengintegrasikan fitur-fitur lanjutan framework (seperti automated testing, caching, atau security middleware) untuk mengoptimalkan kinerja sistem yang mendukung operasional organisasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL06',
      1 => 'PSSI_CPL08',
    ),
  ),
  98 => 
  array (
    'kode' => 'KMU1017',
    'nama' => 'Pemrograman Front-End',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pemrograman Front-End secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pemrograman Front-End sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pemrograman Front-End.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pemrograman Front-End.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mampu membangun antarmuka pengguna (UI) yang responsif dan fungsional menggunakan teknik pemrograman client-side untuk mendukung kebutuhan operasional dan tata kelola sistem informasi organisasi',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mampu menelaah dan mengintegrasikan sumber data eksternal ke dalam komponen antarmuka untuk menyajikan informasi yang akurat dan interaktif guna mendukung pengambilan keputusan di lingkungan organisasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL05',
      1 => 'PSSI_CPL08',
    ),
  ),
  99 => 
  array (
    'kode' => 'KMU1014',
    'nama' => 'Pengembangan Sistem ERP',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pengembangan Sistem ERP secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pengembangan Sistem ERP sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pengembangan Sistem ERP.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pengembangan Sistem ERP.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep sistem ERP',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mahasiswa mampu mengembangkan modul ERP sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL07',
      1 => 'PSSI_CPL08',
    ),
  ),
  100 => 
  array (
    'kode' => 'KMU1012',
    'nama' => 'Perancangan Website Lanjut',
    'sks' => 4,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Perancangan Website Lanjut secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Perancangan Website Lanjut sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Perancangan Website Lanjut.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Perancangan Website Lanjut.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep perancangan website lanjut',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mahasiswa mampu mengembangkan website dinamis',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL05',
      1 => 'PSSI_CPL08',
    ),
  ),
  101 => 
  array (
    'kode' => 'KMU1031',
    'nama' => 'Power Platform Fundamental',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Power Platform Fundamental secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Power Platform Fundamental sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Power Platform Fundamental.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Power Platform Fundamental.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep pengembangan aplikasi low-code',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mahasiswa mampu membangun aplikasi menggunakan platform low-code',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL05',
      1 => 'PSSI_CPL07',
    ),
  ),
  102 => 
  array (
    'kode' => 'KMU1005',
    'nama' => 'Profesionalisme dalam Dunia Kerja',
    'sks' => 2,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Profesionalisme dalam Dunia Kerja secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Profesionalisme dalam Dunia Kerja sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Profesionalisme dalam Dunia Kerja.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Profesionalisme dalam Dunia Kerja.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL01',
        'deskripsi' => 'Mahasiswa mampu menjelaskan prinsip profesionalisme kerja',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mahasiswa mampu menunjukkan sikap profesional dalam pekerjaan',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL01',
      1 => 'PSSI_CPL02',
    ),
  ),
  103 => 
  array (
    'kode' => 'KMU1024',
    'nama' => 'Publikasi Media Digital',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Publikasi Media Digital secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Publikasi Media Digital sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Publikasi Media Digital.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Publikasi Media Digital.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan teknik publikasi media digital',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL09',
        'deskripsi' => 'Mahasiswa mampu menghasilkan konten media digital',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL05',
      1 => 'PSSI_CPL09',
    ),
  ),
  104 => 
  array (
    'kode' => 'KMU1028',
    'nama' => 'Requirement Engineering Lanjut',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Requirement Engineering Lanjut secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Requirement Engineering Lanjut sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Requirement Engineering Lanjut.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Requirement Engineering Lanjut.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan teknik analisis kebutuhan lanjut',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mahasiswa mampu menyusun dokumen kebutuhan sistem secara lengkap',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL06',
      1 => 'PSSI_CPL08',
    ),
  ),
  105 => 
  array (
    'kode' => 'KMU1003',
    'nama' => 'Sikap Kerja',
    'sks' => 2,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Sikap Kerja secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Sikap Kerja sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Sikap Kerja.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Sikap Kerja.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL01',
        'deskripsi' => 'Mahasiswa mampu menjelaskan nilai sikap kerja profesional',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mahasiswa mampu menerapkan sikap kerja yang baik',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL01',
      1 => 'PSSI_CPL02',
    ),
  ),
  106 => 
  array (
    'kode' => 'KMU1033',
    'nama' => 'Sistem Informasi Perbankan',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Sistem Informasi Perbankan secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Sistem Informasi Perbankan sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Sistem Informasi Perbankan.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Sistem Informasi Perbankan.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep sistem informasi perbankan',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL09',
        'deskripsi' => 'Mahasiswa mampu menganalisis proses sistem perbankan',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL05',
      1 => 'PSSI_CPL09',
    ),
  ),
  107 => 
  array (
    'kode' => 'KMU1029',
    'nama' => 'Sistem Keamanan Cloud Computing',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Sistem Keamanan Cloud Computing secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Sistem Keamanan Cloud Computing sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Sistem Keamanan Cloud Computing.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Sistem Keamanan Cloud Computing.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep keamanan cloud computing',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL10',
        'deskripsi' => 'Mahasiswa mampu menerapkan konfigurasi keamanan cloud sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL06',
      1 => 'PSSI_CPL10',
    ),
  ),
  108 => 
  array (
    'kode' => 'KMU1027',
    'nama' => 'Supply Chain Management Lanjut',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Supply Chain Management Lanjut secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Supply Chain Management Lanjut sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Supply Chain Management Lanjut.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Supply Chain Management Lanjut.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep supply chain management lanjut',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mahasiswa mampu menganalisis sistem rantai pasok berbasis TI',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL03',
      1 => 'PSSI_CPL07',
    ),
  ),
  109 => 
  array (
    'kode' => 'KMU1034',
    'nama' => 'Technical Writing',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Technical Writing secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Technical Writing sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Technical Writing.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Technical Writing.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan teknik penulisan teknis',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL01',
        'deskripsi' => 'Mahasiswa mampu menyusun laporan teknis secara sistematis',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL02',
      1 => 'PSSI_CPL01',
    ),
  ),
  110 => 
  array (
    'kode' => 'KMU1010',
    'nama' => 'UI/UX Design',
    'sks' => 4,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan UI/UX Design secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait UI/UX Design sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan UI/UX Design.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada UI/UX Design.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mahasiswa mampu menjelaskan prinsip desain UI/UX',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL10',
        'deskripsi' => 'Mahasiswa mampu merancang prototipe antarmuka pengguna',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL08',
      1 => 'PSSI_CPL10',
    ),
  ),
  111 => 
  array (
    'kode' => 'KMU1042',
    'nama' => 'UI/UX Testing',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan UI/UX Testing secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait UI/UX Testing sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan UI/UX Testing.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada UI/UX Testing.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL09',
        'deskripsi' => 'Mahasiswa mampu menjelaskan metode pengujian UI/UX',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mahasiswa mampu melakukan evaluasi usability sistem',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL09',
      1 => 'PSSI_CPL05',
    ),
  ),
  112 => 
  array (
    'kode' => 'KMU1038',
    'nama' => 'Verifikasi dan Validasi Data',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Verifikasi dan Validasi Data secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Verifikasi dan Validasi Data sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Verifikasi dan Validasi Data.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Verifikasi dan Validasi Data.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL07',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep verifikasi dan validasi data',
      ),
      1 => 
      array (
        'cpl' => 'PSSI_CPL05',
        'deskripsi' => 'Mahasiswa mampu melakukan verifikasi dataset',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL07',
      1 => 'PSSI_CPL05',
    ),
  ),
  113 => 
  array (
    'kode' => 'KMU1023',
    'nama' => 'Video Design',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Video Design secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Video Design sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Video Design.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Video Design.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'CPL-02 Mampu menunjukkan sikap logis, kritis, inovatif serta adaptif terhadap perubahan teknologi dan kemajuan bidang Teknologi Informasi dan Komunikasi dengan pendekatan ilmiah dan etika ilmiah.",  "CPL-04 Mampu mengelola wirausaha dalam bidang Teknologi, Informasi dan Komunikasi khususnya yang mendukung ekosistem agroindustri baik secara individu maupun kelompok',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSSI_CPL02',
      1 => 'PSSI_CPL04',
    ),
  ),
  114 => 
  array (
    'kode' => 'KMU1035',
    'nama' => 'Viral Marketing',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSSI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Viral Marketing secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Viral Marketing sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Viral Marketing.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Viral Marketing.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL03',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep pemasaran viral',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL04',
        'deskripsi' => 'Mahasiswa mampu merancang strategi pemasaran digital',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL03',
      1 => 'PSIF_CPL04',
    ),
  ),
  115 => 
  array (
    'kode' => 'KSU1603',
    'nama' => 'Capstone Project',
    'sks' => 4,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Capstone Project secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Capstone Project sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Capstone Project.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Capstone Project.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu merancang solusi sistem teknologi informasi',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan dan mendokumentasikan proyek secara sistematis',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL08',
        'deskripsi' => 'Mahasiswa dapat mengintegrasikan pengetahuan dan keterampilan yang telah diperoleh selama studi ke dalam sebuah proyek nyata.
2',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL09',
        'deskripsi' => 'Mahasiswa mampu mengidentifikasi dan menganalisis masalah, serta mengembangkan solusi yang efektif dalam konteks yang relevan dengan bidang studi
3',
      ),
      4 => 
      array (
        'cpl' => 'PSTI_CPL08',
        'deskripsi' => 'Mahasiswa dapat merancang, melaksanakan, dan mengevaluasi metodologi riset yang tepat untuk mendukung proyek',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mahasiswa dapat mengintegrasikan pengetahuan dan keterampilan yang telah diperoleh selama studi ke dalam sebuah proyek nyata.
2',
      ),
      6 => 
      array (
        'cpl' => 'PSSI_CPL09',
        'deskripsi' => 'Mahasiswa mampu mengidentifikasi dan menganalisis masalah, serta mengembangkan solusi yang efektif dalam konteks yang relevan dengan bidang studi
3',
      ),
      7 => 
      array (
        'cpl' => 'PSSI_CPL08',
        'deskripsi' => 'Mahasiswa dapat merancang, melaksanakan, dan mengevaluasi metodologi riset yang tepat untuk mendukung proyek',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL07',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL08',
      3 => 'PSTI_CPL09',
      4 => 'PSSI_CPL08',
      5 => 'PSSI_CPL09',
    ),
  ),
  116 => 
  array (
    'kode' => 'KSU1604',
    'nama' => 'Publikasi Produk',
    'sks' => 3,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Publikasi Produk secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Publikasi Produk sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Publikasi Produk.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Publikasi Produk.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL04',
        'deskripsi' => 'Mahasiswa mampu menjelaskan produk kepada calon penggunaa',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL04',
        'deskripsi' => 'Mahasiswa mampu mempresentasikan produk teknologi secara efektif',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL04',
        'deskripsi' => 'Mahasiswa mampu menjelaskan produk kepada calon penggunaa',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL04',
        'deskripsi' => 'Mahasiswa mampu mempresentasikan produk teknologi secara efektif',
      ),
      4 => 
      array (
        'cpl' => 'PSSI_CPL04',
        'deskripsi' => 'Mahasiswa mampu menjelaskan produk kepada calon penggunaa',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL04',
        'deskripsi' => 'Mahasiswa mampu mempresentasikan produk teknologi secara efektif',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL04',
      1 => 'PSTI_CPL04',
      2 => 'PSSI_CPL04',
    ),
  ),
  117 => 
  array (
    'kode' => 'KSU1701',
    'nama' => 'PKL',
    'sks' => 2,
    'lingkup' => 'Fakultas',
    'prodi' => NULL,
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan PKL secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait PKL sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan PKL.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada PKL.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL07',
        'deskripsi' => 'Mahasiswa mampu menerapkan kompetensi teknologi informasi di dunia kerja',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu menyusun laporan kegiatan kerja praktik',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mahasiswa mampu menunjukkan sikap logis, kritis, inovatif, serta adaptif dalam menghadapi perkembangan teknologi dan perubahan di bidang Teknologi Informasi dan Komunikasi (TIK) serta menerapkannya secara profesional dengan pendekatan ilmiah dan etika ilmiah di lingkungan kerja',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu mengintegrasikan pengetahuan dan keterampilan dalam analisis data, manajemen, keamanan sistem, dan pengembangan perangkat lunak untuk menghasilkan solusi yang mendukung operasional organisasi atau industri, termasuk di sektor yang berwawasan lingkungan, bisnis, dan agroindustri',
      ),
      4 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mahasiswa mampu mengelola proyek berbasis teknologi informasi secara individu maupun kelompok, dengan mengorganisasikan sumber daya teknologi (jaringan, perangkat lunak, infrastruktur digital) dan menerapkan strategi mitigasi risiko yang sesuai untuk mendukung operasional serta keberlanjutan organisasi atau perusahaan',
      ),
      5 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mahasiswa mampu menunjukkan sikap logis, kritis, inovatif, serta adaptif dalam menghadapi perkembangan teknologi dan perubahan di bidang Teknologi Informasi dan Komunikasi (TIK) serta menerapkannya secara profesional dengan pendekatan ilmiah dan etika ilmiah di lingkungan kerja',
      ),
      6 => 
      array (
        'cpl' => 'PSSI_CPL03',
        'deskripsi' => 'Mahasiswa mampu mengintegrasikan pengetahuan dan keterampilan dalam analisis data, manajemen, keamanan sistem, dan pengembangan perangkat lunak untuk menghasilkan solusi yang mendukung operasional organisasi atau industri, termasuk di sektor yang berwawasan lingkungan, bisnis, dan agroindustri',
      ),
      7 => 
      array (
        'cpl' => 'PSSI_CPL02',
        'deskripsi' => 'Mahasiswa mampu mengelola proyek berbasis teknologi informasi secara individu maupun kelompok, dengan mengorganisasikan sumber daya teknologi (jaringan, perangkat lunak, infrastruktur digital) dan menerapkan strategi mitigasi risiko yang sesuai untuk mendukung operasional serta keberlanjutan organisasi atau perusahaan',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL07',
      1 => 'PSIF_CPL09',
      2 => 'PSTI_CPL02',
      3 => 'PSTI_CPL03',
      4 => 'PSSI_CPL02',
      5 => 'PSSI_CPL03',
    ),
  ),
  118 => 
  array (
    'kode' => 'KTU1043',
    'nama' => '3D Modeling dan Animasi',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan 3D Modeling dan Animasi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait 3D Modeling dan Animasi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan 3D Modeling dan Animasi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada 3D Modeling dan Animasi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL10',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep pemodelan 3D',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL10',
        'deskripsi' => 'Mahasiswa mampu membuat model 3D sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL10',
    ),
  ),
  119 => 
  array (
    'kode' => 'KTU1042',
    'nama' => 'Augment Reality',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Augment Reality secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Augment Reality sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Augment Reality.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Augment Reality.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep augmented reality',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL10',
        'deskripsi' => 'Mahasiswa mampu mengembangkan aplikasi AR sederhana',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL09',
      1 => 'PSIF_CPL10',
    ),
  ),
  120 => 
  array (
    'kode' => 'KTU1016',
    'nama' => 'Basis Data Terdistribusi',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Basis Data Terdistribusi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Basis Data Terdistribusi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Basis Data Terdistribusi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Basis Data Terdistribusi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL07',
        'deskripsi' => 'Menjelaskan konsep basisdata terdistribusi, fragmentasi, replikasi dan distribusi',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL07',
        'deskripsi' => 'Mempraktekkan teknik fragmentasi database, replikasi database dan distribusi database',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL07',
    ),
  ),
  121 => 
  array (
    'kode' => 'KTU1012',
    'nama' => 'Blockchain',
    'sks' => 2,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Blockchain secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Blockchain sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Blockchain.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Blockchain.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mampu menjelasan tentang apa itu blockchain, bagaimana cara kerjanya, dan konsep-konsep inti seperti blok,
transaksi, konsensus, dan keamanan',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mampu menerapkan teknik kriptografi yang digunakan dalam blockchain untuk mengamankan data',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mampu menjelaskan berbagai jenis arsitektur blockchain',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mampu menerapkan penggunaan smart contracts dalam blockchain',
      ),
      4 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mampu menjelaskan contoh aplikasi nyata dari teknologi blockchain di berbagai industri',
      ),
      5 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mampu menjelaskan perdebatan tentang kendala dan solusi untuk meningkatkan skalabilitas serta kinerja
blockchain',
      ),
      6 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mampu menjelaskan aspek hukum yang terkait dengan penggunaan blockchain',
      ),
      7 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mampu menjelaskan masalah etis, keamanan, dan privasi yang terkait dengan penggunaan blockchain',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL11',
    ),
  ),
  122 => 
  array (
    'kode' => 'KTU1024',
    'nama' => 'Data Warehousing',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Data Warehousing secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Data Warehousing sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Data Warehousing.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Data Warehousing.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dan peran data warehouse dalam mendukung kebutuhan analisis dan pengambilan keputusan organisasi berdasarkan pengalaman magang',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mahasiswa mampu berkontribusi dalam proses perancangan atau implementasi data warehouse di lingkungan industri, seperti pengolahan data, integrasi data, pemodelan skema, atau penyusunan laporan analitik',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mahasiswa mampu menganalisis dan mengevaluasi penerapan data warehouse di tempat magang serta memberikan rekomendasi pengembangan yang relevan secara teoritis dan praktis',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL10',
    ),
  ),
  123 => 
  array (
    'kode' => 'KTU1013',
    'nama' => 'Desain dan Manajemen Jaringan',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Desain dan Manajemen Jaringan secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Desain dan Manajemen Jaringan sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Desain dan Manajemen Jaringan.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Desain dan Manajemen Jaringan.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL09',
        'deskripsi' => 'Mampu mengoperasikan berbagai protokol Jaringan untuk menyelesaikan masalah terkait dengan manajemen jaringan',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL09',
        'deskripsi' => 'Mampu Mengoperasikan Single Area OSPF pada jaringan Multi Akses Point to Point dan Broadcast',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL09',
    ),
  ),
  124 => 
  array (
    'kode' => 'KTU1015',
    'nama' => 'Digital Forensik',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Digital Forensik secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Digital Forensik sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Digital Forensik.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Digital Forensik.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu menerapkan ilmu komputer dan prosedur investigasi untuk tujuan hukum, melakukan analisis buktidigital dengan pencarian yang tepat, serta mendokumentasikanbarang bukti secara valid',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu menggunakan alat-alat Digital Forensik secara  valid untuk mengumpulkan, menganalisis, dan melindungibukti digital sesuai dengan standar forensik yang berlaku',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu menganalisis hasil investigasi atau studi kasus insiden digital di tempat magang serta menyusun laporan dan mempresentasikan temuan secara profesional',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL03',
    ),
  ),
  125 => 
  array (
    'kode' => 'KTU1025',
    'nama' => 'Embedded System Programing',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Embedded System Programing secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Embedded System Programing sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Embedded System Programing.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Embedded System Programing.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL07',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dasar embedded system, arsitektur perangkat keras–perangkat lunak, serta prinsip kerja sistem real-time',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL07',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan pemrograman embedded system dan memanfaatkan sistem operasi atau RTOS sesuai kebutuhan aplikasi',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL07',
        'deskripsi' => 'Mahasiswa mampu merancang dan mengembangkan proyek embedded system sederhana yang sesuai dengan kebutuhan pengguna serta mendemonstrasikan fungsionalitasnya',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL07',
    ),
  ),
  126 => 
  array (
    'kode' => 'KTU1022',
    'nama' => 'Enterprise Architecture',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Enterprise Architecture secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Enterprise Architecture sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Enterprise Architecture.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Enterprise Architecture.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mahasiswa mampu menganalisa kebutuhan suatu perusahaan/organisasi dalam membangun suatu EA',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mahasiswa mampu menggambarkan dan memodelkan proses bisnis dalam suatu bisnis/perusahaan/organisasi sesuai dengan fungsi, kebutuhan dan sumber daya yang dimiliki',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL07',
        'deskripsi' => 'Mahasiswa mampu mengevaluasi framework yang sesuai untuk menyusun enterprise architecture',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL02',
      1 => 'PSTI_CPL05',
      2 => 'PSTI_CPL07',
    ),
  ),
  127 => 
  array (
    'kode' => 'KTU1044',
    'nama' => 'Game Engine Design & Development',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Game Engine Design & Development secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Game Engine Design & Development sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Game Engine Design & Development.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Game Engine Design & Development.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL04',
        'deskripsi' => 'Mengeksplorasi industri game digital beserta siklus hidup pengembangan game digital',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Membangun game digital dengan menggunakan teknik dan pendekatan yang digunakan dalam pengembangan game digital',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL04',
      1 => 'PSTI_CPL10',
    ),
  ),
  128 => 
  array (
    'kode' => 'KTU1046',
    'nama' => 'Intelligent Game Theory',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Intelligent Game Theory secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Intelligent Game Theory sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Intelligent Game Theory.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Intelligent Game Theory.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mampu memahami dan menerapkan konsep keilmuan secara profesional.',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL02',
      1 => 'PSTI_CPL03',
    ),
  ),
  129 => 
  array (
    'kode' => 'KTU1014',
    'nama' => 'Kriptografi',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Kriptografi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Kriptografi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Kriptografi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Kriptografi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep dasar kriptografi serta prinsip pengamanan informasi dalam sistem digital',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mahasiswa mampu menerapkan algoritma kriptografi untuk menjaga kerahasiaan, integritas, dan autentikasi data',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL02',
      1 => 'PSTI_CPL11',
    ),
  ),
  130 => 
  array (
    'kode' => 'KTU1026',
    'nama' => 'Manajemen Kualitas Perangkat Lunak',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Manajemen Kualitas Perangkat Lunak secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Manajemen Kualitas Perangkat Lunak sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Manajemen Kualitas Perangkat Lunak.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Manajemen Kualitas Perangkat Lunak.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mampu menganalisis standar kualitas perangkat lunak secara logis dan kritis serta merancang rencana penjaminan kualitas (Quality Assurance) yang inovatif untuk menghadapi perubahan teknologi informasi yang dinamis dengan pendekatan ilmiah',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mampu mengorganisasikan proses pengendalian kualitas (Quality Control) pada perangkat lunak dan infrastruktur pendukungnya guna memastikan keandalan sistem dalam mendukung operasional ekosistem agroindustri yang berkelanjutan',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL02',
      1 => 'PSTI_CPL05',
    ),
  ),
  131 => 
  array (
    'kode' => 'KTU1045',
    'nama' => 'Multimedia System',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Multimedia System secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Multimedia System sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Multimedia System.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Multimedia System.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mahasiswa mampu menunjukkan pemikiran logis, kritis, dan inovatif dalam menganalisis kebutuhan serta perkembangan teknologi multimedia',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu mengintegrasikan konsep analisis data dan teknologi informasi dalam perancangan sistem multimedia yang sesuai kebutuhan',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL02',
      1 => 'PSTI_CPL03',
    ),
  ),
  132 => 
  array (
    'kode' => 'KTU1011',
    'nama' => 'Pemrograman Jaringan',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pemrograman Jaringan secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pemrograman Jaringan sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pemrograman Jaringan.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pemrograman Jaringan.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mampu menjelaskan konsep komunikasi data, protokol jaringan, dan arsitektur client-server',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu mengimplementasikan aplikasi berbasis jaringan menggunakan socket programming atau API jaringan lainnya secara aman dan efisien',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL05',
      1 => 'PSTI_CPL10',
    ),
  ),
  133 => 
  array (
    'kode' => 'KTU1021',
    'nama' => 'Pola-pola Perancangan',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Pola-pola Perancangan secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Pola-pola Perancangan sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Pola-pola Perancangan.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Pola-pola Perancangan.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL10',
        'deskripsi' => 'Mampu menjelaskan berbagai pola perancangan dalam pengembangan perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => 'Mampu menerapkan pola desain yang tepat sesuai kebutuhan sistem dan karakteristik organisasi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL10',
      1 => 'PSTI_CPL02',
    ),
  ),
  134 => 
  array (
    'kode' => 'KTU1023',
    'nama' => 'Rekayasa Kebutuhan',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Rekayasa Kebutuhan secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Rekayasa Kebutuhan sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Rekayasa Kebutuhan.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Rekayasa Kebutuhan.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mahasiswa mampu untuk menggali kebutuhan perangkat lunak',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mahasiswa mampu untuk menganalisis kebutuhan perangkat lunak',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mahasiswa mampu memvalidasi kebutuhan yang telah didefinisikan dalam tahapan analisis',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mahasiswa mampu untuk mengelola kebutuhan perangkat lunak',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL05',
    ),
  ),
  135 => 
  array (
    'kode' => 'KTU1017',
    'nama' => 'Steganografi',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Steganografi secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Steganografi sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Steganografi.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Steganografi.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL08',
        'deskripsi' => 'Mampu mengimplementasikan berbagai algoritma steganografi (seperti metode spasial atau transformasi) untuk menyisipkan dan mengekstraksi informasi rahasia ke dalam berbagai media digital (citra, audio, atau teks)',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL11',
        'deskripsi' => 'Mampu mengevaluasi kualitas hasil steganografi menggunakan parameter statistik (seperti PSNR dan MSE) serta menganalisis ketahanannya terhadap upaya deteksi atau gangguan (steganalysis)',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL08',
      1 => 'PSTI_CPL11',
    ),
  ),
  136 => 
  array (
    'kode' => 'KTU1041',
    'nama' => 'Strategi Pengembangan Media Pembelajaran',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Strategi Pengembangan Media Pembelajaran secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Strategi Pengembangan Media Pembelajaran sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Strategi Pengembangan Media Pembelajaran.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Strategi Pengembangan Media Pembelajaran.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => '(Turunan CPL-02)
Mahasiswa mampu menganalisis kebutuhan pembelajaran dan merumuskan strategi pengembangan media pembelajaran berbasis teknologi informasi secara sistematis dan ilmiah',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL02',
        'deskripsi' => '(Turunan CPL-02)
Mahasiswa mampu merancang dan mengembangkan media pembelajaran digital yang inovatif dan adaptif terhadap perkembangan teknologi',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL02',
    ),
  ),
  137 => 
  array (
    'kode' => 'KTU1031',
    'nama' => 'System Device',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan System Device secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait System Device sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan System Device.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada System Device.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu menganalisis dan mengintegrasikan konsep perangkat sistem (input, output, processing, storage, dan peripheral) serta arsitektur perangkat keras komputer untuk mendukung kebutuhan organisasi atau industri berbasis teknologi informasi',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL08',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan konfigurasi dan pemrograman dasar perangkat sistem (device management dan interfacing) untuk mendukung kinerja aplikasi berbasis platform secara efektif dan efisien',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL03',
      1 => 'PSTI_CPL08',
      2 => 'PSTI_CPL10',
    ),
  ),
  138 => 
  array (
    'kode' => 'KTU1049',
    'nama' => 'Komputasi Awan',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Komputasi Awan secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Komputasi Awan sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Komputasi Awan.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Komputasi Awan.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL06',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep cloud computing',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL09',
        'deskripsi' => 'Mahasiswa mampu menggunakan layanan cloud computing',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL06',
      1 => 'PSIF_CPL09',
    ),
  ),
  139 => 
  array (
    'kode' => 'KTU10150',
    'nama' => 'Data Mining',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Data Mining secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Data Mining sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Data Mining.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Data Mining.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu mengimplementasikan berbagai algoritma data mining (misalnya klasifikasi, klasterisasi, kaidah asosiasi) untuk menganalisis data dan menyelesaikan permasalahan sederhana maupun kasus nyata secara tepat',
      ),
      1 => 
      array (
        'cpl' => 'PSTI_CPL05',
        'deskripsi' => 'Mahasiswa mampu menganalisis dan membandingkan berbagai algoritma teknik data mining untuk menentukan metode yang paling efektif sesuai konteks kasus atau kebutuhan data',
      ),
      2 => 
      array (
        'cpl' => 'PSTI_CPL09',
        'deskripsi' => 'Mahasiswa mampu mengevaluasi efektivitas model data mining yang telah dikembangkan menggunakan metrik performa seperti akurasi, precision, recall, atau kriteria evaluasi lain yang sesuai',
      ),
      3 => 
      array (
        'cpl' => 'PSTI_CPL03',
        'deskripsi' => 'Mahasiswa mampu merancang solusi inovatif dan efektif untuk masalah pengolahan data yang kompleks dengan menerapkan teknik data mining serta mampu mengkomunikasikan hasilnya secara jelas',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSTI_CPL03',
      1 => 'PSTI_CPL05',
      2 => 'PSTI_CPL09',
    ),
  ),
  140 => 
  array (
    'kode' => 'KTU1057',
    'nama' => 'Text Mining',
    'sks' => 3,
    'lingkup' => 'Prodi',
    'prodi' => 'PSTI',
    'kompetensi' => 'Mampu menguasai dan menerapkan konsep keilmuan Text Mining secara profesional dan terukur.',
    'jobdesks' => 
    array (
      0 => 'Melaksanakan tugas dan pekerjaan teknis terkait Text Mining sesuai standar operasional industri.',
      1 => 'Menyusun dokumentasi teknis, analisis kebutuhan, dan laporan hasil pelaksanaan pekerjaan Text Mining.',
      2 => 'Berkoordinasi dan berkolaborasi dalam tim pengembang untuk penyelesaian kendala teknis pada Text Mining.',
    ),
    'cpmks' => 
    array (
      0 => 
      array (
        'cpl' => 'PSIF_CPL01',
        'deskripsi' => 'Mahasiswa mampu menjelaskan konsep text mining',
      ),
      1 => 
      array (
        'cpl' => 'PSIF_CPL01',
        'deskripsi' => 'Mahasiswa mampu melakukan analisis teks menggunakan metode text mining',
      ),
    ),
    'cpls' => 
    array (
      0 => 'PSIF_CPL01',
    ),
  ),
);

        foreach ($courses as $c) {
            $existingMk = DB::table('mata_kuliahs')->where('kode', $c['kode'])->first();
            if ($existingMk) {
                $mkId = $existingMk->id;
                DB::table('mata_kuliahs')->where('id', $mkId)->update([
                    'nama_mk' => $c['nama'],
                    'sks' => $c['sks'],
                    'lingkup' => $c['lingkup'],
                    'program_studi_id' => $c['prodi'] ? ($prodiMap[$c['prodi']] ?? null) : null,
                    'kompetensi' => $c['kompetensi'],
                    'job_description_default' => implode("\n", $c['jobdesks']),
                    'updated_at' => $now,
                ]);
            } else {
                $mkId = DB::table('mata_kuliahs')->insertGetId([
                    'kode' => $c['kode'],
                    'nama_mk' => $c['nama'],
                    'sks' => $c['sks'],
                    'lingkup' => $c['lingkup'],
                    'program_studi_id' => $c['prodi'] ? ($prodiMap[$c['prodi']] ?? null) : null,
                    'kompetensi' => $c['kompetensi'],
                    'job_description_default' => implode("\n", $c['jobdesks']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($c['cpmks'] as $i => $cpmk) {
                if (!isset($cplMap[$cpmk['cpl']])) continue;
                DB::table('cpmks')->updateOrInsert(
                    [
                        'mata_kuliah_id' => $mkId,
                        'kode' => 'CPMK-' . ($i + 1),
                    ],
                    [
                        'cpl_id' => $cplMap[$cpmk['cpl']],
                        'deskripsi' => $cpmk['deskripsi'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            foreach ($c['cpls'] as $cplKode) {
                if (!isset($cplMap[$cplKode])) continue;
                DB::table('cpl_mata_kuliah')->updateOrInsert(
                    [
                        'cpl_id' => $cplMap[$cplKode],
                        'mata_kuliah_id' => $mkId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            foreach ($c['jobdesks'] as $i => $deskripsi) {
                DB::table('mk_jobdesks')->updateOrInsert(
                    [
                        'mata_kuliah_id' => $mkId,
                        'urutan' => $i + 1,
                    ],
                    [
                        'deskripsi' => $deskripsi,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}
