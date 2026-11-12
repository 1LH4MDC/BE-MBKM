<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MahasiswaTambahanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('password123');
        $prodi = DB::table('program_studis')->pluck('id', 'kode');
        $roles = DB::table('roles')->pluck('id', 'name');

        $mahasiswas = [
            ['Salsabila Nurhaliza', '232410102019', 'PSTI', 2023, '082111110005'],
            ['Aulia Putri Maharani', '232410103021', 'PSIF', 2023, '082111110002'],
            ['Nabila Safitri', '242410103058', 'PSIF', 2024, '082111110004'],
            ['Rizky Pratama Wijaya', '242410101044', 'PSSI', 2024, '082111110003'],
        ];

        foreach ($mahasiswas as [$nama, $nim, $kodeProdi, $angkatan, $noHp]) {
            $existing = DB::table('users')->where('nim_nip', $nim)->first();

            if ($existing) {
                $userId = $existing->id;
                DB::table('users')->where('id', $userId)->update([
                    'nama' => $nama,
                    'email' => "{$nim}@mail.unej.ac.id",
                    'no_hp' => $noHp,
                    'updated_at' => $now,
                ]);
            } else {
                $userId = DB::table('users')->insertGetId([
                    'nama' => $nama,
                    'email' => "{$nim}@mail.unej.ac.id",
                    'nim_nip' => $nim,
                    'password' => $password,
                    'alamat' => 'Jember, Jawa Timur',
                    'no_hp' => $noHp,
                    'avatar_url' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('role_user')->updateOrInsert(
                ['user_id' => $userId, 'role_id' => $roles['Mahasiswa']],
                ['created_at' => $now, 'updated_at' => $now]
            );

            DB::table('mahasiswas')->updateOrInsert(
                ['nim' => $nim],
                [
                    'user_id' => $userId,
                    'program_studi_id' => $prodi[$kodeProdi],
                    'angkatan' => $angkatan,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}

