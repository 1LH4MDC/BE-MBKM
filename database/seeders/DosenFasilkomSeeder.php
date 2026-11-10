<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DosenFasilkomSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('password123');
        $prodi = DB::table('program_studis')->pluck('id', 'kode');
        $roles = DB::table('roles')->pluck('id', 'name');

        $this->simpanUser(
            'Administrator Fasilkom',
            'admin@mail.unej.ac.id',
            '111111111111111111',
            '081234560001',
            ['Admin'],
            $password,
            $roles,
            $now
        );

        $dosens = [
            ['Achmad Maududie, ST., M.Sc.', '197004221995121001', 'PSTI', ['Wakil Dekan', 'DPL'], '081210000001'],
            ['Dr. Oktalia Juwita, S.Kom., M.MT.', '198110202014042001', 'PSSI', ['Tim MBKM', 'DPL', 'Penguji Semhas'], '081210000002'],
            ['M. Habibullah Arief, M.Kom.', '199202112024061001', 'PSSI', ['Ketua Tim MBKM', 'Tim MBKM', 'DPL', 'Penguji Semhas'], '081210000003'],
            ['Nelly Oktavia Adiwijaya, S.Si., MT.', '198410242009122008', 'PSIF', ['Tim MBKM', 'DPL', 'Penguji Semhas'], '081210000004'],
            ['Dr. Dwiretno Istiyadi S, ST., M.Kom.', '197803302003121003', 'PSTI', ['GPM', 'DPL', 'Penguji Semhas'], '081210000005'],
        ];

        foreach ($dosens as [$nama, $nip, $kodeProdi, $roleNames, $noHp]) {
            $email = "{$nip}@mail.unej.ac.id";
            $userId = $this->simpanUser($nama, $email, $nip, $noHp, $roleNames, $password, $roles, $now);

            DB::table('dosens')->updateOrInsert(
                ['nip' => $nip],
                [
                    'user_id' => $userId,
                    'program_studi_id' => $prodi[$kodeProdi],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function simpanUser(string $nama, string $email, string $nimNip, string $noHp, array $roleNames, string $password, $roles, $now): int
    {
        $existing = DB::table('users')
            ->where('nim_nip', $nimNip)
            ->orWhere('email', $email)
            ->first();

        if ($existing) {
            $userId = $existing->id;
            DB::table('users')->where('id', $userId)->update([
                'nama' => $nama,
                'email' => $email,
                'nim_nip' => $nimNip,
                'no_hp' => $noHp,
                'updated_at' => $now,
            ]);
        } else {
            $userId = DB::table('users')->insertGetId([
                'nama' => $nama,
                'email' => $email,
                'nim_nip' => $nimNip,
                'password' => $password,
                'alamat' => 'Jember, Jawa Timur',
                'no_hp' => $noHp,
                'avatar_url' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($roleNames as $roleName) {
            if (isset($roles[$roleName])) {
                DB::table('role_user')->updateOrInsert(
                    ['user_id' => $userId, 'role_id' => $roles[$roleName]],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        return $userId;
    }
}
