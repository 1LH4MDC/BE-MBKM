<?php

namespace App\Models;

use App\Traits\FilterableByPeriode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlottingPemonevMitra extends Model
{
    use FilterableByPeriode;

    protected $table = 'plotting_pemonev_mitras';

    protected $fillable = [
        'mitra_id',
        'periode_akademik_id',
        'dosen_id',
        'assigned_by',
    ];

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function getTahunAjaranAttribute(): string
    {
        return $this->periodeAkademik?->nama_lengkap ?? '2026/2027 Ganjil';
    }
}
