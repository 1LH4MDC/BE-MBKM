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
            PendaftaranMbkmSeeder::class,
            RkmSeeder::class,
            RkpDokumenSeeder::class,
            SuratPengantarSeeder::class,
            KuisionerSeeder::class,
        ]);
    }
}
