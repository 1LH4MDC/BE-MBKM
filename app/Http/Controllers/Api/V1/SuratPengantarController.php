<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SuratPengantar;
use App\Services\PdfDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SuratPengantarController extends Controller
{
    public function __construct(private PdfDocumentService $pdfService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = SuratPengantar::with(['pejabatTtd', 'user', 'mahasiswas.pendaftaranMbkm', 'periodeAkademik']);

        $isAdmin = $user && $user->hasRole('Admin');
        $all = $request->boolean('all') || $request->input('all') === '1';

        if (! $isAdmin && ! $all) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('mahasiswas', fn ($m) => $m->where('user_id', $user->id));
            });
        }

        // Scope filter tahun ajaran
        $query->filterTahunAjaran($request);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nomor_surat', 'like', "%{$search}%")
                    ->orWhere('tujuan_instansi', 'like', "%{$search}%")
                    ->orWhere('perihal', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $query->orderBy('id', 'desc');

        if ($request->input('paginate') === 'false') {
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

    public function show(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $surat = SuratPengantar::with(['pejabatTtd', 'user', 'mahasiswas.pendaftaranMbkm'])->findOrFail($id);

        $isAdmin = $user && $user->hasRole('Admin');
        if (! $isAdmin) {
            $isOwner = $surat->user_id === $user->id || $surat->mahasiswas->contains('user_id', $user->id);
            if (! $isOwner) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'ERR_FORBIDDEN_RESOURCE',
                    'message' => 'Anda tidak memiliki akses ke surat pengantar ini.',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $surat,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'tanggal_surat' => ['nullable', 'date'],
            'perihal' => ['nullable', 'string', 'max:200'],
            'tujuan_instansi' => ['required', 'string', 'max:200'],
            'nama_penerima' => ['nullable', 'string', 'max:150'],
            'alamat_instansi' => ['required', 'string'],
            'kota_instansi' => ['required', 'string', 'max:100'],
            'periode_magang_mulai' => ['nullable', 'date'],
            'periode_magang_selesai' => ['nullable', 'date', 'after_or_equal:periode_magang_mulai'],
            'pejabat_ttd_id' => ['nullable', 'exists:users,id'],
            'catatan' => ['nullable', 'string'],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'posisi_magang' => ['nullable', 'string', 'max:100'],
            'mahasiswas' => ['nullable', 'array'],
            'mahasiswas.*.pendaftaran_mbkm_id' => ['nullable', 'exists:pendaftaran_mbkms,id'],
            'mahasiswas.*.user_id' => ['nullable', 'exists:users,id'],
            'mahasiswas.*.nim' => ['nullable', 'string'],
            'mahasiswas.*.nama_lengkap' => ['nullable', 'string'],
            'mahasiswas.*.program_studi' => ['nullable', 'string'],
            'mahasiswas.*.posisi_magang' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data, $user) {
            $periodeAktif = \App\Models\PeriodeAkademik::where('is_aktif', true)->first();

            $surat = SuratPengantar::create([
                'user_id' => $user->id,
                'periode_akademik_id' => $periodeAktif?->id,
                'tahun_ajaran' => $periodeAktif ? trim("{$periodeAktif->tahun_ajaran} {$periodeAktif->semester}") : null,
                'nomor_surat' => $data['nomor_surat'] ?? null,
                'tanggal_surat' => $data['tanggal_surat'] ?? now()->toDateString(),
                'perihal' => $data['perihal'] ?? 'Permohonan Izin Magang MBKM',
                'tujuan_instansi' => $data['tujuan_instansi'],
                'nama_penerima' => $data['nama_penerima'] ?? null,
                'alamat_instansi' => $data['alamat_instansi'],
                'kota_instansi' => $data['kota_instansi'],
                'periode_magang_mulai' => $data['periode_magang_mulai'] ?? null,
                'periode_magang_selesai' => $data['periode_magang_selesai'] ?? null,
                'pejabat_ttd_id' => $data['pejabat_ttd_id'] ?? null,
                'status' => 'Menunggu',
                'catatan' => $data['catatan'] ?? null,
            ]);

            $mhsList = $data['mahasiswas'] ?? [];
            if (empty($mhsList)) {
                $mhsList[] = [
                    'user_id' => $user->id,
                    'nim' => $user->mahasiswa?->nim ?? $user->nim_nip ?? '-',
                    'nama_lengkap' => $user->nama,
                    'program_studi' => $user->mahasiswa?->programStudi?->nama ?? '-',
                    'posisi_magang' => $data['posisi_magang'] ?? 'Intern',
                ];
            }

            foreach ($mhsList as $mhs) {
                $mhsUserId = $mhs['user_id'] ?? $user->id;
                $surat->mahasiswas()->create([
                    'pendaftaran_mbkm_id' => $mhs['pendaftaran_mbkm_id'] ?? null,
                    'user_id' => $mhsUserId,
                    'nim' => $mhs['nim'] ?? $user->mahasiswa?->nim ?? $user->nim_nip ?? '-',
                    'nama_lengkap' => $mhs['nama_lengkap'] ?? $user->nama,
                    'program_studi' => $mhs['program_studi'] ?? $user->mahasiswa?->programStudi?->nama ?? '-',
                    'posisi_magang' => $mhs['posisi_magang'] ?? ($data['posisi_magang'] ?? 'Intern'),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Permohonan Surat Pengantar berhasil dibuat.',
                'data' => $surat->load(['user', 'mahasiswas.pendaftaranMbkm']),
            ], 201);
        });
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $surat = SuratPengantar::with(['mahasiswas'])->findOrFail($id);

        $isAdmin = $user && $user->hasRole('Admin');
        $isOwner = $surat->user_id === $user->id || $surat->mahasiswas->contains('user_id', $user->id);

        if (! $isAdmin && ! $isOwner) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FORBIDDEN_RESOURCE',
                'message' => 'Anda tidak memiliki hak untuk mengedit permohonan surat pengantar ini.',
            ], 403);
        }

        if (in_array($surat->status, ['Selesai', 'Disetujui', 'Diterbitkan']) || ! empty($surat->file_pdf_path)) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_SURAT_ALREADY_PUBLISHED',
                'message' => 'Permohonan surat pengantar sudah diterbitkan oleh Admin dan tidak dapat diedit.',
            ], 422);
        }

        $data = $request->validate([
            'tanggal_surat' => ['nullable', 'date'],
            'perihal' => ['nullable', 'string', 'max:200'],
            'tujuan_instansi' => ['sometimes', 'required', 'string', 'max:200'],
            'nama_penerima' => ['nullable', 'string', 'max:150'],
            'alamat_instansi' => ['sometimes', 'required', 'string'],
            'kota_instansi' => ['sometimes', 'required', 'string', 'max:100'],
            'periode_magang_mulai' => ['nullable', 'date'],
            'periode_magang_selesai' => ['nullable', 'date', 'after_or_equal:periode_magang_mulai'],
            'pejabat_ttd_id' => ['nullable', 'exists:users,id'],
            'catatan' => ['nullable', 'string'],
            'posisi_magang' => ['nullable', 'string', 'max:100'],
            'mahasiswas' => ['nullable', 'array'],
            'mahasiswas.*.pendaftaran_mbkm_id' => ['nullable', 'exists:pendaftaran_mbkms,id'],
            'mahasiswas.*.user_id' => ['nullable', 'exists:users,id'],
            'mahasiswas.*.nim' => ['nullable', 'string'],
            'mahasiswas.*.nama_lengkap' => ['nullable', 'string'],
            'mahasiswas.*.program_studi' => ['nullable', 'string'],
            'mahasiswas.*.posisi_magang' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($surat, $data, $user) {
            $surat->update(array_filter([
                'tanggal_surat' => $data['tanggal_surat'] ?? $surat->tanggal_surat,
                'perihal' => $data['perihal'] ?? $surat->perihal,
                'tujuan_instansi' => $data['tujuan_instansi'] ?? $surat->tujuan_instansi,
                'nama_penerima' => array_key_exists('nama_penerima', $data) ? $data['nama_penerima'] : $surat->nama_penerima,
                'alamat_instansi' => $data['alamat_instansi'] ?? $surat->alamat_instansi,
                'kota_instansi' => $data['kota_instansi'] ?? $surat->kota_instansi,
                'periode_magang_mulai' => array_key_exists('periode_magang_mulai', $data) ? $data['periode_magang_mulai'] : $surat->periode_magang_mulai,
                'periode_magang_selesai' => array_key_exists('periode_magang_selesai', $data) ? $data['periode_magang_selesai'] : $surat->periode_magang_selesai,
                'pejabat_ttd_id' => array_key_exists('pejabat_ttd_id', $data) ? $data['pejabat_ttd_id'] : $surat->pejabat_ttd_id,
                'catatan' => array_key_exists('catatan', $data) ? $data['catatan'] : $surat->catatan,
            ], fn ($v) => $v !== null));

            if (isset($data['mahasiswas'])) {
                $surat->mahasiswas()->delete();
                foreach ($data['mahasiswas'] as $mhs) {
                    $surat->mahasiswas()->create([
                        'pendaftaran_mbkm_id' => $mhs['pendaftaran_mbkm_id'] ?? null,
                        'user_id' => $mhs['user_id'] ?? $user->id,
                        'nim' => $mhs['nim'] ?? $user->mahasiswa?->nim ?? $user->nim_nip ?? '-',
                        'nama_lengkap' => $mhs['nama_lengkap'] ?? $user->nama,
                        'program_studi' => $mhs['program_studi'] ?? $user->mahasiswa?->programStudi?->nama ?? '-',
                        'posisi_magang' => $mhs['posisi_magang'] ?? ($data['posisi_magang'] ?? 'Intern'),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Permohonan Surat Pengantar berhasil diperbarui.',
                'data' => $surat->fresh(['user', 'mahasiswas.pendaftaranMbkm']),
            ]);
        });
    }

    public function uploadPdf(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('Admin')) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FORBIDDEN_ROLE',
                'message' => 'Hanya Admin yang dapat mengunggah file Surat Pengantar.',
            ], 403);
        }

        $file = $request->file('file_surat') ?? $request->file('file') ?? $request->file('file_pdf');
        if (! $file) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FILE_REQUIRED',
                'message' => 'File PDF Surat Pengantar wajib diunggah (field: file_surat atau file_pdf).',
                'errors' => ['file_surat' => ['File PDF wajib diunggah.']],
            ], 422);
        }

        $request->validate([
            'file_surat' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'file_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'catatan' => ['nullable', 'string'],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
        ]);

        $surat = SuratPengantar::findOrFail($id);

        $path = $file->store('surat-pengantar', 'public');

        $updateData = [
            'file_pdf_path' => $path,
            'status' => 'Selesai',
        ];

        if ($request->filled('catatan')) {
            $updateData['catatan'] = $request->input('catatan');
        }

        if ($request->filled('nomor_surat')) {
            $updateData['nomor_surat'] = $request->input('nomor_surat');
        } elseif (empty($surat->nomor_surat)) {
            $updateData['nomor_surat'] = '-';
        }

        $surat->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'File Surat Pengantar berhasil diunggah.',
            'data' => $surat->fresh(['pejabatTtd', 'user', 'mahasiswas']),
        ]);
    }

    public function streamPdf(int $id, Request $request): Response
    {
        $surat = SuratPengantar::with('mahasiswas')->findOrFail($id);
        $mode = $request->query('mode', 'inline');

        if ($surat->file_pdf_path && Storage::disk('public')->exists($surat->file_pdf_path)) {
            $content = Storage::disk('public')->get($surat->file_pdf_path);
            $filename = 'Surat-Pengantar-'.($surat->nomor_surat && $surat->nomor_surat !== '-' ? str_replace('/', '-', $surat->nomor_surat) : "id-{$surat->id}").'.pdf';

            return $this->pdfService->streamPdfContent($content, $filename, $mode);
        }

        $filename = 'Surat-Pengantar-'.($surat->nomor_surat && $surat->nomor_surat !== '-' ? str_replace('/', '-', $surat->nomor_surat) : "id-{$surat->id}").'.pdf';
        $content = $this->pdfService->generateDummyPdf(
            'SURAT PENGANTAR MAGANG MBKM FASILKOM',
            "Tujuan: {$surat->tujuan_instansi}"
        );

        return $this->pdfService->streamPdfContent($content, $filename, $mode);
    }
}
