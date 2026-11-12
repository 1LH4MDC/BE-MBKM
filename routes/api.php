<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CplController;
use App\Http\Controllers\Api\V1\KuisionerController;
use App\Http\Controllers\Api\V1\MasterDataController;
use App\Http\Controllers\Api\V1\MataKuliahController;
use App\Http\Controllers\Api\V1\MitraController;
use App\Http\Controllers\Api\V1\PaketKonversiController;
use App\Http\Controllers\Api\V1\PendaftaranMbkmController;
use App\Http\Controllers\Api\V1\PlottingController;
use App\Http\Controllers\Api\V1\RkmController;
use App\Http\Controllers\Api\V1\RkpController;
use App\Http\Controllers\Api\V1\SuratPengantarController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VerifikasiRkmController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);

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

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('pendaftaran-mbkm', [PendaftaranMbkmController::class, 'index']);
        Route::get('pendaftaran-mbkm/my', [PendaftaranMbkmController::class, 'myPendaftaran']);
        Route::get('pendaftaran-mbkm/{id}', [PendaftaranMbkmController::class, 'show']);
        Route::post('pendaftaran-mbkm', [PendaftaranMbkmController::class, 'store']);
        Route::post('pendaftaran-mbkm/{id}/verifikasi', [PendaftaranMbkmController::class, 'verifikasi']);

        Route::prefix('plotting')->group(function () {
            Route::get('dosen-list', [PlottingController::class, 'dosenList']);
            Route::get('tahap-1-pic', [PlottingController::class, 'tahap1Pic']);
            Route::post('tahap-1-pic', [PlottingController::class, 'storeTahap1Pic']);
            Route::get('tahap-2-dpl-penguji', [PlottingController::class, 'tahap2DplPenguji']);
            Route::post('tahap-2-dpl-penguji', [PlottingController::class, 'storeTahap2DplPenguji']);
        });

        Route::middleware('pendaftaran.terverifikasi')->prefix('rkm')->group(function () {
            Route::get('available-courses', [RkmController::class, 'availableCourses']);
            Route::get('my-rkm', [RkmController::class, 'myRkm']);

            Route::middleware('ensure.rkm.unlocked')->group(function () {
                Route::post('save-selection', [RkmController::class, 'saveSelection']);
                Route::post('items/{rkm_mk_id}/jobdesk', [RkmController::class, 'addJobdesk']);
                Route::put('jobdesks/{jobdesk_id}', [RkmController::class, 'updateJobdesk']);
                Route::delete('jobdesks/{jobdesk_id}', [RkmController::class, 'deleteJobdesk']);
                Route::delete('items/{rkm_mk_id}', [RkmController::class, 'deleteItem']);
                Route::post('submit', [RkmController::class, 'submit']);
            });
        });

        Route::prefix('tim-mbkm/verifikasi-rkm')->group(function () {
            Route::get('/', [VerifikasiRkmController::class, 'index']);
            Route::get('{rkm_id}', [VerifikasiRkmController::class, 'show']);
            Route::patch('items/{rkm_mk_id}/toggle-drop', [VerifikasiRkmController::class, 'toggleDropItem']);
            Route::put('jobdesks/{jobdesk_id}', [VerifikasiRkmController::class, 'updateJobdesk']);
            Route::post('items/{rkm_mk_id}/jobdesk', [VerifikasiRkmController::class, 'addJobdesk']);
            Route::delete('jobdesks/{jobdesk_id}', [VerifikasiRkmController::class, 'deleteJobdesk']);
            Route::post('{rkm_id}/keputusan', [VerifikasiRkmController::class, 'keputusan']);
        });

        Route::get('rkp/{rkm_id}/preview', [RkpController::class, 'preview']);
        Route::put('rkp/{rkm_id}/metadata', [RkpController::class, 'updateMetadata']);

        Route::get('surat-pengantar', [SuratPengantarController::class, 'index']);
        Route::get('surat-pengantar/{id}', [SuratPengantarController::class, 'show']);
        Route::post('surat-pengantar', [SuratPengantarController::class, 'store']);
        Route::put('surat-pengantar/{id}', [SuratPengantarController::class, 'update']);
        Route::post('surat-pengantar/{id}/upload', [SuratPengantarController::class, 'uploadPdf']);
        Route::post('surat-pengantar/{id}/upload-pdf', [SuratPengantarController::class, 'uploadPdf']);
        Route::get('surat-pengantar/{id}/pdf', [SuratPengantarController::class, 'streamPdf']);

        Route::post('periode-akademik', [MasterDataController::class, 'storePeriodeAkademik']);
        Route::put('periode-akademik/{id}', [MasterDataController::class, 'updatePeriodeAkademik']);
        Route::patch('periode-akademik/{id}/aktifkan', [MasterDataController::class, 'aktifkanPeriodeAkademik']);
        Route::delete('periode-akademik/{id}', [MasterDataController::class, 'destroyPeriodeAkademik']);

        Route::post('kuisioner/kategori', [KuisionerController::class, 'storeKategori']);
        Route::put('kuisioner/kategori/{id}', [KuisionerController::class, 'updateKategori']);
        Route::delete('kuisioner/kategori/{id}', [KuisionerController::class, 'destroyKategori']);

        Route::post('kuisioner/pertanyaan', [KuisionerController::class, 'storePertanyaan']);
        Route::put('kuisioner/pertanyaan/{id}', [KuisionerController::class, 'updatePertanyaan']);
        Route::delete('kuisioner/pertanyaan/{id}', [KuisionerController::class, 'destroyPertanyaan']);

        Route::get('kuisioner/mahasiswa', [KuisionerController::class, 'mahasiswaKuisioner']);
        Route::get('kuisioner/jawaban', [KuisionerController::class, 'indexJawaban']);
        Route::post('kuisioner/jawaban', [KuisionerController::class, 'storeJawaban']);

        Route::get('tim-mbkm/dashboard-stats', [MasterDataController::class, 'timMbkmDashboardStats']);

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
