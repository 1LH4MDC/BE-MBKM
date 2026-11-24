<?php

namespace App\Models;

use App\Traits\FilterableByPeriode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenilaianSupervisor extends Model
{
    use FilterableByPeriode;

    protected $table = 'penilaian_supervisors';

    protected $fillable = [
        'pendaftaran_mbkm_id',
        'periode_akademik_id',
        'pic_id',
        'nama_supervisor',
        'rata_rata_nilai',
    ];

    protected $casts = [
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

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function mataKuliahs(): HasMany
    {
        return $this->hasMany(PenilaianSupervisorMataKuliah::class, 'penilaian_supervisor_id');
    }
}
