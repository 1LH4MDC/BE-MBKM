<?php

namespace Tests\Feature;

use App\Models\KuisionerKategori;
use App\Models\KuisionerPertanyaan;
use App\Models\PendaftaranMbkm;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\KuisionerSeeder;
use Database\Seeders\PendaftaranMbkmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KuisionerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(PendaftaranMbkmSeeder::class);
        $this->seed(KuisionerSeeder::class);
    }

    public function test_get_pertanyaan_returns_all_categories_with_correct_counts(): void
    {
        $res = $this->getJson('/api/v1/kuisioner/pertanyaan');
        $res->assertStatus(200);
        $res->assertJsonStructure(['success', 'data']);

        $data = $res->json('data');
        $dpl = collect($data)->where('kategori', 'dpl');
        $mitra = collect($data)->where('kategori', 'mitra');
        $saran = collect($data)->where('kategori', 'saran');

        $this->assertCount(12, $dpl);
        $this->assertCount(13, $mitra);
        $this->assertCount(1, $saran);
    }

    public function test_crud_kategori_by_authenticated_user(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();
        $token = $admin->createToken('test')->plainTextToken;

        $resList = $this->getJson('/api/v1/kuisioner/kategori');
        $resList->assertStatus(200);
        $this->assertGreaterThanOrEqual(3, count($resList->json('data')));

        $resCreate = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/kuisioner/kategori', [
                'nama' => 'Evaluasi Fasilitas Kampus',
                'target_evaluasi' => 'umum',
                'is_active' => true,
            ]);

        $resCreate->assertStatus(201);
        $catId = $resCreate->json('data.id');
        $this->assertEquals('evaluasi-fasilitas-kampus', $resCreate->json('data.slug'));

        $resUpdate = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/kuisioner/kategori/{$catId}", [
                'is_active' => false,
            ]);

        $resUpdate->assertStatus(200);
        $this->assertFalse($resUpdate->json('data.is_active'));

        $resDelete = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/kuisioner/kategori/{$catId}");

        $resDelete->assertStatus(200);
        $this->assertNull(KuisionerKategori::find($catId));
    }

    public function test_crud_pertanyaan_by_authenticated_user(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();
        $token = $admin->createToken('test')->plainTextToken;

        $resCreate = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/kuisioner/pertanyaan', [
                'kategori' => 'dpl',
                'pertanyaan' => 'Pertanyaan uji coba baru.',
                'tipe_input' => 'likert_5',
            ]);

        $resCreate->assertStatus(201);
        $id = $resCreate->json('data.id');
        $this->assertEquals(13, $resCreate->json('data.nomor_urut'));

        $resUpdate = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/kuisioner/pertanyaan/{$id}", [
                'pertanyaan' => 'Pertanyaan uji coba diperbarui.',
            ]);

        $resUpdate->assertStatus(200);
        $this->assertEquals('Pertanyaan uji coba diperbarui.', $resUpdate->json('data.pertanyaan'));

        $resDelete = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/kuisioner/pertanyaan/{$id}");

        $resDelete->assertStatus(200);
        $this->assertNull(KuisionerPertanyaan::find($id));
    }

    public function test_get_rekap_returns_ranking_and_stats_with_anonymous_saran(): void
    {
        $res = $this->getJson('/api/v1/kuisioner/rekap');
        $res->assertStatus(200);
        $res->assertJsonStructure([
            'success',
            'data' => [
                'statistik' => [
                    'total_responden',
                    'total_dpl_dinilai',
                    'total_mitra_dinilai',
                    'rata_rata_fakultas_dpl',
                    'rata_rata_fakultas_mitra',
                ],
                'ranking_dpl',
                'ranking_mitra',
                'daftar_saran',
            ],
        ]);

        $rankingDpl = $res->json('data.ranking_dpl');
        if (! empty($rankingDpl)) {
            $firstDpl = $rankingDpl[0];
            $this->assertArrayNotHasKey('program_studi', $firstDpl);
            $this->assertArrayHasKey('nama', $firstDpl);
            $this->assertArrayHasKey('nip', $firstDpl);
            $this->assertArrayHasKey('total_distribusi', $firstDpl);
            $this->assertArrayHasKey('distribusi', $firstDpl['skor_per_pertanyaan'][0]);
        }

        $rankingMitra = $res->json('data.ranking_mitra');
        if (! empty($rankingMitra)) {
            $firstMitra = $rankingMitra[0];
            $this->assertArrayHasKey('ringkasan_kompensasi', $firstMitra);
            $this->assertArrayHasKey('kompensasi_diterima', $firstMitra);
            $this->assertArrayHasKey('rata_rata_nominal', $firstMitra);
            $this->assertArrayHasKey('rincian_kompensasi_responden', $firstMitra);
            $this->assertIsArray($firstMitra['rincian_kompensasi_responden']);
            $this->assertArrayHasKey('total_distribusi', $firstMitra);
            $this->assertArrayHasKey('distribusi', $firstMitra['skor_per_pertanyaan'][0]);
        }

        $daftarSaran = $res->json('data.daftar_saran');
        if (! empty($daftarSaran)) {
            $firstSaran = $daftarSaran[0];
            $this->assertArrayNotHasKey('nama_mahasiswa', $firstSaran);
            $this->assertArrayNotHasKey('nim', $firstSaran);
            $this->assertArrayHasKey('saran_masukan', $firstSaran);
            $this->assertArrayHasKey('responden_label', $firstSaran);
        }
    }

    public function test_mahasiswa_cannot_submit_if_no_dpl(): void
    {

        $pendaftaran = PendaftaranMbkm::whereNull('dpl_id')->firstOrFail();
        $mhs = User::findOrFail($pendaftaran->user_id);

        $token = $mhs->createToken('test_no_dpl')->plainTextToken;

        $resGet = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/kuisioner/mahasiswa');
        $resGet->assertStatus(200);
        $this->assertFalse($resGet->json('data.has_dpl'));

        $resPost = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/kuisioner/jawaban', [
                'jawaban_dpl' => ['1' => 5],
                'jawaban_mitra' => ['12' => 5],
            ]);

        $resPost->assertStatus(403);
        $resPost->assertJsonPath('success', false);
        $this->assertStringContainsString('Dosen Pembimbing Lapangan (DPL) belum ditentukan', $resPost->json('message'));
    }

    public function test_mahasiswa_can_get_kuisioner_and_submit_jawaban(): void
    {

        $pendaftaran = PendaftaranMbkm::whereNotNull('dpl_id')->firstOrFail();
        $mhs = User::findOrFail($pendaftaran->user_id);

        $token = $mhs->createToken('test_mhs')->plainTextToken;

        $resGet = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/kuisioner/mahasiswa');

        $resGet->assertStatus(200);
        $this->assertTrue($resGet->json('data.has_dpl'));
        $resGet->assertJsonStructure([
            'success',
            'data' => [
                'has_dpl',
                'mahasiswa',
                'pendaftaran',
                'kategoris',
                'has_submitted',
            ],
        ]);

        $kategoris = $resGet->json('data.kategoris');
        $this->assertIsArray($kategoris);

        $soalDpl = KuisionerPertanyaan::where('kategori', 'dpl')->where('tipe_input', 'likert_5')->get();
        $soalMitra = KuisionerPertanyaan::where('kategori', 'mitra')->where('tipe_input', 'likert_5')->get();

        $dplMap = [];
        foreach ($soalDpl as $s) {
            $dplMap[$s->id] = 5;
        }

        $mitraMap = [];
        foreach ($soalMitra as $s) {
            $mitraMap[$s->id] = 4;
        }

        $resPost = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/kuisioner/jawaban', [
                'jawaban_dpl' => $dplMap,
                'jawaban_mitra' => $mitraMap,
                'kompensasi_tipe' => ['Uang saku', 'Sertifikat'],
                'kompensasi_nominal' => 'Rp2.000.000 - Rp2.999.999',
                'saran_masukan' => 'Pengalaman magang yang sangat menyenangkan dan membangun.',
            ]);

        $resPost->assertStatus(200);
        $resPost->assertJsonPath('success', true);
        $this->assertEquals(5.00, $resPost->json('data.rata_rata_dpl'));
        $this->assertEquals(4.00, $resPost->json('data.rata_rata_mitra'));

        $resCheck = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/kuisioner/mahasiswa');
        $resCheck->assertStatus(200);
        $this->assertTrue($resCheck->json('data.has_submitted'));
        $this->assertNotNull($resCheck->json('data.jawaban'));
    }

    public function test_kuisioner_endpoints_support_pagination(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();
        $token = $admin->createToken('test_pagination')->plainTextToken;

        $resPertanyaan = $this->getJson('/api/v1/kuisioner/pertanyaan?page=1&per_page=5');
        $resPertanyaan->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonStructure(['data', 'pagination' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to']]);

        $resKategori = $this->getJson('/api/v1/kuisioner/kategori?page=1&per_page=2');
        $resKategori->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonStructure(['data', 'pagination']);

        $resRekap = $this->getJson('/api/v1/kuisioner/rekap?saran_page=1&saran_per_page=1');
        $resRekap->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pagination_saran.current_page', 1)
            ->assertJsonPath('data.pagination_saran.per_page', 1);

        $resJawaban = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/kuisioner/jawaban?page=1&per_page=2');
        $resJawaban->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonStructure(['data', 'pagination']);
    }
}
