<?php

namespace Tests\Feature;

use App\Models\Cpl;
use App\Models\Mitra;
use App\Models\ProgramStudi;
use App\Models\User;
use App\Services\PdfDocumentService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Tahap2BugFixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_unej_logo_base64_is_generated_and_available(): void
    {
        $service = app(PdfDocumentService::class);
        $base64 = $service->getUnejLogoBase64();

        $this->assertNotEmpty($base64);
        $this->assertStringStartsWith('data:image/png;base64,', $base64);
    }

    public function test_mitra_logo_can_be_stored_and_updated_via_url_and_file(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();
        $token = $admin->createToken('test')->plainTextToken;

        $payload = [
            'nama' => 'PT Mitra Solusi Digital',
            'kategori' => 'Teknologi',
            'alamat' => 'Jl. Kalimantan No. 37, Jember',
            'website' => 'https://mitradigital.com',
            'kontak' => '081234567890',
            'email' => 'contact@mitradigital.com',
            'status' => 'Aktif',
            'logo_url' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ];

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/mitra', $payload);

        $res->assertStatus(201);
        $this->assertDatabaseHas('mitras', [
            'nama' => 'PT Mitra Solusi Digital',
        ]);
        $mitraId = $res->json('data.id');
        $this->assertNotNull($res->json('data.logo_url'));

        Storage::fake('public');
        $fakeImage = UploadedFile::fake()->image('new-logo.png', 100, 100);

        $updateRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/mitra/{$mitraId}", [
                'nama' => 'PT Mitra Solusi Digital Updated',
                'logo_file' => $fakeImage,
            ]);

        $updateRes->assertStatus(200);
        $mitra = Mitra::findOrFail($mitraId);
        $this->assertEquals('PT Mitra Solusi Digital Updated', $mitra->nama);
        $this->assertNotNull($mitra->logo_url);
    }

    public function test_mata_kuliah_supports_fakultas_filter_and_nullable_prodi(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();
        $token = $admin->createToken('test')->plainTextToken;

        $cpl = Cpl::firstOrFail();

        $payload = [
            'program_studi_id' => null,
            'kode' => 'KSU9999',
            'nama_mk' => 'Praktik Industri Lintas Fakultas',
            'sks' => 4,
            'lingkup' => 'Fakultas',
            'kompetensi' => 'Kompetensi lintas fakultas.',
            'cpls' => [
                [
                    'cpl_id' => $cpl->id,
                    'cpmk_deskripsi' => 'Mampu bekerja secara profesional di lingkungan kerja.',
                ],
            ],
            'jobdesks' => ['Mengikuti orientasi industri', 'Melakukan pekerjaan teknis'],
        ];

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/mata-kuliah', $payload);

        $res->assertStatus(201);
        $this->assertDatabaseHas('mata_kuliahs', [
            'kode' => 'KSU9999',
            'lingkup' => 'Fakultas',
            'program_studi_id' => null,
        ]);

        $resFilterFakultas = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mata-kuliah?prodi=Fakultas');

        $resFilterFakultas->assertStatus(200);
        $dataFakultas = $resFilterFakultas->json('data');
        $this->assertNotEmpty($dataFakultas);
        foreach ($dataFakultas as $item) {
            $this->assertEquals('Fakultas', $item['lingkup']);
        }

        $prodi = ProgramStudi::where('kode', 'PSTI')->first();
        if ($prodi) {
            $resFilterPsti = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/v1/mata-kuliah?prodi=PSTI');
            $resFilterPsti->assertStatus(200);
            $codes = collect($resFilterPsti->json('data'))->pluck('kode');
            $this->assertTrue($codes->contains('KSU9999'));
        }
    }
}
