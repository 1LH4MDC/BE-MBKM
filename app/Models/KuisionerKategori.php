<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KuisionerKategori extends Model
{
    use HasFactory;

    protected $table = 'kuisioner_kategoris';

    protected $fillable = [
        'tahun_ajaran_id',
        'tahun_ajaran',
        'nama',
        'slug',
        'target_evaluasi',
        'is_active',
        'urutan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public function pertanyaans(): HasMany
    {
        return $this->hasMany(KuisionerPertanyaan::class, 'kategori_id');
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class, 'tahun_ajaran_id');
    }
}
