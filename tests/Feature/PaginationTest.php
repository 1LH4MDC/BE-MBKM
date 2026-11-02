<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_pendaftaran_mbkm_supports_pagination_and_no_paginate(): void
    {
        $habib = User::whereIdentifier('197805122005011002')->firstOrFail();

        $paginated = $this->actingAs($habib, 'sanctum')->getJson('/api/v1/pendaftaran-mbkm?page=1&per_page=2');
        $paginated->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonStructure(['data', 'pagination' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to']]);

        $unpaginated = $this->actingAs($habib, 'sanctum')->getJson('/api/v1/pendaftaran-mbkm?paginate=false');
        $unpaginated->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination', null);
    }

    public function test_verifikasi_rkm_supports_pagination(): void
    {
        $habib = User::whereIdentifier('197805122005011002')->firstOrFail();

        $response = $this->actingAs($habib, 'sanctum')->getJson('/api/v1/tim-mbkm/verifikasi-rkm?page=1&per_page=2');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'pagination' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_plotting_supports_pagination(): void
    {
        $habib = User::whereIdentifier('197805122005011002')->firstOrFail();

        $dosenList = $this->actingAs($habib, 'sanctum')->getJson('/api/v1/plotting/dosen-list?page=1&per_page=2');
        $dosenList->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonStructure(['data', 'pagination' => ['current_page', 'last_page', 'per_page', 'total']]);

        $tahap1 = $this->actingAs($habib, 'sanctum')->getJson('/api/v1/plotting/tahap-1-pic?page=1&per_page=2');
        $tahap1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'pagination' => ['current_page', 'last_page', 'per_page', 'total']]);

        $tahap2 = $this->actingAs($habib, 'sanctum')->getJson('/api/v1/plotting/tahap-2-dpl-penguji?page=1&per_page=2');
        $tahap2->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'pagination' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_surat_pengantar_supports_pagination(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/surat-pengantar?all=1&page=1&per_page=1');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonStructure(['data', 'pagination' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_users_list_supports_pagination(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/users?page=1&per_page=5');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonStructure(['data', 'pagination' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_master_data_lists_support_pagination(): void
    {
        $this->getJson('/api/v1/mata-kuliah?page=1&per_page=3')
            ->assertOk()
            ->assertJsonPath('pagination.per_page', 3)
            ->assertJsonStructure(['data', 'pagination']);

        $this->getJson('/api/v1/cpl?page=1&per_page=3')
            ->assertOk()
            ->assertJsonPath('pagination.per_page', 3)
            ->assertJsonStructure(['data', 'pagination']);

        $this->getJson('/api/v1/paket-konversi?page=1&per_page=2')
            ->assertOk()
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonStructure(['data', 'pagination']);

        $this->getJson('/api/v1/mitra?page=1&per_page=2')
            ->assertOk()
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonStructure(['data', 'pagination']);
    }
}
