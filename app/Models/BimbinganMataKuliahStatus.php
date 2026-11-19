<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BimbinganMataKuliahStatus extends Model
{
    protected $table = 'bimbingan_mata_kuliah_statuses';

    protected $fillable = [
        'bimbingan_magang_id',
        'mata_kuliah_id',
        'kode_mk',
        'nama_mk',
        'sks',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sks' => 'integer',
        ];
    }

    public function bimbinganMagang(): BelongsTo
    {
        return $this->belongsTo(BimbinganMagang::class, 'bimbingan_magang_id');
    }
}
