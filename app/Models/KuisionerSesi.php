<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KuisionerSesi extends Model
{
    protected $table = 'kuisioner_sesis';

    protected $fillable = [
        'periode_akademik_id',
        'status',
        'published_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class);
    }
}
