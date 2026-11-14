<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutupBuku extends Model
{
    protected $table = 'tutup_buku';

    protected $fillable = [
        'tahun_akademik_id', 'status', 'ditutup_oleh', 'ditutup_at',
        'dibuka_oleh', 'dibuka_at', 'catatan',
    ];

    public const STATUS_BUKA = 'buka';

    public const STATUS_TUTUP = 'tutup';

    protected function casts(): array
    {
        return ['ditutup_at' => 'datetime', 'dibuka_at' => 'datetime'];
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class);
    }

    /** M-032: periode terkunci bila ada baris tutup (default buka). */
    public static function terkunci(?int $tahunAkademikId): bool
    {
        if (! $tahunAkademikId) {
            return false;
        }

        try {
            return self::where('tahun_akademik_id', $tahunAkademikId)
                ->where('status', self::STATUS_TUTUP)
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}
