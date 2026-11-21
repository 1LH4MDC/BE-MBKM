<?php

namespace Tests\Feature;

use App\Models\BimbinganMagang;
use App\Models\PengajuanSemhas;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
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

class SyncTahap3FeatureTest extends TestCase
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

    public function test_mahasiswa_prasyarat_submit_and_partial_reupload(): void
    {
        Storage::fake('public');

        $mhsUser = User::whereHas('roles', fn ($q) => $q->where('name', 'Mahasiswa'))->firstOrFail();
        $dplUser = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();
        $picUser = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $pendaftaran = PendaftaranMbkm::where('user_id', $mhsUser->id)->first();
        if (! $pendaftaran) {
            $pendaftaran = PendaftaranMbkm::firstOrFail();
            $pendaftaran->user_id = $mhsUser->id;
        }
        $pendaftaran->update([
            'status' => 'Terverifikasi',
            'dpl_id' => $dplUser->id,
            'pic_verifikator_id' => $picUser->id,
            'periode_akademik_id' => $periodeAktif->id,
            'sudah_isi_kuisioner' => true,
        ]);
        if ($pendaftaran->rkm) {
            $pendaftaran->rkm->update(['status' => 'Disetujui']);
        }

        // 1. Cek GET /api/v1/semhas/prasyarat
        $resPrasyarat = $this->actingAs($mhsUser, 'sanctum')->getJson('/api/v1/semhas/prasyarat');
        $resPrasyarat->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rkm_approved', true)
            ->assertJsonPath('data.sudah_isi_kuisioner', true);

        // 2. Submit 3 Dokumen PDF Semhas
        $fileLaporan = UploadedFile::fake()->create('Laporan_Akhir_Mhs.pdf', 500, 'application/pdf');
        $fileLogbook = UploadedFile::fake()->create('Logbook_FB07_Mhs.pdf', 300, 'application/pdf');
        $fileNilai = UploadedFile::fake()->create('Lembar_Nilai_Supervisor.pdf', 200, 'application/pdf');

        $resSubmit = $this->actingAs($mhsUser, 'sanctum')->postJson('/api/v1/semhas/submit', [
            'laporan_akhir' => $fileLaporan,
            'logbook_fb07' => $fileLogbook,
            'nilai_supervisor' => $fileNilai,
        ]);
        $resSubmit->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status_kelayakan', 'Menunggu Verifikasi');

        $semhasId = $resSubmit->json('data.id');

        // 3. Mahasiswa cek GET /api/v1/semhas/my
        $resMy = $this->actingAs($mhsUser, 'sanctum')->getJson('/api/v1/semhas/my');
        $resMy->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', (string) $semhasId)
            ->assertJsonPath('data.status_kelayakan', 'Menunggu Verifikasi');
        $this->assertNotEmpty($resMy->json('data.laporan_akhir_url'));

        // 4. Preview / Streaming berkas PDF
        $resStream = $this->actingAs($mhsUser, 'sanctum')->get("/api/v1/semhas/{$semhasId}/file/laporan_akhir");
        $resStream->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // 5. PIC minta 'Perbaikan Berkas'
        $resPicRev = $this->actingAs($picUser, 'sanctum')->postJson("/api/v1/semhas/{$semhasId}/verifikasi-pic", [
            'status_kelayakan' => 'Perbaikan Berkas',
            'catatan' => 'Format daftar pustaka IEEE pada laporan akhir belum sesuai pedoman.',
        ]);
        $resPicRev->assertOk()
            ->assertJsonPath('data.status_kelayakan', 'Perbaikan Berkas');

        // 6. Mahasiswa unggah parsial (hanya laporan akhir yang diganti)
        $fileLaporanBaru = UploadedFile::fake()->create('Laporan_Akhir_Revisi.pdf', 550, 'application/pdf');
        $resReupload = $this->actingAs($mhsUser, 'sanctum')->postJson('/api/v1/semhas/submit', [
            'laporan_akhir' => $fileLaporanBaru,
        ]);
        $resReupload->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status_kelayakan', 'Menunggu Verifikasi')
            ->assertJsonPath('data.laporan_akhir_name', 'Laporan_Akhir_Revisi.pdf')
            ->assertJsonPath('data.logbook_fb07_name', 'Logbook_FB07_Mhs.pdf'); // berkas lain tetap bertahan
    }

    public function test_queue_isolation_pic_vs_ketua(): void
    {
        $pic1 = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $pic2 = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->where('id', '!=', $pic1->id)->first();
        if (! $pic2) {
            $pic2 = User::create([
                'nama' => 'PIC Verifikator Dua',
                'email' => 'pic2@fasilkom.ac.id',
                'password' => bcrypt('password123'),
            ]);
            $roleTim = Role::firstOrCreate(['name' => 'Tim MBKM']);
            $pic2->roles()->attach($roleTim->id);
        }

        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        // Siapkan 2 pendaftaran berbeda untuk masing-masing PIC
        $allPendaftarans = PendaftaranMbkm::where('status', 'Terverifikasi')->take(2)->get();
        $p1 = $allPendaftarans[0];
        $p2 = $allPendaftarans[1];

        $p1->update(['pic_verifikator_id' => $pic1->id, 'periode_akademik_id' => $periodeAktif->id]);
        $p2->update(['pic_verifikator_id' => $pic2->id, 'periode_akademik_id' => $periodeAktif->id]);

        // Buat pengajuan semhas untuk p1 dan p2
        $s1 = PengajuanSemhas::updateOrCreate(['pendaftaran_mbkm_id' => $p1->id], [
            'periode_akademik_id' => $periodeAktif->id,
            'laporan_akhir_path' => 'dummy1.pdf',
            'logbook_fb07_path' => 'dummy1.pdf',
            'nilai_supervisor_path' => 'dummy1.pdf',
            'status_kelayakan' => 'Menunggu Verifikasi',
        ]);
        $s2 = PengajuanSemhas::updateOrCreate(['pendaftaran_mbkm_id' => $p2->id], [
            'periode_akademik_id' => $periodeAktif->id,
            'laporan_akhir_path' => 'dummy2.pdf',
            'logbook_fb07_path' => 'dummy2.pdf',
            'nilai_supervisor_path' => 'dummy2.pdf',
            'status_kelayakan' => 'Menunggu Verifikasi',
        ]);

        // 1. PIC 1 hanya boleh melihat mahasiswa p1 pada /semhas/pic
        $resPic1 = $this->actingAs($pic1, 'sanctum')->getJson('/api/v1/semhas/pic');
        $resPic1->assertOk();
        $idsPic1 = collect($resPic1->json('data'))->pluck('id')->all();
        $this->assertContains((string) $s1->id, $idsPic1);
        $this->assertNotContains((string) $s2->id, $idsPic1);

        // 2. PIC 1 dilarang mengakses /semhas/ketua (403) jika bukan Ketua
        if (! $pic1->hasRole('Ketua Tim MBKM')) {
            $resPic1Ketua = $this->actingAs($pic1, 'sanctum')->getJson('/api/v1/semhas/ketua');
            $resPic1Ketua->assertStatus(403);
        }

        // 3. Ketua Tim MBKM melihat SELURUH mahasiswa lintas PIC pada /semhas/ketua
        $resKetua = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/semhas/ketua');
        $resKetua->assertOk();
        $idsKetua = collect($resKetua->json('data'))->pluck('id')->all();
        $this->assertContains((string) $s1->id, $idsKetua);
        $this->assertContains((string) $s2->id, $idsKetua);
    }

    public function test_pic_layak_semhas_without_8_sessions_guard_kuisioner(): void
    {
        $pic = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $pendaftaran = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update([
            'pic_verifikator_id' => $pic->id,
            'periode_akademik_id' => $periodeAktif->id,
            'sudah_isi_kuisioner' => false, // Kuesioner belum diisi
        ]);

        // Pastikan bimbingan 0 sesi
        BimbinganMagang::where('pendaftaran_mbkm_id', $pendaftaran->id)->delete();

        $semhas = PengajuanSemhas::updateOrCreate(['pendaftaran_mbkm_id' => $pendaftaran->id], [
            'periode_akademik_id' => $periodeAktif->id,
            'laporan_akhir_path' => 'dummy.pdf',
            'logbook_fb07_path' => 'dummy.pdf',
            'nilai_supervisor_path' => 'dummy.pdf',
            'status_kelayakan' => 'Menunggu Verifikasi',
        ]);

        // 1. PIC mencoba Layak Semhas saat kuesioner belum diisi -> Ditolak 422 ERR_SEMHAS_KUISIONER_BELUM_DIISI
        $resBlocked = $this->actingAs($pic, 'sanctum')->postJson("/api/v1/semhas/{$semhas->id}/verifikasi-pic", [
            'status_kelayakan' => 'Layak Semhas',
            'catatan' => 'Berkas lengkap.',
        ]);
        $resBlocked->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_SEMHAS_KUISIONER_BELUM_DIISI');

        // 2. Mahasiswa menyelesaikan kuesioner
        $pendaftaran->update(['sudah_isi_kuisioner' => true]);

        // 3. PIC meloloskan Layak Semhas meskipun bimbingan_count = 0 -> WAJIB SUKSES 200 OK
        $resSuccess = $this->actingAs($pic, 'sanctum')->postJson("/api/v1/semhas/{$semhas->id}/verifikasi-pic", [
            'status_kelayakan' => 'Layak Semhas',
            'catatan' => 'Lolos verifikasi administrasi.',
        ]);
        $resSuccess->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status_kelayakan', 'Layak Semhas');
    }

    public function test_flow_tidak_layak_to_override_ketua_and_plotting_penguji(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $pic = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $dplUser = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();
        $pengujiUser = User::whereHas('roles', fn ($q) => $q->where('name', 'Penguji Semhas'))->where('id', '!=', $dplUser->id)->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $pendaftaran = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update([
            'dpl_id' => $dplUser->id,
            'pic_verifikator_id' => $pic->id,
            'periode_akademik_id' => $periodeAktif->id,
            'sudah_isi_kuisioner' => true,
        ]);
        if ($pendaftaran->rkm) {
            $pendaftaran->rkm->update(['status' => 'Disetujui']);
        }

        $semhas = PengajuanSemhas::updateOrCreate(['pendaftaran_mbkm_id' => $pendaftaran->id], [
            'periode_akademik_id' => $periodeAktif->id,
            'laporan_akhir_path' => 'dummy.pdf',
            'logbook_fb07_path' => 'dummy.pdf',
            'nilai_supervisor_path' => 'dummy.pdf',
            'status_kelayakan' => 'Menunggu Verifikasi',
        ]);

        // 1. PIC memutuskan 'Tidak Layak'
        $resPicReject = $this->actingAs($pic, 'sanctum')->postJson("/api/v1/semhas/{$semhas->id}/verifikasi-pic", [
            'status_kelayakan' => 'Tidak Layak',
            'catatan' => 'Lembar nilai supervisor mitra tidak dibubuhi stempel resmi instansi.',
        ]);
        $resPicReject->assertOk()
            ->assertJsonPath('data.status_kelayakan', 'Tidak Layak');

        // 2. Admin mencoba plotting Penguji Semhas -> HARUS DITOLAK 422 ERR_PLOTTING_SEMHAS_BELUM_LAYAK
        $resPlottingBlocked = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/plotting/penguji', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'penguji_semhas_id' => $pengujiUser->id,
        ]);
        $resPlottingBlocked->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_PLOTTING_SEMHAS_BELUM_LAYAK');

        // 3. Ketua Tim MBKM melakukan Hak Override
        $resOverride = $this->actingAs($ketua, 'sanctum')->postJson("/api/v1/semhas/{$semhas->id}/override-ketua", [
            'catatan_override' => 'Mitra adalah startup berbasis digital murni (paperless), stempel digital terverifikasi sah.',
        ]);
        $resOverride->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status_kelayakan', 'Layak (Override Ketua)')
            ->assertJsonPath('data.is_override_ketua', true);

        // 4. Admin plotting Penguji Semhas setelah di-override -> WAJIB SUKSES 200 OK
        $resPlottingSuccess = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/plotting/penguji', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'penguji_semhas_id' => $pengujiUser->id,
        ]);
        $resPlottingSuccess->assertOk()
            ->assertJsonPath('success', true);

        $pendaftaran->refresh();
        $this->assertEquals($pengujiUser->id, $pendaftaran->penguji_semhas_id);
    }
}
