<?php

namespace App\Models;

use App\Traits\FilterableByPeriode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenilaianSemhas extends Model
{
    use FilterableByPeriode;

    protected $table = 'penilaian_semhas';

    protected $fillable = [
        'pendaftaran_mbkm_id',
        'pengajuan_semhas_id',
        'periode_akademik_id',
        'penguji_id',
        'nilai_kelengkapan_laporan',
        'deskripsi_kelengkapan_laporan',
        'nilai_slide',
        'deskripsi_slide',
        'nilai_cara_presentasi',
        'deskripsi_cara_presentasi',
        'nilai_tanya_jawab',
        'deskripsi_tanya_jawab',
        'nilai_presentasi',
        'rata_rata_nilai_total',
    ];

    protected $casts = [
        'nilai_kelengkapan_laporan' => 'float',
        'nilai_slide' => 'float',
        'nilai_cara_presentasi' => 'float',
        'nilai_tanya_jawab' => 'float',
        'nilai_presentasi' => 'float',
        'rata_rata_nilai_total' => 'float',
    ];

    public function pendaftaranMbkm(): BelongsTo
    {
        return $this->belongsTo(PendaftaranMbkm::class, 'pendaftaran_mbkm_id');
    }

    public function pengajuanSemhas(): BelongsTo
    {
        return $this->belongsTo(PengajuanSemhas::class, 'pengajuan_semhas_id');
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class, 'periode_akademik_id');
    }

    public function penguji(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penguji_id');
    }

    public function mataKuliahs(): HasMany
    {
        return $this->hasMany(PenilaianSemhasMataKuliah::class, 'penilaian_semhas_id');
    }

    /**
     * Hitung nilai presentasi resmi server (20% Laporan + 20% Slide + 20% Presentasi + 40% Tanya Jawab)
     */
    public static function kalkulasiNilaiPresentasi(float $laporan, float $slide, float $presentasi, float $tanyaJawab): float
    {
        return round(($laporan * 0.20) + ($slide * 0.20) + ($presentasi * 0.20) + ($tanyaJawab * 0.40), 2);
    }
}
