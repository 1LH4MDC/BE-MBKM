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
        'target_nilai_monev',
    ];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
            'target_nilai_monev' => 'decimal:2',
        ];
    }

    protected $appends = ['label', 'nama_lengkap', 'is_active'];

    public function getLabelAttribute(): string
    {
        return trim("{$this->tahun_ajaran} {$this->semester}");
    }

    public function getNamaLengkapAttribute(): string
    {
        return trim("{$this->tahun_ajaran} {$this->semester}");
    }

    public function getIsActiveAttribute(): bool
    {
        return (bool) $this->is_aktif;
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

    public function monevMitras(): HasMany
    {
        return $this->hasMany(MonevMitra::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }
}
