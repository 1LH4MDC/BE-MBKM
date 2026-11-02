<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KuisionerJawaban extends Model
{
    protected $fillable = [
        'tahun_ajaran_id',
        'tahun_ajaran',
        'pendaftaran_mbkm_id',
        'mahasiswa_user_id',
        'dpl_user_id',
        'mitra_id',
        'nama_mitra_snapshot',
        'jawaban_dpl',
        'jawaban_mitra',
        'rata_rata_dpl',
        'rata_rata_mitra',
        'kompensasi_tipe',
        'kompensasi_nominal',
        'saran_masukan',
    ];

    protected $casts = [
        'jawaban_dpl' => 'array',
        'jawaban_mitra' => 'array',
        'kompensasi_tipe' => 'array',
        'rata_rata_dpl' => 'float',
        'rata_rata_mitra' => 'float',
    ];

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class, 'tahun_ajaran_id');
    }

    public function pendaftaranMbkm(): BelongsTo
    {
        return $this->belongsTo(PendaftaranMbkm::class);
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_user_id');
    }

    public function dpl(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dpl_user_id');
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }
}
