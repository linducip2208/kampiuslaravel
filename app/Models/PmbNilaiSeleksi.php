<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nilai seleksi PMB (CBT saringan + wawancara scoring).
 *
 * @property int $id
 * @property int $pendaftar_id
 * @property string $jenis
 * @property float $nilai
 */
class PmbNilaiSeleksi extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'pmb_nilai_seleksis';

    public const JENIS_CBT = 'cbt';

    public const JENIS_WAWANCARA = 'wawancara';

    /** @var list<string> */
    public const JENIS = [
        self::JENIS_CBT,
        self::JENIS_WAWANCARA,
    ];

    /** @var list<string> */
    protected $fillable = [
        'pendaftar_id',
        'jenis',
        'nilai',
        'catatan',
        'penilai_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nilai' => 'float',
        ];
    }

    /**
     * @return BelongsTo<PmbPendaftar, $this>
     */
    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(PmbPendaftar::class, 'pendaftar_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function penilai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penilai_id');
    }
}
