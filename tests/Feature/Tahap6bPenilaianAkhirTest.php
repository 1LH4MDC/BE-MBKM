<?php

namespace Tests\Feature;

use App\Models\BimbinganMagang;
use App\Models\MataKuliah;
use App\Models\PendaftaranMbkm;
use App\Models\PengajuanSemhas;
use App\Models\PenilaianDpl;
use App\Models\PenilaianSemhas;
use App\Models\PenilaianSemhasMataKuliah;
use App\Models\PenilaianSupervisor;
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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Tahap6bPenilaianAkhirTest extends TestCase
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

    private function createUserWithRole(string $nama, string $email, string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => $roleName]);
        $user = User::create([
            'nama' => $nama,
            'email' => $email,
            'password' => bcrypt('password123'),
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    /**
     * 1. PIC input & edit Nilai Supervisor (nama supervisor + nilai per MK)
     * dan penolakan 403 untuk PIC lain
     */
    public function test_pic_input_and_edit_nilai_supervisor_and_forbidden_for_other_pic(): void
    {
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $picA = $this->createUserWithRole('PIC A', 'pic.a@fasilkom.ac.id', 'Tim MBKM');
        $picB = $this->createUserWithRole('PIC B', 'pic.b@fasilkom.ac.id', 'Tim MBKM');

        $pendaftaran = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update([
            'pic_verifikator_id' => $picA->id,
            'periode_akademik_id' => $periodeAktif->id,
        ]);

        $payload = [
            'nama_supervisor' => 'Budi Santoso, S.Kom. (PT. Tech Indo)',
            'nilai_mata_kuliahs' => [
                [
                    'kode_mk' => 'MK-01',
                    'nama_mk' => 'Pengembangan Web',
                    'sks' => 3,
                    'job_description' => 'Membangun API',
                    'nilai' => 88.5,
                ],
                [
                    'kode_mk' => 'MK-02',
                    'nama_mk' => 'Basis Data',
                    'sks' => 3,
                    'job_description' => 'Optimasi query',
                    'nilai' => 90.0,
                ],
            ],
        ];

        // 1. PIC B (bukan verifikator tertugas) mencoba menginput -> 403 Forbidden
        $resB = $this->actingAs($picB, 'sanctum')
            ->postJson("/api/v1/penilaian-akhir/supervisor/{$pendaftaran->id}", $payload);
        $resB->assertStatus(403);

        // 2. PIC A (verifikator tertugas) berhasil menginput -> 200 OK
        $resA = $this->actingAs($picA, 'sanctum')
            ->postJson("/api/v1/penilaian-akhir/supervisor/{$pendaftaran->id}", $payload);
        $resA->assertOk()
            ->assertJsonPath('success', true);

        // Rata-rata = (88.5 + 90.0) / 2 = 89.25
        $this->assertEquals(89.25, $resA->json('data.rata_rata_nilai'));

        // 3. Verifikasi showSupervisor dapat diakses oleh PIC A
        $resShow = $this->actingAs($picA, 'sanctum')
            ->getJson("/api/v1/penilaian-akhir/supervisor/{$pendaftaran->id}");
        $resShow->assertOk()
            ->assertJsonPath('data.can_edit', true)
            ->assertJsonPath('data.penilaian_supervisor.nama_supervisor', 'Budi Santoso, S.Kom. (PT. Tech Indo)')
            ->assertJsonPath('data.penilaian_supervisor.rata_rata_nilai', 89.25);

        // 4. Verifikasi PIC A bisa mengedit ulang nilai selama periode aktif
        $payload['nilai_mata_kuliahs'][0]['nilai'] = 94.0; // Rata-rata jadi: (94 + 90) / 2 = 92.0
        $resUpdate = $this->actingAs($picA, 'sanctum')
            ->postJson("/api/v1/penilaian-akhir/supervisor/{$pendaftaran->id}", $payload);
        $resUpdate->assertOk();
        $this->assertEquals(92.0, $resUpdate->json('data.rata_rata_nilai'));
    }

    /**
     * 2. DPL ditolak 422 (ERR_DPL_BIMBINGAN_KURANG) jika sesi bimbingan < 8 kali,
     * dan berhasil 200 setelah memiliki >= 8 sesi bimbingan.
     */
    public function test_dpl_locked_under_8_bimbingan_and_unlocked_at_8_bimbingan(): void
    {
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();
        $dpl = $this->createUserWithRole('Dosen Pembimbing', 'dpl.test@fasilkom.ac.id', 'DPL');

        $pendaftaran = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update([
            'dpl_id' => $dpl->id,
            'periode_akademik_id' => $periodeAktif->id,
        ]);

        // Buat 7 sesi bimbingan (kurang dari 8)
        BimbinganMagang::where('pendaftaran_mbkm_id', $pendaftaran->id)->delete();
        for ($i = 1; $i <= 7; $i++) {
            BimbinganMagang::create([
                'pendaftaran_mbkm_id' => $pendaftaran->id,
                'dpl_id' => $dpl->id,
                'periode_akademik_id' => $periodeAktif->id,
                'bimbingan_ke' => $i,
                'tanggal_bimbingan' => now()->subDays(10 - $i)->format('Y-m-d'),
                'catatan_bimbingan' => "Bimbingan ke-{$i}",
            ]);
        }

        $payload = [
            'nilai_mata_kuliahs' => [
                [
                    'kode_mk' => 'MK-01',
                    'nama_mk' => 'Pengembangan Web',
                    'sks' => 3,
                    'nilai' => 85.0,
                ],
            ],
        ];

        // 1. Coba simpan nilai saat bimbingan masih 7 -> 422 ERR_DPL_BIMBINGAN_KURANG
        $resLocked = $this->actingAs($dpl, 'sanctum')
            ->postJson("/api/v1/penilaian-akhir/dpl/{$pendaftaran->id}", $payload);
        $resLocked->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_DPL_BIMBINGAN_KURANG');

        // 2. Cek endpoint index DPL dan show DPL: status masih terkunci (< 8 bimbingan)
        $resIndex = $this->actingAs($dpl, 'sanctum')->getJson('/api/v1/penilaian-akhir/dpl');
        $resIndex->assertOk();
        $mhsItem = collect($resIndex->json('data'))->firstWhere('pendaftaran_mbkm_id', $pendaftaran->id);
        $this->assertEquals(7, $mhsItem['bimbingan_count']);
        $this->assertFalse($mhsItem['is_unlocked']);

        $resShowLocked = $this->actingAs($dpl, 'sanctum')
            ->getJson("/api/v1/penilaian-akhir/dpl/{$pendaftaran->id}");
        $resShowLocked->assertOk()
            ->assertJsonPath('data.is_unlocked', false)
            ->assertJsonPath('data.can_edit', false)
            ->assertJsonPath('data.penilaian_dpl', null);
        $this->assertArrayNotHasKey('is_semhas_locked', $resShowLocked->json('data'));

        // 3. Tambahkan bimbingan ke-8 dan buat pengajuan Semhas "Layak Semhas"
        // (harus tetap membuka akses Penilaian DPL dan tidak memicu error $isSemhasLocked)
        BimbinganMagang::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'dpl_id' => $dpl->id,
            'periode_akademik_id' => $periodeAktif->id,
            'bimbingan_ke' => 8,
            'tanggal_bimbingan' => now()->format('Y-m-d'),
            'catatan_bimbingan' => 'Bimbingan ke-8 evaluasi draft laporan',
        ]);

        PengajuanSemhas::updateOrCreate(['pendaftaran_mbkm_id' => $pendaftaran->id], [
            'periode_akademik_id' => $periodeAktif->id,
            'laporan_akhir_path' => 'dummy_lap.pdf',
            'logbook_fb07_path' => 'dummy_fb07.pdf',
            'nilai_supervisor_path' => 'dummy_spv.pdf',
            'status_kelayakan' => 'Layak Semhas',
            'is_override_ketua' => false,
        ]);

        // 4. Cek show DPL sebelum nilai disimpan -> 200 OK, can_edit tetap true
        $resShowBeforeSave = $this->actingAs($dpl, 'sanctum')
            ->getJson("/api/v1/penilaian-akhir/dpl/{$pendaftaran->id}");
        $resShowBeforeSave->assertOk()
            ->assertJsonPath('data.bimbingan_count', 8)
            ->assertJsonPath('data.is_unlocked', true)
            ->assertJsonPath('data.can_edit', true)
            ->assertJsonPath('data.penilaian_dpl', null);

        // 5. Simpan nilai DPL berhasil -> 200 OK
        $resUnlocked = $this->actingAs($dpl, 'sanctum')
            ->postJson("/api/v1/penilaian-akhir/dpl/{$pendaftaran->id}", $payload);
        $resUnlocked->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(85.0, $resUnlocked->json('data.rata_rata_nilai'));
        $this->assertEquals(8, $resUnlocked->json('data.jumlah_bimbingan_saat_dinilai'));

        // 6. Cek show DPL setelah nilai disimpan -> 200 OK, masih dapat diedit (can_edit = true)
        $resShowAfterSave = $this->actingAs($dpl, 'sanctum')
            ->getJson("/api/v1/penilaian-akhir/dpl/{$pendaftaran->id}");
        $resShowAfterSave->assertOk()
            ->assertJsonPath('data.is_unlocked', true)
            ->assertJsonPath('data.can_edit', true);
        $this->assertEquals(85.0, $resShowAfterSave->json('data.penilaian_dpl.rata_rata_nilai'));
    }

    /**
     * 3. Akurasi perhitungan Nilai Akhir Gabungan: (SPV 45%) + (DPL 35%) + (Penguji Semhas 20%)
     * dan memastikan bernilai null jika komponen belum lengkap.
     */
    public function test_accuracy_of_rekap_nilai_akhir_and_null_when_incomplete(): void
    {
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();

        $pendaftaran = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaran->update(['periode_akademik_id' => $periodeAktif->id]);

        // Setup 1 MK konversi aktif pada RKM
        $rkm = Rkm::firstOrCreate(
            ['pendaftaran_mbkm_id' => $pendaftaran->id],
            ['periode_akademik_id' => $periodeAktif->id, 'status' => 'Disetujui']
        );
        $mkMaster = MataKuliah::first();
        RkmMataKuliah::where('rkm_id', $rkm->id)->delete();
        $mkRkm = RkmMataKuliah::create([
            'rkm_id' => $rkm->id,
            'mata_kuliah_id' => $mkMaster?->id ?? 1,
            'kode_mk' => 'MK-UJI',
            'nama_mk' => 'Proyek Capstone',
            'sks' => 4,
            'durasi_jam' => 180,
            'kompetensi' => 'Kompetensi Capstone',
            'status_mk' => 'aktif',
        ]);

        // Case 1: Komponen baru SPV saja yang terisi -> Nilai Akhir harus null & status Sebagian
        $spv = PenilaianSupervisor::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $periodeAktif->id,
            'nama_supervisor' => 'Supervisor Mitra',
            'rata_rata_nilai' => 90.0,
        ]);
        $spv->mataKuliahs()->create([
            'kode_mk' => 'MK-UJI',
            'nama_mk' => 'Proyek Capstone',
            'sks' => 4,
            'nilai' => 90.0,
        ]);

        $resShowPart = $this->actingAs($ketua, 'sanctum')
            ->getJson("/api/v1/penilaian-akhir/rekap-ketua/{$pendaftaran->id}");
        $resShowPart->assertOk();
        $this->assertEquals('Sebagian', $resShowPart->json('data.rekap.status_kelengkapan'));
        $this->assertEquals(1, $resShowPart->json('data.rekap.komponen_terisi'));
        $this->assertNull($resShowPart->json('data.rekap.rata_rata_nilai_akhir'));
        $this->assertNull($resShowPart->json('data.rekap.mata_kuliahs.0.nilai_akhir'));

        // Case 2: Komponen DPL terisi -> masih 2/3 (Sebagian, nilai akhir masih null)
        $dpl = PenilaianDpl::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $periodeAktif->id,
            'dpl_id' => $ketua->id,
            'jumlah_bimbingan_saat_dinilai' => 8,
            'rata_rata_nilai' => 80.0,
        ]);
        $dpl->mataKuliahs()->create([
            'kode_mk' => 'MK-UJI',
            'nama_mk' => 'Proyek Capstone',
            'sks' => 4,
            'nilai' => 80.0,
        ]);

        $resShowPart2 = $this->actingAs($ketua, 'sanctum')
            ->getJson("/api/v1/penilaian-akhir/rekap-ketua/{$pendaftaran->id}");
        $this->assertEquals('Sebagian', $resShowPart2->json('data.rekap.status_kelengkapan'));
        $this->assertEquals(2, $resShowPart2->json('data.rekap.komponen_terisi'));
        $this->assertNull($resShowPart2->json('data.rekap.rata_rata_nilai_akhir'));

        // Case 3: Ketiga komponen lengkap (SPV 90, DPL 80, Penguji 85)
        // Nilai Akhir = (90 * 0.45) + (80 * 0.35) + (85 * 0.20)
        //             = 40.50 + 28.00 + 17.00 = 85.50
        $penguji = PenilaianSemhas::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $periodeAktif->id,
            'penguji_id' => $ketua->id,
            'nilai_kelengkapan_laporan' => 85,
            'nilai_slide' => 85,
            'nilai_cara_presentasi' => 85,
            'nilai_tanya_jawab' => 85,
            'nilai_presentasi' => 85,
            'rata_rata_nilai_total' => 85,
        ]);
        $penguji->mataKuliahs()->create([
            'kode_mk' => 'MK-UJI',
            'nama_mk' => 'Proyek Capstone',
            'sks' => 4,
            'nilai_mata_kuliah' => 85,
            'nilai_presentasi' => 85,
            'nilai_total' => 85,
        ]);

        $resShowComplete = $this->actingAs($ketua, 'sanctum')
            ->getJson("/api/v1/penilaian-akhir/rekap-ketua/{$pendaftaran->id}");
        $resShowComplete->assertOk();
        $this->assertEquals('Lengkap', $resShowComplete->json('data.rekap.status_kelengkapan'));
        $this->assertEquals(3, $resShowComplete->json('data.rekap.komponen_terisi'));
        $this->assertEquals(85.50, $resShowComplete->json('data.rekap.rata_rata_nilai_akhir'));
        $this->assertEquals(85.50, $resShowComplete->json('data.rekap.mata_kuliahs.0.nilai_akhir'));
    }

    /**
     * 4. Endpoint rekap-ketua berhasil (200) untuk Ketua Tim MBKM dan ditolak (403)
     * untuk Admin, Tim MBKM biasa, DPL, dan Mahasiswa.
     */
    public function test_rekap_ketua_access_and_forbidden_for_admin_and_others(): void
    {
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->firstOrFail();
        $timBiasa = $this->createUserWithRole('Tim Anggota', 'tim.anggota@fasilkom.ac.id', 'Tim MBKM');
        $dpl = $this->createUserWithRole('Dosen DPL', 'dpl.user@fasilkom.ac.id', 'DPL');
        $mhs = $this->createUserWithRole('Mhs Biasa', 'mhs.user@fasilkom.ac.id', 'Mahasiswa');

        // 1. Ketua Tim MBKM berhasil (200)
        $resKetua = $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/penilaian-akhir/rekap-ketua');
        $resKetua->assertOk()->assertJsonPath('success', true);

        // 2. Admin ditolak (403)
        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/penilaian-akhir/rekap-ketua')->assertStatus(403);

        // 3. Tim MBKM biasa ditolak (403)
        $this->actingAs($timBiasa, 'sanctum')->getJson('/api/v1/penilaian-akhir/rekap-ketua')->assertStatus(403);

        // 4. DPL ditolak (403)
        $this->actingAs($dpl, 'sanctum')->getJson('/api/v1/penilaian-akhir/rekap-ketua')->assertStatus(403);

        // 5. Mahasiswa ditolak (403)
        $this->actingAs($mhs, 'sanctum')->getJson('/api/v1/penilaian-akhir/rekap-ketua')->assertStatus(403);
    }

    /**
     * 5. Endpoint /penilaian-akhir/my mengembalikan nilai milik mahasiswa login sendiri
     * (dan null pada semester baru yang kosong).
     */
    public function test_mahasiswa_portal_my_penilaian_akhir(): void
    {
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $mhsA = $this->createUserWithRole('Mahasiswa A', 'mhs.a@fasilkom.ac.id', 'Mahasiswa');
        $mhsB = $this->createUserWithRole('Mahasiswa B', 'mhs.b@fasilkom.ac.id', 'Mahasiswa');

        $pendaftaranA = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaranA->update([
            'user_id' => $mhsA->id,
            'periode_akademik_id' => $periodeAktif->id,
        ]);

        // Mahasiswa A berhasil melihat datanya
        $resA = $this->actingAs($mhsA, 'sanctum')->getJson('/api/v1/penilaian-akhir/my');
        $resA->assertOk()->assertJsonPath('success', true);
        $this->assertEquals($pendaftaranA->id, $resA->json('data.pendaftaran_mbkm_id'));

        // Mahasiswa B belum memiliki pendaftaran pada periode aktif -> data: null
        $resB = $this->actingAs($mhsB, 'sanctum')->getJson('/api/v1/penilaian-akhir/my');
        $resB->assertOk()->assertJsonPath('success', true);
        $this->assertNull($resB->json('data'));
    }

    /**
     * 6. Proteksi arsip 403 Forbidden pada mutasi nilai Supervisor dan DPL di periode non-aktif.
     */
    public function test_archive_protection_prevents_supervisor_and_dpl_nilai_mutation(): void
    {
        $pic = $this->createUserWithRole('PIC Arsip', 'pic.arsip@fasilkom.ac.id', 'Tim MBKM');
        $dpl = $this->createUserWithRole('DPL Arsip', 'dpl.arsip@fasilkom.ac.id', 'DPL');

        // Periode arsip (is_aktif = false)
        $periodeArsip = PeriodeAkademik::create([
            'tahun_ajaran' => '2023/2024',
            'semester' => 'Ganjil',
            'is_aktif' => false,
        ]);

        $pendaftaranArsip = PendaftaranMbkm::where('status', 'Terverifikasi')->firstOrFail();
        $pendaftaranArsip->update([
            'pic_verifikator_id' => $pic->id,
            'dpl_id' => $dpl->id,
            'periode_akademik_id' => $periodeArsip->id,
        ]);

        $payload = [
            'nama_supervisor' => 'Supervisor Lama',
            'nilai_mata_kuliahs' => [
                ['kode_mk' => 'MK-01', 'nama_mk' => 'Test', 'nilai' => 80],
            ],
        ];

        // 1. Mutasi Nilai Supervisor pada periode arsip -> 403 Forbidden
        $resSpv = $this->actingAs($pic, 'sanctum')
            ->postJson("/api/v1/penilaian-akhir/supervisor/{$pendaftaranArsip->id}", $payload);
        $resSpv->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_ARCHIVE_READ_ONLY');

        // 2. Mutasi Nilai DPL pada periode arsip -> 403 Forbidden
        $resDpl = $this->actingAs($dpl, 'sanctum')
            ->postJson("/api/v1/penilaian-akhir/dpl/{$pendaftaranArsip->id}", $payload);
        $resDpl->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_ARCHIVE_READ_ONLY');
    }
}
