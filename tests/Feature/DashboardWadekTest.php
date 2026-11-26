<?php

namespace Tests\Feature;

use App\Models\KuisionerJawaban;
use App\Models\KuisionerSesi;
use App\Models\Mitra;
use App\Models\MonevHasilMahasiswa;
use App\Models\MonevMitra;
use App\Models\PendaftaranMbkm;
use App\Models\PengajuanSemhas;
use App\Models\PenilaianDpl;
use App\Models\PenilaianSemhas;
use App\Models\PenilaianSupervisor;
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
use Tests\TestCase;

class DashboardWadekTest extends TestCase
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

    public function test_wakil_dekan_can_load_executive_dashboard_with_real_metrics(): void
    {
        $wadek = User::whereHas('roles', fn ($q) => $q->where('name', 'Wakil Dekan'))->firstOrFail();
        $dpl = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        // Buat periode sebelumnya secara kronologis untuk memverifikasi selisih_periode_sebelumnya
        $periodeSebelumnya = PeriodeAkademik::create([
            'tahun_ajaran' => '2024/2025',
            'semester' => 'Ganjil',
            'is_aktif' => false,
            'target_nilai_monev' => 4.00,
        ]);

        $prodi = ProgramStudi::firstOrFail();
        $mitra = Mitra::firstOrFail();
        $mhsUser = User::whereHas('roles', fn ($q) => $q->where('name', 'Mahasiswa'))->firstOrFail();

        PendaftaranMbkm::create([
            'user_id' => $mhsUser->id,
            'periode_akademik_id' => $periodeSebelumnya->id,
            'program_studi_id' => $prodi->id,
            'mitra_id' => $mitra->id,
            'nama_lengkap' => $mhsUser->nama,
            'nim' => '99000001',
            'mitra_magang_text' => $mitra->nama,
            'posisi_magang' => 'Backend Engineer Intern',
            'transkrip_file_name' => 'transkrip.pdf',
            'transkrip_file_path' => 'pendaftaran/transkrip.pdf',
            'transkrip_file_size' => 102400,
            'loa_file_name' => 'loa.pdf',
            'loa_file_path' => 'pendaftaran/loa.pdf',
            'loa_file_size' => 102400,
            'pic_verifikator_id' => $ketua->id,
            'status' => 'Terverifikasi',
        ]);

        // Lengkapi 1 mahasiswa RKM Disetujui di periode aktif dengan Layak Semhas & nilai gabungan 85.50
        $rkmDisetujui = Rkm::with(['pendaftaranMbkm', 'items'])
            ->where('status', 'Disetujui')
            ->whereHas('pendaftaranMbkm', fn ($q) => $q->where('periode_akademik_id', $periodeAktif->id))
            ->firstOrFail();

        $pendaftaran = $rkmDisetujui->pendaftaranMbkm;
        $pendaftaran->update(['sudah_isi_kuisioner' => true]);

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

        RkmMataKuliah::where('rkm_id', $rkmDisetujui->id)
            ->where('id', '!=', $activeMk->id)
            ->update(['status_mk' => 'dropped']);

        $spv = PenilaianSupervisor::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $periodeAktif->id,
            'nama_supervisor' => 'Supervisor Mitra Wadek Test',
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

        // Tambah data GPM (skala 1-4)
        KuisionerSesi::updateOrCreate(
            ['periode_akademik_id' => $periodeAktif->id],
            ['status' => 'published', 'published_at' => now()]
        );

        KuisionerJawaban::create([
            'tahun_ajaran_id' => $periodeAktif->id,
            'tahun_ajaran' => $periodeAktif->nama_lengkap,
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'mahasiswa_user_id' => $pendaftaran->user_id,
            'dpl_user_id' => $dpl->id,
            'mitra_id' => $pendaftaran->mitra_id ?? $mitra->id,
            'nama_mitra_snapshot' => $pendaftaran->mitra?->nama ?? $mitra->nama,
            'jawaban_dpl' => [1 => 4, 2 => 3],
            'jawaban_mitra' => [3 => 4, 4 => 4],
            'rata_rata_dpl' => 3.50,
            'rata_rata_mitra' => 4.00,
        ]);

        // Tambah data Monev Hasil Minggu ke-18 (skala 1-5)
        $monevMitraId = $pendaftaran->mitra_id ?? $mitra->id;
        $monev18 = MonevMitra::create([
            'mitra_id' => $monevMitraId,
            'periode_akademik_id' => $periodeAktif->id,
            'periode_monev' => 'Minggu ke-18',
            'tanggal_pelaksanaan' => now()->format('Y-m-d'),
            'metode_pelaksanaan' => 'Offline / Kunjungan Langsung',
            'catatan_evaluasi' => 'Mitra sangat mendukung pencapaian CPMK mahasiswa.',
            'filled_by_id' => $ketua->id,
        ]);

        MonevHasilMahasiswa::create([
            'monev_mitra_id' => $monev18->id,
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'kompleksitas_jobdesk' => 5,
            'kesesuaian_rkm' => 4,
            'relevansi_pl' => 4,
            'kualitas_supervisi' => 5,
            'ketercapaian_cpmk' => 4,
            'outcome_pekerjaan' => 4,
            'pengembangan_skill' => 5,
        ]);

        $response = $this->actingAs($wadek)
            ->getJson('/api/v1/dashboard/wadek?active_role='.urlencode('Wakil Dekan'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.periode.id', $periodeAktif->id)
            ->assertJsonPath('data.kpi.rata_rata_skor_gpm', 3.75)
            ->assertJsonPath('data.rekap_gpm.rata_rata_dpl', 3.5)
            ->assertJsonPath('data.rekap_gpm.rata_rata_mitra', 4)
            ->assertJsonPath('data.rekap_gpm.rata_rata_keseluruhan', 3.75)
            ->assertJsonPath('data.rekap_gpm.total_responden', 1);

        $data = $response->json('data');
        $expectedActiveCount = PendaftaranMbkm::where('periode_akademik_id', $periodeAktif->id)
            ->whereIn('status', ['Terverifikasi', 'Disetujui'])
            ->count();

        $this->assertSame($expectedActiveCount, $data['kpi']['mahasiswa_magang_aktif']);
        $this->assertSame($expectedActiveCount - 1, $data['kpi']['selisih_periode_sebelumnya']);
        $this->assertGreaterThanOrEqual(1, $data['kpi']['layak_semhas_count']);
        $this->assertGreaterThanOrEqual(1, $data['kpi']['nilai_lengkap_count']);
        $this->assertNotNull($data['kpi']['rata_rata_nilai_akhir']);

        // Pastikan tren_periode urut kronologis (periodeSebelumnya lebih dulu dari periodeAktif)
        $trenLabels = array_column($data['tren_periode'], 'periode_id');
        $idxPrev = array_search($periodeSebelumnya->id, $trenLabels, true);
        $idxActive = array_search($periodeAktif->id, $trenLabels, true);
        $this->assertNotFalse($idxPrev);
        $this->assertNotFalse($idxActive);
        $this->assertLessThan($idxActive, $idxPrev);

        // Pastikan radar_monev berisi 7 indikator
        $this->assertCount(7, $data['radar_monev']['indikator']);
        $this->assertSame(1, $data['radar_monev']['total_mahasiswa_dinilai']);
        $this->assertSame(1, $data['radar_monev']['total_mitra_dinilai']);

        // Uji filter ?periode_akademik_id= untuk periode sebelumnya (paling awal -> selisih null)
        $prevResponse = $this->actingAs($wadek)
            ->getJson("/api/v1/dashboard/wadek?periode_akademik_id={$periodeSebelumnya->id}");

        $prevResponse->assertOk()
            ->assertJsonPath('data.periode.id', $periodeSebelumnya->id)
            ->assertJsonPath('data.kpi.mahasiswa_magang_aktif', 1)
            ->assertJsonPath('data.kpi.selisih_periode_sebelumnya', null)
            ->assertJsonPath('data.rekap_gpm.total_responden', 0);

        // Pastikan tren_periode tetap terisi multi-periode meski filter ke periode lama
        $this->assertGreaterThanOrEqual(2, count($prevResponse->json('data.tren_periode')));
    }

    public function test_non_wadek_roles_are_forbidden_from_wadek_dashboard(): void
    {
        $mahasiswa = User::whereHas('roles', fn ($q) => $q->where('name', 'Mahasiswa'))->firstOrFail();
        $dpl = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'Wakil Dekan'))
            ->whereHas('roles', fn ($q) => $q->where('name', 'DPL'))
            ->firstOrFail();
        $ketua = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'Wakil Dekan'))
            ->whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))
            ->firstOrFail();
        $wadekDual = User::whereHas('roles', fn ($q) => $q->where('name', 'Wakil Dekan'))
            ->whereHas('roles', fn ($q) => $q->where('name', 'DPL'))
            ->firstOrFail();

        $this->actingAs($mahasiswa)->getJson('/api/v1/dashboard/wadek')->assertStatus(403);
        $this->actingAs($dpl)->getJson('/api/v1/dashboard/wadek')->assertStatus(403);
        $this->actingAs($ketua)->getJson('/api/v1/dashboard/wadek')->assertStatus(403);

        // User dual role Wadek+DPL ketika aktif sebagai DPL tidak boleh membuka dashboard Wadek
        $this->actingAs($wadekDual)
            ->getJson('/api/v1/dashboard/wadek?active_role=DPL')
            ->assertStatus(403);
    }

    public function test_legacy_five_point_gpm_scores_are_clamped_to_official_four_point_scale(): void
    {
        $wadek = User::whereHas('roles', fn ($q) => $q->where('name', 'Wakil Dekan'))->firstOrFail();
        $dpl = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $pendaftaran = PendaftaranMbkm::where('periode_akademik_id', $periodeAktif->id)
            ->whereIn('status', ['Terverifikasi', 'Disetujui'])
            ->whereNotNull('mitra_id')
            ->firstOrFail();

        // Simulasikan data legacy skala 1-5 pada kuisioner_jawabans (misal rata_rata_mitra = 4.73)
        KuisionerJawaban::create([
            'tahun_ajaran_id' => $periodeAktif->id,
            'tahun_ajaran' => $periodeAktif->nama_lengkap,
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'mahasiswa_user_id' => $pendaftaran->user_id,
            'dpl_user_id' => $dpl->id,
            'mitra_id' => $pendaftaran->mitra_id,
            'nama_mitra_snapshot' => $pendaftaran->mitra?->nama ?? 'Mitra Legacy',
            'jawaban_dpl' => [1 => 5, 2 => 4],
            'jawaban_mitra' => [13 => 5, 14 => 5, 15 => 3],
            'rata_rata_dpl' => 4.50,
            'rata_rata_mitra' => 4.73,
        ]);

        $response = $this->actingAs($wadek)
            ->getJson('/api/v1/dashboard/wadek?active_role='.urlencode('Wakil Dekan'))
            ->assertOk();

        $data = $response->json('data');
        $this->assertLessThanOrEqual(4.0, $data['kpi']['rata_rata_skor_gpm']);
        $this->assertLessThanOrEqual(4.0, $data['rekap_gpm']['rata_rata_dpl']);
        $this->assertLessThanOrEqual(4.0, $data['rekap_gpm']['rata_rata_mitra']);
        $this->assertLessThanOrEqual(4.0, $data['rekap_gpm']['rata_rata_keseluruhan']);

        $mitraRow = collect($data['top_mitra'])->firstWhere('mitra_id', $pendaftaran->mitra_id);
        $this->assertNotNull($mitraRow);
        // [5->4, 5->4, 3->3] => 11/3 = 3.67 (tidak pernah tembus > 4.00)
        $this->assertSame(3.67, $mitraRow['skor_gpm_mitra']);
    }
}
