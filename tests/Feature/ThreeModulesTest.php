<?php

namespace Tests\Feature;

use App\Models\PeriodeAkademik;
use App\Models\ProgramStudi;
use App\Models\SuratPengantar;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThreeModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_roles_gpm_and_wakil_dekan_exist(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'GPM', 'display_name' => 'Gugus Penjaminan Mutu']);
        $this->assertDatabaseHas('roles', ['name' => 'Wakil Dekan', 'display_name' => 'Wakil Dekan']);
    }

    public function test_user_creation_with_optional_email_and_nim_nip_saving(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();
        $prodi = ProgramStudi::firstOrFail();

        $responseDosen = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/users', [
            'nama' => 'Dosen GPM Test',
            'nim_nip' => '198901012020011001',
            'tipe_akun' => 'dosen',
            'roles' => ['GPM', 'DPL'],
            'program_studi_id' => $prodi->id,
        ]);

        $responseDosen->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', '198901012020011001@mail.unej.ac.id')
            ->assertJsonPath('data.nim_nip', '198901012020011001');

        $this->assertDatabaseHas('users', [
            'nim_nip' => '198901012020011001',
            'email' => '198901012020011001@mail.unej.ac.id',
        ]);
        $this->assertDatabaseHas('dosens', [
            'nip' => '198901012020011001',
        ]);

        $createdDosen = User::where('email', '198901012020011001@mail.unej.ac.id')->firstOrFail();
        $this->assertTrue($createdDosen->hasRole('GPM'));
        $this->assertTrue($createdDosen->hasRole('DPL'));

        $responseMhs = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/users', [
            'nama' => 'Mahasiswa Test Baru',
            'nim_nip' => '242410109999',
            'tipe_akun' => 'mahasiswa',
            'roles' => ['Mahasiswa'],
            'program_studi_id' => $prodi->id,
            'angkatan' => 2024,
        ]);

        $responseMhs->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', '242410109999@mail.unej.ac.id')
            ->assertJsonPath('data.nim_nip', '242410109999');

        $this->assertDatabaseHas('users', [
            'nim_nip' => '242410109999',
            'email' => '242410109999@mail.unej.ac.id',
        ]);
        $this->assertDatabaseHas('mahasiswas', [
            'nim' => '242410109999',
        ]);
    }

    public function test_user_update_with_optional_email(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();
        $prodi = ProgramStudi::firstOrFail();

        $dosenUser = User::create([
            'nama' => 'Dosen Edit Test',
            'email' => 'dosen.edit@fasilkom.ac.id',
            'nim_nip' => '199011112020011001',
            'password' => 'secret123',
        ]);
        $dosenUser->dosen()->create([
            'nip' => '199011112020011001',
            'program_studi_id' => $prodi->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/v1/users/{$dosenUser->id}", [
            'nama' => 'Dosen Edit Updated',
            'roles' => ['Wakil Dekan', 'DPL'],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nama', 'Dosen Edit Updated')
            ->assertJsonPath('data.email', 'dosen.edit@fasilkom.ac.id');

        $dosenUser->refresh();
        $this->assertTrue($dosenUser->hasRole('Wakil Dekan'));
        $this->assertTrue($dosenUser->hasRole('DPL'));
    }

    public function test_surat_pengantar_lifecycle_without_nomor_surat(): void
    {
        Storage::fake('public');

        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();
        $mahasiswa = User::whereIdentifier('242410102035')->firstOrFail();

        $responseCreate = $this->actingAs($mahasiswa, 'sanctum')->postJson('/api/v1/surat-pengantar', [
            'tujuan_instansi' => 'PT Solusi Teknologi Indonesia',
            'nama_penerima' => 'HR Manager',
            'alamat_instansi' => 'Jl. Sudirman Kav 20, Jakarta',
            'kota_instansi' => 'Jakarta Selatan',
            'posisi_magang' => 'Fullstack Developer',
        ]);

        $responseCreate->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Menunggu')
            ->assertJsonPath('data.tujuan_instansi', 'PT Solusi Teknologi Indonesia');

        $suratId = $responseCreate->json('data.id');

        $responseEdit = $this->actingAs($mahasiswa, 'sanctum')->putJson("/api/v1/surat-pengantar/{$suratId}", [
            'tujuan_instansi' => 'PT Solusi Teknologi Indonesia Revisi',
            'alamat_instansi' => 'Jl. Sudirman Kav 20, Jakarta',
            'kota_instansi' => 'Jakarta Selatan',
        ]);

        $responseEdit->assertOk()
            ->assertJsonPath('data.tujuan_instansi', 'PT Solusi Teknologi Indonesia Revisi');

        $responseAdminList = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/surat-pengantar');
        $responseAdminList->assertOk()
            ->assertJsonPath('success', true);
        $this->assertNotEmpty($responseAdminList->json('data'));

        $file = UploadedFile::fake()->create('surat-pengantar.pdf', 100, 'application/pdf');
        $responseUpload = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/surat-pengantar/{$suratId}/upload", [
            'file_surat' => $file,
            'catatan' => 'Surat pengantar telah diterbitkan oleh Dekanat.',
        ]);

        $responseUpload->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Selesai');

        $surat = SuratPengantar::findOrFail($suratId);
        $this->assertEquals('Selesai', $surat->status);
        $this->assertNotNull($surat->file_pdf_path);

        $this->actingAs($mahasiswa, 'sanctum')->putJson("/api/v1/surat-pengantar/{$suratId}", [
            'tujuan_instansi' => 'PT Lain',
            'alamat_instansi' => 'Alamat',
            'kota_instansi' => 'Kota',
        ])->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_SURAT_ALREADY_PUBLISHED');

        $responseStream = $this->actingAs($mahasiswa, 'sanctum')->get("/api/v1/surat-pengantar/{$suratId}/pdf?mode=inline");
        $responseStream->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_crud_periode_akademik(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/periode-akademik')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/periode-akademik/aktif')
            ->assertOk()
            ->assertJsonPath('success', true);

        $responseCreate = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/periode-akademik', [
            'tahun_ajaran' => '2027/2028',
            'semester' => 'Ganjil',
            'is_aktif' => true,
        ]);

        $responseCreate->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tahun_ajaran', '2027/2028')
            ->assertJsonPath('data.is_aktif', true);

        $newId = $responseCreate->json('data.id');

        $otherActiveCount = PeriodeAkademik::where('id', '!=', $newId)->where('is_aktif', true)->count();
        $this->assertEquals(0, $otherActiveCount);

        $responseUpdate = $this->actingAs($admin, 'sanctum')->putJson("/api/v1/periode-akademik/{$newId}", [
            'tahun_ajaran' => '2027/2028',
            'semester' => 'Genap',
            'is_aktif' => false,
        ]);

        $responseUpdate->assertOk()
            ->assertJsonPath('data.semester', 'Genap')
            ->assertJsonPath('data.is_aktif', false);

        $responseAktifkan = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/periode-akademik/{$newId}/aktifkan");
        $responseAktifkan->assertOk()
            ->assertJsonPath('data.is_aktif', true);

        $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/periode-akademik/{$newId}")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_PERIODE_ACTIVE');

        $this->actingAs($admin, 'sanctum')->putJson("/api/v1/periode-akademik/{$newId}", [
            'tahun_ajaran' => '2027/2028',
            'semester' => 'Genap',
            'is_aktif' => false,
        ])->assertOk();

        $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/periode-akademik/{$newId}")
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
