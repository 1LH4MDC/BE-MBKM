<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\PaketKonversi;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\ProgramStudi;
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
        ]);

        // Jika bukan Admin atau Ketua Tim MBKM, batasi hanya mahasiswa yang di-plot ke PIC ini
        if ($user && ! $user->hasRole(['Admin', 'Ketua Tim MBKM'])) {
            $query->where('is_plotted_pic', true)
                ->where(function ($q) use ($user) {
                    $q->where('pic_verifikator_id', $user->id);
                    if ($user->dosen) {
                        $q->orWhere('pic_verifikator_id', $user->dosen->id);
                    }
                });
        }

        if ($request->filled('status') && $request->input('status') !== 'Semua' && $request->input('status') !== 'Semua Status') {
            $st = $request->input('status');
            if ($st === 'Perbaikan Berkas') {
                $st = 'Perbaikan';
            }
            $query->where('status', $st);
        }

        if ($request->filled('prodi_id')) {
            $query->where('program_studi_id', $request->input('prodi_id'));
        } elseif ($request->filled('prodi') && $request->input('prodi') !== 'Semua Prodi') {
            $prodi = $request->input('prodi');
            $query->whereHas('programStudi', function ($p) use ($prodi) {
                $p->where('nama', $prodi)->orWhere('kode', $prodi);
            });
        }

        // Filter Tahun Ajaran via Trait
        $query->filterTahunAjaran($request);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('posisi_magang', 'like', "%{$search}%")
                    ->orWhere('mitra_magang_text', 'like', "%{$search}%")
                    ->orWhereHas('mitra', fn ($m) => $m->where('nama', 'like', "%{$search}%"));
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

        $targetPeriodeId = $request->input('periode_id') ?? $request->input('periode_akademik_id') ?? $request->query('periode_id') ?? $request->query('periode_akademik_id');
        if (! $targetPeriodeId && ($request->filled('tahun_ajaran') || $request->query('tahun_ajaran'))) {
            $rawTa = trim((string) ($request->input('tahun_ajaran') ?? $request->query('tahun_ajaran')));
            if (! in_array(strtolower($rawTa), ['semua', 'semua tahun ajaran', 'all'], true)) {
                $parts = preg_split('/\s+/', $rawTa);
                $th = $parts[0] ?? null;
                $sem = $parts[1] ?? null;
                $targetPeriodeId = PeriodeAkademik::where(function ($q) use ($th, $sem, $rawTa) {
                    if ($th && $sem) {
                        $q->where('tahun_ajaran', $th)->where('semester', $sem);
                    } else {
                        $q->where('tahun_ajaran', $rawTa);
                    }
                })->value('id');
            }
        }

        if ($targetPeriodeId) {
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
                ->latest('id')
                ->first();

            $isArchived = $periodeAktif ? ((int) $pendaftaran?->periode_akademik_id !== (int) $periodeAktif->id) : false;

            return response()->json([
                'success' => true,
                'data' => $pendaftaran ? array_merge($pendaftaran->toArray(), [
                    'is_archived' => $isArchived,
                    'tahun_ajaran' => $pendaftaran->periodeAkademik?->nama_lengkap ?? $pendaftaran->tahun_ajaran,
                ]) : null,
                'is_archived' => $isArchived,
                'message' => $pendaftaran ? 'Data pendaftaran periode ditemukan.' : 'Belum ada pendaftaran pada periode ini.',
            ]);
        }

        // Default: Utamakan periode aktif
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
            ->latest('id')
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

        $isArchived = $periodeAktif ? ((int) $pendaftaran->periode_akademik_id !== (int) $periodeAktif->id) : false;

        return response()->json([
            'success' => true,
            'data' => array_merge($pendaftaran->toArray(), [
                'is_archived' => $isArchived,
                'tahun_ajaran' => $pendaftaran->periodeAkademik?->nama_lengkap ?? $pendaftaran->tahun_ajaran,
            ]),
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
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->first();

        if (! $periodeAktif) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_NO_ACTIVE_PERIODE',
                'message' => 'Pendaftaran ditutup karena belum ada Tahun Ajaran aktif.',
            ], 422);
        }

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

        if (! $request->filled('program_studi_id')) {
            if ($request->filled('prodi_id')) {
                $request->merge(['program_studi_id' => $request->input('prodi_id')]);
            } elseif ($request->filled('program_studi') || $request->filled('prodi')) {
                $prodiInput = $request->input('program_studi') ?? $request->input('prodi');
                $prodiModel = ProgramStudi::where('kode', $prodiInput)
                    ->orWhere('nama', 'like', "%{$prodiInput}%")
                    ->first();
                if ($prodiModel) {
                    $request->merge(['program_studi_id' => $prodiModel->id]);
                }
            } elseif ($user->mahasiswa?->program_studi_id) {
                $request->merge(['program_studi_id' => $user->mahasiswa->program_studi_id]);
            }
        }

        if (! $request->filled('mitra_magang_text')) {
            $mitraText = $request->input('mitra_magang') ?? $request->input('nama_mitra');
            if (! $mitraText && $request->filled('mitra_id')) {
                $mitraText = Mitra::find($request->input('mitra_id'))?->nama;
            }
            if ($mitraText) {
                $request->merge(['mitra_magang_text' => $mitraText]);
            }
        }

        if (! $request->filled('posisi_magang')) {
            $posisiInput = $request->input('posisi') ?? $request->input('target_posisi') ?? $request->input('role_magang') ?? $request->input('nama_paket');
            if ($posisiInput) {
                $request->merge(['posisi_magang' => $posisiInput]);
            }
        }

        if ($request->input('posisi_magang') === 'Lainnya' && ($request->filled('posisi_magang_custom') || $request->filled('custom_posisi'))) {
            $request->merge([
                'posisi_magang' => $request->input('posisi_magang_custom') ?? $request->input('custom_posisi'),
                'is_custom_role' => true,
            ]);
        }

        if ($request->has('is_custom_role')) {
            $request->merge(['is_custom_role' => $request->boolean('is_custom_role')]);
        }

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
        ], [
            'transkrip_file.max' => 'Ukuran berkas transkrip maksimal 5 MB.',
            'transkrip_file.mimes' => 'Berkas transkrip harus berformat PDF.',
            'loa_file.max' => 'Ukuran berkas LoA maksimal 5 MB.',
            'loa_file.mimes' => 'Berkas LoA harus berformat PDF.',
        ]);

        $isCustomRole = $request->boolean('is_custom_role');
        if (! $request->has('is_custom_role')) {
            $posisi = trim($data['posisi_magang']);
            $matchedPaket = PaketKonversi::where('program_studi_id', $data['program_studi_id'])
                ->get()
                ->first(fn ($p) => strcasecmp((string) $p->target_posisi, $posisi) === 0
                    || strcasecmp((string) $p->nama_paket, $posisi) === 0
                    || stripos((string) $p->target_posisi, $posisi) !== false
                    || stripos((string) $p->nama_paket, $posisi) !== false);
            $isCustomRole = ! $matchedPaket;
        }

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

        $attributes = [
            'user_id' => $user->id,
            'periode_akademik_id' => $periodeAktif->id,
        ];

        $values = [
            'nim' => $nim,
            'nama_lengkap' => $namaLengkap,
            'program_studi_id' => $data['program_studi_id'],
            'mitra_id' => $data['mitra_id'] ?? null,
            'mitra_magang_text' => $data['mitra_magang_text'],
            'posisi_magang' => $data['posisi_magang'],
            'is_custom_role' => $isCustomRole,
            'tahun_ajaran' => trim("{$periodeAktif->tahun_ajaran} {$periodeAktif->semester}"),
            'transkrip_file_name' => $transkripName,
            'transkrip_file_path' => $transkripPath,
            'transkrip_file_size' => $transkripSize,
            'loa_file_name' => $loaName,
            'loa_file_path' => $loaPath,
            'loa_file_size' => $loaSize,
            'status' => 'Menunggu Verifikasi',
        ];

        if ($existing && ! empty($existing->pic_verifikator_id)) {
            $values['pic_verifikator_id'] = $existing->pic_verifikator_id;
            $values['is_plotted_pic'] = true;
        }

        $pendaftaran = PendaftaranMbkm::updateOrCreate($attributes, $values);
        $pendaftaran->load(['programStudi', 'mitra', 'periodeAkademik', 'picVerifikator', 'dpl', 'pengujiSemhas', 'rkm']);

        $message = $isUpdate
            ? 'Perbaikan berkas pendaftaran MBKM berhasil disimpan dan diajukan kembali.'
            : 'Pendaftaran MBKM berhasil diajukan. Menunggu verifikasi berkas oleh Tim MBKM.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $pendaftaran,
        ], $isUpdate ? 200 : 201);
    }

    public function streamFile(int $id, string $jenis, Request $request): Response
    {
        $pendaftaran = PendaftaranMbkm::findOrFail($id);
        $mode = $request->query('mode', 'inline');

        if ($jenis === 'transkrip') {
            $path = $pendaftaran->transkrip_file_path;
            $filename = $pendaftaran->transkrip_file_name;
            $title = 'Transkrip Nilai - '.$pendaftaran->nama_lengkap;
            if ($path && Storage::disk('local')->exists($path)) {
                $content = Storage::disk('local')->get($path);
            } else {
                $content = $this->pdfService->generateDummyPdf($title);
            }

            return $this->pdfService->streamPdfContent($content, $filename, $mode);
        }

        if ($jenis === 'loa') {
            $path = $pendaftaran->loa_file_path;
            $filename = $pendaftaran->loa_file_name;
            $title = 'Letter of Acceptance - '.$pendaftaran->nama_lengkap;
            if ($path && Storage::disk('local')->exists($path)) {
                $content = Storage::disk('local')->get($path);
            } else {
                $content = $this->pdfService->generateDummyPdf($title);
            }

            return $this->pdfService->streamPdfContent($content, $filename, $mode);
        }

        if ($jenis === 'revisi') {
            $path = $pendaftaran->lampiran_revisi_path;
            if (! $path || ! Storage::disk('public')->exists($path)) {
                return response()->json(['success' => false, 'message' => 'Berkas lampiran revisi tidak ditemukan.'], 404);
            }

            $content = Storage::disk('public')->get($path);
            $mimeType = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';
            $filename = basename($path);

            return response($content, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => ($mode === 'download' ? 'attachment' : 'inline').'; filename="'.$filename.'"',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Jenis berkas tidak valid.'], 400);
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

        // Cek wewenang verifikasi: PIC yang ditugaskan, Admin, atau Ketua Tim MBKM
        $isPic = (int) $pendaftaran->pic_verifikator_id === (int) $user->id
            || ($user->dosen && (int) $pendaftaran->pic_verifikator_id === (int) $user->dosen->id);
        $isAdminOrKetua = $user->hasRole(['Admin', 'Ketua Tim MBKM']);

        if (! $isPic && ! $isAdminOrKetua) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_VERIFIKASI_NOT_PLOTTED_PIC',
                'message' => 'Anda tidak berwenang memverifikasi mahasiswa ini karena bukan PIC Verifikator yang ditugaskan.',
            ], 403);
        }

        if ($pendaftaran->status === 'Terverifikasi') {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_VERIFIKASI_MBKM_LOCKED',
                'message' => 'Pendaftaran MBKM ini sudah berstatus Terverifikasi dan terkunci permanen.',
            ], 422);
        }

        $data = $request->validate([
            'status' => ['required', 'in:Terverifikasi,Perbaikan,Ditolak'],
            'link_wa_grup' => ['nullable', 'string', 'max:255', 'required_if:status,Terverifikasi', 'url'],
            'catatan' => ['nullable', 'string', 'required_if:status,Perbaikan', 'required_if:status,Ditolak'],
            'lampiran_revisi' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'file_revisi' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'link_wa_grup.url' => 'Tautan grup WhatsApp harus berupa URL yang valid (contoh: https://chat.whatsapp.com/...).',
            'lampiran_revisi.max' => 'Ukuran berkas lampiran revisi maksimal 5 MB.',
            'file_revisi.max' => 'Ukuran berkas lampiran revisi maksimal 5 MB.',
        ]);

        $updateData = [
            'status' => $data['status'],
            'catatan' => $data['catatan'] ?? null,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ];

        if ($data['status'] === 'Terverifikasi') {
            $updateData['link_wa_grup'] = $data['link_wa_grup'] ?? $pendaftaran->link_wa_grup;
            if ($pendaftaran->lampiran_revisi_path && Storage::disk('public')->exists($pendaftaran->lampiran_revisi_path)) {
                Storage::disk('public')->delete($pendaftaran->lampiran_revisi_path);
            }
            $updateData['lampiran_revisi_path'] = null;
        } elseif ($data['status'] === 'Perbaikan') {
            $lampiranFile = $request->file('lampiran_revisi') ?? $request->file('file_revisi');
            if ($lampiranFile) {
                if ($pendaftaran->lampiran_revisi_path && Storage::disk('public')->exists($pendaftaran->lampiran_revisi_path)) {
                    Storage::disk('public')->delete($pendaftaran->lampiran_revisi_path);
                }
                $origName = $lampiranFile->getClientOriginalName();
                $ext = $lampiranFile->getClientOriginalExtension();
                $fileName = 'revisi_'.time().'_'.Str::slug(pathinfo($origName, PATHINFO_FILENAME)).'.'.$ext;
                $path = $lampiranFile->storeAs('pendaftaran-revisi', $fileName, 'public');
                $updateData['lampiran_revisi_path'] = $path;
            }
        }

        $pendaftaran->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Status verifikasi berkas pendaftaran berhasil disimpan.',
            'data' => $pendaftaran->fresh(['programStudi', 'mitra', 'periodeAkademik', 'picVerifikator', 'dpl', 'pengujiSemhas', 'rkm']),
        ]);
    }
}
