<?php

namespace Tests\Feature;

use App\Models\BimbinganMagang;
use App\Models\DplPeriodeLink;
use App\Models\KuisionerSesi;
use App\Models\MataKuliah;
use App\Models\Mitra;
use App\Models\PendaftaranMbkm;
use App\Models\PengajuanSemhas;
use App\Models\PenilaianDpl;
use App\Models\PenilaianSemhas;
use App\Models\PenilaianSupervisor;
use App\Models\PeriodeAkademik;
use App\Models\ProgramStudi;
use App\Models\Rkm;
use App\Models\RkmMataKuliah;
use App\Models\Role;
use App\Models\SuratPengantar;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PendaftaranMbkmSeeder;
use Database\Seeders\RkmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMahasiswaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(PendaftaranMbkmSeeder::class);
        $this->seed(RkmSeeder::class);
    }

    public function test_unregistered_mahasiswa_gets_200_with_empty_state_and_no_dummy_data(): void
    {
        $mhsRole = Role::where('name', 'Mahasiswa')->firstOrFail();
        $freshMhs = User::create([
            'nama' => 'Mahasiswa Baru Belum Daftar',
            'email' => 'baru.mhs@fasilkom.ac.id',
            'nim_nip' => '252410109999',
            'password' => bcrypt('password123'),
        ]);
        $freshMhs->roles()->attach($mhsRole->id);

        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $res = $this->actingAs($freshMhs, 'sanctum')->getJson('/api/v1/dashboard/mahasiswa');
        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.periode.id', $periodeAktif->id)
            ->assertJsonPath('data.ringkasan.has_pendaftaran', false)
            ->assertJsonPath('data.ringkasan.status_pendaftaran', 'Belum Mendaftar')
            ->assertJsonPath('data.ringkasan.status_rkm', 'Belum Diajukan')
            ->assertJsonPath('data.ringkasan.total_sks_aktif', 0)
            ->assertJsonPath('data.ringkasan.bimbingan_count', 0)
            ->assertJsonPath('data.ringkasan.komponen_nilai_terisi', 0)
            ->assertJsonPath('data.ringkasan.rata_rata_nilai_akhir', null)
            ->assertJsonPath('data.perubahan_status', [])
            ->assertJsonPath('data.informasi_magang.mitra_magang', '-');

        $this->assertCount(6, $res->json('data.alur_magang'));
    }

    public function test_detects_perbaikan_and_ditolak_status_changes_across_all_4_modules(): void
    {
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();
        $prodi = ProgramStudi::firstOrFail();
        $mitra = Mitra::firstOrFail();
        $mhsRole = Role::where('name', 'Mahasiswa')->firstOrFail();

        $mhsUser = User::create([
            'nama' => 'Mahasiswa Uji Revisi',
            'email' => 'revisi.mhs@fasilkom.ac.id',
            'nim_nip' => '252410108888',
            'password' => bcrypt('password123'),
        ]);
        $mhsUser->roles()->attach($mhsRole->id);

        // 1. Surat Pengantar berstatus Perbaikan
        SuratPengantar::create([
            'user_id' => $mhsUser->id,
            'periode_akademik_id' => $periodeAktif->id,
            'tujuan_instansi' => 'PT. Uji Coba',
            'alamat_instansi' => 'Jl. Merdeka No. 1',
            'kota_instansi' => 'Jember',
            'status' => 'Perbaikan',
            'catatan' => 'Nama penerima pada surat pengantar kurang lengkap.',
        ]);

        // 2. Pendaftaran MBKM berstatus Ditolak
        $pendaftaran = PendaftaranMbkm::create([
            'user_id' => $mhsUser->id,
            'nim' => '252410108888',
            'nama_lengkap' => 'Mahasiswa Uji Revisi',
            'program_studi_id' => $prodi->id,
            'mitra_id' => $mitra->id,
            'mitra_magang_text' => $mitra->nama,
            'posisi_magang' => 'Backend Engineer',
            'periode_akademik_id' => $periodeAktif->id,
            'transkrip_file_name' => 'transkrip.pdf',
            'transkrip_file_path' => 'pendaftaran/transkrip.pdf',
            'transkrip_file_size' => '120 KB',
            'loa_file_name' => 'loa.pdf',
            'loa_file_path' => 'pendaftaran/loa.pdf',
            'loa_file_size' => '150 KB',
            'status' => 'Ditolak',
            'catatan' => 'Transkrip tidak terbaca jelas, pendaftaran ditolak.',
        ]);

        // 3. RKM berstatus Perbaikan RKM
        Rkm::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $periodeAktif->id,
            'user_id' => $mhsUser->id,
            'total_sks' => 12,
            'status' => 'Perbaikan RKM',
            'catatan_pic' => 'Tambahkan jobdesk yang sesuai dengan CPL mata kuliah.',
        ]);

        // 4. Semhas berstatus Perbaikan Berkas
        PengajuanSemhas::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $periodeAktif->id,
            'laporan_akhir_path' => 'semhas/lap.pdf',
            'logbook_fb07_path' => 'semhas/log.pdf',
            'nilai_supervisor_path' => 'semhas/spv.pdf',
            'status_kelayakan' => 'Perbaikan Berkas',
            'catatan_pic' => 'Lembar nilai supervisor belum ditandatangani.',
            'is_override_ketua' => false,
        ]);

        $res = $this->actingAs($mhsUser, 'sanctum')->getJson('/api/v1/dashboard/mahasiswa');
        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ringkasan.has_pendaftaran', true)
            ->assertJsonPath('data.ringkasan.status_pendaftaran', 'Ditolak')
            ->assertJsonPath('data.ringkasan.status_rkm', 'Perbaikan RKM')
            ->assertJsonPath('data.ringkasan.status_semhas', 'Perbaikan');

        $perubahan = collect($res->json('data.perubahan_status'));
        $this->assertCount(4, $perubahan);

        $suratItem = $perubahan->firstWhere('modul', 'Surat Pengantar');
        $this->assertEquals('perbaikan', $suratItem['kategori']);
        $this->assertEquals('/mahasiswa/surat-pengantar', $suratItem['action_url']);
        $this->assertStringContainsString('Nama penerima', $suratItem['catatan']);

        $pendaftaranItem = $perubahan->firstWhere('modul', 'Pendaftaran MBKM');
        $this->assertEquals('ditolak', $pendaftaranItem['kategori']);
        $this->assertEquals('/mahasiswa/pendaftaran', $pendaftaranItem['action_url']);
        $this->assertStringContainsString('Transkrip', $pendaftaranItem['catatan']);

        $rkmItem = $perubahan->firstWhere('modul', 'Rencana Kegiatan Mahasiswa (RKM)');
        $this->assertEquals('perbaikan', $rkmItem['kategori']);
        $this->assertEquals('/mahasiswa/rkm', $rkmItem['action_url']);
        $this->assertStringContainsString('jobdesk', $rkmItem['catatan']);

        $semhasItem = $perubahan->firstWhere('modul', 'Pendaftaran Seminar Hasil');
        $this->assertEquals('perbaikan', $semhasItem['kategori']);
        $this->assertEquals('/mahasiswa/semhas', $semhasItem['action_url']);
        $this->assertStringContainsString('Lembar nilai supervisor', $semhasItem['catatan']);
    }

    public function test_full_progress_summary_sks_bimbingan_kuesioner_semhas_and_final_grade(): void
    {
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();
        $dpl = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))->firstOrFail();
        $penguji = User::whereHas('roles', fn ($q) => $q->where('name', 'Penguji Semhas'))->firstOrFail();

        // Gunakan mahasiswa Aulia (232410103021) yang sudah memiliki RKM Disetujui di periode aktif
        $mhsUser = User::where('nim_nip', '232410103021')->firstOrFail();
        $pendaftaran = PendaftaranMbkm::where('user_id', $mhsUser->id)
            ->where('periode_akademik_id', $periodeAktif->id)
            ->firstOrFail();

        $pendaftaran->update([
            'dpl_id' => $dpl->id,
            'penguji_semhas_id' => $penguji->id,
            'link_logbook' => 'https://docs.google.com/document/d/aulia-logbook',
            'sudah_isi_kuisioner' => false,
        ]);

        DplPeriodeLink::updateOrCreate(
            ['dpl_id' => $dpl->id, 'periode_akademik_id' => $periodeAktif->id],
            ['link_wa' => 'https://chat.whatsapp.com/GrupDplAktif']
        );

        KuisionerSesi::updateOrCreate(
            ['periode_akademik_id' => $periodeAktif->id],
            ['status' => 'published', 'published_at' => now()]
        );

        // Cek reminder kuesioner ketika RKM Disetujui & kuesioner published namun belum diisi
        $resBeforeQuiz = $this->actingAs($mhsUser, 'sanctum')->getJson('/api/v1/dashboard/mahasiswa');
        $resBeforeQuiz->assertOk()
            ->assertJsonPath('data.ringkasan.status_kuesioner', 'Belum Mengisi');
        $this->assertNotEmpty(
            collect($resBeforeQuiz->json('data.info_tindak_lanjut'))->firstWhere('action_url', '/mahasiswa/kuisioner')
        );

        // Penuhi 8 sesi bimbingan, kuesioner, semhas layak + jadwal, dan nilai 3/3 komponen
        BimbinganMagang::where('pendaftaran_mbkm_id', $pendaftaran->id)->delete();
        for ($i = 1; $i <= 8; $i++) {
            BimbinganMagang::create([
                'pendaftaran_mbkm_id' => $pendaftaran->id,
                'dpl_id' => $dpl->id,
                'periode_akademik_id' => $periodeAktif->id,
                'bimbingan_ke' => $i,
                'tanggal_bimbingan' => now()->subDays(10 - $i)->format('Y-m-d'),
                'catatan_bimbingan' => "Bimbingan {$i}",
            ]);
        }

        $pendaftaran->update(['sudah_isi_kuisioner' => true]);

        PengajuanSemhas::updateOrCreate(
            ['pendaftaran_mbkm_id' => $pendaftaran->id],
            [
                'periode_akademik_id' => $periodeAktif->id,
                'laporan_akhir_path' => 'semhas/lap.pdf',
                'logbook_fb07_path' => 'semhas/log.pdf',
                'nilai_supervisor_path' => 'semhas/spv.pdf',
                'status_kelayakan' => 'Layak Semhas',
                'jadwal_tanggal' => '2026-12-05',
                'jadwal_waktu_mulai' => '09:00',
                'jadwal_waktu_selesai' => '10:00',
                'jadwal_lokasi' => 'Ruang Sidang 1',
            ]
        );

        $rkm = Rkm::with('items')->where('pendaftaran_mbkm_id', $pendaftaran->id)->firstOrFail();
        $activeMk = $rkm->items->where('status_mk', '!=', 'dropped')->first()
            ?? RkmMataKuliah::where('rkm_id', $rkm->id)->firstOrFail();
        RkmMataKuliah::where('rkm_id', $rkm->id)
            ->where('id', '!=', $activeMk->id)
            ->update(['status_mk' => 'dropped']);

        // Nilai: SPV 90 * 0.45 + DPL 80 * 0.35 + Penguji 85 * 0.20 = 85.50
        $spv = PenilaianSupervisor::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $periodeAktif->id,
            'nama_supervisor' => 'Spv Test',
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

        $semhasNilai = PenilaianSemhas::create([
            'pendaftaran_mbkm_id' => $pendaftaran->id,
            'periode_akademik_id' => $periodeAktif->id,
            'penguji_id' => $penguji->id,
            'nilai_kelengkapan_laporan' => 85,
            'nilai_slide' => 85,
            'nilai_cara_presentasi' => 85,
            'nilai_tanya_jawab' => 85,
            'nilai_presentasi' => 85,
            'rata_rata_nilai_total' => 85.0,
        ]);
        $semhasNilai->mataKuliahs()->create([
            'kode_mk' => $activeMk->kode_mk,
            'nama_mk' => $activeMk->nama_mk,
            'nilai_mata_kuliah' => 85,
            'nilai_presentasi' => 85,
            'nilai_total' => 85,
        ]);

        $res = $this->actingAs($mhsUser, 'sanctum')->getJson('/api/v1/dashboard/mahasiswa');
        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ringkasan.has_pendaftaran', true)
            ->assertJsonPath('data.ringkasan.status_pendaftaran', 'Terverifikasi')
            ->assertJsonPath('data.ringkasan.status_rkm', 'Disetujui')
            ->assertJsonPath('data.ringkasan.total_sks_aktif', (int) $activeMk->sks)
            ->assertJsonPath('data.ringkasan.total_mk_aktif', 1)
            ->assertJsonPath('data.ringkasan.bimbingan_count', 8)
            ->assertJsonPath('data.ringkasan.status_kuesioner', 'Sudah Mengisi')
            ->assertJsonPath('data.ringkasan.status_semhas', 'Layak Semhas')
            ->assertJsonPath('data.ringkasan.jadwal_semhas_tanggal', '2026-12-05')
            ->assertJsonPath('data.ringkasan.komponen_nilai_terisi', 3)
            ->assertJsonPath('data.informasi_magang.link_wa_dpl', 'https://chat.whatsapp.com/GrupDplAktif')
            ->assertJsonPath('data.informasi_magang.link_logbook', 'https://docs.google.com/document/d/aulia-logbook');

        $this->assertEquals(85.5, (float) $res->json('data.ringkasan.rata_rata_nilai_akhir'));

        $alur = collect($res->json('data.alur_magang'));
        $this->assertCount(6, $alur);
        $this->assertTrue($alur->every(fn ($step) => $step['status'] === 'selesai'));

        // Pastikan filter periode kosong (2025/2026 Ganjil) terisolasi bersih
        $periodeKosong = PeriodeAkademik::where('tahun_ajaran', '2025/2026')
            ->where('semester', 'Ganjil')
            ->firstOrFail();
        $this->actingAs($mhsUser, 'sanctum')
            ->getJson("/api/v1/dashboard/mahasiswa?periode_akademik_id={$periodeKosong->id}")
            ->assertOk()
            ->assertJsonPath('data.ringkasan.has_pendaftaran', false);
    }

    public function test_non_mahasiswa_roles_are_forbidden_403(): void
    {
        $ketua = User::whereHas('roles', fn ($q) => $q->where('name', 'Ketua Tim MBKM'))->firstOrFail();
        $dpl = User::whereHas('roles', fn ($q) => $q->where('name', 'DPL'))
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Mahasiswa'))
            ->firstOrFail();

        $this->actingAs($ketua, 'sanctum')
            ->getJson('/api/v1/dashboard/mahasiswa')
            ->assertStatus(403);

        $this->actingAs($dpl, 'sanctum')
            ->getJson('/api/v1/dashboard/mahasiswa')
            ->assertStatus(403);
    }
}
