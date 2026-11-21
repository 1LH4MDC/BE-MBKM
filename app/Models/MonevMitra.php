<?php

namespace App\Models;

use App\Traits\FilterableByPeriode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MonevMitra extends Model
{
    use FilterableByPeriode;

    protected $table = 'monev_mitras';

    protected $fillable = [
        'mitra_id',
        'periode_akademik_id',
        'periode_monev',
        'tanggal_pelaksanaan',
        'metode_pelaksanaan',
        'catatan_evaluasi',
        'kendala_tindak_lanjut',
        'dokumentasi_path',
        'dokumentasi_name',
        'filled_by_id',
    ];

    protected $casts = [
        'tanggal_pelaksanaan' => 'date',
    ];

    protected $appends = [
        'dokumentasi_url',
        'tahun_ajaran',
    ];

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class);
    }

    public function filledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filled_by_id');
    }

    public function getDokumentasiUrlAttribute(): ?string
    {
        if (! $this->dokumentasi_path) {
            return null;
        }

        return url(Storage::url($this->dokumentasi_path));
    }

    public function getTahunAjaranAttribute(): string
    {
        return $this->periodeAkademik?->nama_lengkap ?? '2026/2027 Ganjil';
    }
}
