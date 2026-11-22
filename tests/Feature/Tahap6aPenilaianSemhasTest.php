<?php

namespace Tests\Feature;

use App\Models\MataKuliah;
use App\Models\PendaftaranMbkm;
use App\Models\PengajuanSemhas;
use App\Models\PenilaianSemhas;
use App\Models\PeriodeAkademik;
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

class Tahap6aPenilaianSemhasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(PendaftaranMbkmSeeder::class);
        $this->seed(RkmSeeder::class);
        $this->seed(RkpDokumenSeeder::class);
        Storage::fake('public');
    }

    private function createPenguji(string $nama, string $email): User
    {
        $role = Role::firstOrCreate(['name' => 'Penguji Semhas'], ['display_name' => 'Penguji Semhas']);
        $user = User::create([
            'nama' => $nama,
            'email' => $email,
            'password' => bcrypt('password123'),
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    public function test_penguji_semhas_student_isolation(): void
    {
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $pengujiA = $this->createPenguji('Penguji A', 'penguji.a@fasilkom.ac.id');
        $pengujiB = $this->createPenguji('Penguji B', 'penguji.b@fasilkom.ac.id');

        $pendaftaranA = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaranA->update([
            'penguji_semhas_id' => $pengujiA->id,
            'periode_akademik_id' => $periodeAktif->id,
        ]);

        $pendaftaranB = PendaftaranMbkm::where('id', '!=', $pendaftaranA->id)->firstOrFail();
        $pendaftaranB->update([
            'penguji_semhas_id' => $pengujiB->id,
            'periode_akademik_id' => $periodeAktif->id,
        ]);

        // Penguji A hanya melihat mahasiswanya sendiri
        $resA = $this->actingAs($pengujiA, 'sanctum')->getJson('/api/v1/penilaian-semhas/penguji');
        $resA->assertOk()->assertJsonPath('success', true);

        $dataA = collect($resA->json('data'));
        $this->assertTrue($dataA->contains('pendaftaran_mbkm_id', $pendaftaranA->id));
        $this->assertFalse($dataA->contains('pendaftaran_mbkm_id', $pendaftaranB->id));

        // Penguji B hanya melihat mahasiswanya sendiri
        $resB = $this->actingAs($pengujiB, 'sanctum')->getJson('/api/v1/penilaian-semhas/penguji');
        $resB->assertOk()->assertJsonPath('success', true);

        $dataB = collect($resB->json('data'));
        $this->assertTrue($dataB->contains('pendaftaran_mbkm_id', $pendaftaranB->id));
        $this->assertFalse($dataB->contains('pendaftaran_mbkm_id', $pendaftaranA->id));

        // User tanpa role Penguji Semhas ditolak 403
        $studentUser = $pendaftaranA->user;
        $resForbidden = $this->actingAs($studentUser, 'sanctum')->getJson('/api/v1/penilaian-semhas/penguji');
        $resForbidden->assertStatus(403);
    }

    public function test_penguji_can_access_and_stream_laporan_akhir(): void
    {
        $pengujiA = $this->createPenguji('Penguji A Stream', 'penguji.stream.a@fasilkom.ac.id');
        $pengujiLain = $this->createPenguji('Penguji Lain Stream', 'penguji.stream.lain@fasilkom.ac.id');

        $pendaftaran = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update(['penguji_semhas_id' => $pengujiA->id]);

        $dummyPdf = UploadedFile::fake()->create('laporan_akhir_test.pdf', 500, 'application/pdf');
        $path = $dummyPdf->store('semhas/laporan_akhir', 'public');

        $semhas = PengajuanSemhas::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $pendaftaran->periode_akademik_id,
            'laporan_akhir_path' => $path,
            'laporan_akhir_name' => 'laporan_akhir_test.pdf',
            'status_kelayakan' => 'Layak',
        ]);

        // Penguji A yang di-plotting berhak mengakses berkas
        $resPengujiA = $this->actingAs($pengujiA, 'sanctum')->get("/api/v1/semhas/{$semhas->id}/file/laporan_akhir");
        $resPengujiA->assertOk();

        // Penguji lain yang tidak di-plotting ditolak 403
        $resPengujiLain = $this->actingAs($pengujiLain, 'sanctum')->get("/api/v1/semhas/{$semhas->id}/file/laporan_akhir");
        $resPengujiLain->assertStatus(403);
    }

    public function test_penguji_save_jadwal_and_student_sees_it_in_my_semhas(): void
    {
        $penguji = $this->createPenguji('Dr. Hendra Semhas', 'hendra.semhas@fasilkom.ac.id');
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $pendaftaran = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update([
            'penguji_semhas_id' => $penguji->id,
            'periode_akademik_id' => $periodeAktif->id,
        ]);

        $dummyPdf = UploadedFile::fake()->create('laporan.pdf', 200, 'application/pdf');
        $path = $dummyPdf->store('semhas/laporan_akhir', 'public');
        PengajuanSemhas::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $periodeAktif->id,
            'laporan_akhir_path' => $path,
            'laporan_akhir_name' => 'laporan.pdf',
            'status_kelayakan' => 'Layak',
        ]);

        // Penguji menyimpan jadwal
        $jadwalPayload = [
            'jadwal_tanggal' => '2026-12-15',
            'jadwal_waktu_mulai' => '09:00',
            'jadwal_waktu_selesai' => '10:30',
            'jadwal_lokasi' => 'Ruang Sidang 2 Fasilkom',
        ];

        $resJadwal = $this->actingAs($penguji, 'sanctum')
            ->postJson("/api/v1/penilaian-semhas/{$pendaftaran->id}/jadwal", $jadwalPayload);

        $resJadwal->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.jadwal_tanggal', '2026-12-15')
            ->assertJsonPath('data.jadwal_waktu_mulai', '09:00')
            ->assertJsonPath('data.jadwal_lokasi', 'Ruang Sidang 2 Fasilkom');

        // Mahasiswa melihat jadwal di GET /api/v1/semhas/my
        $resStudent = $this->actingAs($pendaftaran->user, 'sanctum')->getJson('/api/v1/semhas/my');
        $resStudent->assertOk()
            ->assertJsonPath('data.jadwal_semhas.jadwal_tanggal', '2026-12-15')
            ->assertJsonPath('data.jadwal_semhas.jadwal_waktu_mulai', '09:00')
            ->assertJsonPath('data.jadwal_semhas.jadwal_lokasi', 'Ruang Sidang 2 Fasilkom')
            ->assertJsonPath('data.penguji_semhas', 'Dr. Hendra Semhas');
    }

    public function test_penguji_save_and_update_penilaian_with_formula_accuracy(): void
    {
        $penguji = $this->createPenguji('Dr. Nilai Penguji', 'nilai.penguji@fasilkom.ac.id');
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $pendaftaran = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update([
            'penguji_semhas_id' => $penguji->id,
            'periode_akademik_id' => $periodeAktif->id,
        ]);

        // Siapkan RKM dan 2 MK Konversi aktif
        $rkm = Rkm::firstOrCreate(
            ['pendaftaran_mbkm_id' => $pendaftaran->id],
            ['periode_akademik_id' => $periodeAktif->id, 'status' => 'Disetujui']
        );

        $mkSample = MataKuliah::first();
        RkmMataKuliah::firstOrCreate(
            ['rkm_id' => $rkm->id, 'kode_mk' => 'MK-TEST-01'],
            ['mata_kuliah_id' => $mkSample?->id, 'nama_mk' => 'Rekayasa Perangkat Lunak', 'sks' => 3, 'durasi_jam' => 120, 'kompetensi' => 'Mampu merancang perangkat lunak', 'status_mk' => 'aktif']
        );

        RkmMataKuliah::firstOrCreate(
            ['rkm_id' => $rkm->id, 'kode_mk' => 'MK-TEST-02'],
            ['mata_kuliah_id' => $mkSample?->id, 'nama_mk' => 'Manajemen Proyek TI', 'sks' => 3, 'durasi_jam' => 120, 'kompetensi' => 'Mampu mengelola proyek TI', 'status_mk' => 'aktif']
        );

        // Simulasi input penilaian:
        // Laporan: 80 (20%) -> 16
        // Slide: 90 (20%) -> 18
        // Presentasi: 70 (20%) -> 14
        // Tanya Jawab: 85 (40%) -> 34
        // Nilai Presentasi = 16 + 18 + 14 + 34 = 82.00
        // MK1: Nilai MK 80 -> Nilai Total = (80 * 0.75) + (82.00 * 0.25) = 60 + 20.5 = 80.50
        // MK2: Nilai MK 90 -> Nilai Total = (90 * 0.75) + (82.00 * 0.25) = 67.5 + 20.5 = 88.00
        // Rata-rata Nilai Total = (80.50 + 88.00) / 2 = 84.25
        $payloadNilai = [
            'nilai_kelengkapan_laporan' => 80,
            'deskripsi_kelengkapan_laporan' => 'Laporan lengkap dan sistematis',
            'nilai_slide' => 90,
            'deskripsi_slide' => 'Slide presentasi sangat menarik dan visual',
            'nilai_cara_presentasi' => 70,
            'deskripsi_cara_presentasi' => null, // Deskripsi opsional
            'nilai_tanya_jawab' => 85,
            'deskripsi_tanya_jawab' => 'Menjawab pertanyaan dengan lugas',
            'nilai_mata_kuliahs' => [
                [
                    'kode_mk' => 'MK-TEST-01',
                    'nama_mk' => 'Rekayasa Perangkat Lunak',
                    'sks' => 3,
                    'nilai_mata_kuliah' => 80,
                ],
                [
                    'kode_mk' => 'MK-TEST-02',
                    'nama_mk' => 'Manajemen Proyek TI',
                    'sks' => 3,
                    'nilai_mata_kuliah' => 90,
                ],
            ],
        ];

        $resNilai = $this->actingAs($penguji, 'sanctum')
            ->postJson("/api/v1/penilaian-semhas/{$pendaftaran->id}/nilai", $payloadNilai);

        $resNilai->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(82.00, $resNilai->json('data.nilai_presentasi'));
        $this->assertEquals(84.25, $resNilai->json('data.rata_rata_nilai_total'));

        // Verifikasi detail via GET /api/v1/penilaian-semhas/{id}
        $resShow = $this->actingAs($penguji, 'sanctum')
            ->getJson("/api/v1/penilaian-semhas/{$pendaftaran->id}");

        $resShow->assertOk()
            ->assertJsonPath('data.can_edit', true);

        $this->assertEquals(82.00, $resShow->json('data.penilaian.nilai_presentasi'));
        $this->assertEquals(84.25, $resShow->json('data.penilaian.rata_rata_nilai_total'));

        $mkResults = collect($resShow->json('data.penilaian.mata_kuliahs'));
        $mk1 = $mkResults->firstWhere('kode_mk', 'MK-TEST-01');
        $mk2 = $mkResults->firstWhere('kode_mk', 'MK-TEST-02');

        $this->assertEquals(80.50, $mk1['nilai_total']);
        $this->assertEquals(88.00, $mk2['nilai_total']);

        // Verifikasi penguji bebas mengedit ulang nilai selama periode aktif
        $payloadNilai['nilai_tanya_jawab'] = 95; // Presentasi jadi: 16 + 18 + 14 + 38 = 86.00
        $resUpdate = $this->actingAs($penguji, 'sanctum')
            ->postJson("/api/v1/penilaian-semhas/{$pendaftaran->id}/nilai", $payloadNilai);

        $resUpdate->assertOk();
        $this->assertEquals(86.00, $resUpdate->json('data.nilai_presentasi'));
    }

    public function test_rekap_ketua_access_and_forbidden_for_admin_and_others(): void
    {
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();

        // 1. Ketua Tim MBKM berhasil (200)
        $resKetua = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/penilaian-semhas/rekap-ketua');
        $resKetua->assertOk()
            ->assertJsonPath('success', true);

        // 2. Admin ditolak (403)
        $resAdmin = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/penilaian-semhas/rekap-ketua');
        $resAdmin->assertStatus(403);
    }

    public function test_archive_protection_prevents_jadwal_and_nilai_mutation(): void
    {
        $penguji = $this->createPenguji('Penguji Arsip', 'penguji.arsip@fasilkom.ac.id');

        // Periode arsip (is_aktif = false)
        $periodeArsip = PeriodeAkademik::create([
            'tahun_ajaran' => '2024/2025',
            'semester' => 'Genap',
            'is_aktif' => false,
        ]);

        $pendaftaranArsip = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaranArsip->update([
            'penguji_semhas_id' => $penguji->id,
            'periode_akademik_id' => $periodeArsip->id,
        ]);

        // 1. Coba mutasi jadwal pada periode arsip -> 403
        $resJadwal = $this->actingAs($penguji, 'sanctum')
            ->postJson("/api/v1/penilaian-semhas/{$pendaftaranArsip->id}/jadwal", [
                'jadwal_tanggal' => '2025-06-20',
                'jadwal_waktu_mulai' => '08:00',
            ]);
        $resJadwal->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_ARCHIVE_READ_ONLY');

        // 2. Coba mutasi nilai pada periode arsip -> 403
        $resNilai = $this->actingAs($penguji, 'sanctum')
            ->postJson("/api/v1/penilaian-semhas/{$pendaftaranArsip->id}/nilai", [
                'nilai_kelengkapan_laporan' => 85,
                'nilai_slide' => 85,
                'nilai_cara_presentasi' => 85,
                'nilai_tanya_jawab' => 85,
                'nilai_mata_kuliahs' => [
                    ['kode_mk' => 'MK-01', 'nama_mk' => 'Test', 'nilai_mata_kuliah' => 85],
                ],
            ]);
        $resNilai->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_ARCHIVE_READ_ONLY');
    }
}
