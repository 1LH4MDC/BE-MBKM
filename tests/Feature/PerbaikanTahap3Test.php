<?php

namespace Tests\Feature;

use App\Models\MataKuliah;
use App\Models\Mitra;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\ProgramStudi;
use App\Models\Rkm;
use App\Models\RkmMataKuliah;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerbaikanTahap3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function createMahasiswaUser(string $suffix): User
    {
        $roleMhs = Role::firstOrCreate(['name' => 'Mahasiswa']);
        $user = User::create([
            'nama' => "Mahasiswa Uji {$suffix}",
            'email' => "mhs.tahap3.{$suffix}@fasilkom.ac.id",
            'nim_nip' => "2424666{$suffix}",
            'password' => bcrypt('password123'),
        ]);
        $user->roles()->sync([$roleMhs->id]);

        return $user;
    }

    public function test_semhas_endpoints_lock_when_mbkm_unverified_or_rkm_not_approved(): void
    {
        Storage::fake('public');

        $periode = PeriodeAkademik::where('is_aktif', true)->firstOrFail();
        $prodi = ProgramStudi::firstOrFail();
        $mitra = Mitra::firstOrFail();
        $pic = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $mk = MataKuliah::firstOrFail();

        $fileLaporan = UploadedFile::fake()->create('Laporan.pdf', 1000, 'application/pdf');
        $fileLogbook = UploadedFile::fake()->create('Logbook_MG07.pdf', 1000, 'application/pdf');
        $fileNilai = UploadedFile::fake()->create('Nilai_MG09.pdf', 1000, 'application/pdf');

        // 1. Mahasiswa belum mendaftar MBKM sama sekali
        $mhsNoReg = $this->createMahasiswaUser('001');
        $this->actingAs($mhsNoReg, 'sanctum')
            ->getJson('/api/v1/semhas/prasyarat')
            ->assertOk()
            ->assertJsonPath('data.has_pendaftaran', false)
            ->assertJsonPath('data.pendaftaran_terverifikasi', false)
            ->assertJsonPath('data.rkm_approved', false);

        $this->actingAs($mhsNoReg, 'sanctum')
            ->getJson('/api/v1/semhas/my')
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->actingAs($mhsNoReg, 'sanctum')
            ->postJson('/api/v1/semhas/submit', [
                'laporan_akhir' => $fileLaporan,
                'logbook_fb07' => $fileLogbook,
                'nilai_supervisor' => $fileNilai,
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_SEMHAS_PENDAFTARAN_BELUM_TERVERIFIKASI');

        // 2. Pendaftaran MBKM masih Menunggu Verifikasi (belum Terverifikasi)
        $mhsUnverified = $this->createMahasiswaUser('002');
        PendaftaranMbkm::create([
            'user_id' => $mhsUnverified->id,
            'nim' => $mhsUnverified->nim_nip,
            'nama_lengkap' => $mhsUnverified->nama,
            'program_studi_id' => $prodi->id,
            'mitra_id' => $mitra->id,
            'mitra_magang_text' => $mitra->nama,
            'posisi_magang' => 'Developer',
            'periode_akademik_id' => $periode->id,
            'pic_verifikator_id' => $pic->id,
            'is_plotted_pic' => true,
            'transkrip_file_name' => 't.pdf',
            'transkrip_file_path' => 'p/t.pdf',
            'transkrip_file_size' => '100 KB',
            'loa_file_name' => 'l.pdf',
            'loa_file_path' => 'p/l.pdf',
            'loa_file_size' => '100 KB',
            'status' => 'Menunggu Verifikasi',
        ]);

        $this->actingAs($mhsUnverified, 'sanctum')
            ->getJson('/api/v1/semhas/prasyarat')
            ->assertOk()
            ->assertJsonPath('data.has_pendaftaran', true)
            ->assertJsonPath('data.pendaftaran_terverifikasi', false)
            ->assertJsonPath('data.rkm_approved', false);

        $this->actingAs($mhsUnverified, 'sanctum')
            ->postJson('/api/v1/semhas/submit', [
                'laporan_akhir' => $fileLaporan,
                'logbook_fb07' => $fileLogbook,
                'nilai_supervisor' => $fileNilai,
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_SEMHAS_PENDAFTARAN_BELUM_TERVERIFIKASI');

        // 3. Pendaftaran MBKM Terverifikasi, tetapi RKM masih Menunggu Verifikasi
        $mhsPendingRkm = $this->createMahasiswaUser('003');
        $pPendingRkm = PendaftaranMbkm::create([
            'user_id' => $mhsPendingRkm->id,
            'nim' => $mhsPendingRkm->nim_nip,
            'nama_lengkap' => $mhsPendingRkm->nama,
            'program_studi_id' => $prodi->id,
            'mitra_id' => $mitra->id,
            'mitra_magang_text' => $mitra->nama,
            'posisi_magang' => 'UI/UX',
            'periode_akademik_id' => $periode->id,
            'pic_verifikator_id' => $pic->id,
            'is_plotted_pic' => true,
            'transkrip_file_name' => 't.pdf',
            'transkrip_file_path' => 'p/t.pdf',
            'transkrip_file_size' => '100 KB',
            'loa_file_name' => 'l.pdf',
            'loa_file_path' => 'p/l.pdf',
            'loa_file_size' => '100 KB',
            'status' => 'Terverifikasi',
        ]);

        $rkm = Rkm::create([
            'pendaftaran_mbkm_id' => $pPendingRkm->id,
            'periode_akademik_id' => $periode->id,
            'user_id' => $mhsPendingRkm->id,
            'total_sks' => $mk->sks ?? 3,
            'status' => 'Menunggu Verifikasi',
        ]);
        RkmMataKuliah::create([
            'rkm_id' => $rkm->id,
            'mata_kuliah_id' => $mk->id,
            'kode_mk' => $mk->kode,
            'nama_mk' => $mk->nama_mk,
            'sks' => $mk->sks,
            'durasi_jam' => ($mk->sks ?? 2) * 40,
            'kompetensi' => $mk->kompetensi ?? 'Kompetensi magang MBKM.',
            'status_mk' => 'aktif',
        ]);

        $this->actingAs($mhsPendingRkm, 'sanctum')
            ->getJson('/api/v1/semhas/prasyarat')
            ->assertOk()
            ->assertJsonPath('data.has_pendaftaran', true)
            ->assertJsonPath('data.pendaftaran_terverifikasi', true)
            ->assertJsonPath('data.rkm_approved', false);

        $this->actingAs($mhsPendingRkm, 'sanctum')
            ->postJson('/api/v1/semhas/submit', [
                'laporan_akhir' => $fileLaporan,
                'logbook_fb07' => $fileLogbook,
                'nilai_supervisor' => $fileNilai,
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_SEMHAS_RKM_BELUM_DISETUJUI');
    }

    public function test_semhas_file_upload_enforces_5mb_limit_with_indonesian_error(): void
    {
        Storage::fake('public');

        $periode = PeriodeAkademik::where('is_aktif', true)->firstOrFail();
        $prodi = ProgramStudi::firstOrFail();
        $mitra = Mitra::firstOrFail();
        $pic = User::whereHas('roles', fn ($q) => $q->where('name', 'Tim MBKM'))->firstOrFail();
        $mk = MataKuliah::firstOrFail();

        $mhs = $this->createMahasiswaUser('004');
        $otherMhs = $this->createMahasiswaUser('005');

        // Akses stream file & prasyarat tanpa token -> 401 Unauthenticated
        $this->getJson('/api/v1/semhas/prasyarat')->assertStatus(401);
        $this->getJson('/api/v1/semhas/1/file/laporan_akhir')->assertStatus(401);

        $p = PendaftaranMbkm::create([
            'user_id' => $mhs->id,
            'nim' => $mhs->nim_nip,
            'nama_lengkap' => $mhs->nama,
            'program_studi_id' => $prodi->id,
            'mitra_id' => $mitra->id,
            'mitra_magang_text' => $mitra->nama,
            'posisi_magang' => 'Backend Engineer',
            'periode_akademik_id' => $periode->id,
            'pic_verifikator_id' => $pic->id,
            'is_plotted_pic' => true,
            'transkrip_file_name' => 't.pdf',
            'transkrip_file_path' => 'p/t.pdf',
            'transkrip_file_size' => '100 KB',
            'loa_file_name' => 'l.pdf',
            'loa_file_path' => 'p/l.pdf',
            'loa_file_size' => '100 KB',
            'status' => 'Terverifikasi',
            'sudah_isi_kuisioner' => true,
        ]);

        $rkm = Rkm::create([
            'pendaftaran_mbkm_id' => $p->id,
            'periode_akademik_id' => $periode->id,
            'user_id' => $mhs->id,
            'total_sks' => $mk->sks ?? 3,
            'status' => 'Disetujui',
        ]);
        RkmMataKuliah::create([
            'rkm_id' => $rkm->id,
            'mata_kuliah_id' => $mk->id,
            'kode_mk' => $mk->kode,
            'nama_mk' => $mk->nama_mk,
            'sks' => $mk->sks,
            'durasi_jam' => ($mk->sks ?? 2) * 40,
            'kompetensi' => $mk->kompetensi ?? 'Kompetensi magang MBKM.',
            'status_mk' => 'aktif',
        ]);

        $validFile = UploadedFile::fake()->create('valid_5mb.pdf', 5000, 'application/pdf');
        $oversizeLaporan = UploadedFile::fake()->create('oversize_laporan.pdf', 5121, 'application/pdf');
        $oversizeLogbook = UploadedFile::fake()->create('oversize_logbook.pdf', 5500, 'application/pdf');
        $oversizeNilai = UploadedFile::fake()->create('oversize_nilai.pdf', 6000, 'application/pdf');

        // 1. Berkas > 5 MB ditolak HTTP 422 dengan pesan Bahasa Indonesia
        $this->actingAs($mhs, 'sanctum')
            ->postJson('/api/v1/semhas/submit', [
                'laporan_akhir' => $oversizeLaporan,
                'logbook_fb07' => $validFile,
                'nilai_supervisor' => $validFile,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['laporan_akhir'])
            ->assertJsonPath('errors.laporan_akhir.0', 'Ukuran berkas tidak boleh lebih dari 5 MB.');

        $this->actingAs($mhs, 'sanctum')
            ->postJson('/api/v1/semhas/submit', [
                'laporan_akhir' => $validFile,
                'logbook_fb07' => $oversizeLogbook,
                'nilai_supervisor' => $validFile,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['logbook_fb07'])
            ->assertJsonPath('errors.logbook_fb07.0', 'Ukuran berkas tidak boleh lebih dari 5 MB.');

        $this->actingAs($mhs, 'sanctum')
            ->postJson('/api/v1/semhas/submit', [
                'laporan_akhir' => $validFile,
                'logbook_fb07' => $validFile,
                'nilai_supervisor' => $oversizeNilai,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nilai_supervisor'])
            ->assertJsonPath('errors.nilai_supervisor.0', 'Ukuran berkas tidak boleh lebih dari 5 MB.');

        // 2. Berkas <= 5 MB diterima dan URL preview mengarah ke endpoint streaming terautentikasi
        $resOk = $this->actingAs($mhs, 'sanctum')
            ->postJson('/api/v1/semhas/submit', [
                'laporan_akhir' => UploadedFile::fake()->create('Laporan_Akhir_5MB.pdf', 5000, 'application/pdf'),
                'logbook_fb07' => UploadedFile::fake()->create('Logbook_MG07.pdf', 4800, 'application/pdf'),
                'nilai_supervisor' => UploadedFile::fake()->create('Nilai_Supervisor_MG09.pdf', 4900, 'application/pdf'),
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $semhasId = $resOk->json('data.id');
        $this->assertStringContainsString("/api/v1/semhas/{$semhasId}/file/laporan_akhir", (string) $resOk->json('data.laporan_akhir_url'));
        $this->assertStringContainsString("/api/v1/semhas/{$semhasId}/file/logbook_fb07", (string) $resOk->json('data.logbook_fb07_url'));
        $this->assertStringContainsString("/api/v1/semhas/{$semhasId}/file/nilai_supervisor", (string) $resOk->json('data.nilai_supervisor_url'));

        // Pemilik berkas -> 200 PDF; mahasiswa lain -> 403 Forbidden
        $this->actingAs($otherMhs, 'sanctum')
            ->getJson("/api/v1/semhas/{$semhasId}/file/laporan_akhir")
            ->assertStatus(403);
        $this->actingAs($mhs, 'sanctum')
            ->get("/api/v1/semhas/{$semhasId}/file/laporan_akhir")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
