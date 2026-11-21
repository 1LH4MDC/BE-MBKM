<?php

namespace Tests\Feature;

use App\Models\BimbinganMagang;
use App\Models\DplPeriodeLink;
use App\Models\KuisionerJawaban;
use App\Models\KuisionerKategori;
use App\Models\KuisionerPertanyaan;
use App\Models\KuisionerSesi;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\ProgramStudi;
use App\Models\Rkm;
use App\Models\RkmMataKuliah;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PendaftaranMbkmSeeder;
use Database\Seeders\RkmSeeder;
use Database\Seeders\RkpDokumenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SyncTahap2FeatureTest extends TestCase
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

    public function test_monitoring_dpl_mahasiswa_list_and_whatsapp_sync(): void
    {
        $dplUser = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        // Cari atau set pendaftaran bimbingan dosen tersebut
        $pendaftaran = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update([
            'dpl_id' => $dplUser->id,
            'periode_akademik_id' => $periodeAktif->id,
            'link_logbook' => 'https://docs.google.com/document/d/sample-logbook',
        ]);

        // Simpan WhatsApp DPL via POST /dpl/link-wa
        $resWa = $this->actingAs($dplUser, 'sanctum')->postJson('/api/v1/monitoring-bimbingan/dpl/link-wa', [
            'link_wa' => 'https://chat.whatsapp.com/samplegroup123',
            'periode_akademik_id' => $periodeAktif->id,
        ]);
        $resWa->assertOk()
            ->assertJsonPath('success', true);

        // Verifikasi tersimpan di dpl_periode_links dan sync ke pendaftaran_mbkms
        $this->assertDatabaseHas('dpl_periode_links', [
            'dpl_id' => $dplUser->id,
            'periode_akademik_id' => $periodeAktif->id,
            'link_wa' => 'https://chat.whatsapp.com/samplegroup123',
        ]);
        $pendaftaran->refresh();
        $this->assertEquals('https://chat.whatsapp.com/samplegroup123', $pendaftaran->link_wa_dpl);

        // GET /dpl/mahasiswa
        $resList = $this->actingAs($dplUser, 'sanctum')->getJson('/api/v1/monitoring-bimbingan/dpl/mahasiswa');
        $resList->assertOk()
            ->assertJsonPath('success', true);

        $firstItem = collect($resList->json('data'))->firstWhere('id', (string) $pendaftaran->id);
        $this->assertNotNull($firstItem);
        $this->assertEquals('https://chat.whatsapp.com/samplegroup123', $firstItem['link_wa_dpl']);
        $this->assertEquals('https://docs.google.com/document/d/sample-logbook', $firstItem['link_logbook']);
        $this->assertNotEmpty($firstItem['tahun_ajaran']);
    }

    public function test_monitoring_bimbingan_session_lifecycle_and_photo_upload(): void
    {
        Storage::fake('public');

        $dplUser = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();
        $mhsUser = User::whereHas('roles', fn ($q) => $q->where('name', 'Mahasiswa'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $pendaftaran = PendaftaranMbkm::where('user_id', $mhsUser->id)->first();
        if (! $pendaftaran) {
            $pendaftaran = PendaftaranMbkm::firstOrFail();
            $pendaftaran->user_id = $mhsUser->id;
        }
        $pendaftaran->update([
            'status' => 'Terverifikasi',
            'dpl_id' => $dplUser->id,
            'periode_akademik_id' => $periodeAktif->id,
        ]);

        // 1. DPL tambah sesi bimbingan 1
        $resStore1 = $this->actingAs($dplUser, 'sanctum')->postJson("/api/v1/monitoring-bimbingan/mahasiswa/{$pendaftaran->id}", [
            'tanggal_bimbingan' => '2026-11-21',
            'catatan_bimbingan' => 'Diskusi pembagian tugas modul auth dan arsitektur backend.',
        ]);
        $resStore1->assertStatus(201)
            ->assertJsonPath('success', true);
        $bimbingan1Id = $resStore1->json('data.id');

        // 2. DPL tambah sesi bimbingan 2
        $resStore2 = $this->actingAs($dplUser, 'sanctum')->postJson("/api/v1/monitoring-bimbingan/mahasiswa/{$pendaftaran->id}", [
            'tanggal_bimbingan' => '2026-11-28',
            'catatan_bimbingan' => 'Review progress API sprint 1.',
        ]);
        $resStore2->assertStatus(201)
            ->assertJsonPath('success', true);
        $bimbingan2Id = $resStore2->json('data.id');

        // 3. Mahasiswa upload foto bukti pada sesi bimbingan 1
        $fileMhs = UploadedFile::fake()->image('bukti_mhs.jpg', 600, 400);
        $resFotoMhs = $this->actingAs($mhsUser, 'sanctum')->postJson("/api/v1/monitoring-bimbingan/{$bimbingan1Id}/foto-bukti", [
            'foto_bukti' => $fileMhs,
        ]);
        $resFotoMhs->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.foto_uploaded_by_role', 'Mahasiswa');

        // 4. DPL upload ganti foto bukti pada sesi bimbingan 1
        $fileDpl = UploadedFile::fake()->image('bukti_dpl.jpg', 800, 600);
        $resFotoDpl = $this->actingAs($dplUser, 'sanctum')->postJson("/api/v1/monitoring-bimbingan/{$bimbingan1Id}/foto-bukti", [
            'foto_bukti' => $fileDpl,
        ]);
        $resFotoDpl->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.foto_uploaded_by_role', 'DPL');

        // 5. Safe resequencing on delete: hapus bimbingan 1, bimbingan 2 harus resequence jadi 1
        $resDelete = $this->actingAs($dplUser, 'sanctum')->deleteJson("/api/v1/monitoring-bimbingan/{$bimbingan1Id}");
        $resDelete->assertOk()
            ->assertJsonPath('success', true);

        $bimbingan2 = BimbinganMagang::findOrFail($bimbingan2Id);
        $this->assertEquals(1, $bimbingan2->bimbingan_ke);

        // 6. Mahasiswa set link logbook via POST /my/link-logbook
        $resLogbook = $this->actingAs($mhsUser, 'sanctum')->postJson('/api/v1/monitoring-bimbingan/my/link-logbook', [
            'link_logbook' => 'https://docs.google.com/document/d/logbook-mhs-aktif',
        ]);
        $resLogbook->assertOk()
            ->assertJsonPath('success', true);
        $pendaftaran->refresh();
        $this->assertEquals('https://docs.google.com/document/d/logbook-mhs-aktif', $pendaftaran->link_logbook);
    }

    public function test_gpm_kuisioner_lifecycle_and_modifications_lock(): void
    {
        $gpm = User::whereHas('roles', fn ($q) => $q->where('name', 'GPM'))->firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        // Pastikan tidak ada sesi lama aktif
        KuisionerSesi::where('periode_akademik_id', $periodeAktif->id)->delete();

        // 1. Session status awal (draft)
        $resStatus = $this->actingAs($gpm, 'sanctum')->getJson('/api/v1/kuisioner/session-status');
        $resStatus->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft');

        // Alias /kuisioner/sesi
        $resSesiAlias = $this->actingAs($gpm, 'sanctum')->getJson('/api/v1/kuisioner/sesi');
        $resSesiAlias->assertOk()
            ->assertJsonPath('data.status_sesi', 'draft');

        // 2. Publish session
        $resPublish = $this->actingAs($gpm, 'sanctum')->postJson('/api/v1/kuisioner/session/publish');
        $resPublish->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('kuisioner_sesis', [
            'periode_akademik_id' => $periodeAktif->id,
            'status' => 'published',
        ]);

        // 3. Modifikasi pertanyaan saat published harus ditolak dengan 422 ERR_KUISIONER_SESSION_LOCKED
        $resModifKategori = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/kuisioner/kategori', [
            'nama' => 'Kategori Coba-coba',
        ]);
        $resModifKategori->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_KUISIONER_SESSION_LOCKED');

        $resModifSoal = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/kuisioner/pertanyaan', [
            'pertanyaan' => 'Pertanyaan Coba-coba?',
        ]);
        $resModifSoal->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_KUISIONER_SESSION_LOCKED');

        // 4. Mahasiswa isi kuisioner (Likert 4 scale)
        $mhsUser = User::whereHas('roles', fn ($q) => $q->where('name', 'Mahasiswa'))->firstOrFail();
        $dplUser = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();

        $pendaftaran = PendaftaranMbkm::where('user_id', $mhsUser->id)->first();
        if (! $pendaftaran) {
            $pendaftaran = PendaftaranMbkm::firstOrFail();
            $pendaftaran->user_id = $mhsUser->id;
        }
        $pendaftaran->update([
            'status' => 'Terverifikasi',
            'dpl_id' => $dplUser->id,
            'periode_akademik_id' => $periodeAktif->id,
            'sudah_isi_kuisioner' => false,
        ]);

        // Buat soal kategori DPL untuk testing
        $soal = KuisionerPertanyaan::firstOrCreate(
            ['pertanyaan' => 'DPL responsif dalam bimbingan?'],
            [
                'kategori' => 'dpl',
                'tipe_input' => 'likert_5',
                'nomor_urut' => 1,
                'is_active' => true,
            ]
        );

        // Submission dengan skor > 4 harus gagal validasi 422
        $resInvalidSkor = $this->actingAs($mhsUser, 'sanctum')->postJson('/api/v1/kuisioner/jawaban', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'jawaban_dpl' => [$soal->id => 5],
        ]);
        $resInvalidSkor->assertStatus(422);

        // Submission valid (skor 4)
        $resJawaban = $this->actingAs($mhsUser, 'sanctum')->postJson('/api/v1/kuisioner/jawaban', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'jawaban_dpl' => [$soal->id => 4],
            'kompensasi_tipe' => ['Uang saku', 'Sertifikat'],
            'kompensasi_nominal' => 'Rp 2.000.000',
            'saran_masukan' => 'Program sangat baik.',
        ]);
        $resJawaban->assertOk()
            ->assertJsonPath('success', true);

        $pendaftaran->refresh();
        $this->assertTrue((bool) $pendaftaran->sudah_isi_kuisioner);
        $this->assertNotNull($pendaftaran->kuisioner_submitted_at);

        // Pengisian ulang harus ditolak 422
        $resJawabanUlang = $this->actingAs($mhsUser, 'sanctum')->postJson('/api/v1/kuisioner/jawaban', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'jawaban_dpl' => [$soal->id => 4],
        ]);
        $resJawabanUlang->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_KUISIONER_ALREADY_SUBMITTED');

        // 5. Coba unpublish saat sudah ada respon -> harus ditolak 422 ERR_KUISIONER_ALREADY_HAS_RESPONSES
        $resUnpublish = $this->actingAs($gpm, 'sanctum')->postJson('/api/v1/kuisioner/session/unpublish');
        $resUnpublish->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_KUISIONER_ALREADY_HAS_RESPONSES');

        // 6. Close session
        $resClose = $this->actingAs($gpm, 'sanctum')->postJson('/api/v1/kuisioner/session/close');
        $resClose->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('kuisioner_sesis', [
            'periode_akademik_id' => $periodeAktif->id,
            'status' => 'closed',
        ]);

        // 7. Re-open draft (buka draft baru)
        $resDraft = $this->actingAs($gpm, 'sanctum')->postJson('/api/v1/kuisioner/session/draft');
        $resDraft->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('kuisioner_sesis', [
            'periode_akademik_id' => $periodeAktif->id,
            'status' => 'draft',
        ]);
    }

    public function test_plotting_penguji_kuisioner_lock_and_dpl_conflict(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $dplUser = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();
        $pengujiUser = User::whereHas('roles', fn ($q) => $q->where('name', 'Penguji Semhas'))
            ->where('id', '!=', $dplUser->id)
            ->first();
        if (! $pengujiUser) {
            $pengujiUser = User::create([
                'nama' => 'Dosen Penguji Alternatif',
                'email' => 'penguji.alt@fasilkom.ac.id',
                'password' => bcrypt('password123'),
            ]);
            $rolePenguji = Role::firstOrCreate(['name' => 'Penguji Semhas']);
            $pengujiUser->roles()->attach($rolePenguji->id);
        }

        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $pendaftaran = PendaftaranMbkm::with('rkm')->where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update([
            'dpl_id' => $dplUser->id,
            'periode_akademik_id' => $periodeAktif->id,
            'sudah_isi_kuisioner' => false,
        ]);
        if ($pendaftaran->rkm) {
            $pendaftaran->rkm->update(['status' => 'Disetujui']);
        }

        // 1. GET /plotting/tahap-2-dpl-penguji returns sudah_isi_kuisioner: false
        $resTahap2 = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/plotting/tahap-2-dpl-penguji');
        $resTahap2->assertOk()
            ->assertJsonPath('success', true);

        $itemTahap2 = collect($resTahap2->json('data'))->firstWhere('id', $pendaftaran->id);
        $this->assertNotNull($itemTahap2);
        $this->assertFalse($itemTahap2['sudah_isi_kuisioner']);

        // 2. Plotting Penguji Semhas ditolak 422 karena mahasiswa belum isi kuisioner
        $resPengujiBlocked = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/plotting/penguji', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'penguji_semhas_id' => $pengujiUser->id,
        ]);
        $resPengujiBlocked->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_PLOTTING_KUISIONER_BELUM_DIISI');

        // 3. Set mahasiswa sudah isi kuisioner dan berstatus Layak Semhas
        $pendaftaran->update(['sudah_isi_kuisioner' => true]);
        \App\Models\PengajuanSemhas::updateOrCreate(
            ['pendaftaran_mbkm_id' => $pendaftaran->id],
            [
                'periode_akademik_id' => $periodeAktif->id,
                'status_kelayakan' => 'Layak Semhas',
                'laporan_akhir_path' => 'semhas/laporan_akhir/dummy.pdf',
                'logbook_fb07_path' => 'semhas/logbook_fb07/dummy.pdf',
                'nilai_supervisor_path' => 'semhas/nilai_supervisor/dummy.pdf',
            ]
        );

        // 4. Plotting Penguji Semhas ditolak 422 jika Penguji === DPL
        $resSameDplPenguji = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/plotting/tahap-2-dpl-penguji', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'penguji_semhas_id' => $dplUser->id,
        ]);
        $resSameDplPenguji->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_PLOTTING_DPL_EQUALS_PENGUJI');

        // 5. Plotting Penguji Semhas sukses jika penguji !== dpl dan sudah isi kuisioner
        $resPengujiSuccess = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/plotting/penguji', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'penguji_semhas_id' => $pengujiUser->id,
        ]);
        $resPengujiSuccess->assertOk()
            ->assertJsonPath('success', true);

        $pendaftaran->refresh();
        $this->assertEquals($pengujiUser->id, $pendaftaran->penguji_semhas_id);
    }
}
