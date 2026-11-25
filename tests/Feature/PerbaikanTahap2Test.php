<?php

namespace Tests\Feature;

use App\Models\KuisionerJawaban;
use App\Models\KuisionerKategori;
use App\Models\KuisionerPertanyaan;
use App\Models\KuisionerSesi;
use App\Models\Mitra;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\PlottingPemonevMitra;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerbaikanTahap2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_monev_mitra_dpl_and_dual_role_scope_restrictions(): void
    {
        $periode = PeriodeAkademik::where('is_aktif', true)->firstOrFail();
        $prodi = ProgramStudi::firstOrFail();

        $roleDpl = Role::firstOrCreate(['name' => 'DPL']);
        $roleTim = Role::firstOrCreate(['name' => 'Tim MBKM']);
        $roleKetua = Role::firstOrCreate(['name' => 'Ketua Tim MBKM']);
        $roleMhs = Role::firstOrCreate(['name' => 'Mahasiswa']);

        $ketuaDpl = User::create([
            'nama' => 'Pak Habib Dual Role',
            'email' => 'habib.dual@fasilkom.ac.id',
            'nim_nip' => '19800101202601',
            'password' => bcrypt('password123'),
        ]);
        $ketuaDpl->roles()->sync([$roleDpl->id, $roleKetua->id, $roleTim->id]);

        $plainTim = User::create([
            'nama' => 'PIC Tim MBKM Biasa',
            'email' => 'tim.biasa@fasilkom.ac.id',
            'nim_nip' => '19850101202602',
            'password' => bcrypt('password123'),
        ]);
        $plainTim->roles()->sync([$roleTim->id, $roleDpl->id]);

        $mitraAssigned = Mitra::create([
            'nama' => 'PT Mitra Ditugaskan',
            'alamat' => 'Jl. Kaliurang No. 1',
            'kontak' => '081234567890',
            'email' => 'assigned@mitra.id',
        ]);
        $mitraOther = Mitra::create([
            'nama' => 'PT Mitra Lain',
            'alamat' => 'Jl. Sudirman No. 2',
            'kontak' => '081234567891',
            'email' => 'other@mitra.id',
        ]);

        foreach ([$mitraAssigned, $mitraOther] as $idx => $mitra) {
            $mhs = User::create([
                'nama' => "Mhs Monev {$idx}",
                'email' => "mhs.monev.{$idx}@fasilkom.ac.id",
                'nim_nip' => "2424777000{$idx}",
                'password' => bcrypt('password123'),
            ]);
            $mhs->roles()->sync([$roleMhs->id]);

            PendaftaranMbkm::create([
                'user_id' => $mhs->id,
                'nim' => "2424777000{$idx}",
                'nama_lengkap' => "Mhs Monev {$idx}",
                'program_studi_id' => $prodi->id,
                'mitra_id' => $mitra->id,
                'mitra_magang_text' => $mitra->nama,
                'posisi_magang' => 'Software Engineer',
                'periode_akademik_id' => $periode->id,
                'pic_verifikator_id' => $ketuaDpl->id,
                'is_plotted_pic' => true,
                'dpl_id' => $ketuaDpl->id,
                'transkrip_file_name' => 't.pdf',
                'transkrip_file_path' => 'p/t.pdf',
                'transkrip_file_size' => '100 KB',
                'loa_file_name' => 'l.pdf',
                'loa_file_path' => 'p/l.pdf',
                'loa_file_size' => '100 KB',
                'status' => 'Terverifikasi',
            ]);
        }

        PlottingPemonevMitra::create([
            'mitra_id' => $mitraAssigned->id,
            'periode_akademik_id' => $periode->id,
            'dosen_id' => $ketuaDpl->id,
            'assigned_by' => $ketuaDpl->id,
        ]);

        // 1. Dual-role saat bertindak sebagai DPL -> HANYA boleh assigned, ditolak scope=all, global & mitra unassigned
        $resDplAssigned = $this->actingAs($ketuaDpl, 'sanctum')
            ->getJson('/api/v1/monev-mitra/my-assignments?scope=assigned&active_role=DPL')
            ->assertOk();
        $resDplAssigned->assertJsonCount(1, 'data');
        $resDplAssigned->assertJsonPath('data.0.mitra_id', $mitraAssigned->id);

        $this->actingAs($ketuaDpl, 'sanctum')
            ->getJson('/api/v1/monev-mitra/my-assignments?scope=all&active_role=DPL')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_MONEV_FORBIDDEN_SCOPE');

        $this->actingAs($ketuaDpl, 'sanctum')
            ->getJson('/api/v1/monev-mitra/rekap-hasil-global?active_role=DPL')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_MONEV_FORBIDDEN_SCOPE');

        $this->actingAs($ketuaDpl, 'sanctum')
            ->getJson("/api/v1/monev-mitra/{$mitraOther->id}?active_role=DPL")
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_MONEV_FORBIDDEN_SCOPE');

        // 2. Role Tim MBKM biasa (non-Ketua) juga dilarang akses scope=all & global
        $this->actingAs($plainTim, 'sanctum')
            ->getJson('/api/v1/monev-mitra/my-assignments?scope=all&active_role=Tim+MBKM')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_MONEV_FORBIDDEN_SCOPE');

        $this->actingAs($plainTim, 'sanctum')
            ->getJson('/api/v1/monev-mitra/rekap-hasil-global?active_role=Tim+MBKM')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_MONEV_FORBIDDEN_SCOPE');

        // 3. Dual-role saat bertindak sebagai Ketua Tim MBKM -> diperbolehkan akses Semua Mitra, Rekap Global, & Mitra lain
        $resKetuaAll = $this->actingAs($ketuaDpl, 'sanctum')
            ->getJson('/api/v1/monev-mitra/my-assignments?scope=all&active_role=Ketua+Tim+MBKM')
            ->assertOk();
        $resKetuaAll->assertJsonCount(2, 'data');

        $this->actingAs($ketuaDpl, 'sanctum')
            ->getJson('/api/v1/monev-mitra/rekap-hasil-global?active_role=Ketua+Tim+MBKM')
            ->assertOk();

        $this->actingAs($ketuaDpl, 'sanctum')
            ->getJson("/api/v1/monev-mitra/{$mitraOther->id}?active_role=Ketua+Tim+MBKM")
            ->assertOk()
            ->assertJsonPath('data.mitra.id', $mitraOther->id);
    }

    public function test_kuisioner_auto_clone_on_new_period_and_same_period_reopen_behavior(): void
    {
        $periode1 = PeriodeAkademik::where('is_aktif', true)->firstOrFail();
        $prodi = ProgramStudi::firstOrFail();

        $roleGpm = Role::firstOrCreate(['name' => 'GPM']);
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin']);
        $roleDpl = Role::firstOrCreate(['name' => 'DPL']);
        $roleMhs = Role::firstOrCreate(['name' => 'Mahasiswa']);

        $gpm = User::create([
            'nama' => 'GPM Fasilkom',
            'email' => 'gpm.test2@fasilkom.ac.id',
            'nim_nip' => '19880101202610',
            'password' => bcrypt('password123'),
        ]);
        $gpm->roles()->sync([$roleGpm->id]);

        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first();
        if (! $admin) {
            $admin = User::create([
                'nama' => 'Admin Test',
                'email' => 'admin.test2@fasilkom.ac.id',
                'nim_nip' => '19800101202699',
                'password' => bcrypt('password123'),
            ]);
            $admin->roles()->sync([$roleAdmin->id]);
        }

        $dpl = User::create([
            'nama' => 'Dosen Pembimbing Kuisioner',
            'email' => 'dpl.kuis@fasilkom.ac.id',
            'nim_nip' => '19820101202620',
            'password' => bcrypt('password123'),
        ]);
        $dpl->roles()->sync([$roleDpl->id]);

        $mitra = Mitra::create([
            'nama' => 'PT Kuisioner Nusantara',
            'alamat' => 'Jl. Kalimantan',
            'kontak' => '0811112222',
            'email' => 'hr@kuis.id',
        ]);

        $mhs1 = User::create([
            'nama' => 'Mahasiswa Responden 1',
            'email' => 'mhs1.kuis@fasilkom.ac.id',
            'nim_nip' => '24248880001',
            'password' => bcrypt('password123'),
        ]);
        $mhs1->roles()->sync([$roleMhs->id]);

        $mhs2 = User::create([
            'nama' => 'Mahasiswa Responden 2',
            'email' => 'mhs2.kuis@fasilkom.ac.id',
            'nim_nip' => '24248880002',
            'password' => bcrypt('password123'),
        ]);
        $mhs2->roles()->sync([$roleMhs->id]);

        $p1 = PendaftaranMbkm::create([
            'user_id' => $mhs1->id,
            'nim' => '24248880001',
            'nama_lengkap' => 'Mahasiswa Responden 1',
            'program_studi_id' => $prodi->id,
            'mitra_id' => $mitra->id,
            'mitra_magang_text' => $mitra->nama,
            'posisi_magang' => 'Frontend',
            'periode_akademik_id' => $periode1->id,
            'dpl_id' => $dpl->id,
            'transkrip_file_name' => 't1.pdf',
            'transkrip_file_path' => 'p/t1.pdf',
            'transkrip_file_size' => '100 KB',
            'loa_file_name' => 'l1.pdf',
            'loa_file_path' => 'p/l1.pdf',
            'loa_file_size' => '100 KB',
            'status' => 'Terverifikasi',
        ]);

        $p2 = PendaftaranMbkm::create([
            'user_id' => $mhs2->id,
            'nim' => '24248880002',
            'nama_lengkap' => 'Mahasiswa Responden 2',
            'program_studi_id' => $prodi->id,
            'mitra_id' => $mitra->id,
            'mitra_magang_text' => $mitra->nama,
            'posisi_magang' => 'Backend',
            'periode_akademik_id' => $periode1->id,
            'dpl_id' => $dpl->id,
            'transkrip_file_name' => 't2.pdf',
            'transkrip_file_path' => 'p/t2.pdf',
            'transkrip_file_size' => '100 KB',
            'loa_file_name' => 'l2.pdf',
            'loa_file_path' => 'p/l2.pdf',
            'loa_file_size' => '100 KB',
            'status' => 'Terverifikasi',
        ]);

        // 1. Cek inisialisasi periode 1 & tambah kategori baru otomatis is_active = true
        $this->actingAs($gpm, 'sanctum')
            ->getJson('/api/v1/kuisioner/sesi')
            ->assertOk()
            ->assertJsonPath('data.status_sesi', 'draft');

        $katCustomRes = $this->actingAs($gpm, 'sanctum')
            ->postJson('/api/v1/kuisioner/kategori', [
                'nama' => 'Evaluasi Fasilitas Khusus',
                'target_evaluasi' => 'umum',
                'is_active' => false, // harus tetap aktif otomatis sesuai aturan baru
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.tahun_ajaran_id', $periode1->id);

        $katCustomId = $katCustomRes->json('data.id');

        $this->actingAs($gpm, 'sanctum')
            ->postJson('/api/v1/kuisioner/pertanyaan', [
                'kategori_id' => $katCustomId,
                'pertanyaan' => 'Apakah panduan MBKM jelas?',
                'tipe_input' => 'likert_4',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.tahun_ajaran_id', $periode1->id);

        $soalDplP1 = KuisionerPertanyaan::where('tahun_ajaran_id', $periode1->id)
            ->where('kategori', 'dpl')
            ->firstOrFail();
        $soalMitraP1 = KuisionerPertanyaan::where('tahun_ajaran_id', $periode1->id)
            ->where('kategori', 'mitra')
            ->where('tipe_input', '!=', 'textarea')
            ->firstOrFail();

        // 2. Publikasikan periode 1, Mhs 1 submit
        $this->actingAs($gpm, 'sanctum')
            ->postJson('/api/v1/kuisioner/publish')
            ->assertOk();

        $this->actingAs($mhs1, 'sanctum')
            ->getJson('/api/v1/kuisioner/mahasiswa')
            ->assertOk()
            ->assertJsonPath('data.status_sesi', 'published')
            ->assertJsonPath('data.has_submitted', false);

        $this->actingAs($mhs1, 'sanctum')
            ->postJson('/api/v1/kuisioner/jawaban', [
                'pendaftaran_mbkm_id' => $p1->id,
                'jawaban_dpl' => [(string) $soalDplP1->id => 4],
                'jawaban_mitra' => [(string) $soalMitraP1->id => 4],
                'saran_masukan' => 'Sangat terbantu.',
            ])
            ->assertOk();

        // 3. Tutup sesi -> + Buat Sesi Baru (Draf) -> Publikasikan lagi pada periode sama
        $this->actingAs($gpm, 'sanctum')
            ->postJson('/api/v1/kuisioner/close')
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $this->actingAs($mhs2, 'sanctum')
            ->getJson('/api/v1/kuisioner/mahasiswa')
            ->assertOk()
            ->assertJsonPath('data.status_sesi', 'closed');

        $this->actingAs($gpm, 'sanctum')
            ->postJson('/api/v1/kuisioner/draft')
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $this->actingAs($gpm, 'sanctum')
            ->postJson('/api/v1/kuisioner/publish')
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        // Mhs 1 yang sudah isi tidak bisa submit ulang
        $this->actingAs($mhs1, 'sanctum')
            ->postJson('/api/v1/kuisioner/jawaban', [
                'pendaftaran_mbkm_id' => $p1->id,
                'jawaban_dpl' => [(string) $soalDplP1->id => 3],
                'jawaban_mitra' => [(string) $soalMitraP1->id => 3],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_KUISIONER_ALREADY_SUBMITTED');

        // Mhs 2 yang belum isi bisa melihat & mengisi kuesioner saat dibuka lagi
        $this->actingAs($mhs2, 'sanctum')
            ->getJson('/api/v1/kuisioner/mahasiswa')
            ->assertOk()
            ->assertJsonPath('data.status_sesi', 'published')
            ->assertJsonPath('data.has_submitted', false);

        $this->actingAs($mhs2, 'sanctum')
            ->postJson('/api/v1/kuisioner/jawaban', [
                'pendaftaran_mbkm_id' => $p2->id,
                'jawaban_dpl' => [(string) $soalDplP1->id => 3],
                'jawaban_mitra' => [(string) $soalMitraP1->id => 4],
                'saran_masukan' => 'Bagus.',
            ])
            ->assertOk();

        // 4. Aktivasi Tahun Ajaran baru -> otomatis clone template (termasuk kategori khusus) dalam status Draf & 0 responden
        $resNewPeriode = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/periode-akademik', [
                'tahun_ajaran' => '2027/2028',
                'semester' => 'Ganjil',
                'is_aktif' => true,
            ])
            ->assertStatus(201);

        $periode2Id = $resNewPeriode->json('data.id');

        $this->actingAs($gpm, 'sanctum')
            ->getJson('/api/v1/kuisioner/sesi')
            ->assertOk()
            ->assertJsonPath('data.status_sesi', 'draft')
            ->assertJsonPath('data.total_responden', 0)
            ->assertJsonPath('data.periode_akademik_id', $periode2Id);

        $katsP2 = KuisionerKategori::where('tahun_ajaran_id', $periode2Id)->get();
        $this->assertTrue($katsP2->contains(fn ($k) => $k->nama === 'Evaluasi Fasilitas Khusus'));

        $clonedCustomKat = $katsP2->firstWhere('nama', 'Evaluasi Fasilitas Khusus');
        $this->assertNotEquals($katCustomId, $clonedCustomKat->id);
        $this->assertDatabaseHas('kuisioner_pertanyaans', [
            'tahun_ajaran_id' => $periode2Id,
            'kategori_id' => $clonedCustomKat->id,
            'pertanyaan' => 'Apakah panduan MBKM jelas?',
        ]);

        // Rekap periode 1 tetap aman (2 responden), sedangkan rekap periode 2 masih 0 responden
        $this->actingAs($gpm, 'sanctum')
            ->getJson("/api/v1/kuisioner/rekap?tahun_ajaran_id={$periode1->id}")
            ->assertOk()
            ->assertJsonPath('data.statistik.total_responden', 2);

        $this->actingAs($gpm, 'sanctum')
            ->getJson("/api/v1/kuisioner/rekap?tahun_ajaran_id={$periode2Id}")
            ->assertOk()
            ->assertJsonPath('data.statistik.total_responden', 0);

        // Semua tahun ajaran juga menampilkan kedua responden tanpa duplikasi soal
        $this->assertEquals(2, KuisionerJawaban::count());
        $this->assertNotNull(KuisionerSesi::where('periode_akademik_id', $periode2Id)->first());
    }
}
