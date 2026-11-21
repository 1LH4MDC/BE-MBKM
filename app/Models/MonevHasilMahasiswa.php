<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonevHasilMahasiswa extends Model
{
    protected $table = 'monev_hasil_mahasiswas';

    protected $fillable = [
        'monev_mitra_id',
        'pendaftaran_mbkm_id',
        'kompleksitas_jobdesk',
        'kesesuaian_rkm',
        'relevansi_pl',
        'kualitas_supervisi',
        'ketercapaian_cpmk',
        'outcome_pekerjaan',
        'pengembangan_skill',
    ];

    protected $casts = [
        'kompleksitas_jobdesk' => 'integer',
        'kesesuaian_rkm' => 'integer',
        'relevansi_pl' => 'integer',
        'kualitas_supervisi' => 'integer',
        'ketercapaian_cpmk' => 'integer',
        'outcome_pekerjaan' => 'integer',
        'pengembangan_skill' => 'integer',
    ];

    public function monevMitra(): BelongsTo
    {
        return $this->belongsTo(MonevMitra::class);
    }

    public function pendaftaranMbkm(): BelongsTo
    {
        return $this->belongsTo(PendaftaranMbkm::class);
    }
}
