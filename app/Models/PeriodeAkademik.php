<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodeAkademik extends Model
{
    protected $fillable = [
        'tahun_ajaran',
        'semester',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    protected $appends = ['label'];

    public function getLabelAttribute(): string
    {
        return trim("{$this->tahun_ajaran} {$this->semester}");
    }

    public function pendaftaranMbkms(): HasMany
    {
        return $this->hasMany(PendaftaranMbkm::class);
    }

    public function kuisionerKategoris(): HasMany
    {
        return $this->hasMany(KuisionerKategori::class, 'tahun_ajaran_id');
    }

    public function kuisionerPertanyaans(): HasMany
    {
        return $this->hasMany(KuisionerPertanyaan::class, 'tahun_ajaran_id');
    }

    public function kuisionerJawabans(): HasMany
    {
        return $this->hasMany(KuisionerJawaban::class, 'tahun_ajaran_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }
}
