<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ClearUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');
        } else {
            Schema::disableForeignKeyConstraints();
            DB::table('role_user')->truncate();
            DB::table('dosens')->truncate();
            DB::table('mahasiswas')->truncate();
            DB::table('users')->truncate();
            Schema::enableForeignKeyConstraints();
        }

        $now = now();
        $adminRoleId = DB::table('roles')->where('name', 'Admin')->value('id');

        $userId = DB::table('users')->insertGetId([
            'nama' => 'Administrator Fasilkom',
            'email' => 'admin@mail.unej.ac.id',
            'nim_nip' => '111111111111111111',
            'password' => Hash::make('password123'),
            'alamat' => 'Jember, Jawa Timur',
            'no_hp' => '081234560001',
            'avatar_url' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($adminRoleId) {
            DB::table('role_user')->insert([
                'user_id' => $userId,
                'role_id' => $adminRoleId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
