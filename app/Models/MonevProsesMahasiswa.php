<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonevProsesMahasiswa extends Model
{
    protected $table = 'monev_proses_mahasiswas';

    protected $fillable = [
        'monev_mitra_id',
        'pendaftaran_mbkm_id',
        'kehadiran_ketepatan_waktu',
        'ketaatan_aturan',
        'pencapaian_tugas',
        'kualitas_hasil_kerja',
        'kemampuan_menyelesaikan_masalah',
        'penguasaan_tools',
        'kemampuan_analisis',
        'komunikasi_kolaborasi',
        'tanggung_jawab_inisiatif',
        'etika_profesional',
        'relevansi_ilmu_komputer',
        'kontribusi_kompetensi',
        'catatan_pemonev',
        'temuan_monev',
        'status_monev',
    ];

    protected $casts = [
        'kehadiran_ketepatan_waktu' => 'integer',
        'ketaatan_aturan' => 'integer',
        'pencapaian_tugas' => 'integer',
        'kualitas_hasil_kerja' => 'integer',
        'kemampuan_menyelesaikan_masalah' => 'integer',
        'penguasaan_tools' => 'integer',
        'kemampuan_analisis' => 'integer',
        'komunikasi_kolaborasi' => 'integer',
        'tanggung_jawab_inisiatif' => 'integer',
        'etika_profesional' => 'integer',
        'relevansi_ilmu_komputer' => 'integer',
        'kontribusi_kompetensi' => 'integer',
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
