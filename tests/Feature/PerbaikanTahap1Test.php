<?php

namespace Tests\Feature;

use App\Models\MataKuliah;
use App\Models\PendaftaranMbkm;
use App\Models\PengajuanSemhas;
use App\Models\PenilaianSupervisor;
use App\Models\PenilaianSupervisorMataKuliah;
use App\Models\PeriodeAkademik;
use App\Models\ProgramStudi;
use App\Models\Rkm;
use App\Models\RkmJobdesk;
use App\Models\RkmMataKuliah;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PendaftaranMbkmSeeder;
use Database\Seeders\RkmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerbaikanTahap1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(PendaftaranMbkmSeeder::class);
        $this->seed(RkmSeeder::class);
    }

    public function test_verifikasi_mbkm_terverifikasi_locked_permanently_while_ditolak_and_perbaikan_remain_editable(): void
    {
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $pic = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $mhs = User::create([
            'nama' => 'Mahasiswa Uji Kunci MBKM',
            'email' => 'mhs.kunci.mbkm@fasilkom.ac.id',
            'nim_nip' => '242499990001',
            'password' => bcrypt('password123'),
        ]);
        $roleMhs = \App\Models\Role::firstOrCreate(['name' => 'Mahasiswa']);
        $mhs->roles()->syncWithoutDetaching([$roleMhs->id]);
        $prodi = ProgramStudi::firstOrFail();
        $periode = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $p = PendaftaranMbkm::create([
            'user_id' => $mhs->id,
            'nim' => '242499990001',
            'nama_lengkap' => 'Mahasiswa Uji Kunci MBKM',
            'program_studi_id' => $prodi->id,
            'mitra_magang_text' => 'PT Kunci Digital',
            'posisi_magang' => 'Backend Engineer',
            'periode_akademik_id' => $periode->id,
            'pic_verifikator_id' => $pic->id,
            'is_plotted_pic' => true,
            'transkrip_file_name' => 'transkrip.pdf',
            'transkrip_file_path' => 'pendaftaran/transkrip.pdf',
            'transkrip_file_size' => '100 KB',
            'loa_file_name' => 'loa.pdf',
            'loa_file_path' => 'pendaftaran/loa.pdf',
            'loa_file_size' => '100 KB',
            'status' => 'Menunggu Verifikasi',
        ]);

        // 1. Ditolak -> TIDAK dikunci, masih bisa diproses ulang
        $this->actingAs($ketua, 'sanctum')
            ->postJson("/api/v1/pendaftaran-mbkm/{$p->id}/verifikasi", [
                'status' => 'Ditolak',
                'catatan' => 'Berkas perlu tinjauan ulang.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Ditolak');

        // 2. Perbaikan -> TIDAK dikunci, masih bisa diproses menjadi Terverifikasi
        $this->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/pendaftaran-mbkm/{$p->id}/verifikasi", [
                'status' => 'Perbaikan',
                'catatan' => 'Perbaiki halaman 1 LoA.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Perbaikan');

        // 3. Terverifikasi -> Sukses
        $this->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/pendaftaran-mbkm/{$p->id}/verifikasi", [
                'status' => 'Terverifikasi',
                'link_wa_grup' => 'https://chat.whatsapp.com/TestGroup123',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Terverifikasi');

        // 4. Ubah ulang setelah Terverifikasi -> DITOLAK 422 permanen
        $this->actingAs($ketua, 'sanctum')
            ->postJson("/api/v1/pendaftaran-mbkm/{$p->id}/verifikasi", [
                'status' => 'Perbaikan',
                'catatan' => 'Coba buka ulang setelah terverifikasi.',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_VERIFIKASI_MBKM_LOCKED');
    }

    public function test_verifikasi_rkm_disetujui_locks_all_mutations_while_perbaikan_remains_editable(): void
    {
        $pic = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $rkm = Rkm::with('items.jobdesks')->firstOrFail();
        $rkm->update(['status' => 'Diajukan']);

        $firstItem = $rkm->items->firstOrFail();
        $otherMk = MataKuliah::whereNotIn('id', $rkm->items->pluck('mata_kuliah_id')->filter())->firstOrFail();

        $jobdesk = RkmJobdesk::firstOrCreate([
            'rkm_mata_kuliah_id' => $firstItem->id,
            'urutan' => 1,
        ], [
            'deskripsi' => 'Analisis kebutuhan sistem.',
            'origin' => 'custom',
        ]);

        // 1. Perbaikan RKM -> Masih boleh edit kompetensi dan ubah keputusan lagi
        $this->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/tim-mbkm/verifikasi-rkm/{$rkm->id}/keputusan", [
                'keputusan' => 'Perbaikan RKM',
                'catatan' => 'Mohon perjelas deskripsi kompetensi.',
            ])
            ->assertOk();

        $this->actingAs($pic, 'sanctum')
            ->putJson("/api/v1/tim-mbkm/verifikasi-rkm/items/{$firstItem->id}", [
                'kompetensi' => 'Kompetensi diperbarui saat Perbaikan RKM',
            ])
            ->assertOk();

        // 2. Setujui RKM -> menjadi permanen Disetujui
        $this->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/tim-mbkm/verifikasi-rkm/{$rkm->id}/keputusan", [
                'keputusan' => 'Disetujui',
            ])
            ->assertOk();

        // 3. Semua endpoint mutasi RKM wajib mengembalikan 422 ERR_RKM_FINAL_LOCKED
        $this->actingAs($pic, 'sanctum')
            ->postJson('/api/v1/tim-mbkm/verifikasi-rkm/items', [
                'rkm_id' => $rkm->id,
                'mata_kuliah_id' => $otherMk->id,
                'kompetensi' => 'Coba tambah saat disetujui',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_RKM_FINAL_LOCKED');

        $this->actingAs($pic, 'sanctum')
            ->putJson("/api/v1/tim-mbkm/verifikasi-rkm/items/{$firstItem->id}", [
                'kompetensi' => 'Coba ubah kompetensi saat disetujui',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_RKM_FINAL_LOCKED');

        $this->actingAs($pic, 'sanctum')
            ->patchJson("/api/v1/tim-mbkm/verifikasi-rkm/items/{$firstItem->id}/toggle-drop")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_RKM_FINAL_LOCKED');

        $this->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/tim-mbkm/verifikasi-rkm/items/{$firstItem->id}/jobdesk", [
                'deskripsi' => 'Coba tambah jobdesk saat disetujui',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_RKM_FINAL_LOCKED');

        $this->actingAs($pic, 'sanctum')
            ->putJson("/api/v1/tim-mbkm/verifikasi-rkm/jobdesks/{$jobdesk->id}", [
                'deskripsi' => 'Coba edit jobdesk saat disetujui',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_RKM_FINAL_LOCKED');

        $this->actingAs($pic, 'sanctum')
            ->deleteJson("/api/v1/tim-mbkm/verifikasi-rkm/jobdesks/{$jobdesk->id}")
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_RKM_FINAL_LOCKED');

        $this->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/tim-mbkm/verifikasi-rkm/{$rkm->id}/keputusan", [
                'keputusan' => 'Perbaikan RKM',
                'catatan' => 'Coba batalkan persetujuan',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_RKM_FINAL_LOCKED');
    }

    public function test_semhas_pic_requires_complete_supervisor_scores_and_locks_both_permanently(): void
    {
        $pic = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $pendaftaran = PendaftaranMbkm::with(['rkm.items', 'user'])->where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update([
            'pic_verifikator_id' => $pic->id,
            'periode_akademik_id' => $periodeAktif->id,
            'sudah_isi_kuisioner' => true,
        ]);

        // Pastikan RKM punya minimal 2 item aktif
        $activeItems = $pendaftaran->rkm->items->whereNotIn('status_mk', ['drop', 'dropped'])->values();
        if ($activeItems->count() < 2) {
            $mkExtra = MataKuliah::whereNotIn('id', $activeItems->pluck('mata_kuliah_id')->filter())->firstOrFail();
            RkmMataKuliah::create([
                'rkm_id' => $pendaftaran->rkm->id,
                'mata_kuliah_id' => $mkExtra->id,
                'kode_mk' => $mkExtra->kode,
                'nama_mk' => $mkExtra->nama_mk,
                'sks' => $mkExtra->sks,
                'durasi_jam' => $mkExtra->sks * 40,
                'kompetensi' => 'Tambahan uji',
                'status_mk' => 'aktif',
            ]);
            $pendaftaran->load('rkm.items');
            $activeItems = $pendaftaran->rkm->items->whereNotIn('status_mk', ['drop', 'dropped'])->values();
        }

        PenilaianSupervisor::where('pendaftaran_mbkm_id', $pendaftaran->id)->delete();

        $semhas = PengajuanSemhas::updateOrCreate(['pendaftaran_mbkm_id' => $pendaftaran->id], [
            'periode_akademik_id' => $periodeAktif->id,
            'laporan_akhir_path' => 'dummy_lap.pdf',
            'logbook_fb07_path' => 'dummy_fb07.pdf',
            'nilai_supervisor_path' => 'dummy_spv.pdf',
            'status_kelayakan' => 'Menunggu Verifikasi',
            'is_override_ketua' => false,
        ]);

        // 1. Layak Semhas TANPA nilai supervisor -> 422
        $this->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/semhas/{$semhas->id}/verifikasi-pic", [
                'status_kelayakan' => 'Layak Semhas',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_SEMHAS_NILAI_SUPERVISOR_BELUM_LENGKAP')
            ->assertJsonPath('message', 'Lengkapi Nama Supervisor dan seluruh nilai mata kuliah Supervisor terlebih dahulu sebelum menetapkan Layak Semhas.');

        // 2. Hanya sebagian nilai supervisor terisi -> tetap ditolak 422
        $firstOnly = $activeItems->first();
        $spv = PenilaianSupervisor::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $periodeAktif->id,
            'pic_id' => $pic->id,
            'nama_supervisor' => 'Pak Hendro',
            'rata_rata_nilai' => 85,
        ]);
        PenilaianSupervisorMataKuliah::create([
            'penilaian_supervisor_id' => $spv->id,
            'mata_kuliah_id' => $firstOnly->mata_kuliah_id,
            'kode_mk' => $firstOnly->kode_mk,
            'nama_mk' => $firstOnly->nama_mk,
            'sks' => $firstOnly->sks,
            'nilai' => 85,
        ]);

        $this->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/semhas/{$semhas->id}/verifikasi-pic", [
                'status_kelayakan' => 'Layak Semhas',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_SEMHAS_NILAI_SUPERVISOR_BELUM_LENGKAP');

        // 3. Lengkapi seluruh nilai MK supervisor via endpoint -> Sukses 200
        $allPayload = $activeItems->map(fn ($it) => [
            'mata_kuliah_id' => $it->mata_kuliah_id,
            'kode_mk' => $it->kode_mk,
            'nama_mk' => $it->nama_mk,
            'sks' => $it->sks,
            'nilai' => 90,
        ])->values()->all();

        $this->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/penilaian-akhir/supervisor/{$pendaftaran->id}", [
                'nama_supervisor' => 'Pak Hendro S.Kom',
                'nilai_mata_kuliahs' => $allPayload,
            ])
            ->assertOk();

        // 4. PIC tetapkan Layak Semhas -> Sukses 200
        $this->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/semhas/{$semhas->id}/verifikasi-pic", [
                'status_kelayakan' => 'Layak Semhas',
            ])
            ->assertOk()
            ->assertJsonPath('data.status_kelayakan', 'Layak Semhas');

        // 5. Setelah Layak Semhas: form Supervisor & verifikasi Semhas & kelayakan Ketua terkunci permanen (422)
        $this->actingAs($pic, 'sanctum')
            ->getJson("/api/v1/penilaian-akhir/supervisor/{$pendaftaran->id}")
            ->assertOk()
            ->assertJsonPath('data.can_edit', false)
            ->assertJsonPath('data.is_semhas_locked', true);

        $this->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/penilaian-akhir/supervisor/{$pendaftaran->id}", [
                'nama_supervisor' => 'Coba Edit',
                'nilai_mata_kuliahs' => $allPayload,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_NILAI_SPV_LOCKED');

        $this->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/semhas/{$semhas->id}/verifikasi-pic", [
                'status_kelayakan' => 'Perbaikan Berkas',
                'catatan' => 'Coba buka ulang',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_SEMHAS_FINAL_LOCKED');

        $this->actingAs($ketua, 'sanctum')
            ->postJson("/api/v1/semhas/{$semhas->id}/override-ketua", [
                'catatan' => 'Coba ubah ulang setelah final',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_SEMHAS_FINAL_LOCKED');

        if ($pendaftaran->user) {
            Storage::fake('public');
            $this->actingAs($pendaftaran->user, 'sanctum')
                ->postJson('/api/v1/semhas/submit', [
                    'laporan_akhir' => UploadedFile::fake()->create('reupload.pdf', 100, 'application/pdf'),
                ])
                ->assertStatus(422)
                ->assertJsonPath('error_code', 'ERR_SEMHAS_FINAL_LOCKED');
        }
    }

    public function test_ketua_kelayakan_semhas_without_override_wording_and_locked_after_approval(): void
    {
        $pic = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $pendaftaran = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update([
            'pic_verifikator_id' => $pic->id,
            'periode_akademik_id' => $periodeAktif->id,
        ]);

        $semhas = PengajuanSemhas::updateOrCreate(['pendaftaran_mbkm_id' => $pendaftaran->id], [
            'periode_akademik_id' => $periodeAktif->id,
            'laporan_akhir_path' => 'dummy.pdf',
            'logbook_fb07_path' => 'dummy.pdf',
            'nilai_supervisor_path' => 'dummy.pdf',
            'status_kelayakan' => 'Tidak Layak',
            'is_override_ketua' => false,
        ]);

        $res = $this->actingAs($ketua, 'sanctum')
            ->postJson("/api/v1/semhas/{$semhas->id}/override-ketua", [
                'catatan' => 'Berkas sudah diverifikasi ulang oleh Ketua Tim MBKM.',
            ]);

        $res->assertOk()
            ->assertJsonPath('data.status_kelayakan', 'Layak Semhas');
        $this->assertStringNotContainsStringIgnoringCase('override', (string) $res->json('message'));

        // Pemanggilan ulang ditolak permanen
        $this->actingAs($ketua, 'sanctum')
            ->postJson("/api/v1/semhas/{$semhas->id}/override-ketua", [
                'catatan' => 'Coba ubah catatan kedua kali.',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_SEMHAS_FINAL_LOCKED');
    }

    public function test_mata_kuliah_index_case_insensitive_prodi_returns_prodi_and_fakultas_courses(): void
    {
        $pic = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $prodiTi = ProgramStudi::where('nama', 'like', '%Teknologi Informasi%')->firstOrFail();

        $res = $this->actingAs($pic, 'sanctum')
            ->getJson('/api/v1/mata-kuliah?all=true&prodi=teknologi%20informasi');

        $res->assertOk();
        $data = collect($res->json('data'));
        $this->assertNotEmpty($data);
        $this->assertTrue($data->contains(fn ($mk) => strtolower((string) ($mk['lingkup'] ?? '')) === 'fakultas'));
        $this->assertTrue($data->contains(fn ($mk) => (int) ($mk['program_studi_id'] ?? 0) === (int) $prodiTi->id));
    }

    public function test_validation_error_messages_are_strictly_indonesian(): void
    {
        Storage::fake('local');
        $mhs = User::whereHas('roles', fn ($q) => $q->where('name', 'Mahasiswa'))->firstOrFail();
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $pic = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $prodi = ProgramStudi::firstOrFail();
        $periode = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        // 1. Pendaftaran file > 5MB -> pesan bahasa Indonesia
        PendaftaranMbkm::where('user_id', $mhs->id)->delete();
        $bigFile = UploadedFile::fake()->create('transkrip_besar.pdf', 6000, 'application/pdf');
        $validLoa = UploadedFile::fake()->create('loa.pdf', 100, 'application/pdf');

        $resUpload = $this->actingAs($mhs, 'sanctum')->postJson('/api/v1/pendaftaran-mbkm', [
            'program_studi_id' => $prodi->id,
            'mitra_magang_text' => 'PT Uji Bahasa',
            'posisi_magang' => 'Frontend',
            'transkrip_file' => $bigFile,
            'loa_file' => $validLoa,
        ]);

        $resUpload->assertStatus(422);
        $msgUpload = (string) ($resUpload->json('errors.transkrip_file.0') ?? $resUpload->json('message'));
        $this->assertStringContainsString('5 MB', $msgUpload);
        $this->assertStringNotContainsStringIgnoringCase('must not be greater than', $msgUpload);

        // 2. Link WA grup bukan URL valid -> pesan bahasa Indonesia
        $p = PendaftaranMbkm::create([
            'user_id' => $mhs->id,
            'nim' => '242499990002',
            'nama_lengkap' => 'Mhs Uji Validasi',
            'program_studi_id' => $prodi->id,
            'mitra_magang_text' => 'PT Validasi',
            'posisi_magang' => 'QA',
            'periode_akademik_id' => $periode->id,
            'pic_verifikator_id' => $pic->id,
            'is_plotted_pic' => true,
            'transkrip_file_name' => 't.pdf',
            'transkrip_file_path' => 'pendaftaran/t.pdf',
            'transkrip_file_size' => '100 KB',
            'loa_file_name' => 'l.pdf',
            'loa_file_path' => 'pendaftaran/l.pdf',
            'loa_file_size' => '100 KB',
            'status' => 'Menunggu Verifikasi',
        ]);

        $resUrl = $this->actingAs($ketua, 'sanctum')->postJson("/api/v1/pendaftaran-mbkm/{$p->id}/verifikasi", [
            'status' => 'Terverifikasi',
            'link_wa_grup' => 'bukan-url-valid',
        ]);

        $resUrl->assertStatus(422);
        $msgUrl = (string) ($resUrl->json('errors.link_wa_grup.0') ?? $resUrl->json('message'));
        $this->assertStringContainsString('URL', $msgUrl);
        $this->assertStringNotContainsStringIgnoringCase('must be a valid URL', $msgUrl);

        // 3. Wajib isi -> pesan bahasa Indonesia
        $resReq = $this->actingAs($ketua, 'sanctum')->postJson("/api/v1/pendaftaran-mbkm/{$p->id}/verifikasi", [
            'status' => 'Perbaikan',
            'catatan' => '',
        ]);
        $resReq->assertStatus(422);
        $msgReq = (string) ($resReq->json('errors.catatan.0') ?? $resReq->json('message'));
        $this->assertStringContainsString('wajib diisi', $msgReq);
        $this->assertStringNotContainsStringIgnoringCase('field is required', $msgReq);
    }
}
