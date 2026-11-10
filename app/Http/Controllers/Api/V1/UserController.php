<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['roles', 'dosen.programStudi', 'mahasiswa.programStudi']);

        if ($request->filled('role')) {
            $role = $request->input('role');
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nim_nip', 'like', "%{$search}%")
                    ->orWhereHas('dosen', fn ($d) => $d->where('nip', 'like', "%{$search}%"))
                    ->orWhereHas('mahasiswa', fn ($m) => $m->where('nim', 'like', "%{$search}%"));
            });
        }

        $query->orderBy('id', 'desc');

        if ($request->input('paginate') === 'false' || $request->boolean('all')) {
            return response()->json([
                'success' => true,
                'data' => $query->get()->map(fn ($u) => $this->formatUser($u)),
                'pagination' => null,
            ]);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map(fn ($u) => $this->formatUser($u)),
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
        $user = User::with(['roles', 'dosen.programStudi', 'mahasiswa.programStudi'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatUser($user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $nimNip = $request->input('nim_nip') ?? $request->input('nip') ?? $request->input('nim');

        $tipeAkun = $request->input('tipe_akun');
        if (! $tipeAkun) {
            $roles = (array) $request->input('roles', []);
            if (in_array('Mahasiswa', $roles)) {
                $tipeAkun = 'mahasiswa';
            } elseif (array_intersect(['DPL', 'Ketua Tim MBKM', 'Tim MBKM', 'Penguji Semhas', 'GPM', 'Wakil Dekan'], $roles)) {
                $tipeAkun = 'dosen';
            } else {
                $tipeAkun = 'admin';
            }
            $request->merge(['tipe_akun' => $tipeAkun]);
        }

        if (! $request->filled('email')) {
            $generatedEmail = $nimNip ? "{$nimNip}@mail.unej.ac.id" : 'user_'.uniqid().'@mail.unej.ac.id';
            $request->merge(['email' => $generatedEmail]);
        }

        if ($nimNip) {
            $request->merge(['nim_nip' => $nimNip]);
            if ($tipeAkun === 'dosen' && ! $request->has('nip')) {
                $request->merge(['nip' => $nimNip]);
            } elseif ($tipeAkun === 'mahasiswa' && ! $request->has('nim')) {
                $request->merge(['nim' => $nimNip]);
            }
        }

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'nim_nip' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'tipe_akun' => ['required', 'in:dosen,mahasiswa,admin'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
            'avatar_url' => ['nullable', 'string'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'nip' => ['nullable', 'string', 'max:30', 'required_if:tipe_akun,dosen', 'unique:dosens,nip'],
            'program_studi_id' => ['nullable', 'exists:program_studis,id', 'required_if:tipe_akun,mahasiswa'],
            'nim' => ['nullable', 'string', 'max:30', 'required_if:tipe_akun,mahasiswa', 'unique:mahasiswas,nim'],
            'angkatan' => ['nullable', 'integer', 'required_if:tipe_akun,mahasiswa'],
        ]);

        return DB::transaction(function () use ($data, $nimNip) {
            $user = User::create([
                'nama' => $data['nama'],
                'email' => $data['email'],
                'nim_nip' => $data['nim_nip'] ?? $nimNip,
                'password' => Hash::make($data['password'] ?? 'password123'),
                'avatar_url' => $data['avatar_url'] ?? null,
                'no_hp' => $data['no_hp'] ?? null,
                'alamat' => $data['alamat'] ?? null,
            ]);

            $roleIds = Role::whereIn('name', $data['roles'])->pluck('id');
            $user->roles()->sync($roleIds);

            if ($data['tipe_akun'] === 'dosen') {
                Dosen::create([
                    'user_id' => $user->id,
                    'nip' => $data['nip'] ?? $nimNip,
                    'program_studi_id' => $data['program_studi_id'] ?? null,
                ]);
            } elseif ($data['tipe_akun'] === 'mahasiswa') {
                Mahasiswa::create([
                    'user_id' => $user->id,
                    'nim' => $data['nim'] ?? $nimNip,
                    'program_studi_id' => $data['program_studi_id'],
                    'angkatan' => $data['angkatan'] ?? (int) date('Y'),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Akun pengguna berhasil dibuat.',
                'data' => $this->formatUser($user->fresh(['roles', 'dosen.programStudi', 'mahasiswa.programStudi'])),
            ], 201);
        });
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $user = User::with(['dosen', 'mahasiswa', 'roles'])->findOrFail($id);

        $nimNip = $request->input('nim_nip') ?? $request->input('nip') ?? $request->input('nim') ?? $user->nim_nip;

        if (! $request->filled('email')) {
            $email = $user->email ?: ($nimNip ? "{$nimNip}@fasilkom.ac.id" : null);
            if ($email) {
                $request->merge(['email' => $email]);
            }
        }

        if ($nimNip) {
            $request->merge(['nim_nip' => $nimNip]);
            if (($user->dosen || $request->input('tipe_akun') === 'dosen') && ! $request->has('nip')) {
                $request->merge(['nip' => $nimNip]);
            }
            if (($user->mahasiswa || $request->input('tipe_akun') === 'mahasiswa') && ! $request->has('nim')) {
                $request->merge(['nim' => $nimNip]);
            }
        }

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'nim_nip' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'avatar_url' => ['nullable', 'string'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('dosens', 'nip')->ignore($user->dosen?->id)],
            'nim' => ['nullable', 'string', 'max:30', Rule::unique('mahasiswas', 'nim')->ignore($user->mahasiswa?->id)],
            'program_studi_id' => ['nullable', 'exists:program_studis,id'],
            'angkatan' => ['nullable', 'integer'],
        ]);

        return DB::transaction(function () use ($user, $data, $request, $nimNip) {
            $userUpdate = [
                'nama' => $data['nama'],
                'email' => $data['email'] ?? $user->email,
                'nim_nip' => $data['nim_nip'] ?? $nimNip ?? $user->nim_nip,
                'avatar_url' => $data['avatar_url'] ?? $user->avatar_url,
                'no_hp' => $data['no_hp'] ?? $user->no_hp,
                'alamat' => $data['alamat'] ?? $user->alamat,
            ];

            if (! empty($data['password'])) {
                $userUpdate['password'] = Hash::make($data['password']);
            }

            $user->update($userUpdate);

            if (isset($data['roles'])) {
                $roleIds = Role::whereIn('name', $data['roles'])->pluck('id');
                $user->roles()->sync($roleIds);
            }

            $targetNip = $data['nip'] ?? $data['nim_nip'] ?? null;
            if ($user->dosen) {
                $user->dosen->update([
                    'nip' => $targetNip ?? $user->dosen->nip,
                    'program_studi_id' => $data['program_studi_id'] ?? $user->dosen->program_studi_id,
                ]);
            } elseif ($targetNip && ($request->input('tipe_akun') === 'dosen' || (isset($data['roles']) && array_intersect(['DPL', 'Ketua Tim MBKM', 'Tim MBKM', 'Penguji Semhas', 'GPM', 'Wakil Dekan'], $data['roles'])))) {
                Dosen::create([
                    'user_id' => $user->id,
                    'nip' => $targetNip,
                    'program_studi_id' => $data['program_studi_id'] ?? null,
                ]);
            }

            $targetNim = $data['nim'] ?? $data['nim_nip'] ?? null;
            if ($user->mahasiswa) {
                $user->mahasiswa->update([
                    'nim' => $targetNim ?? $user->mahasiswa->nim,
                    'program_studi_id' => $data['program_studi_id'] ?? $user->mahasiswa->program_studi_id,
                    'angkatan' => $data['angkatan'] ?? $user->mahasiswa->angkatan,
                ]);
            } elseif ($targetNim && ($request->input('tipe_akun') === 'mahasiswa' || (isset($data['roles']) && in_array('Mahasiswa', $data['roles'])))) {
                Mahasiswa::create([
                    'user_id' => $user->id,
                    'nim' => $targetNim,
                    'program_studi_id' => $data['program_studi_id'] ?? null,
                    'angkatan' => $data['angkatan'] ?? (int) date('Y'),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data pengguna berhasil diperbarui.',
                'data' => $this->formatUser($user->fresh(['roles', 'dosen.programStudi', 'mahasiswa.programStudi'])),
            ]);
        });
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($request->user()->id === $user->id) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FORBIDDEN_ROLE',
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 403);
        }

        if ($user->pendaftaranMbkm()->exists()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FOREIGN_KEY_CONSTRAINT',
                'message' => 'Pengguna tidak dapat dihapus karena memiliki transaksi pendaftaran MBKM.',
            ], 409);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }

    private function formatUser(User $u): array
    {
        return [
            'id' => $u->id,
            'nama' => $u->nama,
            'email' => $u->email,
            'nim_nip' => $u->nim_nip,
            'roles' => $u->roles->pluck('name')->values(),
            'avatar_url' => $u->avatar_url,
            'no_hp' => $u->no_hp,
            'alamat' => $u->alamat,
            'profil_tipe' => $u->dosen ? 'dosen' : ($u->mahasiswa ? 'mahasiswa' : 'admin'),
            'dosen' => $u->dosen ? [
                'id' => $u->dosen->id,
                'nip' => $u->dosen->nip,
                'program_studi' => $u->dosen->programStudi?->nama,
                'program_studi_id' => $u->dosen->program_studi_id,
            ] : null,
            'mahasiswa' => $u->mahasiswa ? [
                'id' => $u->mahasiswa->id,
                'nim' => $u->mahasiswa->nim,
                'program_studi' => $u->mahasiswa->programStudi?->nama,
                'program_studi_id' => $u->mahasiswa->program_studi_id,
                'angkatan' => $u->mahasiswa->angkatan,
            ] : null,
        ];
    }
}
