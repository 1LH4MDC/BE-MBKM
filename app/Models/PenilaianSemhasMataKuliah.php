<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenilaianSemhasMataKuliah extends Model
{
    protected $table = 'penilaian_semhas_mata_kuliahs';

    protected $fillable = [
        'penilaian_semhas_id',
        'mata_kuliah_id',
        'kode_mk',
        'nama_mk',
        'sks',
        'kompetensi',
        'aktivitas_pembelajaran',
        'nilai_mata_kuliah',
        'nilai_presentasi',
        'nilai_total',
    ];

    protected $casts = [
        'sks' => 'integer',
        'nilai_mata_kuliah' => 'float',
        'nilai_presentasi' => 'float',
        'nilai_total' => 'float',
    ];

    public function penilaianSemhas(): BelongsTo
    {
        return $this->belongsTo(PenilaianSemhas::class, 'penilaian_semhas_id');
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }

    /**
     * Hitung nilai total resmi server per MK: (75% Nilai MK) + (25% Nilai Presentasi)
     */
    public static function kalkulasiNilaiTotal(float $nilaiMk, float $nilaiPresentasi): float
    {
        return round(($nilaiMk * 0.75) + ($nilaiPresentasi * 0.25), 2);
    }
}
