<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MahasiswaSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('password123');
        $prodi = DB::table('program_studis')->pluck('id', 'kode');
        $roles = DB::table('roles')->pluck('id', 'name');

        $mahasiswas = [
            ['Rafa Aulia', '242410102038', 'PSTI', 2024, '082111110006'],
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
