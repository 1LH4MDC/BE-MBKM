<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('nama', 'asc')->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $mitra = Mitra::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $mitra,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'nomor_pks' => ['nullable', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
            'kontak' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:255'],
        ]);

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

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'nomor_pks' => ['nullable', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
            'kontak' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:255'],
        ]);

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
}
