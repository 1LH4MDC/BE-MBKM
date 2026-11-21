<?php

namespace Tests\Feature;

use App\Models\BimbinganMagang;
use App\Models\Cpl;
use App\Models\KuisionerJawaban;
use App\Models\MataKuliah;
use App\Models\Mitra;
use App\Models\MonevMitra;
use App\Models\PaketKonversi;
use App\Models\PengajuanSemhas;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\PlottingPemonevMitra;
use App\Models\ProgramStudi;
use App\Models\Rkm;
use App\Models\RkmMataKuliah;
use App\Models\SuratPengantar;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PendaftaranMbkmSeeder;
use Database\Seeders\RkmSeeder;
use Database\Seeders\RkpDokumenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TahunAjaranAuditTest extends TestCase
{
    use RefreshDatabase;

    protected PeriodeAkademik $periodeGanjil;
    protected PeriodeAkademik $periodeGenap;
    protected User $admin;
    protected User $ketuaMbkm;
    protected User $timMbkm;
    protected User $dpl;
    protected User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(PendaftaranMbkmSeeder::class);
        $this->seed(RkmSeeder::class);
        $this->seed(RkpDokumenSeeder::class);

        Storage::fake('local');
        Storage::fake('public');

        $this->periodeGanjil = PeriodeAkademik::where('tahun_ajaran', '2026/2027')
            ->where('semester', 'Ganjil')
            ->firstOrFail();
        $this->periodeGanjil->update(['is_aktif' => true]);

        // Pastikan ada periode genap untuk simulasi ganti semester
        $this->periodeGenap = PeriodeAkademik::firstOrCreate(
            ['tahun_ajaran' => '2026/2027', 'semester' => 'Genap'],
            ['is_aktif' => false]
        );

        $this->admin = User::whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $this->ketuaMbkm = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $this->timMbkm = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $this->dpl = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();
        $this->mahasiswa = User::whereHas('roles', fn ($q) => $q->where('name', 'Mahasiswa'))->firstOrFail();
    }

    /**
     * Test 1: Konsistensi aturan hanya ada 1 periode akademik aktif di database
     */
    public function test_single_active_periode_rule_enforced(): void
    {
        $this->assertTrue($this->periodeGanjil->fresh()->is_aktif);
        $this->assertFalse($this->periodeGenap->fresh()->is_aktif);

        // Admin mengaktifkan periode genap
        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/periode-akademik/{$this->periodeGenap->id}/aktifkan");

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Pastikan hanya periode genap yang aktif, periode ganjil otomatis non-aktif
        $this->assertTrue($this->periodeGenap->fresh()->is_aktif);
        $this->assertFalse($this->periodeGanjil->fresh()->is_aktif);
        $this->assertEquals(1, PeriodeAkademik::where('is_aktif', true)->count());

        // Format JSON endpoint periode aktif
        $aktifRes = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/periode-akademik/aktif');
        $aktifRes->assertOk()
            ->assertJsonPath('data.is_aktif', true)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.semester', 'Genap')
            ->assertJsonPath('data.nama_lengkap', '2026/2027 Genap');
    }

    /**
     * Test 2: Simulasi ganti semester - endpoint mahasiswa otomatis reset kosong di semester aktif baru
     */
    public function test_semester_switch_shows_clean_empty_state_in_new_active_semester(): void
    {
        // 1. Setup mahasiswa punya pendaftaran lengkap di Ganjil
        $pendaftaranGanjil = PendaftaranMbkm::firstOrCreate(
            [
                'user_id' => $this->mahasiswa->id,
                'periode_akademik_id' => $this->periodeGanjil->id,
            ],
            [
                'nim' => $this->mahasiswa->nim_nip ?? '212410101001',
                'nama_lengkap' => $this->mahasiswa->nama,
                'program_studi_id' => ProgramStudi::firstOrFail()->id,
                'mitra_id' => Mitra::firstOrFail()->id,
                'mitra_magang_text' => 'PT Telkom Indonesia',
                'posisi_magang' => 'Frontend Engineer',
                'tahun_ajaran' => '2026/2027 Ganjil',
                'status' => 'Terverifikasi',
                'dpl_id' => $this->dpl->id,
                'sudah_isi_kuisioner' => true,
            ]
        );

        $rkmGanjil = Rkm::firstOrCreate(
            ['pendaftaran_mbkm_id' => $pendaftaranGanjil->id],
            [
                'user_id' => $this->mahasiswa->id,
                'periode_akademik_id' => $this->periodeGanjil->id,
                'tahun_ajaran' => '2026/2027 Ganjil',
                'status' => 'Disetujui',
                'total_sks' => 20,
            ]
        );

        BimbinganMagang::firstOrCreate(
            [
                'pendaftaran_mbkm_id' => $pendaftaranGanjil->id,
                'bimbingan_ke' => 1,
            ],
            [
                'dpl_id' => $this->dpl->id,
                'periode_akademik_id' => $this->periodeGanjil->id,
                'tahun_ajaran' => '2026/2027 Ganjil',
                'tanggal_bimbingan' => now()->toDateString(),
                'catatan_bimbingan' => 'Bimbingan pertama bab 1.',
            ]
        );

        // 2. Admin mengaktifkan semester Genap
        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/periode-akademik/{$this->periodeGenap->id}/aktifkan")
            ->assertOk();

        // 3. Mahasiswa mengakses endpoint 'my' tanpa parameter di semester Genap:
        // Wajib menampilkan STATE KOSONG (tidak bocor dari Ganjil)
        $mhsAuth = $this->actingAs($this->mahasiswa, 'sanctum');

        // Pendaftaran: data null
        $resPendaftaran = $mhsAuth->getJson('/api/v1/pendaftaran-mbkm/my');
        $resPendaftaran->assertOk()
            ->assertJsonPath('data', null);

        // RKM: rkm_id null, status Belum Mendaftar
        $resRkm = $mhsAuth->getJson('/api/v1/rkm/my-rkm');
        $resRkm->assertOk()
            ->assertJsonPath('data.rkm_id', null)
            ->assertJsonPath('data.pendaftaran', null)
            ->assertJsonPath('data.status', 'Belum Mendaftar');

        // Monitoring: data null
        $resMonitoring = $mhsAuth->getJson('/api/v1/monitoring-bimbingan/my');
        $resMonitoring->assertOk()
            ->assertJsonPath('data', null);

        // Semhas: data prasyarat kosong
        $resPrasyarat = $mhsAuth->getJson('/api/v1/semhas/prasyarat');
        $resPrasyarat->assertOk()
            ->assertJsonPath('data.pendaftaran_id', null)
            ->assertJsonPath('data.bimbingan_count', 0)
            ->assertJsonPath('data.rkm_approved', false);

        // 4. Mahasiswa tetap bisa melihat arsip Ganjil jika menyertakan ?periode_akademik_id=
        $resArsip = $mhsAuth->getJson("/api/v1/pendaftaran-mbkm/my?periode_akademik_id={$this->periodeGanjil->id}");
        $resArsip->assertOk()
            ->assertJsonPath('is_archived', true)
            ->assertJsonPath('data.id', $pendaftaranGanjil->id);
    }

    /**
     * Test 3: Mahasiswa yang sama dapat mendaftar kembali di semester Genap tanpa SQL Unique Violation
     */
    public function test_same_student_can_register_in_new_semester_without_unique_conflict(): void
    {
        // Pastikan Ganjil punya pendaftaran mahasiswa
        PendaftaranMbkm::firstOrCreate(
            [
                'user_id' => $this->mahasiswa->id,
                'periode_akademik_id' => $this->periodeGanjil->id,
            ],
            [
                'nim' => $this->mahasiswa->nim_nip ?? '212410101001',
                'nama_lengkap' => $this->mahasiswa->nama,
                'program_studi_id' => ProgramStudi::firstOrFail()->id,
                'mitra_id' => Mitra::firstOrFail()->id,
                'mitra_magang_text' => 'PT Telkom Indonesia',
                'posisi_magang' => 'Frontend Engineer',
                'tahun_ajaran' => '2026/2027 Ganjil',
                'status' => 'Terverifikasi',
            ]
        );

        // Aktifkan Genap
        $this->periodeGenap->update(['is_aktif' => true]);
        $this->periodeGanjil->update(['is_aktif' => false]);

        $prodi = ProgramStudi::firstOrFail();
        $mitra = Mitra::firstOrFail();

        // Mahasiswa mendaftar baru di Genap
        $resPost = $this->actingAs($this->mahasiswa, 'sanctum')->postJson('/api/v1/pendaftaran-mbkm', [
            'program_studi_id' => $prodi->id,
            'mitra_id' => $mitra->id,
            'mitra_magang_text' => $mitra->nama,
            'posisi_magang' => 'Data Analyst',
            'transkrip_file' => UploadedFile::fake()->create('transkrip.pdf', 300, 'application/pdf'),
            'loa_file' => UploadedFile::fake()->create('loa.pdf', 300, 'application/pdf'),
        ]);

        $resPost->assertStatus(201)
            ->assertJsonPath('success', true);

        // Mahasiswa sekarang punya 2 pendaftaran berbeda di database (Ganjil & Genap)
        $totalPendaftaran = PendaftaranMbkm::where('user_id', $this->mahasiswa->id)->count();
        $this->assertEquals(2, $totalPendaftaran);

        $pendaftaranGenap = PendaftaranMbkm::where('user_id', $this->mahasiswa->id)
            ->where('periode_akademik_id', $this->periodeGenap->id)
            ->firstOrFail();

        $this->assertEquals('Data Analyst', $pendaftaranGenap->posisi_magang);
        $this->assertFalse((bool) $pendaftaranGenap->sudah_isi_kuisioner);
    }

    /**
     * Test 4: Seluruh mutasi (POST, PUT, PATCH, DELETE) pada data milik semester arsip (Ganjil) ditolak 403 Forbidden
     */
    public function test_mutations_on_archived_semester_data_rejected_with_http_403(): void
    {
        // Setup record Ganjil
        $pendaftaranGanjil = PendaftaranMbkm::firstOrCreate(
            [
                'user_id' => $this->mahasiswa->id,
                'periode_akademik_id' => $this->periodeGanjil->id,
            ],
            [
                'nim' => $this->mahasiswa->nim_nip ?? '212410101001',
                'nama_lengkap' => $this->mahasiswa->nama,
                'program_studi_id' => ProgramStudi::firstOrFail()->id,
                'mitra_id' => Mitra::firstOrFail()->id,
                'mitra_magang_text' => 'PT Telkom Indonesia',
                'posisi_magang' => 'Frontend Engineer',
                'tahun_ajaran' => '2026/2027 Ganjil',
                'status' => 'Menunggu Verifikasi',
            ]
        );

        $rkmGanjil = Rkm::firstOrCreate(
            ['pendaftaran_mbkm_id' => $pendaftaranGanjil->id],
            [
                'user_id' => $this->mahasiswa->id,
                'periode_akademik_id' => $this->periodeGanjil->id,
                'tahun_ajaran' => '2026/2027 Ganjil',
                'status' => 'Diajukan',
                'total_sks' => 20,
            ]
        );

        $bimbinganGanjil = BimbinganMagang::firstOrCreate(
            [
                'pendaftaran_mbkm_id' => $pendaftaranGanjil->id,
                'bimbingan_ke' => 1,
            ],
            [
                'dpl_id' => $this->dpl->id,
                'periode_akademik_id' => $this->periodeGanjil->id,
                'tahun_ajaran' => '2026/2027 Ganjil',
                'tanggal_bimbingan' => now()->toDateString(),
                'catatan_bimbingan' => 'Bimbingan bab 1',
            ]
        );

        $semhasGanjil = PengajuanSemhas::firstOrCreate(
            ['pendaftaran_mbkm_id' => $pendaftaranGanjil->id],
            [
                'periode_akademik_id' => $this->periodeGanjil->id,
                'status_kelayakan' => 'Menunggu Verifikasi',
            ]
        );

        $suratGanjil = SuratPengantar::firstOrCreate(
            [
                'user_id' => $this->mahasiswa->id,
                'nomor_surat' => 'SP/TEST/GANJIL/001',
            ],
            [
                'periode_akademik_id' => $this->periodeGanjil->id,
                'tahun_ajaran' => '2026/2027 Ganjil',
                'tujuan_instansi' => 'PT Telkom Indonesia',
                'alamat_instansi' => 'Jl. Merdeka No. 1 Jakarta',
                'kota_instansi' => 'Jakarta',
                'status' => 'Diajukan',
            ]
        );

        // Aktifkan Genap -> Ganjil jadi Arsip Read-Only
        $this->periodeGenap->update(['is_aktif' => true]);
        $this->periodeGanjil->update(['is_aktif' => false]);

        // A. Verifikasi Pendaftaran MBKM Ganjil -> 403
        $this->actingAs($this->timMbkm, 'sanctum')
            ->postJson("/api/v1/pendaftaran-mbkm/{$pendaftaranGanjil->id}/verifikasi", [
                'status' => 'Terverifikasi',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_ARCHIVE_READ_ONLY');

        // B. Plotting PIC pada pendaftaran Ganjil -> 403
        $this->actingAs($this->ketuaMbkm, 'sanctum')
            ->postJson('/api/v1/plotting/tahap-1-pic', [
                'pendaftaran_mbkm_id' => $pendaftaranGanjil->id,
                'pic_verifikator_id' => $this->timMbkm->id,
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_ARCHIVE_READ_ONLY');

        // C. Keputusan Verifikasi RKM Ganjil -> 403
        $this->actingAs($this->timMbkm, 'sanctum')
            ->postJson("/api/v1/tim-mbkm/verifikasi-rkm/{$rkmGanjil->id}/keputusan", [
                'status' => 'Disetujui',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_ARCHIVE_READ_ONLY');

        // D. Tambah Bimbingan pada pendaftaran Ganjil -> 403
        $this->actingAs($this->dpl, 'sanctum')
            ->postJson("/api/v1/monitoring-bimbingan/mahasiswa/{$pendaftaranGanjil->id}", [
                'tanggal_bimbingan' => now()->toDateString(),
                'catatan_bimbingan' => 'Catatan sesi baru.',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_ARCHIVE_READ_ONLY');

        // E. Update Bimbingan Ganjil -> 403
        $this->actingAs($this->dpl, 'sanctum')
            ->putJson("/api/v1/monitoring-bimbingan/{$bimbinganGanjil->id}", [
                'catatan_bimbingan' => 'Update catatan',
                'tanggal_bimbingan' => now()->toDateString(),
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_ARCHIVE_READ_ONLY');

        // F. Verifikasi Semhas Ganjil -> 403
        $this->actingAs($this->timMbkm, 'sanctum')
            ->postJson("/api/v1/semhas/{$semhasGanjil->id}/verifikasi-pic", [
                'status_kelayakan' => 'Layak Semhas',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_ARCHIVE_READ_ONLY');

        // G. Update Surat Pengantar Ganjil -> 403
        $this->actingAs($this->mahasiswa, 'sanctum')
            ->putJson("/api/v1/surat-pengantar/{$suratGanjil->id}", [
                'tujuan_instansi' => 'PT Telkom Update',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_ARCHIVE_READ_ONLY');

        // H. Submit Monev Mitra pada periode Ganjil -> 403
        $mitra = Mitra::firstOrFail();
        $this->actingAs($this->dpl, 'sanctum')
            ->postJson("/api/v1/monev-mitra/{$mitra->id}/submit?periode_akademik_id={$this->periodeGanjil->id}", [
                'periode_monev' => 'Minggu ke-0',
                'tanggal_pelaksanaan' => now()->toDateString(),
                'metode_pelaksanaan' => 'Luring',
                'catatan_evaluasi' => 'Evaluasi awal.',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_ARCHIVE_READ_ONLY');
    }

    /**
     * Test 5: Data Master Global (Mitra, MK, CPL, Users) tetap utuh dan bisa dikelola bebas di semester Genap
     */
    public function test_global_master_data_remains_intact_and_editable_in_any_semester(): void
    {
        // Aktifkan Genap
        $this->periodeGenap->update(['is_aktif' => true]);
        $this->periodeGanjil->update(['is_aktif' => false]);

        // 1. Data Master Mitra tetap bisa diakses dan diperbarui
        $mitra = Mitra::firstOrFail();
        $resMitra = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/mitra/{$mitra->id}", [
            'nama' => 'PT Mitra Baru Update',
            'alamat' => 'Jl. Kalimantan No. 37 Jember',
            'kontak' => '081234567890',
            'bidang_usaha' => 'Teknologi Informasi',
            'status' => 'Aktif',
        ]);
        $resMitra->assertOk();
        $this->assertEquals('PT Mitra Baru Update', $mitra->fresh()->nama);

        // 2. Data Master Mata Kuliah tetap bisa diperbarui
        $mk = MataKuliah::firstOrFail();
        $cpl = Cpl::firstOrFail();
        $resMk = $this->actingAs($this->timMbkm, 'sanctum')->putJson("/api/v1/mata-kuliah/{$mk->id}", [
            'kode' => $mk->kode,
            'nama_mk' => 'Pemrograman Lanjut Terintegrasi',
            'sks' => $mk->sks,
            'program_studi_id' => $mk->program_studi_id,
            'cpls' => [['cpl_id' => $cpl->id, 'cpmk_deskripsi' => 'Deskripsi CPMK']],
        ]);
        $resMk->assertOk();
        $this->assertEquals('Pemrograman Lanjut Terintegrasi', $mk->fresh()->nama_mk);

        // 3. Data Master CPL tetap bisa diakses
        $resCpl = $this->actingAs($this->timMbkm, 'sanctum')->getJson('/api/v1/cpl');
        $resCpl->assertOk()
            ->assertJsonPath('success', true);
        $this->assertNotEmpty($resCpl->json('data'));

        // 4. Data Master Paket Konversi tetap bisa diakses
        $resPaket = $this->actingAs($this->timMbkm, 'sanctum')->getJson('/api/v1/paket-konversi');
        $resPaket->assertOk();
    }

    /**
     * Test 6: Audit filtering ?tahun_ajaran= dan ?periode_akademik_id= memisahkan data semester tanpa kebocoran
     */
    public function test_filtering_by_periode_separates_data_correctly(): void
    {
        $prodi = ProgramStudi::firstOrFail();
        $mitra = Mitra::firstOrFail();

        // Buat 1 pendaftaran di Ganjil
        PendaftaranMbkm::firstOrCreate(
            [
                'user_id' => $this->mahasiswa->id,
                'periode_akademik_id' => $this->periodeGanjil->id,
            ],
            [
                'nim' => '212410101001',
                'nama_lengkap' => 'Mahasiswa Ganjil',
                'program_studi_id' => $prodi->id,
                'mitra_id' => $mitra->id,
                'mitra_magang_text' => $mitra->nama,
                'posisi_magang' => 'Web Developer Ganjil',
                'tahun_ajaran' => '2026/2027 Ganjil',
                'status' => 'Terverifikasi',
                'transkrip_file_name' => 'transkrip.pdf',
                'transkrip_file_path' => 'pendaftaran/transkrip.pdf',
                'transkrip_file_size' => '100 KB',
                'loa_file_name' => 'loa.pdf',
                'loa_file_path' => 'pendaftaran/loa.pdf',
                'loa_file_size' => '100 KB',
            ]
        );

        // Buat 1 pendaftaran di Genap dengan user mahasiswa lain
        $mhsLain = User::whereHas('roles', fn ($q) => $q->where('name', 'Mahasiswa'))
            ->where('id', '!=', $this->mahasiswa->id)
            ->firstOrFail();

        PendaftaranMbkm::firstOrCreate(
            [
                'user_id' => $mhsLain->id,
                'periode_akademik_id' => $this->periodeGenap->id,
            ],
            [
                'nim' => $mhsLain->nim_nip ?? '212410101099',
                'nama_lengkap' => 'Mahasiswa Genap',
                'program_studi_id' => $prodi->id,
                'mitra_id' => $mitra->id,
                'mitra_magang_text' => $mitra->nama,
                'posisi_magang' => 'Mobile Developer Genap',
                'tahun_ajaran' => '2026/2027 Genap',
                'status' => 'Terverifikasi',
                'transkrip_file_name' => 'transkrip.pdf',
                'transkrip_file_path' => 'pendaftaran/transkrip.pdf',
                'transkrip_file_size' => '100 KB',
                'loa_file_name' => 'loa.pdf',
                'loa_file_path' => 'pendaftaran/loa.pdf',
                'loa_file_size' => '100 KB',
            ]
        );

        $authTim = $this->actingAs($this->timMbkm, 'sanctum');

        // Filter Ganjil: hanya muncul data Ganjil
        $resGanjil = $authTim->getJson('/api/v1/pendaftaran-mbkm?tahun_ajaran=2026/2027 Ganjil');
        $resGanjil->assertOk();
        $itemsGanjil = collect($resGanjil->json('data'));
        $this->assertTrue($itemsGanjil->every(fn ($it) => ($it['tahun_ajaran'] ?? '') === '2026/2027 Ganjil'));

        // Filter Genap: hanya muncul data Genap
        $resGenap = $authTim->getJson('/api/v1/pendaftaran-mbkm?tahun_ajaran=2026/2027 Genap');
        $resGenap->assertOk();
        $itemsGenap = collect($resGenap->json('data'));
        $this->assertTrue($itemsGenap->every(fn ($it) => ($it['tahun_ajaran'] ?? '') === '2026/2027 Genap'));

        // Dashboard stats terisolasi per tahun ajaran
        $statsGanjil = $authTim->getJson('/api/v1/tim-mbkm/dashboard-stats?tahun_ajaran=2026/2027 Ganjil');
        $statsGanjil->assertOk();

        $statsGenap = $authTim->getJson('/api/v1/tim-mbkm/dashboard-stats?tahun_ajaran=2026/2027 Genap');
        $statsGenap->assertOk();

        $this->assertGreaterThan(0, $statsGanjil->json('data.total_pendaftar'));
        $this->assertGreaterThan(0, $statsGenap->json('data.total_pendaftar'));
    }
}
