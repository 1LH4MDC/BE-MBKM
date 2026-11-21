<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class BimbinganMagang extends Model
{
    protected $table = 'bimbingan_magangs';

    protected $fillable = [
        'pendaftaran_mbkm_id',
        'dpl_id',
        'periode_akademik_id',
        'tahun_ajaran',
        'bimbingan_ke',
        'tanggal_bimbingan',
        'catatan_bimbingan',
        'foto_bukti_path',
        'foto_uploaded_by_role',
    ];

    protected function casts(): array
    {
        return [
            'bimbingan_ke' => 'integer',
            'tanggal_bimbingan' => 'date:Y-m-d',
        ];
    }

    protected $appends = ['foto_bukti_url'];

    public function pendaftaranMbkm(): BelongsTo
    {
        return $this->belongsTo(PendaftaranMbkm::class, 'pendaftaran_mbkm_id');
    }

    public function dpl(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dpl_id');
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(BimbinganMataKuliahStatus::class, 'bimbingan_magang_id');
    }

    public function getRawFotoBuktiPath(): ?string
    {
        return $this->getRawOriginal('foto_bukti_path');
    }

    public function getFotoBuktiPathAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, 'data:')) {
            return $value;
        }

        return url(Storage::url($value));
    }

    public function getFotoBuktiUrlAttribute(): ?string
    {
        $raw = $this->getRawOriginal('foto_bukti_path');
        if (! $raw) {
            return null;
        }
        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://') || str_starts_with($raw, 'data:')) {
            return $raw;
        }

        return url(Storage::url($raw));
    }
}
