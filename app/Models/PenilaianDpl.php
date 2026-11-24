<?php

namespace App\Models;

use App\Traits\FilterableByPeriode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenilaianDpl extends Model
{
    use FilterableByPeriode;

    protected $table = 'penilaian_dpls';

    protected $fillable = [
        'pendaftaran_mbkm_id',
        'periode_akademik_id',
        'dpl_id',
        'jumlah_bimbingan_saat_dinilai',
        'rata_rata_nilai',
    ];

    protected $casts = [
        'jumlah_bimbingan_saat_dinilai' => 'integer',
        'rata_rata_nilai' => 'float',
    ];

    public function pendaftaranMbkm(): BelongsTo
    {
        return $this->belongsTo(PendaftaranMbkm::class, 'pendaftaran_mbkm_id');
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class, 'periode_akademik_id');
    }

    public function dpl(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dpl_id');
    }

    public function mataKuliahs(): HasMany
    {
        return $this->hasMany(PenilaianDplMataKuliah::class, 'penilaian_dpl_id');
    }
}
