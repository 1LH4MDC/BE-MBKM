<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Services\PdfDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PendaftaranMbkmController extends Controller
{
    public function __construct(private PdfDocumentService $pdfService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = PendaftaranMbkm::with([
            'programStudi',
            'mitra',
            'periodeAkademik',
            'picVerifikator',
            'dpl',
            'pengujiSemhas',
            'rkm',
        ])
            ->where('is_plotted_pic', true)
            ->where('pic_verifikator_id', $user->id);

        if ($request->filled('status') && $request->input('status') !== 'Semua') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('prodi_id')) {
            $query->where('program_studi_id', $request->input('prodi_id'));
        }

        if ($request->filled('periode_id') || $request->filled('periode_akademik_id')) {
            $query->where('periode_akademik_id', $request->input('periode_id') ?? $request->input('periode_akademik_id'));
        } elseif ($request->filled('tahun_ajaran') && $request->input('tahun_ajaran') !== 'Semua Tahun Ajaran') {
            $ta = $request->input('tahun_ajaran');
            $query->where(function ($q) use ($ta) {
                $q->where('tahun_ajaran', $ta)
                    ->orWhereHas('periodeAkademik', fn ($p) => $p->whereRaw("CONCAT(tahun_ajaran, ' ', semester) = ?", [$ta]));
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('posisi_magang', 'like', "%{$search}%")
                    ->orWhere('mitra_magang_text', 'like', "%{$search}%");
            });
        }

        $query->orderBy('id', 'desc');

        if ($request->input('paginate') === 'false' || $request->boolean('all')) {
            return response()->json([
                'success' => true,
                'data' => $query->get(),
                'pagination' => null,
            ]);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
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

    public function myPendaftaran(Request $request): JsonResponse
    {
        $user = $request->user();

        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->first();

        if ($request->filled('periode_id') || $request->filled('periode_akademik_id')) {
            $targetPeriodeId = $request->input('periode_id') ?? $request->input('periode_akademik_id');
            $pendaftaran = PendaftaranMbkm::with([
                'programStudi',
                'mitra',
                'periodeAkademik',
                'picVerifikator',
                'dpl',
                'pengujiSemhas',
                'rkm',
            ])
                ->where('user_id', $user->id)
                ->where('periode_akademik_id', $targetPeriodeId)
                ->latest()
                ->first();

            return response()->json([
                'success' => true,
                'data' => $pendaftaran,
                'message' => $pendaftaran ? 'Data pendaftaran periode ditemukan.' : 'Belum ada pendaftaran pada periode ini.',
            ]);
        }

        $pendaftaran = PendaftaranMbkm::with([
            'programStudi',
            'mitra',
            'periodeAkademik',
            'picVerifikator',
            'dpl',
            'pengujiSemhas',
            'rkm',
        ])
            ->where('user_id', $user->id)
            ->whereHas('periodeAkademik', fn ($q) => $q->where('is_aktif', true))
            ->latest()
            ->first();

        $riwayat = PendaftaranMbkm::with(['periodeAkademik', 'mitra', 'rkm'])
            ->where('user_id', $user->id)
            ->when($periodeAktif, fn ($q) => $q->where('periode_akademik_id', '!=', $periodeAktif->id))
            ->orderBy('id', 'desc')
            ->get();

        if (! $pendaftaran) {
            return response()->json([
                'success' => true,
                'message' => 'Belum ada data pendaftaran MBKM pada periode aktif saat ini.',
                'data' => null,
                'periode_aktif' => $periodeAktif,
                'riwayat' => $riwayat,
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => $pendaftaran,
            'periode_aktif' => $periodeAktif,
            'riwayat' => $riwayat,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $pendaftaran = PendaftaranMbkm::with([
            'programStudi',
            'mitra',
            'periodeAkademik',
            'picVerifikator',
            'dpl',
            'pengujiSemhas',
            'rkm.items.jobdesks',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $pendaftaran,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->firstOrFail();

        $existing = PendaftaranMbkm::where('user_id', $user->id)
            ->where('periode_akademik_id', $periodeAktif->id)
            ->first();

        if ($existing && $existing->status === 'Terverifikasi') {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_PENDAFTARAN_ALREADY_VERIFIED',
                'message' => 'Pendaftaran MBKM Anda sudah Terverifikasi dan tidak dapat diubah kembali.',
            ], 403);
        }

        $isUpdate = (bool) $existing;

        $data = $request->validate([
            'nama_lengkap' => ['nullable', 'string', 'max:150'],
            'nim' => ['nullable', 'string', 'max:30'],
            'program_studi_id' => ['required', 'exists:program_studis,id'],
            'mitra_id' => ['nullable', 'exists:mitras,id'],
            'mitra_magang_text' => ['required', 'string', 'max:150'],
            'posisi_magang' => ['required', 'string', 'max:100'],
            'is_custom_role' => ['nullable', 'boolean'],
            'transkrip_file' => [$isUpdate ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:5120'],
            'loa_file' => [$isUpdate ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $nim = ! empty($data['nim']) ? $data['nim'] : ($user->nim_nip ?? $user->mahasiswa?->nim ?? 'MHS-'.$user->id);
        $namaLengkap = ! empty($data['nama_lengkap']) ? $data['nama_lengkap'] : $user->nama;
        $slugPeriode = Str::slug($periodeAktif->tahun_ajaran.'-'.$periodeAktif->semester);

        $transkripName = $existing?->transkrip_file_name;
        $transkripPath = $existing?->transkrip_file_path;
        $transkripSize = $existing?->transkrip_file_size;

        if ($request->hasFile('transkrip_file')) {
            $transkrip = $request->file('transkrip_file');
            $transkripName = $transkrip->getClientOriginalName();
            $storedTranskripName = 'transkrip-'.time().'-'.Str::slug(pathinfo($transkripName, PATHINFO_FILENAME)).'.pdf';
            $transkripPath = "pendaftaran/{$nim}/{$slugPeriode}/{$storedTranskripName}";
            Storage::disk('local')->putFileAs("pendaftaran/{$nim}/{$slugPeriode}", $transkrip, $storedTranskripName);
            $transkripSize = number_format($transkrip->getSize() / 1024, 1).' KB';
        }

        $loaName = $existing?->loa_file_name;
        $loaPath = $existing?->loa_file_path;
        $loaSize = $existing?->loa_file_size;

        if ($request->hasFile('loa_file')) {
            $loa = $request->file('loa_file');
            $loaName = $loa->getClientOriginalName();
            $storedLoaName = 'loa-'.time().'-'.Str::slug(pathinfo($loaName, PATHINFO_FILENAME)).'.pdf';
            $loaPath = "pendaftaran/{$nim}/{$slugPeriode}/{$storedLoaName}";
            Storage::disk('local')->putFileAs("pendaftaran/{$nim}/{$slugPeriode}", $loa, $storedLoaName);
            $loaSize = number_format($loa->getSize() / 1024, 1).' KB';
        }

        $payload = [
            'user_id' => $user->id,
            'nim' => $nim,
            'nama_lengkap' => $namaLengkap,
            'program_studi_id' => $data['program_studi_id'],
            'mitra_id' => $data['mitra_id'] ?? null,
            'mitra_magang_text' => $data['mitra_magang_text'],
            'posisi_magang' => $data['posisi_magang'],
            'is_custom_role' => $request->boolean('is_custom_role'),
            'periode_akademik_id' => $periodeAktif->id,
            'tahun_ajaran' => trim("{$periodeAktif->tahun_ajaran} {$periodeAktif->semester}"),
            'transkrip_file_name' => $transkripName,
            'transkrip_file_path' => $transkripPath,
            'transkrip_file_size' => $transkripSize,
            'loa_file_name' => $loaName,
            'loa_file_path' => $loaPath,
            'loa_file_size' => $loaSize,
            'status' => 'Menunggu Verifikasi',
        ];

        if ($existing) {
            if (! empty($existing->pic_verifikator_id)) {
                $payload['pic_verifikator_id'] = $existing->pic_verifikator_id;
                $payload['is_plotted_pic'] = true;
            }

            $existing->update($payload);
            $pendaftaran = $existing->fresh(['programStudi', 'mitra', 'periodeAkademik', 'picVerifikator', 'dpl', 'pengujiSemhas', 'rkm']);
            $message = 'Perbaikan berkas pendaftaran MBKM berhasil disimpan dan diajukan kembali.';
            $statusCode = 200;
        } else {
            $pendaftaran = PendaftaranMbkm::create($payload);
            $pendaftaran->load(['programStudi', 'mitra', 'periodeAkademik', 'picVerifikator', 'dpl', 'pengujiSemhas', 'rkm']);
            $message = 'Pendaftaran MBKM berhasil diajukan. Menunggu verifikasi berkas oleh Tim MBKM.';
            $statusCode = 201;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $pendaftaran,
        ], $statusCode);
    }

    public function streamFile(int $id, string $jenis, Request $request): Response
    {
        $pendaftaran = PendaftaranMbkm::findOrFail($id);
        $mode = $request->query('mode', 'inline');

        if ($jenis === 'transkrip') {
            $path = $pendaftaran->transkrip_file_path;
            $filename = $pendaftaran->transkrip_file_name;
            $title = 'Transkrip Nilai - '.$pendaftaran->nama_lengkap;
        } elseif ($jenis === 'loa') {
            $path = $pendaftaran->loa_file_path;
            $filename = $pendaftaran->loa_file_name;
            $title = 'Letter of Acceptance - '.$pendaftaran->nama_lengkap;
        } else {
            return response()->json(['success' => false, 'message' => 'Jenis berkas tidak valid.'], 400);
        }

        if ($path && Storage::disk('local')->exists($path)) {
            $content = Storage::disk('local')->get($path);
        } else {
            $content = $this->pdfService->generateDummyPdf($title);
        }

        return $this->pdfService->streamPdfContent($content, $filename, $mode);
    }

    public function verifikasi(int $id, Request $request): JsonResponse
    {
        $pendaftaran = PendaftaranMbkm::findOrFail($id);
        $user = $request->user();

        if (! $pendaftaran->is_plotted_pic || ! $pendaftaran->pic_verifikator_id) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_VERIFIKASI_NO_PIC_ASSIGNED',
                'message' => 'Mahasiswa ini belum memiliki PIC Verifikator. Ketua Tim MBKM wajib melakukan Plotting PIC terlebih dahulu.',
            ], 422);
        }

        if ((int) $pendaftaran->pic_verifikator_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_VERIFIKASI_NOT_PLOTTED_PIC',
                'message' => 'Anda tidak berwenang memverifikasi mahasiswa ini karena bukan PIC Verifikator yang ditugaskan.',
            ], 403);
        }

        $data = $request->validate([
            'status' => ['required', 'in:Terverifikasi,Perbaikan,Ditolak'],
            'link_wa_grup' => ['nullable', 'string', 'max:255', 'required_if:status,Terverifikasi'],
            'catatan' => ['nullable', 'string', 'required_if:status,Perbaikan', 'required_if:status,Ditolak'],
        ]);

        $pendaftaran->update([
            'status' => $data['status'],
            'link_wa_grup' => $data['status'] === 'Terverifikasi' ? ($data['link_wa_grup'] ?? $pendaftaran->link_wa_grup) : $pendaftaran->link_wa_grup,
            'catatan' => $data['catatan'] ?? null,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status verifikasi berkas pendaftaran berhasil disimpan.',
            'data' => $pendaftaran->fresh(['programStudi', 'mitra', 'periodeAkademik', 'picVerifikator', 'dpl', 'pengujiSemhas', 'rkm']),
        ]);
    }
}
