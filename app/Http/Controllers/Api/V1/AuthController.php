<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function ssoRedirect(Request $request): RedirectResponse|JsonResponse
    {
        $baseUrl = config('services.gapura.url', 'https://gapura.unej.ac.id');
        $clientId = config('services.gapura.client_id');
        $redirectUri = $request->query('redirect_uri') ?: config('services.gapura.redirect_uri');

        if (! $clientId) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_SSO_NOT_CONFIGURED',
                'message' => 'GAPURA_CLIENT_ID belum diatur pada file .env server.',
            ], 500);
        }

        $state = Str::random(40);
        Cache::put("gapura_sso_state:{$state}", [
            'redirect_uri' => $redirectUri,
            'frontend_redirect' => $request->query('frontend_redirect'),
        ], now()->addMinutes(10));

        $authorizeUrl = "{$baseUrl}/oauth/authorize?".http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
        ]);

        if ($request->boolean('json') || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'authorize_url' => $authorizeUrl,
                    'state' => $state,
                ],
            ]);
        }

        return redirect()->away($authorizeUrl);
    }

    public function ssoCallback(Request $request): RedirectResponse|JsonResponse
    {
        $code = $request->input('code');
        $state = $request->input('state');
        $frontendUrl = config('services.gapura.frontend_url', 'https://mbkm.fasilkomapp.id');

        if (! $code) {
            return $this->ssoErrorResponse($request, $frontendUrl, 'Kode otorisasi SSO Gapura tidak ditemukan.');
        }

        $cachedState = $state ? Cache::pull("gapura_sso_state:{$state}") : null;
        if ($state && ! $cachedState && ! $request->boolean('skip_state')) {
            return $this->ssoErrorResponse($request, $frontendUrl, 'Sesi state SSO tidak valid atau sudah kedaluwarsa. Silakan coba login ulang.');
        }

        $baseUrl = config('services.gapura.url', 'https://gapura.unej.ac.id');
        $redirectUri = $request->input('redirect_uri')
            ?: ($cachedState['redirect_uri'] ?? config('services.gapura.redirect_uri'));

        $tokenResponse = Http::timeout(10)->asForm()->post("{$baseUrl}/oauth/token", [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.gapura.client_id'),
            'client_secret' => config('services.gapura.client_secret'),
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        if (! $tokenResponse->successful()) {
            return $this->ssoErrorResponse($request, $frontendUrl, 'Gagal menukar kode otorisasi dengan server Gapura UNEJ.');
        }

        $tokenData = $tokenResponse->json();
        $accessToken = $tokenData['access_token'] ?? null;
        if (! $accessToken) {
            return $this->ssoErrorResponse($request, $frontendUrl, 'Access token tidak ditemukan dari respons Gapura UNEJ.');
        }

        $profileResponse = Http::timeout(10)->withToken($accessToken)->get("{$baseUrl}/api/user");
        if (! $profileResponse->successful()) {
            $profileResponse = Http::timeout(10)->withToken($accessToken)->get("{$baseUrl}/oauth/userinfo");
        }

        if (! $profileResponse->successful()) {
            return $this->ssoErrorResponse($request, $frontendUrl, 'Gagal mengambil profil pengguna dari Gapura UNEJ.');
        }

        $rawProfile = $profileResponse->json();
        $profile = is_array($rawProfile['data'] ?? null) ? $rawProfile['data'] : (array) $rawProfile;

        $user = $this->findAndSyncGapuraUser($profile);
        if (! $user) {
            $idLabel = $profile['identifier'] ?? $profile['username'] ?? $profile['email'] ?? 'Anda';

            return $this->ssoErrorResponse(
                $request,
                $frontendUrl,
                "Akun dengan NIM/NIP ({$idLabel}) belum terdaftar di sistem MBKM Fasilkom. Silakan hubungi Admin."
            );
        }

        $user->load(['roles', 'dosen.programStudi', 'mahasiswa.programStudi']);

        $sanctumToken = $user->createToken('mbkm-sso')->plainTextToken;
        $userPayload = $this->userPayload($user);

        if ($request->isMethod('post') || $request->boolean('json') || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Login SSO Gapura berhasil.',
                'data' => [
                    'token' => $sanctumToken,
                    'user' => $userPayload,
                ],
            ]);
        }

        $targetFrontend = $cachedState['frontend_redirect'] ?? "{$frontendUrl}/login";
        $separator = str_contains($targetFrontend, '?') ? '&' : '?';

        return redirect()->away("{$targetFrontend}{$separator}".http_build_query([
            'sso_token' => $sanctumToken,
        ]));
    }

    private function findAndSyncGapuraUser(array $profile): ?User
    {
        $personalId = isset($profile['personal_id']) ? (string) $profile['personal_id'] : (isset($profile['sub']) ? (string) $profile['sub'] : null);
        $identifier = isset($profile['identifier']) ? trim((string) $profile['identifier']) : (isset($profile['username']) ? trim((string) $profile['username']) : null);
        $nama = isset($profile['name']) && trim((string) $profile['name']) !== '' ? trim((string) $profile['name']) : null;
        $email = isset($profile['email']) && trim((string) $profile['email']) !== '' ? trim((string) $profile['email']) : null;

        $hasPersonalIdCol = Schema::hasColumn('users', 'gapura_personal_id');

        $user = null;

        if ($hasPersonalIdCol && $personalId) {
            $user = User::where('gapura_personal_id', $personalId)->first();
        }

        if (! $user && $identifier) {
            $user = User::whereIdentifier($identifier)->first();
        }

        if (! $user && $email) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            return null;
        }

        return DB::transaction(function () use ($user, $personalId, $identifier, $nama, $email, $hasPersonalIdCol) {
            $updates = [];

            if ($hasPersonalIdCol && $personalId && $user->gapura_personal_id !== $personalId) {
                $updates['gapura_personal_id'] = $personalId;
            }

            if ($identifier && empty($user->nim_nip)) {
                $updates['nim_nip'] = $identifier;
            }

            if ($nama && empty($user->nama)) {
                $updates['nama'] = $nama;
            }

            if ($email && $user->email !== $email && ! User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
                $updates['email'] = $email;
            }

            if (! empty($updates)) {
                $user->update($updates);
            }

            return $user;
        });
    }

    private function ssoErrorResponse(Request $request, string $frontendUrl, string $message): RedirectResponse|JsonResponse
    {
        if ($request->isMethod('post') || $request->boolean('json') || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_SSO_FAILED',
                'message' => $message,
            ], 401);
        }

        return redirect()->away("{$frontendUrl}/login?".http_build_query([
            'sso_error' => $message,
        ]));
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nim_nip' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with(['roles', 'dosen.programStudi', 'mahasiswa.programStudi'])
            ->whereIdentifier($data['nim_nip'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_AUTH_INVALID_CREDENTIALS',
                'message' => 'NIM/NIP atau password salah.',
                'errors' => ['nim_nip' => ['Kredensial tidak valid.']],
            ], 401);
        }

        $token = $user->createToken('mbkm-web')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => ['token' => $token, 'user' => $this->userPayload($user)],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->userPayload($request->user()->load(['roles', 'dosen.programStudi', 'mahasiswa.programStudi'])),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Logout berhasil']);
    }

    private function userPayload(User $user): array
    {
        $roles = $user->roles->pluck('name')->values();

        if ($user->mahasiswa) {
            $nimSource = $user->mahasiswa->nim ?: $user->nim_nip;
            $expectedProdiId = UserController::resolveProgramStudiIdFromNim($nimSource, false);
            $expectedAngkatan = UserController::resolveAngkatanFromNim($nimSource, false);
            $updates = [];
            if ($expectedProdiId && $user->mahasiswa->program_studi_id !== $expectedProdiId) {
                $updates['program_studi_id'] = $expectedProdiId;
            }
            if ($expectedAngkatan && (int) $user->mahasiswa->angkatan !== $expectedAngkatan) {
                $updates['angkatan'] = $expectedAngkatan;
            }
            if (! empty($updates)) {
                $user->mahasiswa->update($updates);
                $user->load('mahasiswa.programStudi');
            }
        }

        $prodiObj = $user->mahasiswa?->programStudi ?? $user->dosen?->programStudi;

        $resolvedNim = $user->mahasiswa?->nim
            ?? $user->nim
            ?? ($roles->contains('Mahasiswa') && ! $user->dosen ? $user->nim_nip : null);

        $resolvedNip = $user->dosen?->nip
            ?? $user->nip
            ?? (! $user->mahasiswa && ! $roles->contains('Mahasiswa') ? $user->nim_nip : null);

        $payload = [
            'id' => $user->id,
            'nama' => $user->nama,
            'nim_nip' => $user->nim_nip,
            'nim' => $resolvedNim,
            'nip' => $resolvedNip,
            'angkatan' => $user->mahasiswa?->angkatan,
            'program_studi_id' => $prodiObj?->id,
            'program_studi' => $prodiObj?->nama,
            'kode_prodi' => $prodiObj?->kode,
            'email' => $user->email,
            'roles' => $roles,
            'active_role' => $roles->first(),
            'avatar_url' => $user->avatar_url,
            'profil_tipe' => $user->dosen ? 'dosen' : ($user->mahasiswa ? 'mahasiswa' : 'admin'),
        ];

        if ($user->dosen) {
            $payload['dosen'] = [
                'id' => $user->dosen->id,
                'nip' => $user->dosen->nip,
                'program_studi_id' => $user->dosen->program_studi_id,
                'program_studi' => $user->dosen->programStudi?->nama,
                'kode_prodi' => $user->dosen->programStudi?->kode,
            ];
        }

        if ($user->mahasiswa) {
            $payload['mahasiswa'] = [
                'id' => $user->mahasiswa->id,
                'nim' => $user->mahasiswa->nim,
                'angkatan' => $user->mahasiswa->angkatan,
                'program_studi_id' => $user->mahasiswa->program_studi_id,
                'program_studi' => $user->mahasiswa->programStudi?->nama,
                'kode_prodi' => $user->mahasiswa->programStudi?->kode,
            ];
        }

        return $payload;
    }
}
