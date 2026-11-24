<?php

namespace App\Models;

use App\Traits\FilterableByPeriode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PendaftaranMbkm extends Model
{
    use FilterableByPeriode;

    protected $fillable = [
        'user_id',
        'nim',
        'nama_lengkap',
        'program_studi_id',
        'mitra_id',
        'mitra_magang_text',
        'posisi_magang',
        'is_custom_role',
        'periode_akademik_id',
        'tahun_ajaran',
        'transkrip_file_name',
        'transkrip_file_path',
        'transkrip_file_size',
        'loa_file_name',
        'loa_file_path',
        'loa_file_size',
        'status',
        'catatan',
        'lampiran_revisi_path',
        'link_wa_grup',
        'link_logbook',
        'link_wa_dpl',
        'sudah_isi_kuisioner',
        'kuisioner_submitted_at',
        'verified_by',
        'verified_at',
        'is_plotted_pic',
        'pic_verifikator_id',
        'is_plotted_dpl_penguji',
        'dpl_id',
        'penguji_semhas_id',
    ];

    protected function casts(): array
    {
        return [
            'is_custom_role' => 'boolean',
            'is_plotted_pic' => 'boolean',
            'is_plotted_dpl_penguji' => 'boolean',
            'sudah_isi_kuisioner' => 'boolean',
            'kuisioner_submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    protected $appends = [
        'lampiran_revisi_url',
        'lampiran_revisi_name',
        'status_pendaftaran',
        'catatan_verifikasi',
    ];

    protected static function booted(): void
    {
        static::saving(function (PendaftaranMbkm $model) {
            if (! empty($model->pic_verifikator_id)) {
                $model->is_plotted_pic = true;
            } elseif ($model->isDirty('pic_verifikator_id')) {
                $model->is_plotted_pic = false;
            }

            if (empty($model->pic_verifikator_id) && $model->status === 'Terverifikasi') {
                $model->status = 'Menunggu Verifikasi';
            }
        });
    }

    public function getTahunAjaranAttribute($value): ?string
    {
        if (! empty($value)) {
            return $value;
        }

        return $this->periodeAkademik?->nama_lengkap;
    }

    public function getLampiranRevisiUrlAttribute(): ?string
    {
        if (empty($this->lampiran_revisi_path)) {
            return null;
        }

        if (Str::startsWith($this->lampiran_revisi_path, ['http://', 'https://', 'data:'])) {
            return $this->lampiran_revisi_path;
        }

        return url(Storage::url($this->lampiran_revisi_path));
    }

    public function getLampiranRevisiNameAttribute(): ?string
    {
        if (empty($this->lampiran_revisi_path)) {
            return null;
        }

        return basename($this->lampiran_revisi_path);
    }

    public function getStatusPendaftaranAttribute(): ?string
    {
        return $this->status;
    }

    public function getCatatanVerifikasiAttribute(): ?string
    {
        return $this->catatan;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class);
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function picVerifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_verifikator_id');
    }

    public function dpl(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dpl_id');
    }

    public function pengujiSemhas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penguji_semhas_id');
    }

    public function rkm(): HasOne
    {
        return $this->hasOne(Rkm::class);
    }

    public function suratPengantarMahasiswas(): HasMany
    {
        return $this->hasMany(SuratPengantarMahasiswa::class);
    }

    public function bimbingans(): HasMany
    {
        return $this->hasMany(BimbinganMagang::class)->orderBy('bimbingan_ke');
    }

    public function pengajuanSemhas(): HasOne
    {
        return $this->hasOne(PengajuanSemhas::class, 'pendaftaran_mbkm_id');
    }

    public function penilaianSemhas(): HasOne
    {
        return $this->hasOne(PenilaianSemhas::class, 'pendaftaran_mbkm_id');
    }

    public function penilaianSupervisor(): HasOne
    {
        return $this->hasOne(PenilaianSupervisor::class, 'pendaftaran_mbkm_id');
    }

    public function penilaianDpl(): HasOne
    {
        return $this->hasOne(PenilaianDpl::class, 'pendaftaran_mbkm_id');
    }
}
