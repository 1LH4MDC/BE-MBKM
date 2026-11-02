<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MitraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Mitra::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nomor_pks', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $query->orderBy('nama', 'asc');

        $shouldPaginate = ($request->has('page') || $request->has('per_page') || $request->input('paginate') === 'true')
            && $request->input('paginate') !== 'false'
            && ! $request->boolean('all');

        if (! $shouldPaginate) {
            return response()->json([
                'success' => true,
                'data' => $query->get()->map(fn (Mitra $m) => $this->withLogo($m)),
                'pagination' => null,
            ]);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map(fn (Mitra $m) => $this->withLogo($m)),
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
            'data' => $this->withLogo($mitra),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->applyLogo($request, $request->validate($this->rules()));

        $mitra = Mitra::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Data mitra berhasil ditambahkan.',
            'data' => $mitra,
        ], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $mitra = Mitra::findOrFail($id);

        $data = $this->applyLogo($request, $request->validate($this->rules(partial: true)));

        $mitra->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data mitra berhasil diperbarui.',
            'data' => $mitra,
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

    private function rules(bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'nama' => [...$required, 'string', 'max:150'],
            'nomor_pks' => ['nullable', 'string', 'max:100'],
            'alamat' => [...$required, 'string'],
            'kontak' => [...$required, 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'logo_url' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string'],
            'logo_file' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
        ];
    }

    private function applyLogo(Request $request, array $data): array
    {
        if ($request->hasFile('logo_file')) {
            $file = $request->file('logo_file');
            $filename = 'mitra_'.time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
            $url = Storage::url($file->storeAs('mitras', $filename, 'public'));

            $data['logo_url'] = $url;
            $data['image_url'] = $url;
        } elseif (! empty($data['logo_url'])) {
            $data['image_url'] = $data['logo_url'];
        } elseif (! empty($data['image_url'])) {
            $data['logo_url'] = $data['image_url'];
        }

        unset($data['logo_file']);

        return $data;
    }

    private function withLogo(Mitra $mitra): Mitra
    {
        $logo = $mitra->logo_url ?: $mitra->image_url;
        $mitra->logo_url = $logo;
        $mitra->image_url = $logo;

        return $mitra;
    }
}
