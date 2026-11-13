<?php

namespace App\Models;

use App\Services\AkademikService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kartu Rencana Studi per mahasiswa per tahun akademik.
 *
 * @property int $id
 * @property int|null $mahasiswa_id
 * @property int|null $tahun_akademik_id
 * @property string $status
 * @property int $total_sks
 */
class Krs extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'krs';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_DIAJUKAN,
        self::STATUS_DISETUJUI,
        self::STATUS_DITOLAK,
    ];

    /** @var list<string> */
    protected $fillable = [
        'mahasiswa_id',
        'tahun_akademik_id',
        'status',
        'total_sks',
        'ipk_snapshot',
        'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ipk_snapshot' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<KrsDetail, $this>
     */
    public function details(): HasMany
    {
        return $this->hasMany(KrsDetail::class, 'krs_id');
    }

    /**
     * @return BelongsTo<Mahasiswa, $this>
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }

    /**
     * @return BelongsTo<TahunAkademik, $this>
     */
    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    /**
     * Batas SKS berdasarkan IPK semester lalu (aturan umum kampus).
     * Satu sumber kebenaran: App\Services\AkademikService::batasSks().
     * >=3.00 → 24, >=2.50 → 22, >=2.00 → 20, <2.00 → 18.
     */
    public static function sksMaksByIpk(float $ipk): int
    {
        return AkademikService::batasSks($ipk);
    }
}
