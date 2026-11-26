<?php

namespace Tests\Feature;

use App\Models\BimbinganMagang;
use App\Models\KuisionerSesi;
use App\Models\Mitra;
use App\Models\MonevMitra;
use App\Models\PendaftaranMbkm;
use App\Models\PengajuanSemhas;
use App\Models\PenilaianDpl;
use App\Models\PenilaianSemhas;
use App\Models\PenilaianSupervisor;
use App\Models\PeriodeAkademik;
use App\Models\Rkm;
use App\Models\RkmMataKuliah;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PendaftaranMbkmSeeder;
use Database\Seeders\RkmSeeder;
use Database\Seeders\RkpDokumenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardKetuaMbkmTest extends TestCase
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

    public function test_ketua_tim_mbkm_can_load_real_dashboard_data(): void
    {
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $dpl = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        // Lengkapi 1 mahasiswa RKM Disetujui pada periode aktif agar memiliki 8 bimbingan,
        // Layak Semhas, Nilai Lengkap 3/3 (SPV 90, DPL 80, Penguji 85 -> 85.50), dan Kuesioner terisi
        $rkmDisetujui = Rkm::with(['pendaftaranMbkm', 'items'])
            ->where('status', 'Disetujui')
            ->whereHas('pendaftaranMbkm', fn ($q) => $q->where('periode_akademik_id', $periodeAktif->id))
            ->firstOrFail();

        $pendaftaran = $rkmDisetujui->pendaftaranMbkm;
        $pendaftaran->update(['sudah_isi_kuisioner' => true]);

        BimbinganMagang::where('pendaftaran_mbkm_id', $pendaftaran->id)->delete();
        for ($i = 1; $i <= 8; $i++) {
            BimbinganMagang::create([
                'pendaftaran_mbkm_id' => $pendaftaran->id,
                'dpl_id' => $dpl->id,
                'periode_akademik_id' => $periodeAktif->id,
                'bimbingan_ke' => $i,
                'tanggal_bimbingan' => now()->subDays(10 - $i)->format('Y-m-d'),
                'catatan_bimbingan' => "Bimbingan ke-{$i}",
            ]);
        }

        PengajuanSemhas::updateOrCreate(
            ['pendaftaran_mbkm_id' => $pendaftaran->id],
            [
                'periode_akademik_id' => $periodeAktif->id,
                'laporan_akhir_path' => 'semhas/lap.pdf',
                'logbook_fb07_path' => 'semhas/fb07.pdf',
                'nilai_supervisor_path' => 'semhas/spv.pdf',
                'status_kelayakan' => 'Layak Semhas',
                'is_override_ketua' => false,
            ]
        );

        $activeMk = $rkmDisetujui->items->where('status_mk', '!=', 'dropped')->first()
            ?? RkmMataKuliah::where('rkm_id', $rkmDisetujui->id)->firstOrFail();

        // Pastikan hanya ada 1 MK aktif untuk uji akurasi rata-rata gabungan 85.50
        RkmMataKuliah::where('rkm_id', $rkmDisetujui->id)
            ->where('id', '!=', $activeMk->id)
            ->update(['status_mk' => 'dropped']);

        $spv = PenilaianSupervisor::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $periodeAktif->id,
            'nama_supervisor' => 'Supervisor Mitra Test',
            'rata_rata_nilai' => 90.0,
        ]);
        $spv->mataKuliahs()->create([
            'kode_mk' => $activeMk->kode_mk,
            'nama_mk' => $activeMk->nama_mk,
            'sks' => $activeMk->sks,
            'nilai' => 90.0,
        ]);

        $dplNilai = PenilaianDpl::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $periodeAktif->id,
            'dpl_id' => $dpl->id,
            'jumlah_bimbingan_saat_dinilai' => 8,
            'rata_rata_nilai' => 80.0,
        ]);
        $dplNilai->mataKuliahs()->create([
            'kode_mk' => $activeMk->kode_mk,
            'nama_mk' => $activeMk->nama_mk,
            'sks' => $activeMk->sks,
            'nilai' => 80.0,
        ]);

        $penguji = PenilaianSemhas::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $periodeAktif->id,
            'penguji_id' => $ketua->id,
            'nilai_kelengkapan_laporan' => 85,
            'nilai_slide' => 85,
            'nilai_cara_presentasi' => 85,
            'nilai_tanya_jawab' => 85,
            'nilai_presentasi' => 85,
            'rata_rata_nilai_total' => 85.0,
        ]);
        $penguji->mataKuliahs()->create([
            'kode_mk' => $activeMk->kode_mk,
            'nama_mk' => $activeMk->nama_mk,
            'nilai_mata_kuliah' => 85,
            'nilai_presentasi' => 85,
            'nilai_total' => 85,
        ]);

        KuisionerSesi::updateOrCreate(
            ['periode_akademik_id' => $periodeAktif->id],
            ['status' => 'published', 'published_at' => now()]
        );

        $mitraAktifId = $pendaftaran->mitra_id ?? Mitra::firstOrFail()->id;
        MonevMitra::create([
            'mitra_id' => $mitraAktifId,
            'periode_akademik_id' => $periodeAktif->id,
            'periode_monev' => 'Minggu ke-0',
            'tanggal_pelaksanaan' => now()->subDays(20)->format('Y-m-d'),
            'metode_pelaksanaan' => 'Daring',
            'catatan_evaluasi' => 'Onboarding mitra berjalan baik dan sesuai RKM.',
            'filled_by_id' => $ketua->id,
        ]);
        MonevMitra::create([
            'mitra_id' => $mitraAktifId,
            'periode_akademik_id' => $periodeAktif->id,
            'periode_monev' => 'Minggu ke-8',
            'tanggal_pelaksanaan' => now()->subDays(2)->format('Y-m-d'),
            'catatan_evaluasi' => 'Progres mahasiswa sangat baik pada minggu ke-8.',
            'filled_by_id' => $ketua->id,
        ]);

        $res = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/dashboard/ketua-mbkm');
        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_ketua_scope', true)
            ->assertJsonPath('data.periode.id', $periodeAktif->id)
            ->assertJsonPath('data.periode.is_active', true)
            ->assertJsonPath('data.kpi.mahasiswa_terdaftar', 5)
            ->assertJsonPath('data.kpi.mahasiswa_terverifikasi', 3)
            ->assertJsonPath('data.kpi.mitra_aktif', 2)
            ->assertJsonPath('data.kpi.layak_semhas_count', 1)
            ->assertJsonPath('data.kpi.nilai_lengkap_count', 1)
            ->assertJsonPath('data.kuesioner.status_sesi', 'Dibuka (Dipublikasikan)')
            ->assertJsonPath('data.kuesioner.sudah_mengisi', 1)
            ->assertJsonPath('data.kuesioner.total_target', 3)
            ->assertJsonPath('data.pipeline.pendaftaran', 5)
            ->assertJsonPath('data.pipeline.terverifikasi', 3)
            ->assertJsonPath('data.pipeline.rkm_disetujui', 1)
            ->assertJsonPath('data.pipeline.bimbingan_memenuhi', 1)
            ->assertJsonPath('data.pipeline.layak_semhas', 1)
            ->assertJsonPath('data.pipeline.nilai_lengkap', 1);

        $this->assertEquals(85.5, (float) $res->json('data.kpi.rata_rata_nilai_akhir'));

        $payload = $res->json('data');
        $this->assertCount(3, $payload['sebaran_prodi']);
        $this->assertEquals(
            $payload['status_rkm']['total'],
            $payload['status_rkm']['disetujui'] + $payload['status_rkm']['menunggu'] + $payload['status_rkm']['perbaikan'] + $payload['status_rkm']['belum_ajukan']
        );
        $this->assertEquals(
            $payload['kelengkapan_plotting']['total'],
            $payload['kelengkapan_plotting']['lengkap'] + $payload['kelengkapan_plotting']['sebagian'] + $payload['kelengkapan_plotting']['belum_diplot']
        );
        $this->assertNotEmpty($payload['ringkasan_mitra']);
        $this->assertCount(2, $payload['monev_terbaru']);
        $this->assertEquals('Mg-8', $payload['monev_terbaru'][0]['tahap']);
        $this->assertEquals('2 / 3 Selesai', $payload['monev_terbaru'][0]['progres_tahap']);
    }

    public function test_tim_mbkm_non_ketua_scope_filters_to_assigned_pic(): void
    {
        $timBiasa = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))
            ->firstOrFail();

        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();
        $expectedCount = PendaftaranMbkm::where('periode_akademik_id', $periodeAktif->id)
            ->where('pic_verifikator_id', $timBiasa->id)
            ->count();

        $res = $this->actingAs($timBiasa, 'sanctum')
            ->getJson('/api/v1/dashboard/ketua-mbkm?active_role=Tim+MBKM');

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_ketua_scope', false)
            ->assertJsonPath('data.kpi.mahasiswa_terdaftar', $expectedCount);
    }

    public function test_periode_filter_isolates_dashboard_data_accurately(): void
    {
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();

        // Periode 2025/2026 Ganjil (kosong: 0 pendaftar)
        $periodeKosong = PeriodeAkademik::where('tahun_ajaran', '2025/2026')
            ->where('semester', 'Ganjil')
            ->firstOrFail();

        $this->actingAs($ketua, 'sanctum')
            ->getJson("/api/v1/dashboard/ketua-mbkm?periode_akademik_id={$periodeKosong->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.periode.id', $periodeKosong->id)
            ->assertJsonPath('data.periode.is_active', false)
            ->assertJsonPath('data.kpi.mahasiswa_terdaftar', 0)
            ->assertJsonPath('data.kpi.mahasiswa_terverifikasi', 0);

        // Periode 2025/2026 Genap (1 mahasiswa terverifikasi, 1 RKM disetujui, tanpa monev)
        $periodeArsip = PeriodeAkademik::where('tahun_ajaran', '2025/2026')
            ->where('semester', 'Genap')
            ->firstOrFail();

        $res = $this->actingAs($ketua, 'sanctum')
            ->getJson("/api/v1/dashboard/ketua-mbkm?periode_akademik_id={$periodeArsip->id}");

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.periode.id', $periodeArsip->id)
            ->assertJsonPath('data.periode.is_active', false)
            ->assertJsonPath('data.kpi.mahasiswa_terdaftar', 1)
            ->assertJsonPath('data.kpi.mahasiswa_terverifikasi', 1)
            ->assertJsonPath('data.pipeline.rkm_disetujui', 1)
            ->assertJsonPath('data.monev_terbaru', []);
    }

    public function test_non_tim_mbkm_roles_are_forbidden_403(): void
    {
        $mahasiswa = User::whereHas('roles', fn ($q) => $q->where('name', 'Mahasiswa'))->firstOrFail();
        $dplMurni = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['Tim MBKM', 'Ketua Tim MBKM']))
            ->firstOrFail();

        $this->actingAs($mahasiswa, 'sanctum')
            ->getJson('/api/v1/dashboard/ketua-mbkm')
            ->assertStatus(403);

        $this->actingAs($dplMurni, 'sanctum')
            ->getJson('/api/v1/dashboard/ketua-mbkm')
            ->assertStatus(403);
    }
}
