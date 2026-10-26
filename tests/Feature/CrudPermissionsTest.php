<?php

namespace Tests\Feature;

use App\Models\Cpl;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_tim_mbkm_can_crud_cpl_and_student_is_forbidden(): void
    {
        $habib = User::whereIdentifier('197805122005011002')->firstOrFail(); // Tim MBKM
        $danang = User::whereIdentifier('242410102035')->firstOrFail();      // Mahasiswa
        $prodi = ProgramStudi::where('kode', 'PSTI')->firstOrFail();

        $this->actingAs($danang, 'sanctum')->postJson('/api/v1/cpl', [
            'program_studi_id' => $prodi->id,
            'kode' => 'CPL-99',
            'deskripsi' => 'CPL baru dari mahasiswa',
        ])->assertStatus(403)->assertJsonPath('error_code', 'ERR_FORBIDDEN_ROLE');

        $res = $this->actingAs($habib, 'sanctum')->postJson('/api/v1/cpl', [
            'program_studi_id' => $prodi->id,
            'kode' => 'CPL-99',
            'deskripsi' => 'CPL baru dari Tim MBKM',
        ]);
        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kode', 'PSTI_CPL-99');

        $cplId = $res->json('data.id');

        $this->actingAs($habib, 'sanctum')->putJson("/api/v1/cpl/{$cplId}", [
            'program_studi_id' => $prodi->id,
            'kode' => 'CPL-99',
            'deskripsi' => 'Deskripsi CPL update',
        ])->assertOk();

        $this->actingAs($habib, 'sanctum')->deleteJson("/api/v1/cpl/{$cplId}")->assertOk();
    }

    public function test_matakuliah_requires_prodi_first_and_each_cpl_must_have_cpmk(): void
    {
        $habib = User::whereIdentifier('197805122005011002')->firstOrFail(); // Tim MBKM
        $prodi = ProgramStudi::where('kode', 'PSTI')->firstOrFail();
        $cpl = Cpl::where('program_studi_id', $prodi->id)->firstOrFail();

        $this->actingAs($habib, 'sanctum')->postJson('/api/v1/mata-kuliah', [
            'kode' => 'TI9999',
            'nama_mk' => 'Mata Kuliah Uji',
            'sks' => 3,
        ])->assertStatus(422)->assertJsonValidationErrors(['program_studi_id']);

        $this->actingAs($habib, 'sanctum')->postJson('/api/v1/mata-kuliah', [
            'program_studi_id' => $prodi->id,
            'kode' => 'TI9999',
            'nama_mk' => 'Mata Kuliah Uji',
            'sks' => 3,
            'cpls' => [
                [
                    'cpl_id' => $cpl->id,
                    'cpmk_deskripsi' => '',
                ],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['cpls.0.cpmk_deskripsi']);

        $res = $this->actingAs($habib, 'sanctum')->postJson('/api/v1/mata-kuliah', [
            'program_studi_id' => $prodi->id,
            'kode' => 'TI9999',
            'nama_mk' => 'Mata Kuliah Uji Sukses',
            'sks' => 3,
            'cpls' => [
                [
                    'cpl_id' => $cpl->id,
                    'cpmk_deskripsi' => 'Mahasiswa menguasai konsep dasar.',
                ],
            ],
        ]);
        $res->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_paket_konversi_crud_and_prodi_validation(): void
    {
        $habib = User::whereIdentifier('197805122005011002')->firstOrFail();
        $prodiTi = ProgramStudi::where('kode', 'PSTI')->firstOrFail();
        $prodiSi = ProgramStudi::where('kode', 'PSSI')->firstOrFail();

        $mkTi = MataKuliah::where('program_studi_id', $prodiTi->id)->firstOrFail();
        $mkSi = MataKuliah::where('program_studi_id', $prodiSi->id)->firstOrFail();

        $this->actingAs($habib, 'sanctum')->postJson('/api/v1/paket-konversi', [
            'nama_paket' => 'PAKET UJI MISMATCH',
            'program_studi_id' => $prodiTi->id,
            'mata_kuliah_ids' => [$mkSi->id],
        ])->assertStatus(422)->assertJsonPath('error_code', 'ERR_PAKET_PRODI_MISMATCH');

        $res = $this->actingAs($habib, 'sanctum')->postJson('/api/v1/paket-konversi', [
            'nama_paket' => 'PAKET UJI SUKSES',
            'program_studi_id' => $prodiTi->id,
            'mata_kuliah_ids' => [$mkTi->id],
        ]);
        $res->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_admin_can_manage_users_and_mitra(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();
        $danang = User::whereIdentifier('242410102035')->firstOrFail();

        $this->actingAs($danang, 'sanctum')->getJson('/api/v1/users')
            ->assertStatus(403)->assertJsonPath('error_code', 'ERR_FORBIDDEN_ROLE');

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/users')
            ->assertOk()->assertJsonPath('success', true);

        $mitraRes = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/mitra', [
            'nama' => 'PT Mitra Pengujian Baru',
            'nomor_pks' => '099/PKS/2026',
            'alamat' => 'Jl. Soekarno Hatta No. 10',
            'kontak' => 'Bpk. Budi (08123456789)',
            'email' => 'contact@mitrabaru.com',
        ]);
        $mitraRes->assertStatus(201);
    }
}

