<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaketKonversiFasilkomSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $prodi = DB::table('program_studis')->pluck('id', 'kode');
        $mk = DB::table('mata_kuliahs')->get()->keyBy('kode');

        $pakets = [
            ['PSTI', 'UI/UX DESIGN', 'UI/UX Designer',
                ['KSU1701', 'KTU1043', 'KTU1045', 'KTU1021', 'KTU1023', 'KFU1405', 'KFU1103']],
            ['PSTI', 'FRONTEND DEVELOPER', 'Frontend Developer',
                ['KSU1701', 'KTU1045', 'KTU1043', 'KTU1021', 'KTU1023', 'KFU1405', 'KFU1103']],
            ['PSTI', 'NETWORK & CLOUD ENGINEER', 'Network & Cloud Engineer',
                ['KSU1701', 'KTU1013', 'KTU1011', 'KTU1049', 'KTU1016', 'KFU1507', 'KFU1512']],

            ['PSSI', 'BUSINESS ANALYST', 'Business Analyst',
                ['KSU1701', 'KMU1001', 'KMU1026', 'KMU1014', 'KMU1028', 'KMU1011', 'KMU1041', 'KFU1404']],
            ['PSSI', 'DATA ANALYST', 'Data Analyst',
                ['KSU1701', 'KMU1001', 'KMU1021', 'KMU1038', 'KMU1025', 'KFU1012', 'KFU1015', 'KFU1104']],
            ['PSSI', 'IT PROJECT SPECIALIST', 'IT Project Specialist',
                ['KSU1701', 'KMU1001', 'KMU1004', 'KMU1005', 'KMU1011', 'KMU1028', 'KMU1026', 'KFU1404']],

            ['PSIF', 'SOFTWARE ENGINEER', 'Software Engineer',
                ['KSU1701', 'KIU1031', 'KIU1046', 'KIU1047', 'KIU1033', 'KFU1401', 'KFU1405', 'KFU1304']],
            ['PSIF', 'AI & INTELLIGENT SYSTEMS', 'AI Engineer',
                ['KSU1701', 'KIU1032', 'KFU1202', 'KFU1108', 'KFU1204', 'KFU1105', 'KFU1205']],
            ['PSIF', 'SYSTEM & CLOUD SPECIALIST', 'System & Cloud Specialist',
                ['KSU1701', 'KIU1031', 'KIU1045', 'KFU1507', 'KFU1501', 'KFU1503', 'KFU1512']],
        ];

        foreach ($pakets as [$kodeProdi, $nama, $target, $kodes]) {
            $validKodes = array_values(array_filter($kodes, fn ($k) => isset($mk[$k])));
            $totalSks = collect($validKodes)->sum(fn ($k) => $mk[$k]->sks);

            $existing = DB::table('paket_konversis')
                ->where('nama_paket', $nama)
                ->where('program_studi_id', $prodi[$kodeProdi])
                ->first();

            if ($existing) {
                $paketId = $existing->id;
                DB::table('paket_konversis')->where('id', $paketId)->update([
                    'target_posisi' => $target,
                    'jumlah_mk' => count($validKodes),
                    'total_sks_pool' => $totalSks,
                    'updated_at' => $now,
                ]);
                DB::table('paket_konversi_mata_kuliah')->where('paket_konversi_id', $paketId)->delete();
            } else {
                $paketId = DB::table('paket_konversis')->insertGetId([
                    'nama_paket' => $nama,
                    'program_studi_id' => $prodi[$kodeProdi],
                    'target_posisi' => $target,
                    'jumlah_mk' => count($validKodes),
                    'total_sks_pool' => $totalSks,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($validKodes as $i => $kode) {
                DB::table('paket_konversi_mata_kuliah')->updateOrInsert(
                    [
                        'paket_konversi_id' => $paketId,
                        'mata_kuliah_id' => $mk[$kode]->id,
                    ],
                    [
                        'urutan' => $i + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}
