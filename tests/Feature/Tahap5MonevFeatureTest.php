<?php

namespace Tests\Feature;

use App\Models\Mitra;
use App\Models\MonevMitra;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\PlottingPemonevMitra;
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

class Tahap5MonevFeatureTest extends TestCase
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

    public function test_plotting_candidates_and_rules(): void
    {
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        // Siapkan 1 mitra dengan mahasiswa magang aktif
        $mitra = Mitra::firstOrCreate(
            ['nama' => 'PT Solusi Teknologi Nusantara'],
            [
                'alamat' => 'Jl. Tidar No 10, Jember',
                'kontak' => '08123456789',
                'email' => 'hr@solusitek.id',
            ]
        );

        // Ambil DPL untuk mahasiswa di mitra ini
        $dplMitra = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();

        // Pastikan ada pendaftaran terverifikasi di mitra ini
        $pendaftaran = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update([
            'mitra_id' => $mitra->id,
            'dpl_id' => $dplMitra->id,
            'periode_akademik_id' => $periodeAktif->id,
        ]);

        // 1. GET /api/v1/monev-mitra/plotting
        $resList = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/monev-mitra/plotting');
        $resList->assertOk()
            ->assertJsonPath('success', true);

        $mitraItem = collect($resList->json('data'))->firstWhere('mitra_id', $mitra->id);
        $this->assertNotNull($mitraItem);
        $this->assertGreaterThanOrEqual(1, $mitraItem['jumlah_mahasiswa']);

        $candidates = collect($mitraItem['kandidat_dosen']);
        $this->assertTrue($candidates->contains('id', $dplMitra->id));

        // 2. Ambil 2 dosen Tim MBKM yang bukan DPL pada mitra ini
        $timMbkmDosen = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))
            ->where('id', '!=', $dplMitra->id)
            ->take(2)
            ->get();

        $timMbkmIds = $timMbkmDosen->pluck('id')->all();
        if (count($timMbkmIds) < 2) {
            $extraTim = User::create([
                'nama' => 'Dosen Tim MBKM Tambahan',
                'email' => 'extra.tim@fasilkom.ac.id',
                'password' => bcrypt('password123'),
            ]);
            $roleTim = Role::firstOrCreate(['name' => 'Tim MBKM']);
            $extraTim->roles()->attach($roleTim->id);
            $timMbkmIds[] = $extraTim->id;
        }

        // 3. Plotting 2 dosen Tim MBKM TANPA menyertakan DPL Mitra -> WAJIB SUKSES 200 OK
        $resPlotSuccess = $this->actingAs($ketua, 'sanctum')->postJson('/api/v1/monev-mitra/plotting', [
            'mitra_id' => $mitra->id,
            'periode_akademik_id' => $periodeAktif->id,
            'dosen_ids' => array_slice($timMbkmIds, 0, 2),
        ]);
        $resPlotSuccess->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('plotting_pemonev_mitras', [
            'mitra_id' => $mitra->id,
            'periode_akademik_id' => $periodeAktif->id,
            'dosen_id' => $timMbkmIds[0],
        ]);

        // 4. Plotting lebih dari 2 dosen -> HARUS DITOLAK 422
        $resPlotMaxExceeded = $this->actingAs($ketua, 'sanctum')->postJson('/api/v1/monev-mitra/plotting', [
            'mitra_id' => $mitra->id,
            'periode_akademik_id' => $periodeAktif->id,
            'dosen_ids' => array_merge($timMbkmIds, [$dplMitra->id]), // 3 dosen
        ]);
        $resPlotMaxExceeded->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_MAX_PEMONEV_EXCEEDED');

        // 5. Plotting dosen di luar kandidat sah (bukan DPL mitra dan bukan Tim MBKM) -> HARUS DITOLAK 422
        $dosenLuar = User::create([
            'nama' => 'Dosen Luar Non Tim Non DPL',
            'email' => 'dosen.luar@fasilkom.ac.id',
            'password' => bcrypt('password123'),
        ]);

        $resPlotInvalidCandidate = $this->actingAs($ketua, 'sanctum')->postJson('/api/v1/monev-mitra/plotting', [
            'mitra_id' => $mitra->id,
            'periode_akademik_id' => $periodeAktif->id,
            'dosen_ids' => [$dosenLuar->id],
        ]);
        $resPlotInvalidCandidate->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_INVALID_PEMONEV_CANDIDATE');
    }

    public function test_isolation_and_monev_submissions_across_stages(): void
    {
        Storage::fake('public');

        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $mitra = Mitra::firstOrFail();

        // Tetapkan 1 dosen pemonev
        $pemonev = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $nonPemonev = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))
            ->where('id', '!=', $pemonev->id)
            ->where('id', '!=', $ketua->id)
            ->firstOrFail();

        // Hubungkan pendaftaran
        $pendaftaran = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update([
            'mitra_id' => $mitra->id,
            'periode_akademik_id' => $periodeAktif->id,
        ]);

        PlottingPemonevMitra::updateOrCreate([
            'mitra_id' => $mitra->id,
            'periode_akademik_id' => $periodeAktif->id,
            'dosen_id' => $pemonev->id,
        ], [
            'assigned_by' => $ketua->id,
        ]);

        // 1. Pemonev melihat penugasan di /my-assignments dengan can_edit === true
        $resMyAssigned = $this->actingAs($pemonev, 'sanctum')->getJson('/api/v1/monev-mitra/my-assignments');
        $resMyAssigned->assertOk();
        $itemPemonev = collect($resMyAssigned->json('data'))->firstWhere('mitra_id', $mitra->id);
        $this->assertNotNull($itemPemonev);
        $this->assertTrue($itemPemonev['can_edit']);

        // 2. Dosen yang TIDAK ditugaskan tidak melihat mitra tersebut pada /my-assignments
        $resMyNon = $this->actingAs($nonPemonev, 'sanctum')->getJson('/api/v1/monev-mitra/my-assignments');
        $resMyNon->assertOk();
        $itemNon = collect($resMyNon->json('data'))->firstWhere('mitra_id', $mitra->id);
        $this->assertNull($itemNon);

        // 3. Dosen non-pemonev mencoba submit Monev -> DITOLAK 403 ERR_NOT_ASSIGNED_PEMONEV
        $resSubmitNon = $this->actingAs($nonPemonev, 'sanctum')->postJson("/api/v1/monev-mitra/{$mitra->id}/submit", [
            'periode_monev' => 'Minggu ke-0',
            'tanggal_pelaksanaan' => '2026-11-20',
            'metode_pelaksanaan' => 'Luring',
            'catatan_evaluasi' => 'Penerimaan mahasiswa magang berjalan baik.',
        ]);
        $resSubmitNon->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_NOT_ASSIGNED_PEMONEV');

        // 4. Pemonev mengisi Monev Minggu ke-0 dengan file dokumentasi
        $fileDoc = UploadedFile::fake()->create('berita_acara_monev0.pdf', 300, 'application/pdf');
        $resSubmit0 = $this->actingAs($pemonev, 'sanctum')->postJson("/api/v1/monev-mitra/{$mitra->id}/submit", [
            'periode_monev' => 'Minggu ke-0',
            'tanggal_pelaksanaan' => '2026-11-20',
            'metode_pelaksanaan' => 'Luring',
            'catatan_evaluasi' => 'Penyambutan mahasiswa oleh mentor industri, pembagian divisi kerja sesuai RKM.',
            'kendala_tindak_lanjut' => 'Akses repo GitHub sedang dalam proses invite oleh tim DevOps mitra.',
            'dokumentasi' => $fileDoc,
        ]);
        $resSubmit0->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.periode_monev', 'Minggu ke-0');

        $monev0Id = $resSubmit0->json('data.id');

        // 5. Streaming dokumentasi file via GET /monev-mitra/{monevId}/file
        $resStream = $this->actingAs($pemonev, 'sanctum')->get("/api/v1/monev-mitra/{$monev0Id}/file");
        $resStream->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // 6. Pemonev mengisi Monev Minggu ke-8 (Monev Proses - 12 Indikator)
        $resSubmit8 = $this->actingAs($pemonev, 'sanctum')->postJson("/api/v1/monev-mitra/{$mitra->id}/submit", [
            'periode_monev' => 'Minggu ke-8',
            'tanggal_pelaksanaan' => '2026-12-15',
            'catatan_evaluasi' => 'Evaluasi tengah magang (mid-term). Progress modul auth dan backend mencapai 60%.',
            'penilaian_mahasiswas' => [
                [
                    'pendaftaran_mbkm_id' => $pendaftaran->id,
                    'kehadiran_ketepatan_waktu' => 5,
                    'ketaatan_aturan' => 4,
                    'pencapaian_tugas' => 5,
                    'kualitas_hasil_kerja' => 4,
                    'kemampuan_menyelesaikan_masalah' => 5,
                    'penguasaan_tools' => 4,
                    'kemampuan_analisis' => 5,
                    'komunikasi_kolaborasi' => 4,
                    'tanggung_jawab_inisiatif' => 5,
                    'etika_profesional' => 5,
                    'relevansi_ilmu_komputer' => 5,
                    'kontribusi_kompetensi' => 4,
                    'catatan_pemonev' => 'Mahasiswa sangat adaptif dengan stack Laravel & Docker.',
                    'temuan_monev' => 'Perlu pendampingan untuk unit test coverage.',
                    'status_monev' => 'Observasi',
                ],
            ],
        ]);
        $resSubmit8->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('monev_proses_mahasiswas', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'status_monev' => 'Observasi',
            'kehadiran_ketepatan_waktu' => 5,
        ]);

        // 7. Pemonev mengisi Monev Minggu ke-18 (Monev Hasil - 7 Indikator & Proyeksi Magang)
        $resSubmit18 = $this->actingAs($pemonev, 'sanctum')->postJson("/api/v1/monev-mitra/{$mitra->id}/submit", [
            'periode_monev' => 'Minggu ke-18',
            'tanggal_pelaksanaan' => '2027-01-20',
            'catatan_evaluasi' => 'Penarikan mahasiswa magang. Seluruh pekerjaan deliverable selesai memuaskan.',
            'proyeksi_magang_selanjutnya' => [
                'Optimalisasi proses onboarding di minggu awal magang',
                'Peningkatan dokumentasi arsitektur database',
            ],
            'penilaian_mahasiswas' => [
                [
                    'pendaftaran_mbkm_id' => $pendaftaran->id,
                    'kompleksitas_jobdesk' => 4,
                    'kesesuaian_rkm' => 5,
                    'relevansi_pl' => 4,
                    'kualitas_supervisi' => 5,
                    'ketercapaian_cpmk' => 5,
                    'outcome_pekerjaan' => 5,
                    'pengembangan_skill' => 4,
                ],
            ],
        ]);
        $resSubmit18->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rata_rata_indikator.kompleksitas_jobdesk', 4);

        $this->assertDatabaseHas('monev_hasil_mahasiswas', [
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'kompleksitas_jobdesk' => 4,
            'kesesuaian_rkm' => 5,
        ]);

        // 8. Cek detail mitra GET /monev-mitra/{mitraId}
        $resDetail = $this->actingAs($pemonev, 'sanctum')->getJson("/api/v1/monev-mitra/{$mitra->id}");
        $resDetail->assertOk()
            ->assertJsonPath('data.monevs.minggu_0.is_filled', true)
            ->assertJsonPath('data.monevs.minggu_8.is_filled', true)
            ->assertJsonPath('data.monevs.minggu_8.penilaian_mahasiswas.0.status_monev', 'Observasi')
            ->assertJsonPath('data.monevs.minggu_18.is_filled', true)
            ->assertJsonPath('data.monevs.minggu_18.proyeksi_magang_selanjutnya.0', 'Optimalisasi proses onboarding di minggu awal magang')
            ->assertJsonPath('data.monevs.minggu_18.rata_rata_indikator.kompleksitas_jobdesk', 4);
    }

    public function test_rekap_hasil_global_and_target_for_ketua_tim_mbkm(): void
    {
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $dpl = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))
            ->firstOrFail();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        // 1. Non-Ketua (DPL) mengakses rekap global -> 403 Forbidden
        $resDpl = $this->actingAs($dpl, 'sanctum')->getJson('/api/v1/monev-mitra/rekap-hasil-global');
        $resDpl->assertStatus(403);

        // 2. Non-Ketua (DPL) mengupdate target -> 403 Forbidden
        $resDplTarget = $this->actingAs($dpl, 'sanctum')->patchJson('/api/v1/monev-mitra/rekap-hasil-global/target', [
            'target_nilai_monev' => 4.50,
        ]);
        $resDplTarget->assertStatus(403);

        // 3. Ketua Tim MBKM mengakses rekap global -> 200 OK
        $resKetua = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/monev-mitra/rekap-hasil-global');
        $resKetua->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'periode_akademik',
                    'target_nilai_monev',
                    'total_mitra_dinilai',
                    'total_mahasiswa_dinilai',
                    'rata_rata_global',
                    'rerata_global_keseluruhan',
                    'detail_per_mitra',
                ],
            ]);

        // 4. Ketua Tim MBKM mengupdate target nilai -> 200 OK
        $resUpdateTarget = $this->actingAs($ketua, 'sanctum')->patchJson('/api/v1/monev-mitra/rekap-hasil-global/target', [
            'target_nilai_monev' => 4.35,
            'periode_akademik_id' => $periodeAktif->id,
        ]);
        $resUpdateTarget->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.target_nilai_monev', 4.35);

        $this->assertDatabaseHas('periode_akademiks', [
            'id' => $periodeAktif->id,
            'target_nilai_monev' => 4.35,
        ]);
    }
}
