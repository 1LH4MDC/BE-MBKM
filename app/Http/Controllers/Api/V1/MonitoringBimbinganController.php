<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BimbinganMagang;
use App\Models\MataKuliah;
use App\Models\PaketKonversi;
use App\Models\PendaftaranMbkm;
use App\Models\PeriodeAkademik;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MonitoringBimbinganController extends Controller
{
    private const STATUS_MK = ['Belum Terlaksana', 'Sebagian Terlaksana', 'Terlaksana'];

    public function getDplMahasiswaList(Request $request): JsonResponse
    {
        $user = $request->user();

        $baseQuery = PendaftaranMbkm::with(['user.mahasiswa', 'programStudi', 'mitra', 'periodeAkademik', 'dpl', 'rkm.items.mataKuliah'])
            ->withCount('bimbingans')
            ->whereIn('status', ['Terverifikasi', 'Disetujui']);

        $this->applyPeriodeFilter($baseQuery, $request);

        if ($request->filled('prodi') && $request->input('prodi') !== 'Semua' && $request->input('prodi') !== 'Semua Prodi') {
            $prodi = $request->input('prodi');
            $baseQuery->whereHas('programStudi', function ($q) use ($prodi) {
                $q->where('nama', $prodi)->orWhere('kode', $prodi);
            });
        } elseif ($request->filled('program_studi_id')) {
            $baseQuery->where('program_studi_id', $request->input('program_studi_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $baseQuery->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%");
            });
        }

        $query = clone $baseQuery;
        if (! $user->hasRole('Admin', 'Ketua Tim MBKM', 'Tim MBKM')) {
            $dosenId = $user->dosen?->id;
            $query->where(function ($q) use ($user, $dosenId) {
                $q->where('dpl_id', $user->id);
                if ($dosenId) {
                    $q->orWhere('dpl_id', $dosenId);
                }
            });
        }

        $items = $query->orderBy('nama_lengkap')->get();

        if ($items->isEmpty() && $user->hasRole('DPL')) {
            $items = (clone $baseQuery)->whereNotNull('dpl_id')->orderBy('nama_lengkap')->get();
        }

        $fallbackWa = \Illuminate\Support\Facades\Cache::get("dpl_wa_link:{$user->id}");

        $data = $items->map(function (PendaftaranMbkm $p) use ($fallbackWa) {
            $courses = $this->courseList($p);
            $payload = $this->mahasiswaPayload($p);
            if (empty($payload['link_wa_dpl']) && $fallbackWa) {
                $payload['link_wa_dpl'] = $fallbackWa;
            }

            return [
                ...$payload,
                'rkm_id' => $p->rkm?->id,
                'total_sks_konversi' => (int) ($courses->sum('sks') ?: ($p->rkm?->total_sks ?? 20)),
                'total_bimbingan' => (int) $p->bimbingans_count,
                'link_logbook' => $p->link_logbook,
                'mata_kuliah_konversi' => $courses->values(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->values(),
        ]);
    }

    public function getMonitoringDetail(Request $request, int $pendaftaranId): JsonResponse
    {
        $p = PendaftaranMbkm::findOrFail($pendaftaranId);

        if (! $this->canView($request->user(), $p)) {
            return $this->forbidden('Anda tidak memiliki akses ke data monitoring mahasiswa ini.');
        }

        return response()->json([
            'success' => true,
            'data' => $this->detailPayload($p),
        ]);
    }

    public function storeBimbinganSession(Request $request, int $pendaftaranId): JsonResponse
    {
        $p = PendaftaranMbkm::with(['rkm.items.mataKuliah', 'periodeAkademik'])->findOrFail($pendaftaranId);
        $user = $request->user();

        if (! $this->canManageBimbingan($user, $p)) {
            return $this->forbidden('Anda tidak memiliki akses untuk mencatat sesi bimbingan mahasiswa ini.');
        }

        $this->normalizeStatusesInput($request);

        $data = $request->validate([
            'bimbingan_ke' => ['nullable', 'integer', 'min:1'],
            'tanggal_bimbingan' => ['required', 'date'],
            'catatan_bimbingan' => ['required', 'string'],
            'statuses' => ['nullable', 'array'],
            'statuses.*.mata_kuliah_id' => ['nullable', 'integer'],
            'statuses.*.kode_mk' => ['required_with:statuses', 'string', 'max:50'],
            'statuses.*.nama_mk' => ['required_with:statuses', 'string', 'max:255'],
            'statuses.*.sks' => ['required_with:statuses', 'integer', 'min:0'],
            'statuses.*.status' => ['required_with:statuses', 'in:'.implode(',', self::STATUS_MK)],
            'foto_bukti' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $fotoPath = $request->hasFile('foto_bukti')
            ? $request->file('foto_bukti')->store('monitoring-bukti', 'public')
            : null;

        $statusesToSave = ! empty($data['statuses'])
            ? $data['statuses']
            : $this->courseList($p)->map(fn (array $mk) => [
                'mata_kuliah_id' => $mk['mata_kuliah_id'] ?? null,
                'kode_mk' => $mk['kode_mk'],
                'nama_mk' => $mk['nama_mk'],
                'sks' => $mk['sks'],
                'status' => 'Belum Terlaksana',
            ])->all();

        $session = DB::transaction(function () use ($data, $p, $user, $fotoPath, $statusesToSave) {
            $nextNumber = (int) BimbinganMagang::where('pendaftaran_mbkm_id', $p->id)->max('bimbingan_ke') + 1;

            $bimbingan = BimbinganMagang::create([
                'pendaftaran_mbkm_id' => $p->id,
                'dpl_id' => $user->id,
                'periode_akademik_id' => $p->periode_akademik_id,
                'tahun_ajaran' => $p->tahun_ajaran ?? $p->periodeAkademik?->nama_lengkap,
                'bimbingan_ke' => ! empty($data['bimbingan_ke']) ? (int) $data['bimbingan_ke'] : $nextNumber,
                'tanggal_bimbingan' => $data['tanggal_bimbingan'],
                'catatan_bimbingan' => $data['catatan_bimbingan'],
                'foto_bukti_path' => $fotoPath,
                'foto_uploaded_by_role' => $fotoPath ? ($user->hasRole('DPL', 'Admin', 'Ketua Tim MBKM') ? 'DPL' : 'Mahasiswa') : null,
            ]);

            foreach ($statusesToSave as $st) {
                $bimbingan->statuses()->create([
                    'mata_kuliah_id' => $st['mata_kuliah_id'] ?? null,
                    'kode_mk' => $st['kode_mk'],
                    'nama_mk' => $st['nama_mk'],
                    'sks' => (int) ($st['sks'] ?? 0),
                    'status' => $st['status'] ?? 'Belum Terlaksana',
                ]);
            }

            return $bimbingan->load('statuses');
        });

        return response()->json([
            'success' => true,
            'message' => 'Sesi bimbingan berhasil disimpan.',
            'data' => $session,
        ], 201);
    }

    public function updateBimbinganSession(Request $request, int $bimbinganId): JsonResponse
    {
        $bimbingan = BimbinganMagang::with('pendaftaranMbkm')->findOrFail($bimbinganId);

        if (! $this->canManageBimbingan($request->user(), $bimbingan->pendaftaranMbkm)) {
            return $this->forbidden('Anda tidak memiliki akses untuk mengubah sesi bimbingan ini.');
        }

        $this->normalizeStatusesInput($request);

        $data = $request->validate([
            'tanggal_bimbingan' => ['nullable', 'date'],
            'catatan_bimbingan' => ['nullable', 'string'],
            'statuses' => ['nullable', 'array'],
            'statuses.*.mata_kuliah_id' => ['nullable', 'integer'],
            'statuses.*.kode_mk' => ['required_with:statuses', 'string', 'max:50'],
            'statuses.*.nama_mk' => ['nullable', 'string', 'max:255'],
            'statuses.*.sks' => ['nullable', 'integer', 'min:0'],
            'statuses.*.status' => ['required_with:statuses', 'in:'.implode(',', self::STATUS_MK)],
            'foto_bukti' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $oldFotoPath = null;
        if ($request->hasFile('foto_bukti')) {
            $oldFotoPath = $bimbingan->getRawFotoBuktiPath();
            $bimbingan->foto_bukti_path = $request->file('foto_bukti')->store('monitoring-bukti', 'public');
        }

        DB::transaction(function () use ($data, $bimbingan) {
            if (! empty($data['tanggal_bimbingan'])) {
                $bimbingan->tanggal_bimbingan = $data['tanggal_bimbingan'];
            }
            if (array_key_exists('catatan_bimbingan', $data) && $data['catatan_bimbingan'] !== null) {
                $bimbingan->catatan_bimbingan = $data['catatan_bimbingan'];
            }
            $bimbingan->save();

            foreach ($data['statuses'] ?? [] as $st) {
                $existing = $bimbingan->statuses()->where('kode_mk', $st['kode_mk'])->first();
                if ($existing) {
                    $existing->update(['status' => $st['status']]);
                } else {
                    $bimbingan->statuses()->create([
                        'mata_kuliah_id' => $st['mata_kuliah_id'] ?? null,
                        'kode_mk' => $st['kode_mk'],
                        'nama_mk' => $st['nama_mk'] ?? $st['kode_mk'],
                        'sks' => (int) ($st['sks'] ?? 0),
                        'status' => $st['status'],
                    ]);
                }
            }
        });

        if ($oldFotoPath && $oldFotoPath !== $bimbingan->getRawFotoBuktiPath() && Storage::disk('public')->exists($oldFotoPath)) {
            Storage::disk('public')->delete($oldFotoPath);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesi bimbingan berhasil diperbarui.',
            'data' => $bimbingan->fresh('statuses'),
        ]);
    }

    public function deleteBimbinganSession(Request $request, int $bimbinganId): JsonResponse
    {
        $bimbingan = BimbinganMagang::with('pendaftaranMbkm')->findOrFail($bimbinganId);

        if (! $this->canManageBimbingan($request->user(), $bimbingan->pendaftaranMbkm)) {
            return $this->forbidden('Anda tidak memiliki akses untuk menghapus sesi bimbingan ini.');
        }

        $pendaftaranId = $bimbingan->pendaftaran_mbkm_id;
        $fotoPath = $bimbingan->getRawFotoBuktiPath();

        DB::transaction(function () use ($bimbingan, $pendaftaranId) {
            $bimbingan->delete();

            $remaining = BimbinganMagang::where('pendaftaran_mbkm_id', $pendaftaranId)
                ->orderBy('bimbingan_ke')
                ->get();

            foreach ($remaining as $item) {
                $item->bimbingan_ke = $item->bimbingan_ke + 100000;
                $item->saveQuietly();
            }

            foreach ($remaining as $idx => $item) {
                $item->bimbingan_ke = $idx + 1;
                $item->saveQuietly();
            }
        });

        if ($fotoPath && Storage::disk('public')->exists($fotoPath)) {
            Storage::disk('public')->delete($fotoPath);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesi bimbingan berhasil dihapus dan urutan nomor disesuaikan.',
        ]);
    }

    public function getMyMonitoring(Request $request): JsonResponse
    {
        $user = $request->user();
        $periodeAktif = PeriodeAkademik::where('is_aktif', true)->first();

        $targetPeriodeId = $request->query('periode_akademik_id') ?? $request->query('periode_id');
        if (! $targetPeriodeId && $request->filled('tahun_ajaran')) {
            $rawTa = trim((string) $request->input('tahun_ajaran'));
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

        if (! $targetPeriodeId) {
            $targetPeriodeId = $periodeAktif?->id;
        }

        $p = PendaftaranMbkm::where('user_id', $user->id)
            ->when($targetPeriodeId, fn ($q) => $q->where('periode_akademik_id', $targetPeriodeId))
            ->latest('id')
            ->first();

        if (! $p) {
            return response()->json([
                'success' => true,
                'message' => 'Belum ada pendaftaran MBKM pada periode ini.',
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->detailPayload($p),
        ]);
    }

    public function saveMyLogbookLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'link_logbook' => ['required', 'url', 'max:500'],
            'pendaftaran_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();

        $p = ! empty($data['pendaftaran_id'])
            ? PendaftaranMbkm::where('user_id', $user->id)->find($data['pendaftaran_id'])
            : null;

        if (! $p) {
            $p = PendaftaranMbkm::where('user_id', $user->id)
                ->whereHas('periodeAkademik', fn ($q) => $q->where('is_aktif', true))
                ->latest('id')
                ->first()
                ?? PendaftaranMbkm::where('user_id', $user->id)->latest('id')->first();
        }

        if (! $p) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_NOT_FOUND',
                'message' => 'Pendaftaran MBKM tidak ditemukan.',
            ], 404);
        }

        if ($this->isArchived($p)) {
            return $this->forbidden('Data berasal dari Tahun Ajaran Arsip dan hanya dapat dilihat (Read-Only).');
        }

        $p->update(['link_logbook' => $data['link_logbook']]);

        return response()->json([
            'success' => true,
            'message' => 'Tautan Google Docs Logbook berhasil disimpan.',
            'data' => ['link_logbook' => $p->link_logbook],
        ]);
    }

    public function uploadFotoBukti(Request $request, int $bimbinganId): JsonResponse
    {
        $bimbingan = BimbinganMagang::with('pendaftaranMbkm')->findOrFail($bimbinganId);
        $user = $request->user();

        $isMahasiswaOwner = (int) $bimbingan->pendaftaranMbkm?->user_id === (int) $user->id;
        $isDplAssigned = (int) $bimbingan->dpl_id === (int) $user->id
            || (int) $bimbingan->pendaftaranMbkm?->dpl_id === (int) $user->id
            || ($user->dosen && (int) $bimbingan->pendaftaranMbkm?->dpl_id === (int) $user->dosen->id)
            || $this->canManageBimbingan($user, $bimbingan->pendaftaranMbkm);

        if (! $isMahasiswaOwner && ! $isDplAssigned) {
            return $this->forbidden('Anda tidak memiliki hak akses untuk mengunggah foto bukti pada sesi bimbingan ini.');
        }

        $request->validate([
            'foto_bukti' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $oldPath = $bimbingan->getRawFotoBuktiPath();
        $path = $request->file('foto_bukti')->store('monitoring-bukti', 'public');
        $role = $isDplAssigned ? 'DPL' : 'Mahasiswa';

        $bimbingan->update([
            'foto_bukti_path' => $path,
            'foto_uploaded_by_role' => $role,
        ]);

        if ($oldPath && $oldPath !== $path && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $fullUrl = url(Storage::url($path));

        return response()->json([
            'success' => true,
            'message' => 'Foto bukti bimbingan berhasil diunggah.',
            'data' => [
                'foto_bukti_path' => $fullUrl,
                'foto_bukti_url' => $fullUrl,
                'foto_uploaded_by_role' => $role,
            ],
        ]);
    }

    public function saveDplWhatsappLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'link_wa' => ['nullable', 'url', 'max:500'],
            'pendaftaran_id' => ['nullable', 'integer'],
            'periode_akademik_id' => ['nullable', 'integer'],
            'periode_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $dosenId = $user->dosen?->id;
        $linkWa = $data['link_wa'] ?? null;

        $targetPeriodeId = $data['periode_akademik_id'] ?? $data['periode_id'];
        if (! $targetPeriodeId) {
            $targetPeriodeId = PeriodeAkademik::where('is_aktif', true)->value('id') ?? PeriodeAkademik::latest('id')->value('id');
        }

        if ($targetPeriodeId) {
            \App\Models\DplPeriodeLink::updateOrCreate(
                ['dpl_id' => $user->id, 'periode_akademik_id' => $targetPeriodeId],
                ['link_wa' => $linkWa]
            );
        }

        \Illuminate\Support\Facades\Cache::forever("dpl_wa_link:{$user->id}", $linkWa);

        $query = PendaftaranMbkm::where(function ($q) use ($user, $dosenId) {
            $q->where('dpl_id', $user->id);
            if ($dosenId) {
                $q->orWhere('dpl_id', $dosenId);
            }
        });

        if ($targetPeriodeId) {
            $query->where('periode_akademik_id', $targetPeriodeId);
        }

        if (! empty($data['pendaftaran_id'])) {
            $query->where('id', $data['pendaftaran_id']);
        }

        $updated = $query->update(['link_wa_dpl' => $linkWa]);

        if ($updated === 0 && empty($data['pendaftaran_id']) && $user->hasRole('DPL', 'Admin', 'Ketua Tim MBKM')) {
            $updated = PendaftaranMbkm::whereNotNull('dpl_id')
                ->whereIn('status', ['Terverifikasi', 'Disetujui'])
                ->when($targetPeriodeId, fn ($q) => $q->where('periode_akademik_id', $targetPeriodeId))
                ->update(['link_wa_dpl' => $linkWa]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tautan grup WhatsApp DPL berhasil disimpan.',
            'data' => [
                'link_wa_dpl' => $linkWa,
                'periode_akademik_id' => $targetPeriodeId,
                'updated_count' => $updated,
            ],
        ]);
    }

    private function normalizeStatusesInput(Request $request): void
    {
        $raw = $request->input('statuses');
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (is_array($raw)) {
            $normalized = array_map(function ($st) {
                if (! is_array($st)) {
                    return $st;
                }
                $kode = isset($st['kode_mk']) && trim((string) $st['kode_mk']) !== '' ? trim((string) $st['kode_mk']) : 'MK';
                $nama = isset($st['nama_mk']) && trim((string) $st['nama_mk']) !== '' ? trim((string) $st['nama_mk']) : $kode;
                $status = isset($st['status']) && in_array($st['status'], self::STATUS_MK, true)
                    ? $st['status']
                    : 'Belum Terlaksana';

                return [
                    'mata_kuliah_id' => isset($st['mata_kuliah_id']) && is_numeric($st['mata_kuliah_id']) ? (int) $st['mata_kuliah_id'] : null,
                    'kode_mk' => $kode,
                    'nama_mk' => $nama,
                    'sks' => isset($st['sks']) && is_numeric($st['sks']) ? (int) $st['sks'] : 3,
                    'status' => $status,
                ];
            }, $raw);

            $request->merge(['statuses' => $normalized]);
        }
    }

    private function detailPayload(PendaftaranMbkm $p): array
    {
        $p->loadMissing(['user.mahasiswa', 'programStudi', 'mitra', 'periodeAkademik', 'dpl', 'rkm.items.mataKuliah']);

        $mkList = $this->courseList($p)->values();

        $bimbingans = BimbinganMagang::with('statuses')
            ->where('pendaftaran_mbkm_id', $p->id)
            ->orderBy('bimbingan_ke')
            ->get();

        return [
            'pendaftaran_id' => (string) $p->id,
            'mahasiswa' => $this->mahasiswaPayload($p),
            'link_logbook' => $p->link_logbook,
            'link_wa_dpl' => $p->link_wa_dpl,
            'is_archived' => $this->isArchived($p),
            'rkm_status' => $p->rkm?->status ?? 'Belum Mengajukan',
            'total_sks_konversi' => (int) ($mkList->sum('sks') ?: ($p->rkm?->total_sks ?? 20)),
            'total_bimbingan' => $bimbingans->count(),
            'target_bimbingan' => 8,
            'mata_kuliah_konversi' => $mkList,
            'bimbingans' => $bimbingans,
        ];
    }

    private function mahasiswaPayload(PendaftaranMbkm $p): array
    {
        $prodiNama = $p->programStudi?->nama ?? '';
        $mitraNama = $p->mitra?->nama ?? $p->mitra_magang_text ?? '-';
        $posisi = $p->posisi_magang ?? '-';
        $tahunAjaran = $p->tahun_ajaran ?? $p->periodeAkademik?->nama_lengkap ?? '';

        $dplWa = null;
        if ($p->dpl_id && $p->periode_akademik_id) {
            $dplWa = \App\Models\DplPeriodeLink::where('dpl_id', $p->dpl_id)
                ->where('periode_akademik_id', $p->periode_akademik_id)
                ->value('link_wa');
        }
        $linkWa = $dplWa ?: ($p->link_wa_dpl ?: \Illuminate\Support\Facades\Cache::get("dpl_wa_link:{$p->dpl_id}"));

        return [
            'id' => (string) $p->id,
            'pendaftaran_id' => (string) $p->id,
            'mahasiswa_id' => $p->user?->mahasiswa?->id ?? $p->user_id,
            'user_id' => $p->user_id,
            'nim' => $p->nim,
            'nama' => $p->nama_lengkap,
            'nama_lengkap' => $p->nama_lengkap,
            'program_studi' => $prodiNama,
            'programStudi' => $prodiNama,
            'mitra_magang' => $mitraNama,
            'mitraMagang' => $mitraNama,
            'posisi_magang' => $posisi,
            'posisiMagang' => $posisi,
            'tahun_ajaran' => $tahunAjaran,
            'tahunAjaran' => $tahunAjaran,
            'periode_akademik_id' => $p->periode_akademik_id,
            'dpl' => $p->dpl?->nama ?? '',
            'dpl_id' => $p->dpl_id,
            'link_wa_dpl' => $linkWa,
        ];
    }

    private function courseList(PendaftaranMbkm $p): Collection
    {
        if ($p->rkm && $p->rkm->items && $p->rkm->items->isNotEmpty()) {
            $active = $p->rkm->items->filter(fn ($i) => $i->status_mk !== 'dropped')->values();
            if ($active->isNotEmpty()) {
                return $active->map(fn ($item) => [
                    'id' => $item->id,
                    'mata_kuliah_id' => $item->mata_kuliah_id,
                    'kode_mk' => $item->kode_mk ?: ($item->mataKuliah?->kode ?? ''),
                    'nama_mk' => $item->nama_mk ?: ($item->mataKuliah?->nama_mk ?? ''),
                    'sks' => (int) ($item->sks ?: ($item->mataKuliah?->sks ?? 3)),
                ]);
            }
        }

        if ($p->posisi_magang) {
            $posLower = strtolower(trim($p->posisi_magang));
            $paket = PaketKonversi::with('mataKuliahs')
                ->where('program_studi_id', $p->program_studi_id)
                ->where(function ($q) use ($posLower) {
                    $q->whereRaw('LOWER(target_posisi) = ?', [$posLower])
                        ->orWhereRaw('LOWER(nama_paket) = ?', [$posLower]);
                })
                ->first();

            if ($paket && $paket->mataKuliahs->isNotEmpty()) {
                return $paket->mataKuliahs->map(fn ($mk) => [
                    'id' => $mk->id,
                    'mata_kuliah_id' => $mk->id,
                    'kode_mk' => $mk->kode,
                    'nama_mk' => $mk->nama_mk,
                    'sks' => (int) $mk->sks,
                ]);
            }
        }

        return MataKuliah::where(function ($q) use ($p) {
            $q->where('program_studi_id', $p->program_studi_id)
                ->orWhere('lingkup', 'Fakultas');
        })
            ->limit(6)
            ->get()
            ->map(fn ($mk) => [
                'id' => $mk->id,
                'mata_kuliah_id' => $mk->id,
                'kode_mk' => $mk->kode,
                'nama_mk' => $mk->nama_mk,
                'sks' => (int) $mk->sks,
            ]);
    }

    private function applyPeriodeFilter($query, Request $request): void
    {
        $periodeId = $request->input('periode_akademik_id') ?? $request->input('periode_id') ?? $request->query('periode_akademik_id') ?? $request->query('periode_id');
        if (! empty($periodeId) && is_numeric($periodeId)) {
            $query->where('periode_akademik_id', (int) $periodeId);

            return;
        }

        $ta = $request->input('tahun_ajaran') ?? $request->query('tahun_ajaran');
        if (! $ta || in_array(strtolower(trim($ta)), ['semua', 'semua tahun ajaran', 'all'], true)) {
            return;
        }

        $trimmed = trim($ta);
        $parts = preg_split('/\s+/', $trimmed);
        $tahun = $parts[0] ?? null;
        $semester = $parts[1] ?? null;

        $query->where(function ($sq) use ($trimmed, $tahun, $semester) {
            $sq->where('tahun_ajaran', $trimmed)
                ->orWhereHas('periodeAkademik', function ($q) use ($tahun, $semester) {
                    if ($tahun && $semester) {
                        $q->where('tahun_ajaran', $tahun)->where('semester', $semester);
                    } elseif ($tahun) {
                        $q->where('tahun_ajaran', $tahun);
                    }
                });
        });
    }

    private function isArchived(PendaftaranMbkm $p): bool
    {
        $aktifId = PeriodeAkademik::where('is_aktif', true)->value('id');

        return $aktifId && $p->periode_akademik_id ? (int) $p->periode_akademik_id !== (int) $aktifId : false;
    }

    private function canManageBimbingan(User $user, ?PendaftaranMbkm $p): bool
    {
        if (! $p) {
            return false;
        }

        if ((int) $p->dpl_id === (int) $user->id) {
            return true;
        }

        if ($user->dosen && (int) $p->dpl_id === (int) $user->dosen->id) {
            return true;
        }

        return $user->hasRole('DPL', 'Admin', 'Ketua Tim MBKM', 'Tim MBKM');
    }

    private function canView(User $user, PendaftaranMbkm $p): bool
    {
        return $this->canManageBimbingan($user, $p)
            || (int) $p->user_id === (int) $user->id
            || $user->hasRole('Admin', 'Ketua Tim MBKM', 'Tim MBKM', 'DPL', 'Penguji Semhas', 'GPM', 'Wakil Dekan');
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error_code' => 'ERR_FORBIDDEN_ROLE',
            'message' => $message,
        ], 403);
    }
}
