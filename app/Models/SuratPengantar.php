<?php

namespace App\Models;

use App\Traits\FilterableByPeriode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratPengantar extends Model
{
    use FilterableByPeriode;

    protected $fillable = [
        'user_id',
        'periode_akademik_id',
        'nomor_surat',
        'tanggal_surat',
        'perihal',
        'tujuan_instansi',
        'nama_penerima',
        'alamat_instansi',
        'kota_instansi',
        'periode_magang_mulai',
        'periode_magang_selesai',
        'pejabat_ttd_id',
        'status',
        'catatan',
        'tahun_ajaran',
        'file_pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'periode_magang_mulai' => 'date',
            'periode_magang_selesai' => 'date',
        ];
    }

    public function getTahunAjaranAttribute($value): ?string
    {
        if (! empty($value)) {
            return $value;
        }

        return $this->periodeAkademik?->nama_lengkap;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class);
    }

    public function pejabatTtd(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pejabat_ttd_id');
    }

    public function mahasiswas(): HasMany
    {
        return $this->hasMany(SuratPengantarMahasiswa::class);
    }
}
