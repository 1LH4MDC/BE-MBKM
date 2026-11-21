<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DplPeriodeLink extends Model
{
    protected $table = 'dpl_periode_links';

    protected $fillable = [
        'dpl_id',
        'periode_akademik_id',
        'link_wa',
    ];

    public function dpl(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dpl_id');
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class);
    }
}
