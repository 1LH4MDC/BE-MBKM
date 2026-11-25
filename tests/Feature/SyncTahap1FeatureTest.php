<?php

namespace Tests\Feature;

use App\Models\Mitra;
use App\Models\PaketKonversi;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\ProgramStudi;
use App\Models\Rkm;
use App\Models\RkmMataKuliah;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PendaftaranMbkmSeeder;
use Database\Seeders\RkmSeeder;
use Database\Seeders\RkpDokumenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SyncTahap1FeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(PendaftaranMbkmSeeder::class);
        $this->seed(RkmSeeder::class);
        $this->seed(RkpDokumenSeeder::class);
    }

    public function test_periode_akademik_standardized_json_response(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();

        $resAktif = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/periode-akademik/aktif');
        $resAktif->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'tahun_ajaran',
                    'semester',
                    'nama_lengkap',
                    'is_active',
                    'is_aktif',
                ],
            ]);

        $resTahunAjaran = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/tahun-ajaran');
        $resTahunAjaran->assertOk()
            ->assertJsonPath('success', true);

        $firstItem = $resTahunAjaran->json('data.0');
        $this->assertArrayHasKey('tahun_ajaran', $firstItem);
        $this->assertArrayHasKey('nama_lengkap', $firstItem);
        $this->assertArrayHasKey('is_active', $firstItem);
    }

    public function test_user_pagination_and_role_filtering(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();

        $res = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/users?page=1&per_page=10&role=Admin');
        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'from',
                    'to',
                ],
            ]);

        $this->assertEquals(10, $res->json('pagination.per_page'));
    }

    public function test_mitra_file_pks_upload_and_preview_url(): void
    {
        Storage::fake('public');
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();

        $file = UploadedFile::fake()->create('pks_mou.pdf', 200, 'application/pdf');

        $resStore = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/mitra', [
            'nama' => 'PT Mitra Baru Sinkron',
            'bidang_usaha' => 'Teknologi Informasi',
            'alamat' => 'Jl. Kalimantan No 37, Jember',
            'website' => 'https://mitrabaru.com',
            'narahubung_nama' => 'Budi Santoso',
            'narahubung_kontak' => '081234567890',
            'status' => 'Aktif',
            'file_pks' => $file,
        ]);

        $resStore->assertStatus(201)
            ->assertJsonPath('success', true);

        $mitraId = $resStore->json('data.id');
        $resDetail = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/mitra/{$mitraId}");
        $resDetail->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'nama',
                    'file_pks',
                    'file_pks_url',
                ],
            ]);
        $this->assertNotNull($resDetail->json('data.file_pks_url'));
    }

    public function test_pendaftaran_mbkm_revision_attachment_flow(): void
    {
        Storage::fake('public');
        $ketua = User::whereIdentifier('197805122005011002')->firstOrFail(); // Ketua Tim MBKM

        $pendaftaran = PendaftaranMbkm::whereNotNull('pic_verifikator_id')
            ->where('status', '!=', 'Terverifikasi')
            ->first();
        if (! $pendaftaran) {
            $pendaftaran = PendaftaranMbkm::whereNotNull('pic_verifikator_id')->firstOrFail();
            $pendaftaran->update(['status' => 'Menunggu Verifikasi']);
        }

        $revFile = UploadedFile::fake()->create('catatan_revisi.jpg', 300, 'image/jpeg');

        $resVerif = $this->actingAs($ketua, 'sanctum')->postJson("/api/v1/pendaftaran-mbkm/{$pendaftaran->id}/verifikasi", [
            'status' => 'Perbaikan',
            'catatan' => 'Mohon sesuaikan transkrip dengan semester terbaru.',
            'lampiran_revisi' => $revFile,
        ]);

        $resVerif->assertOk()
            ->assertJsonPath('success', true);

        $pendaftaran->refresh();
        $this->assertEquals('Perbaikan', $pendaftaran->status);
        $this->assertNotNull($pendaftaran->lampiran_revisi_path);
        $this->assertNotNull($pendaftaran->lampiran_revisi_url);
        $this->assertNotNull($pendaftaran->lampiran_revisi_name);
    }

    public function test_ensure_active_periode_blocks_mutations_on_archived_records(): void
    {
        $admin = User::whereIdentifier('admin@fasilkom.ac.id')->firstOrFail();

        // Create an inactive/archived period
        $archivedPeriode = PeriodeAkademik::create([
            'tahun_ajaran' => '2023/2024',
            'semester' => 'Genap',
            'is_aktif' => false,
        ]);

        $mhs = User::whereIdentifier('242410101044')->firstOrFail();
        $prodi = ProgramStudi::firstOrFail();

        $archivedPendaftaran = PendaftaranMbkm::create([
            'user_id' => $mhs->id,
            'nim' => $mhs->nim_nip,
            'nama_lengkap' => $mhs->nama,
            'program_studi_id' => $prodi->id,
            'mitra_magang_text' => 'PT Lama',
            'posisi_magang' => 'Legacy Role',
            'periode_akademik_id' => $archivedPeriode->id,
            'transkrip_file_name' => 'transkrip.pdf',
            'transkrip_file_path' => 'pendaftaran/transkrip.pdf',
            'transkrip_file_size' => '100 KB',
            'loa_file_name' => 'loa.pdf',
            'loa_file_path' => 'pendaftaran/loa.pdf',
            'loa_file_size' => '100 KB',
            'status' => 'Menunggu Verifikasi',
            'is_plotted_pic' => true,
            'pic_verifikator_id' => $admin->id,
        ]);

        // Attempting to verify or mutate archived record should yield 403 Forbidden
        $res = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/pendaftaran-mbkm/{$archivedPendaftaran->id}/verifikasi", [
            'status' => 'Terverifikasi',
            'link_wa_grup' => 'https://chat.whatsapp.com/test',
        ]);

        $res->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_ARCHIVE_READ_ONLY');
    }

    public function test_rkm_pic_verification_actions(): void
    {
        $ketua = User::whereIdentifier('197805122005011002')->firstOrFail();
        $rkm = Rkm::with('items')->firstOrFail();
        $mk = \App\Models\MataKuliah::firstOrFail();

        // 1. Queue list endpoint
        $resQueue = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/tim-mbkm/verifikasi-rkm');
        $resQueue->assertOk()->assertJsonPath('success', true);

        // 2. Add course item
        $resAddItem = $this->actingAs($ketua, 'sanctum')->postJson('/api/v1/tim-mbkm/verifikasi-rkm/items', [
            'rkm_id' => $rkm->id,
            'mata_kuliah_id' => $mk->id,
            'kompetensi' => 'Kompetensi pengujian sistem',
            'jobdesks' => ['Jobdesk testing 1', 'Jobdesk testing 2'],
        ]);

        $resAddItem->assertStatus(201)->assertJsonPath('success', true);
        $mkId = $resAddItem->json('data.id');

        // 3. Edit course competency
        $resEdit = $this->actingAs($ketua, 'sanctum')->putJson("/api/v1/tim-mbkm/verifikasi-rkm/items/{$mkId}", [
            'kompetensi' => 'Kompetensi terupdate oleh PIC',
        ]);
        $resEdit->assertOk()->assertJsonPath('success', true);

        // 4. Toggle drop course item
        $resDrop = $this->actingAs($ketua, 'sanctum')->patchJson("/api/v1/tim-mbkm/verifikasi-rkm/items/{$mkId}/toggle-drop");
        $resDrop->assertOk()->assertJsonPath('success', true);
        $this->assertEquals('dropped', $resDrop->json('data.status_mk'));

        // 5. PIC Decision
        $resKeputusan = $this->actingAs($ketua, 'sanctum')->postJson("/api/v1/tim-mbkm/verifikasi-rkm/{$rkm->id}/keputusan", [
            'status' => 'Disetujui',
            'catatan' => 'RKM telah disetujui oleh PIC.',
        ]);
        $resKeputusan->assertOk()->assertJsonPath('success', true);
    }
}
