<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Services\PdfDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MitraController extends Controller
{
    public function __construct(
        private readonly PdfDocumentService $pdfService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Mitra::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nomor_pks', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%")
                    ->orWhere('kontak', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $query->orderBy('nama', 'asc');

        $shouldPaginate = ($request->has('page') || $request->has('per_page') || $request->input('paginate') === 'true')
            && $request->input('paginate') !== 'false'
            && ! $request->boolean('all');

        if (! $shouldPaginate) {
            return response()->json([
                'success' => true,
                'data' => $query->get()->map(fn (Mitra $m) => $this->formatMitra($m)),
                'pagination' => null,
            ]);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map(fn (Mitra $m) => $this->formatMitra($m)),
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

    public function show(int $id): JsonResponse
    {
        $mitra = Mitra::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatMitra($mitra),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->applyFiles($request, $request->validate($this->rules($request)));

        $mitra = Mitra::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Data mitra berhasil ditambahkan.',
            'data' => $this->formatMitra($mitra->fresh()),
        ], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $mitra = Mitra::findOrFail($id);

        $data = $this->applyFiles($request, $request->validate($this->rules($request, partial: true)));

        $mitra->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data mitra berhasil diperbarui.',
            'data' => $this->formatMitra($mitra->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $mitra = Mitra::findOrFail($id);

        if ($mitra->pendaftaranMbkms()->exists()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FOREIGN_KEY_CONSTRAINT',
                'message' => 'Mitra tidak dapat dihapus karena masih terhubung dengan pendaftaran MBKM aktif.',
            ], 409);
        }

        $mitra->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data mitra berhasil dihapus.',
        ]);
    }

    public function streamFilePks(int $id, Request $request): Response
    {
        $mitra = Mitra::findOrFail($id);
        $mode = $request->query('mode', 'inline');
        $safeName = preg_replace('/[^A-Za-z0-9_\-.]+/', '_', $mitra->file_pks_name ?: "Dokumen_PKS_{$mitra->nama}.pdf");
        if (! str_ends_with(strtolower($safeName), '.pdf')) {
            $safeName .= '.pdf';
        }

        $relativePath = $mitra->file_pks ? ltrim(preg_replace('#^/storage/#', '', $mitra->file_pks), '/') : null;
        if ($relativePath && Storage::disk('public')->exists($relativePath)) {
            $content = Storage::disk('public')->get($relativePath);

            return $this->pdfService->streamPdfContent($content, $safeName, $mode);
        }

        $content = $this->pdfService->generateDummyPdf(
            'DOKUMEN PERJANJIAN KERJA SAMA (PKS)',
            "Mitra: {$mitra->nama} | Nomor PKS: ".($mitra->nomor_pks ?: '-')
        );

        return $this->pdfService->streamPdfContent($content, $safeName, $mode);
    }

    private function rules(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        $rules = [
            'nama' => [...$required, 'string', 'max:150'],
            'nomor_pks' => ['nullable', 'string', 'max:100'],
            'alamat' => [...$required, 'string'],
            'kontak' => [...$required, 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'logo_url' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string'],
            'logo_file' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
        ];

        if ($request->hasFile('file_pks')) {
            $rules['file_pks'] = ['nullable', 'file', 'mimes:pdf', 'max:5120'];
        } else {
            $rules['file_pks'] = ['nullable', 'string'];
        }

        if ($request->hasFile('pks_file')) {
            $rules['pks_file'] = ['nullable', 'file', 'mimes:pdf', 'max:5120'];
        }

        return $rules;
    }

    private function applyFiles(Request $request, array $data): array
    {
        $logoUpload = $request->file('logo_file') ?? $request->file('logo') ?? $request->file('image');
        if ($logoUpload) {
            $filename = 'mitra_'.time().'_'.Str::slug(pathinfo($logoUpload->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$logoUpload->getClientOriginalExtension();
            $url = Storage::url($logoUpload->storeAs('mitras', $filename, 'public'));

            $data['logo_url'] = $url;
            $data['image_url'] = $url;
        } elseif (! empty($data['logo_url'])) {
            $data['image_url'] = $data['logo_url'];
        } elseif (! empty($data['image_url'])) {
            $data['logo_url'] = $data['image_url'];
        }

        $pksUpload = $request->file('file_pks') ?? $request->file('pks_file');
        if ($pksUpload) {
            $originalName = $pksUpload->getClientOriginalName();
            $sizeBytes = $pksUpload->getSize();
            $filename = 'pks_'.time().'_'.Str::slug(pathinfo($originalName, PATHINFO_FILENAME)).'.pdf';
            $storedPath = $pksUpload->storeAs('mitras/pks', $filename, 'public');

            $data['file_pks'] = $storedPath;
            $data['file_pks_name'] = $originalName;
            $data['file_pks_size'] = $sizeBytes;
        }

        unset($data['logo_file'], $data['logo'], $data['image'], $data['pks_file']);

        return $data;
    }

    private function formatMitra(Mitra $mitra): array
    {
        $logo = $mitra->logo_url ?: $mitra->image_url;
        $hasPks = ! empty($mitra->file_pks);
        $filePksUrl = $hasPks ? "/api/v1/mitra/{$mitra->id}/file-pks" : null;
        $filePksName = $mitra->file_pks_name ?: ($hasPks ? basename($mitra->file_pks) : null);

        return [
            'id' => $mitra->id,
            'nama' => $mitra->nama,
            'nomor_pks' => $mitra->nomor_pks,
            'file_pks' => $mitra->file_pks,
            'file_pks_url' => $filePksUrl,
            'file_pks_name' => $filePksName,
            'file_pks_size' => $mitra->file_pks_size,
            'pks_file' => $mitra->file_pks,
            'pks_file_url' => $filePksUrl,
            'pks_file_name' => $filePksName,
            'pks_file_size' => $mitra->file_pks_size,
            'alamat' => $mitra->alamat,
            'kontak' => $mitra->kontak,
            'email' => $mitra->email,
            'deskripsi' => $mitra->deskripsi,
            'logo_url' => $logo,
            'image_url' => $logo,
            'created_at' => $mitra->created_at,
            'updated_at' => $mitra->updated_at,
        ];
    }
}
