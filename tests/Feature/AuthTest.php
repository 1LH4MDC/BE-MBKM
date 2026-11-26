<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ProgramStudiSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            ProgramStudiSeeder::class,
            UserSeeder::class,
        ]);
    }

    public function test_dosen_can_login_using_nip(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'nim_nip' => '197805122005011002',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.nama', 'Dr. Habib, M.Kom')
            ->assertJsonPath('data.user.nim_nip', '197805122005011002')
            ->assertJsonPath('data.user.nip', '197805122005011002')
            ->assertJsonPath('data.user.nim', null)
            ->assertJsonPath('data.user.profil_tipe', 'dosen')
            ->assertJsonPath('data.user.dosen.nip', '197805122005011002');
    }

    public function test_mahasiswa_can_login_using_nim(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'nim_nip' => '242410102035',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.nama', 'Danang Rizki Ramadhan')
            ->assertJsonPath('data.user.nim_nip', '242410102035')
            ->assertJsonPath('data.user.nim', '242410102035')
            ->assertJsonPath('data.user.nip', null)
            ->assertJsonPath('data.user.profil_tipe', 'mahasiswa')
            ->assertJsonPath('data.user.mahasiswa.nim', '242410102035')
            ->assertJsonPath('data.user.mahasiswa.angkatan', 2024);
    }

    public function test_admin_can_login_using_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'nim_nip' => 'admin@fasilkom.ac.id',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.nama', 'Administrator Fasilkom')
            ->assertJsonPath('data.user.nip', null)
            ->assertJsonPath('data.user.nim', null)
            ->assertJsonPath('data.user.profil_tipe', 'admin');
    }

    public function test_login_with_invalid_credentials_fails(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'nim_nip' => '999999999999',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_access_me(): void
    {
        $mahasiswa = User::whereIdentifier('242410102035')->firstOrFail();

        $responseMhs = $this->actingAs($mahasiswa, 'sanctum')->getJson('/api/v1/auth/me');

        $responseMhs->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nama', 'Danang Rizki Ramadhan')
            ->assertJsonPath('data.nim_nip', '242410102035')
            ->assertJsonPath('data.nim', '242410102035')
            ->assertJsonPath('data.nip', null)
            ->assertJsonPath('data.mahasiswa.angkatan', 2024);

        $dosen = User::whereIdentifier('197805122005011002')->firstOrFail();

        $responseDosen = $this->actingAs($dosen, 'sanctum')->getJson('/api/v1/auth/me');

        $responseDosen->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nip', '197805122005011002')
            ->assertJsonPath('data.nim', null);
    }
}
