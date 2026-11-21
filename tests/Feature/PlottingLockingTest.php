<?php

namespace Tests\Feature;

use App\Models\Mitra;
use App\Models\MonevMitra;
use App\Models\PengajuanSemhas;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\PlottingPemonevMitra;
use App\Models\Rkm;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PendaftaranMbkmSeeder;
use Database\Seeders\RkmSeeder;
use Database\Seeders\RkpDokumenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlottingLockingTest extends TestCase
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

    public function test_pic_verifikator_locking_on_verified_pendaftaran(): void
    {
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $pic1 = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $pic2 = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->where('id', '!=', $pic1->id)->firstOrFail();

        $pendaftaran = PendaftaranMbkm::firstOrFail();
        $pendaftaran->update([
            'status' => 'Terverifikasi',
            'pic_verifikator_id' => $pic1->id,
            'is_plotted_pic' => true,
        ]);

        // 1. GET /api/v1/plotting/tahap-1-pic menyertakan is_pic_locked => true
        $resGet = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/plotting/tahap-1-pic');
        $resGet->assertOk();
        $item = collect($resGet->json('data'))->firstWhere('id', $pendaftaran->id);
        $this->assertNotNull($item);
        $this->assertTrue($item['is_pic_locked']);

        // 2. POST /api/v1/plotting/tahap-1-pic mencoba ubah PIC => wajib ditolak 422 ERR_PLOTTING_PIC_LOCKED
        $resChange = $this->actingAs($ketua, 'sanctum')->postJson('/api/v1/plotting/tahap-1-pic', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'pic_verifikator_id' => $pic2->id,
        ]);
        $resChange->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_PLOTTING_PIC_LOCKED');

        $this->assertEquals($pic1->id, $pendaftaran->fresh()->pic_verifikator_id);
    }

    public function test_dpl_locking_on_approved_rkm(): void
    {
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $dpl1 = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();
        $dpl2 = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->where('id', '!=', $dpl1->id)->firstOrFail();

        $pendaftaran = PendaftaranMbkm::whereHas('rkm')->firstOrFail();
        $pendaftaran->update([
            'status' => 'Terverifikasi',
            'dpl_id' => $dpl1->id,
        ]);
        $pendaftaran->rkm->update(['status' => 'Disetujui']);

        // 1. GET /api/v1/plotting/tahap-2-dpl-penguji menyertakan is_dpl_locked => true
        $resGet = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/plotting/tahap-2-dpl-penguji');
        $resGet->assertOk();
        $item = collect($resGet->json('data'))->firstWhere('id', $pendaftaran->id);
        $this->assertNotNull($item);
        $this->assertTrue($item['is_dpl_locked']);

        // 2. POST /api/v1/plotting/tahap-2-dpl-penguji mencoba ganti dpl_id ke dpl2 => wajib ditolak 422 ERR_PLOTTING_DPL_LOCKED
        $resChange = $this->actingAs($ketua, 'sanctum')->postJson('/api/v1/plotting/tahap-2-dpl-penguji', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'dpl_id' => $dpl2->id,
        ]);
        $resChange->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_PLOTTING_DPL_LOCKED');

        $this->assertEquals($dpl1->id, $pendaftaran->fresh()->dpl_id);
    }

    public function test_penguji_semhas_locking(): void
    {
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $dpl = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();
        $penguji1 = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->where('id', '!=', $dpl->id)->firstOrFail();
        $penguji2 = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))
            ->whereNotIn('id', [$dpl->id, $penguji1->id])->firstOrFail();

        $pendaftaran = PendaftaranMbkm::whereHas('rkm')->firstOrFail();
        $pendaftaran->update([
            'status' => 'Terverifikasi',
            'dpl_id' => $dpl->id,
            'penguji_semhas_id' => $penguji1->id,
            'sudah_isi_kuisioner' => true,
        ]);
        $pendaftaran->rkm->update(['status' => 'Disetujui']);
        PengajuanSemhas::updateOrCreate(
            ['pendaftaran_mbkm_id' => $pendaftaran->id],
            ['status_kelayakan' => 'Layak Semhas']
        );

        // 1. GET menyertakan is_penguji_locked => true
        $resGet = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/plotting/tahap-2-dpl-penguji');
        $resGet->assertOk();
        $item = collect($resGet->json('data'))->firstWhere('id', $pendaftaran->id);
        $this->assertNotNull($item);
        $this->assertTrue($item['is_penguji_locked']);

        // 2. Mengirim dpl_id yang sama tanpa mengubah penguji => tidak ditolak
        $resSameDpl = $this->actingAs($ketua, 'sanctum')->postJson('/api/v1/plotting/tahap-2-dpl-penguji', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'dpl_id' => $dpl->id,
            'penguji_semhas_id' => $penguji1->id,
        ]);
        $resSameDpl->assertOk();

        // 3. Mengubah penguji ke dosen lain => wajib ditolak 422 ERR_PLOTTING_PENGUJI_LOCKED
        $resChangePenguji = $this->actingAs($ketua, 'sanctum')->postJson('/api/v1/plotting/tahap-2-dpl-penguji', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'penguji_semhas_id' => $penguji2->id,
        ]);
        $resChangePenguji->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_PLOTTING_PENGUJI_LOCKED');

        $this->assertEquals($penguji1->id, $pendaftaran->fresh()->penguji_semhas_id);
    }

    public function test_pemonev_mitra_locking_when_monev_already_submitted(): void
    {
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $timMbkm = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $mitra = Mitra::firstOrFail();

        $pendaftaran = PendaftaranMbkm::firstOrFail();
        $pendaftaran->update([
            'status' => 'Terverifikasi',
            'mitra_id' => $mitra->id,
            'periode_akademik_id' => $periodeAktif->id,
        ]);

        PlottingPemonevMitra::create([
            'mitra_id' => $mitra->id,
            'periode_akademik_id' => $periodeAktif->id,
            'dosen_id' => $timMbkm->id,
            'assigned_by' => $ketua->id,
        ]);

        // Belum ada monev => is_pemonev_locked = false
        $resListBefore = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/monev-mitra/plotting');
        $itemBefore = collect($resListBefore->json('data'))->firstWhere('mitra_id', $mitra->id);
        $this->assertNotNull($itemBefore);
        $this->assertFalse($itemBefore['is_pemonev_locked']);

        // Isi monev Minggu ke-0
        MonevMitra::create([
            'mitra_id' => $mitra->id,
            'periode_akademik_id' => $periodeAktif->id,
            'periode_monev' => 'Minggu ke-0',
            'tanggal_pelaksanaan' => now()->toDateString(),
            'metode_pelaksanaan' => 'Daring',
            'catatan_evaluasi' => 'Evaluasi awal berjalan lancar',
            'filled_by_id' => $timMbkm->id,
        ]);

        // Sudah ada monev => is_pemonev_locked = true
        $resListAfter = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/monev-mitra/plotting');
        $itemAfter = collect($resListAfter->json('data'))->firstWhere('mitra_id', $mitra->id);
        $this->assertNotNull($itemAfter);
        $this->assertTrue($itemAfter['is_pemonev_locked']);

        // Coba simpan plotting ulang => wajib ditolak 422 ERR_PLOTTING_PEMONEV_LOCKED
        $resPost = $this->actingAs($ketua, 'sanctum')->postJson('/api/v1/monev-mitra/plotting', [
            'mitra_id' => $mitra->id,
            'periode_akademik_id' => $periodeAktif->id,
            'dosen_ids' => [$timMbkm->id],
        ]);
        $resPost->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_PLOTTING_PEMONEV_LOCKED');
    }
}
