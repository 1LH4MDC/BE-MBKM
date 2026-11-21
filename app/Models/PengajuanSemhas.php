<?php

namespace App\Models;

use App\Traits\FilterableByPeriode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PengajuanSemhas extends Model
{
    use FilterableByPeriode;

    protected $table = 'pengajuan_semhas';

    protected $fillable = [
        'pendaftaran_mbkm_id',
        'periode_akademik_id',
        'laporan_akhir_path',
        'laporan_akhir_name',
        'logbook_fb07_path',
        'logbook_fb07_name',
        'nilai_supervisor_path',
        'nilai_supervisor_name',
        'status_kelayakan',
        'catatan_pic',
        'verified_by_pic_id',
        'verified_at_pic',
        'is_override_ketua',
        'catatan_override_ketua',
        'override_by_ketua_id',
        'override_at',
    ];

    protected $casts = [
        'is_override_ketua' => 'boolean',
        'verified_at_pic' => 'datetime',
        'override_at' => 'datetime',
    ];

    protected $appends = [
        'laporan_akhir_url',
        'logbook_fb07_url',
        'nilai_supervisor_url',
        'tahun_ajaran',
    ];

    public function pendaftaranMbkm(): BelongsTo
    {
        return $this->belongsTo(PendaftaranMbkm::class, 'pendaftaran_mbkm_id');
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class, 'periode_akademik_id');
    }

    public function verifiedByPic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_pic_id');
    }

    public function overrideByKetua(): BelongsTo
    {
        return $this->belongsTo(User::class, 'override_by_ketua_id');
    }

    public function getLaporanAkhirUrlAttribute(): ?string
    {
        if (! $this->laporan_akhir_path) {
            return null;
        }

        return url(Storage::url($this->laporan_akhir_path));
    }

    public function getLogbookFb07UrlAttribute(): ?string
    {
        if (! $this->logbook_fb07_path) {
            return null;
        }

        return url(Storage::url($this->logbook_fb07_path));
    }

    public function getNilaiSupervisorUrlAttribute(): ?string
    {
        if (! $this->nilai_supervisor_path) {
            return null;
        }

        return url(Storage::url($this->nilai_supervisor_path));
    }

    public function getTahunAjaranAttribute(): string
    {
        return $this->periodeAkademik?->nama_lengkap
            ?? $this->pendaftaranMbkm?->tahun_ajaran
            ?? ($this->pendaftaranMbkm?->periodeAkademik?->nama_lengkap ?? '2026/2027 Ganjil');
    }
}
