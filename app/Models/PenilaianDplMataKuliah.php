<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenilaianDplMataKuliah extends Model
{
    protected $table = 'penilaian_dpl_mata_kuliahs';

    protected $fillable = [
        'penilaian_dpl_id',
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

    public function penilaianDpl(): BelongsTo
    {
        return $this->belongsTo(PenilaianDpl::class, 'penilaian_dpl_id');
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }
}
