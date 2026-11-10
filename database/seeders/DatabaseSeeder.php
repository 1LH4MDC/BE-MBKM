<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ProgramStudiSeeder::class,
            UserSeeder::class,
            PeriodeAkademikSeeder::class,
            MitraSeeder::class,
            CplSeeder::class,
            MataKuliahSeeder::class,
            PaketKonversiSeeder::class,
            DosenFasilkomSeeder::class,
            MahasiswaSeeder::class,
            MahasiswaTambahanSeeder::class,
            CplFasilkomSeeder::class,
            MataKuliahFasilkomSeeder::class,
            PaketKonversiFasilkomSeeder::class,
        ]);
    }
}
