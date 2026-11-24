<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenilaianSupervisorMataKuliah extends Model
{
    protected $table = 'penilaian_supervisor_mata_kuliahs';

    protected $fillable = [
        'penilaian_supervisor_id',
        'mata_kuliah_id',
        'kode_mk',
        'nama_mk',
        'sks',
        'job_description',
        'nilai',
    ];

    protected $casts = [
        'sks' => 'integer',
        'nilai' => 'float',
    ];

    public function penilaianSupervisor(): BelongsTo
    {
        return $this->belongsTo(PenilaianSupervisor::class, 'penilaian_supervisor_id');
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }
}
