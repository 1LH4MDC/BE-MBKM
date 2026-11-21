<?php

namespace Tests\Feature;

use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\ProgramStudi;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PendaftaranMbkmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Tahap1BugFixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(PendaftaranMbkmSeeder::class);
    }

    public function test_model_auto_syncs_is_plotted_pic_and_guards_unplotted_verification(): void
    {
        $mhs = User::whereIdentifier('242410101044')->firstOrFail();
        $prodi = ProgramStudi::firstOrFail();
        $periode = PeriodeAkademik::where('is_aktif', true)->firstOrFail();
        PendaftaranMbkm::where('user_id', $mhs->id)->delete();

        $p = PendaftaranMbkm::create([
            'user_id' => $mhs->id,
            'nim' => $mhs->nim_nip,
            'nama_lengkap' => $mhs->nama,
            'program_studi_id' => $prodi->id,
            'mitra_magang_text' => 'PT Testing',
            'posisi_magang' => 'Developer',
            'periode_akademik_id' => $periode->id,
            'transkrip_file_name' => 'transkrip.pdf',
            'transkrip_file_path' => 'pendaftaran/transkrip.pdf',
            'transkrip_file_size' => '100 KB',
            'loa_file_name' => 'loa.pdf',
            'loa_file_path' => 'pendaftaran/loa.pdf',
            'loa_file_size' => '100 KB',
            'status' => 'Terverifikasi',
            'is_plotted_pic' => false,
            'pic_verifikator_id' => null,
        ]);

        $p->refresh();
        $this->assertEquals('Menunggu Verifikasi', $p->status);
        $this->assertFalse($p->is_plotted_pic);

        $dosen = User::whereIdentifier('197805122005011002')->firstOrFail();
        $p->update(['pic_verifikator_id' => $dosen->id]);
        $p->refresh();
        $this->assertTrue($p->is_plotted_pic);
    }

    public function test_verifikasi_rejected_with_422_if_pic_not_assigned(): void
    {
        $dosen = User::whereIdentifier('197805122005011002')->firstOrFail();
        $mhs = User::whereIdentifier('242410101044')->firstOrFail();
        $prodi = ProgramStudi::firstOrFail();
        $periode = PeriodeAkademik::where('is_aktif', true)->firstOrFail();
        PendaftaranMbkm::where('user_id', $mhs->id)->delete();

        $p = PendaftaranMbkm::create([
            'user_id' => $mhs->id,
            'nim' => $mhs->nim_nip,
            'nama_lengkap' => $mhs->nama,
            'program_studi_id' => $prodi->id,
            'mitra_magang_text' => 'PT Testing',
            'posisi_magang' => 'Developer',
            'periode_akademik_id' => $periode->id,
            'transkrip_file_name' => 'transkrip.pdf',
            'transkrip_file_path' => 'pendaftaran/transkrip.pdf',
            'transkrip_file_size' => '100 KB',
            'loa_file_name' => 'loa.pdf',
            'loa_file_path' => 'pendaftaran/loa.pdf',
            'loa_file_size' => '100 KB',
            'status' => 'Menunggu Verifikasi',
            'is_plotted_pic' => false,
            'pic_verifikator_id' => null,
        ]);

        $res = $this->actingAs($dosen, 'sanctum')->postJson("/api/v1/pendaftaran-mbkm/{$p->id}/verifikasi", [
            'status' => 'Terverifikasi',
            'link_wa_grup' => 'https://chat.whatsapp.com/test',
        ]);

        $res->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_VERIFIKASI_NO_PIC_ASSIGNED');
    }

    public function test_plotting_endpoints_return_pic_verifikator_and_sync(): void
    {
        $ketua = User::whereIdentifier('197805122005011002')->firstOrFail();

        $resTahap1 = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/plotting/tahap-1-pic');
        $resTahap1->assertOk()->assertJsonPath('success', true);
        $data1 = $resTahap1->json('data');
        $this->assertNotEmpty($data1);
        $this->assertArrayHasKey('pic_verifikator', $data1[0]);
        $this->assertArrayHasKey('pic_verifikator_nama', $data1[0]);

        $resTahap2 = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/plotting/tahap-2-dpl-penguji');
        $resTahap2->assertOk()->assertJsonPath('success', true);
        $data2 = $resTahap2->json('data');
        if (! empty($data2)) {
            $this->assertArrayHasKey('pic_verifikator', $data2[0]);
            $this->assertArrayHasKey('pic_verifikator_nama', $data2[0]);
            $this->assertArrayHasKey('is_plotted_pic', $data2[0]);
        }
    }

    public function test_my_rkm_returns_pendaftaran_with_mitra_and_pic(): void
    {
        $danang = User::whereIdentifier('242410102035')->firstOrFail();

        $res = $this->actingAs($danang, 'sanctum')->getJson('/api/v1/rkm/my-rkm');
        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'pendaftaran' => [
                        'id',
                        'posisi_magang',
                        'mitra_magang_text',
                        'mitra',
                        'pic_verifikator',
                    ],
                ],
            ]);

        $this->assertNotEmpty($res->json('data.pendaftaran.mitra_magang_text'));
        $this->assertNotNull($res->json('data.pendaftaran.pic_verifikator'));
    }
}
