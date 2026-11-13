<?php

namespace App\Models;

use Database\Factories\MahasiswaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mahasiswa extends Model
{
    /** @use HasFactory<MahasiswaFactory> */
    use HasFactory;

    protected $table = 'mahasiswa';

    protected $fillable = ['user_id', 'nim', 'prodi_id', 'angkatan', 'jalur', 'dosen_pa_id', 'status'];

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_CUTI = 'cuti';

    public const STATUS_NONAKTIF = 'nonaktif';

    public const STATUS_DO = 'do';

    public const STATUS_KELUAR = 'keluar';

    public const STATUS_LULUS = 'lulus';

    public const STATUSES = [
        self::STATUS_AKTIF,
        self::STATUS_CUTI,
        self::STATUS_NONAKTIF,
        self::STATUS_DO,
        self::STATUS_KELUAR,
        self::STATUS_LULUS,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Prodi, $this>
     */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function dosenPa(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_pa_id');
    }

    /**
     * @return HasMany<Tagihan, $this>
     */
    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class);
    }

    /**
     * @return HasMany<CutiPengajuan, $this>
     */
    public function cutiPengajuan(): HasMany
    {
        return $this->hasMany(CutiPengajuan::class);
    }

    public function isAktif(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }
}
