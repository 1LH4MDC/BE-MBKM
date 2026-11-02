<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KuisionerPertanyaan extends Model
{
    protected $fillable = [
        'tahun_ajaran_id',
        'tahun_ajaran',
        'kategori_id',
        'kategori',
        'nomor_urut',
        'pertanyaan',
        'tipe_input',
        'opsi_pilihan',
        'is_active',
    ];

    protected $casts = [
        'nomor_urut' => 'integer',
        'opsi_pilihan' => 'array',
        'is_active' => 'boolean',
    ];

    public function kategoriKategori(): BelongsTo
    {
        return $this->belongsTo(KuisionerKategori::class, 'kategori_id');
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class, 'tahun_ajaran_id');
    }
}
