<?php

namespace Tests\Feature;

use App\Models\MataKuliah;
use App\Models\PendaftaranMbkm;
use App\Models\Rkm;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MbkmFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(\Database\Seeders\PendaftaranMbkmSeeder::class);
        $this->seed(\Database\Seeders\RkmSeeder::class);
        $this->seed(\Database\Seeders\RkpDokumenSeeder::class);
    }

    public function test_master_data_endpoints(): void
    {
        $this->getJson('/api/v1/program-studi')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/v1/periode-akademik')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/v1/periode-akademik/aktif')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/v1/mitra')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/v1/mata-kuliah?prodi=Teknologi%20Informasi')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/v1/cpl')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/v1/paket-konversi')->assertOk()->assertJsonPath('success', true);
    }

    public function test_danang_can_access_available_courses_and_rkm(): void
    {

        $danang = User::whereIdentifier('242410102035')->firstOrFail();

        $response = $this->actingAs($danang, 'sanctum')->getJson('/api/v1/rkm/available-courses');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['paket_konversi', 'total_sks_pool', 'courses']]);

        $rkmResponse = $this->actingAs($danang, 'sanctum')->getJson('/api/v1/rkm/my-rkm');
        $rkmResponse->assertOk()->assertJsonPath('success', true);
    }

    public function test_rkm_selection_validates_max_20_sks(): void
    {
        $danang = User::whereIdentifier('242410102035')->firstOrFail();

        $allMks = MataKuliah::take(10)->pluck('id')->all();

        $response = $this->actingAs($danang, 'sanctum')->postJson('/api/v1/rkm/save-selection', [
            'selected_mata_kuliah_ids' => $allMks,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'ERR_RKM_SKS_LIMIT_EXCEEDED');
    }

    public function test_unverified_student_cannot_access_rkm(): void
    {

        $rizky = User::whereIdentifier('242410101044')->firstOrFail();

        $response = $this->actingAs($rizky, 'sanctum')->getJson('/api/v1/rkm/available-courses');
        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'ERR_RKM_PENDAFTARAN_NOT_VERIFIED');
    }

    public function test_student_cannot_edit_master_jobdesk_but_can_edit_custom(): void
    {

        $danang = User::whereIdentifier('242410102035')->firstOrFail();

        $mkIds = MataKuliah::take(2)->pluck('id')->all();
        $this->actingAs($danang, 'sanctum')->postJson('/api/v1/rkm/save-selection', [
            'selected_mata_kuliah_ids' => $mkIds,
        ])->assertOk();

        $rkm = Rkm::where('user_id', $danang->id)->firstOrFail();
        $item = $rkm->items()->firstOrFail();

        $masterJobdesk = $item->jobdesks()->where('origin', 'master_mk')->first();
        if ($masterJobdesk) {
            $resp = $this->actingAs($danang, 'sanctum')->putJson("/api/v1/rkm/jobdesks/{$masterJobdesk->id}", [
                'deskripsi' => 'Coba edit oleh mahasiswa',
            ]);
            $resp->assertStatus(403)
                ->assertJsonPath('error_code', 'ERR_JOBDESK_MASTER_LOCKED');
        }

        $addResp = $this->actingAs($danang, 'sanctum')->postJson("/api/v1/rkm/items/{$item->id}/jobdesk", [
            'deskripsi' => 'Jobdesk custom mandiri',
        ]);
        $addResp->assertStatus(201);
        $customId = $addResp->json('data.id');

        $editResp = $this->actingAs($danang, 'sanctum')->putJson("/api/v1/rkm/jobdesks/{$customId}", [
            'deskripsi' => 'Jobdesk custom revisi',
        ]);
        $editResp->assertOk();

        $delResp = $this->actingAs($danang, 'sanctum')->deleteJson("/api/v1/rkm/jobdesks/{$customId}");
        $delResp->assertOk();
    }

    public function test_approved_rkm_is_locked_for_student_mutations(): void
    {

        $aulia = User::whereIdentifier('232410103021')->firstOrFail();
        $rkm = Rkm::where('user_id', $aulia->id)->firstOrFail();
        $this->assertSame('Disetujui', $rkm->status);

        $resp = $this->actingAs($aulia, 'sanctum')->postJson('/api/v1/rkm/save-selection', [
            'selected_mata_kuliah_ids' => [1, 2],
        ]);
        $resp->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_RKM_LOCKED_APPROVED');

        $submitResp = $this->actingAs($aulia, 'sanctum')->postJson('/api/v1/rkm/submit');
        $submitResp->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_RKM_LOCKED_APPROVED');
    }

    public function test_pic_can_toggle_drop_edit_jobdesk_and_approve_rkm(): void
    {

        $habib = User::whereIdentifier('197805122005011002')->firstOrFail();

        $salsabila = User::whereIdentifier('232410102019')->firstOrFail();
        $pendaftaran = PendaftaranMbkm::where('user_id', $salsabila->id)->latest()->firstOrFail();
        $rkm = Rkm::where('pendaftaran_mbkm_id', $pendaftaran->id)->firstOrFail();
        $item = $rkm->items()->firstOrFail();

        $toggleResp = $this->actingAs($habib, 'sanctum')->patchJson("/api/v1/tim-mbkm/verifikasi-rkm/items/{$item->id}/toggle-drop");
        $toggleResp->assertOk()->assertJsonPath('data.status_mk', 'dropped');

        $restoreResp = $this->actingAs($habib, 'sanctum')->patchJson("/api/v1/tim-mbkm/verifikasi-rkm/items/{$item->id}/toggle-drop");
        $restoreResp->assertOk()->assertJsonPath('data.status_mk', 'aktif');

        $approveResp = $this->actingAs($habib, 'sanctum')->postJson("/api/v1/tim-mbkm/verifikasi-rkm/{$rkm->id}/keputusan", [
            'keputusan' => 'Disetujui',
            'catatan' => 'Semua susunan MK dan jobdesk disetujui.',
        ]);
        $approveResp->assertOk();

        $this->assertDatabaseHas('rkp_dokumens', [
            'rkm_id' => $rkm->id,
            'durasi_pelaksanaan' => '4 bulan',
        ]);
    }

    public function test_rkp_preview_and_metadata_update(): void
    {
        $rkm = Rkm::where('status', 'Disetujui')->firstOrFail();
        $habib = User::whereIdentifier('197805122005011002')->firstOrFail();

        $previewResp = $this->actingAs($habib, 'sanctum')->getJson("/api/v1/rkp/{$rkm->id}/preview");
        $previewResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'rkp_id',
                    'nomor_dokumen',
                    'status',
                    'metadata' => [
                        'durasi_pelaksanaan',
                        'semester_tahun_akademik',
                        'prasyarat_sks',
                        'nama_dpl',
                        'nip_dpl',
                        'nama_kaprodi',
                        'nip_kaprodi',
                    ],
                    'student_info',
                    'bagian_1_identitas',
                    'bagian_2_cpl',
                    'bagian_3_cpmk',
                    'bagian_4_pengalaman_pembelajaran',
                    'bagian_5_monitoring',
                    'bagian_6_asesmen',
                    'bagian_7_pengesahan',
                ],
            ]);

        $updateResp = $this->actingAs($habib, 'sanctum')->putJson("/api/v1/rkp/{$rkm->id}/metadata", [
            'durasi_pelaksanaan' => '5 bulan',
            'semester_tahun_akademik' => '5 / 2026/2027 Ganjil',
            'prasyarat_sks' => '800 SKS',
            'nama_dpl' => 'Dr. Habib, M.Kom',
            'nip_dpl' => '197805122005011002',
            'nama_kaprodi' => 'Priza Pandunata, S.Kom., M.Sc.',
            'nip_kaprodi' => '198301312015041001',
        ]);

        $updateResp->assertOk()
            ->assertJsonPath('data.prasyarat_sks', '80 SKS')
            ->assertJsonPath('data.durasi_pelaksanaan', '5 bulan');
    }

    public function test_plotting_tahap_2_validations(): void
    {
        $habib = User::whereIdentifier('197805122005011002')->firstOrFail();
        $sari = User::whereIdentifier('198003152006042001')->firstOrFail();

        $aulia = User::whereIdentifier('232410103021')->firstOrFail();
        $pendaftaran = PendaftaranMbkm::where('user_id', $aulia->id)->latest()->firstOrFail();
        $pendaftaran->update(['dpl_id' => $habib->id, 'penguji_semhas_id' => null]);

        $respSame = $this->actingAs($habib, 'sanctum')->postJson('/api/v1/plotting/tahap-2-dpl-penguji', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'dpl_id' => $habib->id,
            'penguji_semhas_id' => $habib->id,
        ]);
        $respSame->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_PLOTTING_DPL_EQUALS_PENGUJI');

        $pendaftaran->update(['sudah_isi_kuisioner' => true]);
        \App\Models\PengajuanSemhas::updateOrCreate(
            ['pendaftaran_mbkm_id' => $pendaftaran->id],
            ['status_kelayakan' => 'Layak Semhas']
        );

        $respDiff = $this->actingAs($habib, 'sanctum')->postJson('/api/v1/plotting/tahap-2-dpl-penguji', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'dpl_id' => $habib->id,
            'penguji_semhas_id' => $sari->id,
        ]);
        $respDiff->assertOk();
    }

    public function test_pdf_streaming_endpoints(): void
    {
        $pendaftaran = PendaftaranMbkm::firstOrFail();

        $transkrip = $this->get("/api/v1/pendaftaran-mbkm/{$pendaftaran->id}/file/transkrip?mode=inline");
        $transkrip->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $loa = $this->get("/api/v1/pendaftaran-mbkm/{$pendaftaran->id}/file/loa?mode=download");
        $loa->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $rkm = Rkm::where('status', 'Disetujui')->firstOrFail();
        $rkpPdf = $this->get("/api/v1/rkp/{$rkm->id}/pdf?mode=inline");
        $rkpPdf->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
