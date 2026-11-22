<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CplController;
use App\Http\Controllers\Api\V1\KuisionerController;
use App\Http\Controllers\Api\V1\KuisionerSessionController;
use App\Http\Controllers\Api\V1\MasterDataController;
use App\Http\Controllers\Api\V1\MataKuliahController;
use App\Http\Controllers\Api\V1\MitraController;
use App\Http\Controllers\Api\V1\MonevMitraController;
use App\Http\Controllers\Api\V1\MonitoringBimbinganController;
use App\Http\Controllers\Api\V1\PaketKonversiController;
use App\Http\Controllers\Api\V1\PendaftaranMbkmController;
use App\Http\Controllers\Api\V1\PenilaianSemhasController;
use App\Http\Controllers\Api\V1\PlottingController;
use App\Http\Controllers\Api\V1\RkmController;
use App\Http\Controllers\Api\V1\RkpController;
use App\Http\Controllers\Api\V1\SemhasController;
use App\Http\Controllers\Api\V1\SuratPengantarController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VerifikasiRkmController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::get('auth/sso/redirect', [AuthController::class, 'ssoRedirect']);
    Route::get('auth/sso/callback', [AuthController::class, 'ssoCallback']);
    Route::post('auth/sso/callback', [AuthController::class, 'ssoCallback']);

    Route::get('program-studi', [MasterDataController::class, 'programStudi']);
    Route::get('periode-akademik', [MasterDataController::class, 'periodeAkademik']);
    Route::get('periode-akademik/aktif', [MasterDataController::class, 'periodeAktif']);
    Route::get('tahun-ajaran', [MasterDataController::class, 'periodeAkademik']);
    Route::get('tahun-ajaran/aktif', [MasterDataController::class, 'periodeAktif']);

    Route::get('kuisioner/kategori', [KuisionerController::class, 'kategori']);
    Route::get('kuisioner/pertanyaan', [KuisionerController::class, 'pertanyaan']);
    Route::get('kuisioner/rekap', [KuisionerController::class, 'rekap']);

    Route::get('mata-kuliah', [MataKuliahController::class, 'index']);
    Route::get('mata-kuliah/{id}', [MataKuliahController::class, 'show']);

    Route::get('cpl', [CplController::class, 'index']);
    Route::get('cpl/{id}', [CplController::class, 'show']);

    Route::get('paket-konversi', [PaketKonversiController::class, 'index']);
    Route::get('paket-konversi/{id}', [PaketKonversiController::class, 'show']);

    Route::get('mitra', [MitraController::class, 'index']);
    Route::get('mitra/{id}', [MitraController::class, 'show']);
    Route::get('mitra/{id}/file-pks', [MitraController::class, 'streamFilePks']);

    Route::get('pendaftaran-mbkm/{id}/file/{jenis}', [PendaftaranMbkmController::class, 'streamFile']);
    Route::get('rkp/{rkm_id}/pdf', [RkpController::class, 'downloadPdf']);
    Route::get('surat-pengantar/{id}/pdf', [SuratPengantarController::class, 'streamPdf']);
    Route::get('semhas/{id}/file/{jenis}', [SemhasController::class, 'streamFile']);
    Route::get('monev-mitra/{monevId}/file', [MonevMitraController::class, 'streamFile']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('pendaftaran-mbkm', [PendaftaranMbkmController::class, 'index']);
        Route::get('pendaftaran-mbkm/my', [PendaftaranMbkmController::class, 'myPendaftaran']);
        Route::get('pendaftaran-mbkm/{id}', [PendaftaranMbkmController::class, 'show']);
        Route::post('pendaftaran-mbkm', [PendaftaranMbkmController::class, 'store'])
            ->middleware('check.active.periode');
        Route::post('pendaftaran-mbkm/{id}/verifikasi', [PendaftaranMbkmController::class, 'verifikasi'])
            ->middleware('check.active.periode:PendaftaranMbkm,id');

        Route::get('tim-mbkm/verifikasi-mbkm', [PendaftaranMbkmController::class, 'index']);
        Route::post('tim-mbkm/verifikasi-mbkm/{id}', [PendaftaranMbkmController::class, 'verifikasi'])
            ->middleware('check.active.periode:PendaftaranMbkm,id');
        Route::put('tim-mbkm/verifikasi-mbkm/{id}', [PendaftaranMbkmController::class, 'verifikasi'])
            ->middleware('check.active.periode:PendaftaranMbkm,id');

        Route::prefix('plotting')->group(function () {
            Route::get('dosen-list', [PlottingController::class, 'dosenList']);
            Route::get('tahap-1-pic', [PlottingController::class, 'tahap1Pic']);
            Route::post('tahap-1-pic', [PlottingController::class, 'storeTahap1Pic'])
                ->middleware('check.active.periode');
            Route::get('tahap-2-dpl-penguji', [PlottingController::class, 'tahap2DplPenguji']);
            Route::post('tahap-2-dpl-penguji', [PlottingController::class, 'storeTahap2DplPenguji'])
                ->middleware('check.active.periode');
            Route::post('penguji', [PlottingController::class, 'storeTahap2DplPenguji'])
                ->middleware('check.active.periode');
            Route::post('dpl', [PlottingController::class, 'storeTahap2DplPenguji'])
                ->middleware('check.active.periode');
        });

        Route::middleware('pendaftaran.terverifikasi')->prefix('rkm')->group(function () {
            Route::get('available-courses', [RkmController::class, 'availableCourses']);
            Route::get('my-rkm', [RkmController::class, 'myRkm']);

            Route::middleware('ensure.rkm.unlocked')->group(function () {
                Route::post('save-selection', [RkmController::class, 'saveSelection'])
                    ->middleware('check.active.periode');
                Route::post('items/{rkm_mk_id}/jobdesk', [RkmController::class, 'addJobdesk'])
                    ->middleware('check.active.periode:RkmMataKuliah,rkm_mk_id');
                Route::put('jobdesks/{jobdesk_id}', [RkmController::class, 'updateJobdesk'])
                    ->middleware('check.active.periode:RkmJobdesk,jobdesk_id');
                Route::delete('jobdesks/{jobdesk_id}', [RkmController::class, 'deleteJobdesk'])
                    ->middleware('check.active.periode:RkmJobdesk,jobdesk_id');
                Route::delete('items/{rkm_mk_id}', [RkmController::class, 'deleteItem'])
                    ->middleware('check.active.periode:RkmMataKuliah,rkm_mk_id');
                Route::post('submit', [RkmController::class, 'submit'])
                    ->middleware('check.active.periode');
            });
        });

        Route::prefix('tim-mbkm/verifikasi-rkm')->group(function () {
            Route::get('/', [VerifikasiRkmController::class, 'index']);
            Route::get('{rkm_id}', [VerifikasiRkmController::class, 'show']);
            Route::post('items', [VerifikasiRkmController::class, 'addItemByPic'])
                ->middleware('check.active.periode');
            Route::put('items/{rkm_mk_id}', [VerifikasiRkmController::class, 'updateItemByPic'])
                ->middleware('check.active.periode:RkmMataKuliah,rkm_mk_id');
            Route::patch('items/{rkm_mk_id}/toggle-drop', [VerifikasiRkmController::class, 'toggleDropItem'])
                ->middleware('check.active.periode:RkmMataKuliah,rkm_mk_id');
            Route::put('jobdesks/{jobdesk_id}', [VerifikasiRkmController::class, 'updateJobdesk'])
                ->middleware('check.active.periode:RkmJobdesk,jobdesk_id');
            Route::post('items/{rkm_mk_id}/jobdesk', [VerifikasiRkmController::class, 'addJobdesk'])
                ->middleware('check.active.periode:RkmMataKuliah,rkm_mk_id');
            Route::delete('jobdesks/{jobdesk_id}', [VerifikasiRkmController::class, 'deleteJobdesk'])
                ->middleware('check.active.periode:RkmJobdesk,jobdesk_id');
            Route::post('{rkm_id}/keputusan', [VerifikasiRkmController::class, 'keputusan'])
                ->middleware('check.active.periode:Rkm,rkm_id');
        });

        Route::get('rkp/{rkm_id}/preview', [RkpController::class, 'preview']);
        Route::put('rkp/{rkm_id}/metadata', [RkpController::class, 'updateMetadata'])
            ->middleware('check.active.periode:Rkm,rkm_id');

        Route::get('surat-pengantar', [SuratPengantarController::class, 'index']);
        Route::get('surat-pengantar/{id}', [SuratPengantarController::class, 'show']);
        Route::post('surat-pengantar', [SuratPengantarController::class, 'store'])
            ->middleware('check.active.periode');
        Route::put('surat-pengantar/{id}', [SuratPengantarController::class, 'update'])
            ->middleware('check.active.periode:SuratPengantar,id');
        Route::post('surat-pengantar/{id}/upload', [SuratPengantarController::class, 'uploadPdf'])
            ->middleware('check.active.periode:SuratPengantar,id');
        Route::post('surat-pengantar/{id}/upload-pdf', [SuratPengantarController::class, 'uploadPdf'])
            ->middleware('check.active.periode:SuratPengantar,id');
        Route::get('surat-pengantar/{id}/pdf', [SuratPengantarController::class, 'streamPdf']);

        Route::post('periode-akademik', [MasterDataController::class, 'storePeriodeAkademik']);
        Route::put('periode-akademik/{id}', [MasterDataController::class, 'updatePeriodeAkademik']);
        Route::patch('periode-akademik/{id}/aktifkan', [MasterDataController::class, 'aktifkanPeriodeAkademik']);
        Route::delete('periode-akademik/{id}', [MasterDataController::class, 'destroyPeriodeAkademik']);

        Route::get('kuisioner/session-status', [KuisionerSessionController::class, 'status']);
        Route::get('kuisioner/sesi', [KuisionerSessionController::class, 'status']);

        Route::middleware('role:GPM,Ketua Tim MBKM,Tim MBKM,Admin')->group(function () {
            Route::post('kuisioner/session/publish', [KuisionerSessionController::class, 'publish'])
                ->middleware('check.active.periode');
            Route::post('kuisioner/publish', [KuisionerSessionController::class, 'publish'])
                ->middleware('check.active.periode');
            Route::post('kuisioner/session/unpublish', [KuisionerSessionController::class, 'unpublish'])
                ->middleware('check.active.periode');
            Route::post('kuisioner/unpublish', [KuisionerSessionController::class, 'unpublish'])
                ->middleware('check.active.periode');
            Route::post('kuisioner/session/close', [KuisionerSessionController::class, 'close'])
                ->middleware('check.active.periode');
            Route::post('kuisioner/close', [KuisionerSessionController::class, 'close'])
                ->middleware('check.active.periode');
            Route::post('kuisioner/session/draft', [KuisionerSessionController::class, 'draft'])
                ->middleware('check.active.periode');
            Route::post('kuisioner/draft', [KuisionerSessionController::class, 'draft'])
                ->middleware('check.active.periode');
        });

        Route::post('kuisioner/kategori', [KuisionerController::class, 'storeKategori'])
            ->middleware('check.active.periode');
        Route::put('kuisioner/kategori/{id}', [KuisionerController::class, 'updateKategori'])
            ->middleware('check.active.periode:KuisionerKategori,id');
        Route::delete('kuisioner/kategori/{id}', [KuisionerController::class, 'destroyKategori'])
            ->middleware('check.active.periode:KuisionerKategori,id');

        Route::post('kuisioner/pertanyaan', [KuisionerController::class, 'storePertanyaan'])
            ->middleware('check.active.periode');
        Route::put('kuisioner/pertanyaan/{id}', [KuisionerController::class, 'updatePertanyaan'])
            ->middleware('check.active.periode:KuisionerPertanyaan,id');
        Route::delete('kuisioner/pertanyaan/{id}', [KuisionerController::class, 'destroyPertanyaan'])
            ->middleware('check.active.periode:KuisionerPertanyaan,id');

        Route::get('kuisioner/mahasiswa', [KuisionerController::class, 'mahasiswaKuisioner']);
        Route::get('kuisioner/jawaban', [KuisionerController::class, 'indexJawaban']);
        Route::post('kuisioner/jawaban', [KuisionerController::class, 'storeJawaban'])
            ->middleware('check.active.periode');

        Route::get('tim-mbkm/dashboard-stats', [MasterDataController::class, 'timMbkmDashboardStats']);

        Route::prefix('monitoring-bimbingan')->group(function () {
            Route::get('dpl/mahasiswa', [MonitoringBimbinganController::class, 'getDplMahasiswaList']);
            Route::post('dpl/link-wa', [MonitoringBimbinganController::class, 'saveDplWhatsappLink'])
                ->middleware('check.active.periode');

            Route::get('my', [MonitoringBimbinganController::class, 'getMyMonitoring']);
            Route::post('my/link-logbook', [MonitoringBimbinganController::class, 'saveMyLogbookLink'])
                ->middleware('check.active.periode');

            Route::get('mahasiswa/{pendaftaranId}', [MonitoringBimbinganController::class, 'getMonitoringDetail'])
                ->whereNumber('pendaftaranId');
            Route::post('mahasiswa/{pendaftaranId}', [MonitoringBimbinganController::class, 'storeBimbinganSession'])
                ->whereNumber('pendaftaranId')
                ->middleware('check.active.periode:PendaftaranMbkm,pendaftaranId');

            Route::put('{bimbinganId}', [MonitoringBimbinganController::class, 'updateBimbinganSession'])
                ->whereNumber('bimbinganId')
                ->middleware('check.active.periode:BimbinganMagang,bimbinganId');
            Route::post('{bimbinganId}', [MonitoringBimbinganController::class, 'updateBimbinganSession'])
                ->whereNumber('bimbinganId')
                ->middleware('check.active.periode:BimbinganMagang,bimbinganId');
            Route::delete('{bimbinganId}', [MonitoringBimbinganController::class, 'deleteBimbinganSession'])
                ->whereNumber('bimbinganId')
                ->middleware('check.active.periode:BimbinganMagang,bimbinganId');
            Route::post('{bimbinganId}/foto-bukti', [MonitoringBimbinganController::class, 'uploadFotoBukti'])
                ->whereNumber('bimbinganId')
                ->middleware('check.active.periode:BimbinganMagang,bimbinganId');
        });

        Route::prefix('semhas')->group(function () {
            Route::get('prasyarat', [SemhasController::class, 'prasyarat']);
            Route::get('my', [SemhasController::class, 'my']);
            Route::post('submit', [SemhasController::class, 'submit'])
                ->middleware('check.active.periode');
            Route::get('pic', [SemhasController::class, 'indexPic']);
            Route::get('ketua', [SemhasController::class, 'indexKetua']);
            Route::get('daftar', [SemhasController::class, 'indexDaftar']);
            Route::post('{id}/verifikasi-pic', [SemhasController::class, 'verifikasiPic'])
                ->whereNumber('id')
                ->middleware('check.active.periode:PengajuanSemhas,id');
            Route::post('{id}/override-ketua', [SemhasController::class, 'overrideKetua'])
                ->whereNumber('id')
                ->middleware('check.active.periode:PengajuanSemhas,id');
            Route::get('{id}/file/{jenis}', [SemhasController::class, 'streamFile'])
                ->whereNumber('id');
        });

        Route::prefix('monev-mitra')->group(function () {
            Route::get('plotting', [MonevMitraController::class, 'plottingList']);
            Route::post('plotting', [MonevMitraController::class, 'storePlotting'])
                ->middleware('check.active.periode');
            Route::get('my-assignments', [MonevMitraController::class, 'myAssignments']);
            Route::get('rekap-hasil-global', [MonevMitraController::class, 'rekapHasilGlobal']);
            Route::patch('rekap-hasil-global/target', [MonevMitraController::class, 'updateTargetNilaiGlobal'])
                ->middleware('check.active.periode');
            Route::get('{mitraId}', [MonevMitraController::class, 'show'])
                ->whereNumber('mitraId');
            Route::post('{mitraId}/submit', [MonevMitraController::class, 'submit'])
                ->whereNumber('mitraId')
                ->middleware('check.active.periode');
            Route::get('{monevId}/file', [MonevMitraController::class, 'streamFile'])
                ->whereNumber('monevId');
        });

        Route::prefix('penilaian-semhas')->group(function () {
            Route::get('penguji', [PenilaianSemhasController::class, 'indexPenguji']);
            Route::get('rekap-ketua', [PenilaianSemhasController::class, 'rekapKetua']);
            Route::get('{pendaftaranId}', [PenilaianSemhasController::class, 'show'])
                ->whereNumber('pendaftaranId');
            Route::post('{pendaftaranId}/jadwal', [PenilaianSemhasController::class, 'storeJadwal'])
                ->whereNumber('pendaftaranId')
                ->middleware('check.active.periode:PendaftaranMbkm,pendaftaranId');
            Route::post('{pendaftaranId}/nilai', [PenilaianSemhasController::class, 'storeNilai'])
                ->whereNumber('pendaftaranId')
                ->middleware('check.active.periode:PendaftaranMbkm,pendaftaranId');
        });

        Route::middleware('role:Tim MBKM,Ketua Tim MBKM,Admin')->group(function () {
            Route::post('cpl', [CplController::class, 'store']);
            Route::put('cpl/{id}', [CplController::class, 'update']);
            Route::delete('cpl/{id}', [CplController::class, 'destroy']);

            Route::post('mata-kuliah', [MataKuliahController::class, 'store']);
            Route::put('mata-kuliah/{id}', [MataKuliahController::class, 'update']);
            Route::delete('mata-kuliah/{id}', [MataKuliahController::class, 'destroy']);

            Route::post('paket-konversi', [PaketKonversiController::class, 'store']);
            Route::put('paket-konversi/{id}', [PaketKonversiController::class, 'update']);
            Route::delete('paket-konversi/{id}', [PaketKonversiController::class, 'destroy']);
        });

        Route::middleware('role:Admin')->group(function () {
            Route::apiResource('users', UserController::class);

            Route::post('mitra', [MitraController::class, 'store']);
            Route::post('mitra/{id}', [MitraController::class, 'update']);
            Route::put('mitra/{id}', [MitraController::class, 'update']);
            Route::delete('mitra/{id}', [MitraController::class, 'destroy']);
        });
    });
});
