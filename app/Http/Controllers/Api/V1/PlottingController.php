<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\PendaftaranMbkm;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlottingController extends Controller
{
    public function dosenList(Request $request): JsonResponse
    {
        $query = Dosen::with(['user.roles', 'programStudi']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('prodi_id')) {
            $query->where('program_studi_id', $request->input('prodi_id'));
        }

        $formatDosen = function ($dosen) {
            $user = $dosen->user;
            $picCount = PendaftaranMbkm::where('pic_verifikator_id', $user?->id)->count();
            $dplCount = PendaftaranMbkm::where('dpl_id', $user?->id)->count();
            $pengujiCount = PendaftaranMbkm::where('penguji_semhas_id', $user?->id)->count();

            return [
                'id' => $user?->id,
                'dosen_id' => $dosen->id,
                'nama' => $user?->nama,
                'nip' => $dosen->nip,
                'program_studi' => $dosen->programStudi?->nama,
                'program_studi_id' => $dosen->program_studi_id,
                'roles' => $user?->roles->pluck('name') ?? [],
                'beban_bimbingan' => [
                    'pic_verifikator' => $picCount,
                    'dpl' => $dplCount,
                    'penguji_semhas' => $pengujiCount,
                    'total' => $picCount + $dplCount + $pengujiCount,
                ],
            ];
        };

        if ($request->input('paginate') === 'false' || $request->boolean('all')) {
            return response()->json([
                'success' => true,
                'data' => $query->get()->map($formatDosen),
                'pagination' => null,
            ]);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map($formatDosen),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function tahap1Pic(Request $request): JsonResponse
    {
        $this->syncPicPlotting();

        $query = PendaftaranMbkm::with(['programStudi', 'picVerifikator']);

        if ($request->filled('status_plotting') && $request->input('status_plotting') !== 'Semua') {
            if ($request->input('status_plotting') === 'Sudah Di-plot') {
                $query->where('is_plotted_pic', true);
            } elseif ($request->input('status_plotting') === 'Belum Di-plot') {
                $query->where('is_plotted_pic', false);
            }
        }

        if ($request->filled('prodi') && $request->input('prodi') !== 'Semua Prodi') {
            $prodi = $request->input('prodi');
            $query->whereHas('programStudi', function ($p) use ($prodi) {
                $p->where('nama', $prodi)->orWhere('kode', $prodi);
            });
        }

        $query->orderBy('is_plotted_pic', 'asc')
            ->orderBy('id', 'desc');

        $mapPendaftaran = function ($p) {
            $status = empty($p->pic_verifikator_id) && $p->status === 'Terverifikasi'
                ? 'Menunggu Verifikasi'
                : $p->status;

            return [
                'id' => $p->id,
                'nim' => $p->nim,
                'nama_lengkap' => $p->nama_lengkap,
                'program_studi' => $p->programStudi?->nama,
                'mitra_magang' => $p->mitra_magang_text,
                'posisi_magang' => $p->posisi_magang,
                'status_pendaftaran' => $status,
                'status' => $status,
                'is_plotted_pic' => (bool) $p->is_plotted_pic,
                'pic_verifikator_id' => $p->pic_verifikator_id,
                'pic_verifikator' => $p->picVerifikator ? [
                    'id' => $p->picVerifikator->id,
                    'nama' => $p->picVerifikator->nama,
                    'nip' => $p->picVerifikator->nim_nip ?? $p->picVerifikator->dosen?->nip,
                ] : null,
                'pic_verifikator_nama' => $p->picVerifikator?->nama,
            ];
        };

        if ($request->input('paginate') === 'false' || $request->boolean('all')) {
            return response()->json([
                'success' => true,
                'data' => $query->get()->map($mapPendaftaran),
                'pagination' => null,
            ]);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map($mapPendaftaran),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function storeTahap1Pic(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pendaftaran_mbkm_id' => ['required', 'exists:pendaftaran_mbkms,id'],
            'pic_verifikator_id' => ['required', 'exists:users,id'],
        ]);

        $pendaftaran = PendaftaranMbkm::findOrFail($data['pendaftaran_mbkm_id']);
        $pendaftaran->update([
            'pic_verifikator_id' => $data['pic_verifikator_id'],
            'is_plotted_pic' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plotting PIC Verifikator berhasil disimpan!',
        ]);
    }

    public function tahap2DplPenguji(Request $request): JsonResponse
    {
        $query = PendaftaranMbkm::with(['programStudi', 'picVerifikator', 'dpl.dosen', 'pengujiSemhas.dosen', 'rkm'])
            ->where('status', 'Terverifikasi')
            ->whereHas('rkm', fn ($q) => $q->where('status', 'Disetujui'));

        if ($request->filled('prodi') && $request->input('prodi') !== 'Semua Prodi') {
            $prodi = $request->input('prodi');
            $query->whereHas('programStudi', function ($p) use ($prodi) {
                $p->where('nama', $prodi)->orWhere('kode', $prodi);
            });
        }

        $query->orderBy('id', 'desc');

        $mapTahap2 = function ($p) {
            return [
                'id' => $p->id,
                'nim' => $p->nim,
                'nama_lengkap' => $p->nama_lengkap,
                'program_studi' => $p->programStudi?->nama,
                'mitra_magang' => $p->mitra_magang_text,
                'posisi_magang' => $p->posisi_magang,
                'status_pendaftaran' => $p->status,
                'status' => $p->status,
                'status_rkm' => $p->rkm?->status,
                'is_plotted_pic' => (bool) $p->is_plotted_pic,
                'pic_verifikator_id' => $p->pic_verifikator_id,
                'pic_verifikator' => $p->picVerifikator ? [
                    'id' => $p->picVerifikator->id,
                    'nama' => $p->picVerifikator->nama,
                    'nip' => $p->picVerifikator->nim_nip ?? $p->picVerifikator->dosen?->nip,
                ] : null,
                'pic_verifikator_nama' => $p->picVerifikator?->nama,
                'is_plotted_dpl' => ! is_null($p->dpl_id),
                'is_plotted_penguji' => ! is_null($p->penguji_semhas_id),
                'is_plotted_dpl_penguji' => (bool) $p->is_plotted_dpl_penguji,
                'dpl' => $p->dpl ? [
                    'id' => $p->dpl->id,
                    'nama' => $p->dpl->nama,
                    'nip' => $p->dpl->nim_nip ?? $p->dpl->dosen?->nip,
                ] : null,
                'penguji_semhas' => $p->pengujiSemhas ? [
                    'id' => $p->pengujiSemhas->id,
                    'nama' => $p->pengujiSemhas->nama,
                    'nip' => $p->pengujiSemhas->nim_nip ?? $p->pengujiSemhas->dosen?->nip,
                ] : null,
            ];
        };

        if ($request->input('paginate') === 'false' || $request->boolean('all')) {
            return response()->json([
                'success' => true,
                'data' => $query->get()->map($mapTahap2),
                'pagination' => null,
            ]);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map($mapTahap2),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function storeTahap2DplPenguji(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pendaftaran_mbkm_id' => ['required', 'exists:pendaftaran_mbkms,id'],
            'dpl_id' => ['nullable', 'exists:users,id'],
            'penguji_semhas_id' => ['nullable', 'exists:users,id'],
        ]);

        if (empty($data['dpl_id']) && empty($data['penguji_semhas_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pilih Dosen Pembimbing Lapangan (DPL) atau Dosen Penguji Semhas.',
            ], 422);
        }

        $pendaftaran = PendaftaranMbkm::with(['rkm.rkpDokumen'])->findOrFail($data['pendaftaran_mbkm_id']);

        if ($pendaftaran->status !== 'Terverifikasi' || $pendaftaran->rkm?->status !== 'Disetujui') {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_PLOTTING_TAHAP2_PRECONDITION_FAILED',
                'message' => 'Plotting hanya dapat diproses setelah pendaftaran Terverifikasi dan RKM Disetujui.',
            ], 422);
        }

        $targetDplId = $data['dpl_id'] ?? $pendaftaran->dpl_id;
        $targetPengujiId = array_key_exists('penguji_semhas_id', $data) && ! empty($data['penguji_semhas_id'])
            ? $data['penguji_semhas_id']
            : $pendaftaran->penguji_semhas_id;

        if (! empty($data['penguji_semhas_id'])) {
            if (empty($targetDplId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mahasiswa harus di-plotting Dosen Pembimbing Lapangan (DPL) terlebih dahulu sebelum memilih Penguji Semhas.',
                ], 422);
            }

            if ((int) $targetDplId === (int) $data['penguji_semhas_id']) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'ERR_PLOTTING_DPL_EQUALS_PENGUJI',
                    'message' => 'Dosen Pembimbing Lapangan (DPL) tidak boleh merangkap sebagai Dosen Penguji Semhas untuk mahasiswa bimbingannya sendiri.',
                    'errors' => [
                        'penguji_semhas_id' => ['Penguji Semhas harus dosen yang berbeda dari DPL.'],
                    ],
                ], 422);
            }
        }

        $pendaftaran->update([
            'dpl_id' => $targetDplId,
            'penguji_semhas_id' => $targetPengujiId,
            'is_plotted_dpl_penguji' => ! empty($targetDplId) && ! empty($targetPengujiId),
        ]);

        if (! empty($data['dpl_id']) && $pendaftaran->rkm?->rkpDokumen) {
            $dplUser = User::with('dosen')->find($data['dpl_id']);
            if ($dplUser) {
                $pendaftaran->rkm->rkpDokumen->update([
                    'nama_dpl' => $dplUser->nama,
                    'nip_dpl' => $dplUser->nim_nip ?? $dplUser->dosen?->nip,
                ]);
            }
        }

        $msg = ! empty($data['dpl_id']) && empty($data['penguji_semhas_id'])
            ? 'Plotting Dosen Pembimbing Lapangan (DPL) berhasil disimpan!'
            : 'Plotting Dosen Penguji Semhas berhasil disimpan!';

        return response()->json([
            'success' => true,
            'message' => $msg,
        ]);
    }

    private function syncPicPlotting(): void
    {
        PendaftaranMbkm::whereNotNull('pic_verifikator_id')
            ->where('is_plotted_pic', false)
            ->update(['is_plotted_pic' => true]);

        PendaftaranMbkm::whereNull('pic_verifikator_id')
            ->where('is_plotted_pic', true)
            ->update(['is_plotted_pic' => false]);

        PendaftaranMbkm::whereNull('pic_verifikator_id')
            ->where('status', 'Terverifikasi')
            ->update(['status' => 'Menunggu Verifikasi']);
    }
}
